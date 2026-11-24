<?php

declare(strict_types=1);

use KnockKnock\Client;
use KnockKnock\Exception\TransportException;

/** Spins up PHP's built-in server with tests/Support/router.php and returns its base URL. */
$server = static function (): string {
    static $url = null;
    if ($url !== null) {
        return $url;
    }
    $port = random_int(20000, 40000);
    $proc = proc_open(
        [PHP_BINARY, '-S', "127.0.0.1:$port", __DIR__ . '/Support/router.php'],
        [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
        $pipes,
    );
    register_shutdown_function(static fn () => proc_terminate($proc));
    for ($i = 0; $i < 50; $i++) {
        if (@fsockopen('127.0.0.1', $port)) {
            return $url = "http://127.0.0.1:$port";
        }
        usleep(100_000);
    }
    throw new RuntimeException('test server did not start');
};

return [
    'cookies remain separate with commas in Expires' => function () use ($server) {
        $r = (new Client($server()))->get('/cookies');
        $first = 'a=1; Expires=Wed, 09 Jun 2027 10:18:14 GMT';
        same([$first, 'b=2'], $r->headerValues('Set-Cookie'));
        same($first, $r->header('set-cookie'));
    },
    'GET round trip with query, headers and parsed response headers' => function () use ($server) {
        $r = (new Client($server()))->withBearer('t')->get('/echo', ['a' => 1]);
        $j = $r->json();
        same('GET', $j['method']);
        same('/echo?a=1', $j['uri']);
        same('Bearer t', $j['headers']['Authorization']);
        same('yes', $r->header('X-Test'));
        same(200, $r->status);
    },
    'POST JSON body' => function () use ($server) {
        $j = (new Client($server()))->post('/echo', ['k' => 'v'])->json();
        same('POST', $j['method']);
        same('{"k":"v"}', $j['body']);
        same('application/json', $j['headers']['Content-Type']);
    },
    'DELETE / PATCH / PUT send custom method with body' => function () use ($server) {
        $c = new Client($server());
        foreach (['delete', 'patch', 'put'] as $verb) {
            $j = $c->$verb('/echo', ['x' => 1])->json();
            same(strtoupper($verb), $j['method']);
            same('{"x":1}', $j['body']);
        }
    },
    'HEAD returns headers without body' => function () use ($server) {
        $r = (new Client($server()))->head('/echo');
        same('', $r->body);
        same('yes', $r->header('x-test'));
    },
    '404 is a response, not an exception' => function () use ($server) {
        $r = (new Client($server()))->get('/missing');
        same(404, $r->status);
        check($r->failed());
    },
    'redirects only followed when enabled' => function () use ($server) {
        $c = new Client($server());
        same(302, $c->get('/redirect')->status);
        same(200, $c->withRedirects()->get('/redirect')->status);
    },
    'connection failure raises TransportException' => function () {
        throws(TransportException::class, fn () => (new Client('http://127.0.0.1:1'))->withTimeout(2, 1)->get('/'));
    },
    'handle is reused: options do not leak between requests' => function () use ($server) {
        $c = new Client($server());
        $c->post('/echo', ['a' => 1]);
        $j = $c->get('/echo')->json();
        same('GET', $j['method']);
        same('', $j['body']);
    },
];
