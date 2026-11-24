<?php

declare(strict_types=1);

header('X-Test: yes');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/redirect') {
    header('Location: /echo', true, 302);
    return;
}
if ($path !== '/echo') {
    http_response_code(404);
    echo 'not found';
    return;
}
if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
    return;
}

header('Content-Type: application/json');
echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'headers' => getallheaders(),
    'body' => file_get_contents('php://input'),
]);
