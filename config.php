<?php

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
