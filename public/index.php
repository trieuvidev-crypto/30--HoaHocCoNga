<?php

declare(strict_types=1);

use App\Core\Request;

[$container, $router] = require __DIR__ . '/../bootstrap/app.php';

$request = Request::capture();
$response = $router->dispatch($request, $container);
$response->send();
