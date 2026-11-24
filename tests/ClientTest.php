<?php

declare(strict_types=1);

use KnockKnock\{Client, Event};
use KnockKnock\Exception\{HttpException, InvalidRequestException, TransportException};
use KnockKnock\Http\{ContentType, Method};
use KnockKnock\Transport\FakeTransport;

$make = static fn (FakeTransport $t, string $base = 'https://api.test/v1/'): Client => new Client($base, $t);

return [
    'GET data goes to query string' => function () use ($make) {
        $t = (new FakeTransport())->queue();
        $make($t)->get('/users', ['a' => 1, 'q' => 'x y']);
        same('https://api.test/v1/users?a=1&q=x%20y', $t->sent[0]->url);
        same(null, $t->sent[0]->body);
    },
    'query appended with & when path has one' => function () use ($make) {
        $t = (new FakeTransport())->queue();
        $make($t)->get('/s?a=1', ['b' => 2]);
        same('https://api.test/v1/s?a=1&b=2', $t->sent[0]->url);
    },
    'POST encodes JSON by default with content-type' => function () use ($make) {
        $t = (new FakeTransport())->queue();
        $make($t)->post('/n', ['name' => 'Привет']);
        same('{"name":"Привет"}', $t->sent[0]->body);
        same('application/json', $t->sent[0]->header('content-type'));
    },
    'form content type' => function () use ($make) {
        $t = (new FakeTransport())->queue();
        $make($t)->withContentType(ContentType::Form)->post('/n', ['a' => 'b c']);
        same('a=b%20c', $t->sent[0]->body);
    },
    'string body sent as-is, including "0"' => function () use ($make) {
        $t = (new FakeTransport())->queue();
        $make($t)->post('/n', '0');
        same('0', $t->sent[0]->body);
    },
    'empty POST sends no body or content-type' => function () use ($make) {
        $t = (new FakeTransport())->queue();
        $make($t)->post('/n');
        same(null, $t->sent[0]->body);
        same(null, $t->sent[0]->header('content-type'));
    },
    'all verbs map to methods' => function () use ($make) {
        $t = new FakeTransport();
        $c = $make($t);
        foreach (['get', 'post', 'put', 'patch', 'delete', 'head', 'options', 'trace'] as $verb) {
            $t->queue();
            $c->$verb('/x');
        }
        same(
            array_map(static fn (Method $m) => $m->value, Method::cases()),
            array_map(static fn ($r) => $r->method->value, $t->sent),
        );
    },
    'client is immutable; headers merge case-insensitively' => function () use ($make) {
        $t = (new FakeTransport())->queue()->queue();
        $base = $make($t)->withHeaders(['X-A' => '1']);
        $derived = $base->withBearer('tok')->withHeaders(['x-a' => '2']);
        $base->get('/a');
        $derived->get('/a');
        same(null, $t->sent[0]->header('authorization'));
        same('1', $t->sent[0]->header('x-a'));
        same('Bearer tok', $t->sent[1]->header('authorization'));
        same('2', $t->sent[1]->header('x-a'));
        same(1, count(array_filter(array_keys($t->sent[1]->headers), fn ($k) => strtolower($k) === 'x-a')));
    },
    'basic auth' => function () use ($make) {
        $t = (new FakeTransport())->queue();
        $make($t)->withBasic('u', 'p')->get('/');
        same('Basic ' . base64_encode('u:p'), $t->sent[0]->header('Authorization'));
    },
    'per-request header overrides default' => function () use ($make) {
        $t = (new FakeTransport())->queue();
        $make($t)->withHeaders(['Accept' => 'a/b'])->get('/', [], ['accept' => 'c/d']);
        same('c/d', $t->sent[0]->header('Accept'));
    },
    'header injection rejected' => function () use ($make) {
        throws(InvalidRequestException::class, fn () => $make(new FakeTransport())->get('/', [], ['X' => "a\r\nEvil: 1"]));
        throws(InvalidRequestException::class, fn () => $make(new FakeTransport())->get('/', [], ["Bad Name" => 'v']));
    },
    'absolute URL in path rejected' => function () use ($make) {
        throws(InvalidRequestException::class, fn () => $make(new FakeTransport())->get('http://evil.test/'));
    },
    'invalid base url rejected; scheme defaults to https' => function () {
        throws(InvalidRequestException::class, fn () => new Client('ftp://x.test'));
        $t = (new FakeTransport())->queue();
        (new Client('api.test', $t))->get('/');
        same('https://api.test/', $t->sent[0]->url);
    },
    'curl options reflect config' => function () use ($make) {
        $t = (new FakeTransport())->queue();
        $make($t)->withTimeout(2.5, 1)->withSslVerification(false)->withRedirects()->get('/');
        $o = $t->sent[0]->curlOptions;
        same(2500, $o[CURLOPT_TIMEOUT_MS]);
        same(1000, $o[CURLOPT_CONNECTTIMEOUT_MS]);
        same(0, $o[CURLOPT_SSL_VERIFYHOST]);
        same(true, $o[CURLOPT_FOLLOWLOCATION]);
    },
    'response helpers' => function () use ($make) {
        $t = (new FakeTransport())->queueJson(['ok' => true])->queue(404, 'nope')->queue(200, 'not json');
        $c = $make($t);
        $r = $c->get('/');
        check($r->successful());
        same(['ok' => true], $r->json());
        same('application/json', $r->header('Content-Type'));
        $e = throws(HttpException::class, fn () => $c->get('/')->throw());
        same(404, $e->response->status);
        throws(InvalidRequestException::class, fn () => $c->get('/')->json());
    },
    'events: before/after can replace, failure observed' => function () use ($make) {
        $t = (new FakeTransport())->queue(200, 'orig')->queue(200, 'x');
        $log = [];
        $c = $make($t)
            ->on(Event::BeforeSend, fn ($req) => $req->withHeader('X-Trace', 'abc'))
            ->on(Event::AfterSend, function ($res) use (&$log) {
                $log[] = $res->status;
                return new KnockKnock\Http\Response(201, 'patched', $res->request);
            });
        $r = $c->get('/');
        same('abc', $t->sent[0]->header('x-trace'));
        same('patched', $r->body);
        same([200], $log);

        $failures = 0;
        $c2 = $make(new FakeTransport())->on(Event::Failure, function () use (&$failures) { $failures++; });
        throws(TransportException::class, fn () => $c2->get('/'));
        same(1, $failures);
    },
];
