<?php

declare(strict_types=1);

use KnockKnock\{Client, Event};
use KnockKnock\Exception\{HttpException, TransportException};

require __DIR__ . '/../autoload.php';

$api = (new Client('https://postman-echo.com'))
    ->withBearer('secret-token')
    ->withTimeout(10)
    ->on(Event::BeforeSend, fn ($request) => $request->withHeader('X-Request-Id', bin2hex(random_bytes(4))));

try {
    $user = $api->get('/get', ['id' => 806034])->throw()->json();
    $created = $api->post('/post', ['title' => 'Hello'])->throw()->json();
} catch (HttpException $e) {
    echo "Server said {$e->response->status}\n";
} catch (TransportException $e) {
    echo "Network problem: {$e->getMessage()}\n";
}
