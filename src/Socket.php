<?php

use Aerys\Request;
use Aerys\Response;
use Aerys\Websocket;
use Aerys\Websocket\Endpoint;
use Aerys\Websocket\Message;
use Amp\File;
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
    private $waitingCoordinates = "1098 16 45";

    /**
     * @var Game[]
     */
    private $games = [];

    /**
     * @var string[]
     */
    private $players = [];

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

        Amp\File\put($this->path, "");
    }

    /**
     * @inheritdoc
     *
     * @param Endpoint $endpoint
     */
    public function onStart(Endpoint $endpoint)
    {
        $this->endpoint = $endpoint;

        Amp\repeat(function () {
            $newLines = yield Amp\resolve($this->getNewLines());

            foreach ($newLines as $line) {
                preg_match("/(\\S+) joined the game/", $line, $matches);

                if (count($matches) === 2) {
                    $this->initiatePlayer($matches[1]);
                }
            }
        }, 500);
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
        $time = yield Amp\File\mtime($this->path);

        if ($this->logTime !== $time) {
            $body = yield Amp\File\get($this->path);

            $allLines = explode(PHP_EOL, $body);
            $newLines = array_slice($allLines, $this->logLines);

            $this->logLines = count($allLines);

            return array_filter($newLines);
        }

        return [];
    }

    /**
     * @param string $player
     */
    private function initiatePlayer(string $player)
    {
        $this->players[$player] = $player;

        $this->builder->exec("/gamemode a {$player}");
        $this->builder->exec("/tp {$player} {$this->waitingCoordinates}");
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

        if ($parsed["type"] === "players") {
            $this->endpoint->send($client, json_encode([
                "type" => "players",
                "data" => $this->players,
            ]));
        }

        if ($parsed["type"] === "join") {
            $player = $parsed["data"];

            $this->builder->exec(
                "/w {$player} Your friend has joined"
            );

            $game = new Game($player, $client);
            $this->games[$player] = $game;
        }
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

        foreach ($this->games as $player => $game) {
            if ($game->hasCoder($client)) {
                unset($this->games[$player]);

                $this->builder->exec(
                    "/w {$player} Your friend has left"
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
