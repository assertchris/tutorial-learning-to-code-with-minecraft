<?php

use AsyncInterop\Loop;

$host = new Aerys\Host();
$host->expose("*", 8080);

$public = Aerys\root(__DIR__ . "/public");
$host->use($public);

$builder = new Theory\Builder\Client(
    "127.0.0.1", 25575, "password"
);

$socket = new Socket($builder);

$router = new Aerys\Router();
$router->route("GET", "/ws", Aerys\websocket($socket));
$host->use($router);

foreach ($socket->positions as $position) {
    $x = $position[0];
    $y = $position[1];
    $z = $position[2];

    for ($i = 0; $i < 15; $i++) {
        $builder->exec(
            sprintf(
                "fill %s %s %s %s %s %s air",
                $x, $y + $i, $z, $x + 22, $y + $i, $z + 22
            )
        );
    }
}

Loop::repeat(1000, function() use ($builder, $socket) {
    static $running;

    if (is_null($running)) {
        $running = [];
    }

    $running = array_filter($running, function($game) use ($socket) {
        return in_array($game, $socket->games);
    });

    if (count($running) < 9 && count($socket->games) > 0) {
        $game = array_pop($socket->games);

        if ($game->position !== Game::POSITION_NONE) {
            return;
        }

        $available = [
            Game::POSITION_NORTH_WEST => true,
            Game::POSITION_NORTH => true,
            Game::POSITION_NORTH_EAST => true,
            Game::POSITION_WEST => true,
            Game::POSITION_ORIGIN => true,
            Game::POSITION_EAST => true,
            Game::POSITION_SOUTH_WEST => true,
            Game::POSITION_SOUTH => true,
            Game::POSITION_SOUTH_EAST => true,
        ];

        foreach ($running as $next) {
            unset($available[$next->position]);
        }

        $game->position = array_shift($available);
        $position = $socket->positions[$game->position];

        $x = $position[0];
        $y = $position[1];
        $z = $position[2];

        $game->clear($builder, $x, $y, $z, function() use ($x, $y, $z, $game, $builder, $position) {
            $game->build($builder, $x, $y, $z, function() use ($x, $y, $z, $game, $builder, $position) {
                $builder->exec(
                    sprintf(
                        "/tp {$game->player} %s %s %s",
                        $x + 11, $y + 5, $z + 11
                    )
                );
            });
        });

        array_push($running, $game);
        array_unshift($socket->games, $game);
    }
});
