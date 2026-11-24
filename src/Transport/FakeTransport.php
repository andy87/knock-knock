<?php

declare(strict_types=1);

namespace KnockKnock\Transport;

use KnockKnock\Exception\TransportException;
use KnockKnock\Http\{Request, Response};

/** In-memory transport for tests: queue canned responses, inspect what was sent. */
final class FakeTransport implements Transport
{
    /** @var list<array{int, string, array<string, string>}> */
    private array $queue = [];

    /** @var list<Request> */
    public array $sent = [];

    /** @param array<string, string> $headers */
    public function queue(int $status = 200, string $body = '', array $headers = []): self
    {
        $this->queue[] = [$status, $body, array_change_key_case($headers)];

        return $this;
    }

    /** @param array<mixed> $data */
    public function queueJson(array $data, int $status = 200): self
    {
        return $this->queue($status, json_encode($data, JSON_THROW_ON_ERROR), ['content-type' => 'application/json']);
    }

    public function send(Request $request): Response
    {
        $this->sent[] = $request;
        [$status, $body, $headers] = array_shift($this->queue)
            ?? throw new TransportException('FakeTransport: no queued response left.');

        return new Response($status, $body, $request, $headers);
    }
}
