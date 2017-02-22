<?php

use Aerys\Request;
use Aerys\Response;
use Aerys\Websocket;
use Aerys\Websocket\Endpoint;
use Aerys\Websocket\Message;
use Amp\File;
use Amp\Coroutine;
use AsyncInterop\Loop;
use Theory\Builder\Client;

class Socket implements Websocket
{
    /**
     * @var Endpoint
     */
    private $endpoint;

    /**
     * @var array
     */
    private $connections = [];

    /**
     * @var Client
     */
    private $builder;

    /**
     * @var int
     */
    private $logTime = 0;

    /**
     * @var int
     */
    private $logLines = 0;

    /**
     * @var string
     */
    private $waitingCoordinates = "471 27 -381";

    /**
     * @var Game[]
     */
    public $games = [];

    /**
     * @var string[]
     */
    private $players = [];

    /**
     * @var int[][]
     */
    public $positions = [];

    /**
     * @var string
     */
    private $path = __DIR__ . "/../server/logs/latest.log";

    /**
     * @param Client $builder
     */
    public function __construct(Client $builder)
    {
        $this->builder = $builder;

        Loop::execute(Amp\wrap(function() {
            yield Amp\File\put($this->path, "");
        }));

        $this->setPositions();
    }

    private function setPositions()
    {
        $parts = explode(" ", $this->waitingCoordinates);

        $x = $parts[0] - 11;
        $y = 4;
        $z = $parts[2] - 11;

        $this->positions = [
            Game::POSITION_NORTH_WEST => [$x - 22 - 3, $y, $z - 22 - 3],
            Game::POSITION_NORTH => [$x - 22 - 3, $y, $z],
            Game::POSITION_NORTH_EAST => [$x - 22 - 3, $y, $z + 22 + 3],
            Game::POSITION_WEST => [$x, $y, $z - 22 - 3],
            Game::POSITION_ORIGIN => [$x, $y, $z],
            Game::POSITION_EAST => [$x, $y, $z + 22 + 3],
            Game::POSITION_SOUTH_WEST => [$x + 22 + 3, $y, $z - 22 - 3],
            Game::POSITION_SOUTH => [$x + 22 + 3, $y, $z],
            Game::POSITION_SOUTH_EAST => [$x + 22 + 3, $y, $z + 22 + 3],
        ];
    }

    /**
     * @inheritdoc
     *
     * @param Endpoint $endpoint
     */
    public function onStart(Endpoint $endpoint)
    {
        $this->endpoint = $endpoint;

        Loop::repeat(500, Amp\wrap(function() {
            $newLines = yield $this->getNewLines();

            foreach ($newLines as $line) {
                preg_match("/(\\S+) joined the game/", $line, $matches);

                if (count($matches) === 2) {
                    yield $this->playerJoined($matches[1]);
                }

                preg_match("/(\\S+) left the game/", $line, $matches);

                if (count($matches) === 2) {
                    yield $this->playerLeft($matches[1]);
                }
            }
        }));
    }

    /**
     * Get the newest log file lines.
     *
     * @coroutine
     *
     * @return array
     */
    private function getNewLines()
    {
        $generator = function() {
            $time = yield Amp\File\mtime($this->path);

            if ($this->logTime !== $time) {
                $body = yield Amp\File\get($this->path);

                $allLines = explode(PHP_EOL, $body);
                $newLines = array_slice($allLines, $this->logLines);

                $this->logLines = count($allLines);

                return array_filter($newLines);
            }

            return [];
        };

        return new Amp\Coroutine($generator());
    }

    /**
     * @param string $player
     */
    private function playerJoined(string $player)
    {
        $generator = function() use ($player) {
            $this->players[$player] = $player;

            yield $this->broadcast([
                "type" => "player-joined",
                "data" => $player,
            ]);

            $this->builder->exec("/gamemode a {$player}");
            $this->builder->exec("/tp {$player} {$this->waitingCoordinates}");
        };

        return new Amp\Coroutine($generator());
    }

    /**
     * @param mixed $payload
     *
     * @return Amp\Coroutine
     */
    private function broadcast($payload) {
        $generator = function() use ($payload) {
            $payload = json_encode($payload);

            yield $this->endpoint->broadcast($payload);
        };

        return new Amp\Coroutine($generator());
    }

    /**
     * @param string $player
     */
    private function playerLeft(string $player)
    {
        $generator = function() use ($player) {
            unset($this->players[$player]);

            yield $this->broadcast([
                "type" => "player-left",
                "data" => $player,
            ]);

            foreach ($this->games as $i => $game) {
                if ($game->hasPlayer($player)) {
                    unset($this->games[$i]);
                }
            }
        };

        return new Amp\Coroutine($generator());
    }

    /**
     * @inheritdoc
     *
     * @param Request $request
     * @param Response $response
     *
     * @return mixed
     */
    public function onHandshake(Request $request, Response $response)
    {
        $origin = $request->getHeader("origin");

        if ($origin !== "http://localhost:8080") {
            $response->setStatus(403);
            $response->end("<h1>origin not allowed</h1>");

            return null;
        }

        $info = $request->getConnectionInfo();

        return $info["client_addr"];
    }

    /**
     * @inheritdoc
     *
     * @param int $client
     * @param mixed $handshakeData
     */
    public function onOpen(int $client, $handshakeData)
    {
        $this->connections[$client] = $handshakeData;
    }

    /**
     * @inheritdoc
     *
     * @param int $client
     * @param Message $message
     */
    public function onData(int $client, Message $message)
    {
        $raw = yield $message;
        $parsed = json_decode($raw, true);

        if ($parsed["type"] === "get-players") {
            yield $this->send($client, [
                "type" => "get-players",
                "data" => array_values($this->players),
            ]);
        }

        if ($parsed["type"] === "join") {
            $player = $parsed["data"];

            yield $this->send($client, [
                "type" => "joined"
            ]);

            $this->builder->exec(
                "/w {$player} Your friend has joined"
            );

            array_push($this->games, new Game($player, $client));
        }
    }

    /**
     * @param int $client
     * @param mixed $payload
     *
     * @return Amp\Coroutine
     */
    private function send($client, $payload) {
        $generator = function() use ($payload, $client) {
            $payload = json_encode($payload);

            yield $this->endpoint->send($payload, $client);
        };

        return new Amp\Coroutine($generator());
    }

    /**
     * @inheritdoc
     *
     * @param int $client
     * @param int $code
     * @param string $reason
     */
    public function onClose(int $client, int $code, string $reason)
    {
        unset($this->connections[$client]);

        foreach ($this->games as $i => $game) {
            if ($game->hasCoder($client)) {
                unset($this->games[$i]);

                $this->builder->exec(
                    "/w {$game->player} Your friend has left"
                );
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function onStop()
    {
        // ...intentionally left blank
    }
}
