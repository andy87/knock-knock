<?php declare(strict_types=1);

/** Локальный HTTP endpoint для проверки фактически отправленных запросов. */
if (str_starts_with($_SERVER['REQUEST_URI'], '/slow')) {
    usleep(300000);
}

$content = json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'headers' => array_change_key_case(getallheaders(), CASE_LOWER),
    'body' => file_get_contents('php://input'),
    'form' => $_POST,
]);

header('Content-Type: application/json');
if (!str_starts_with($_SERVER['REQUEST_URI'], '/no-length')) {
    header('Content-Length: ' . strlen($content));
}
echo $content;
