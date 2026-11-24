<?php

declare(strict_types=1);

/**
 * Log every HTTP call to PostgreSQL: INSERT before sending, UPDATE after the response.
 *
 * Why a Transport decorator instead of Event::BeforeSend / Event::AfterSend?
 * It sees the exact final Request, keeps the row id in a local variable (no way to carry
 * state between two separate event listeners), and also catches network failures.
 *
 * Schema (PostgreSQL):
 *
 *   CREATE TABLE http_log (
 *       id            bigserial PRIMARY KEY,
 *       method        text        NOT NULL,
 *       url           text        NOT NULL,
 *       request_headers jsonb     NOT NULL DEFAULT '{}',
 *       request_body  text,
 *       status        smallint,                 -- NULL until a response arrives
 *       response_headers jsonb,
 *       response_body text,
 *       error         text,                     -- transport error, if any
 *       duration_ms   integer,
 *       created_at    timestamptz NOT NULL DEFAULT now(),
 *       finished_at   timestamptz
 *   );
 *   -- A row with finished_at IS NULL means the call is in flight or the process died mid-request.
 */

use KnockKnock\Client;
use KnockKnock\Exception\TransportException;
use KnockKnock\Http\{Request, Response};
use KnockKnock\Transport\{CurlTransport, Transport};

require __DIR__ . '/../autoload.php';

final class DbLoggingTransport implements Transport
{
    private const SECRET_HEADERS = ['authorization', 'cookie', 'x-api-key'];
    private const MAX_BODY = 65536; // bytes stored per body

    public function __construct(
        private readonly Transport $inner,
        private readonly \PDO $pdo,
    ) {
    }

    public function send(Request $request): Response
    {
        // 1. Save the request before it goes out.
        $stmt = $this->pdo->prepare(
            'INSERT INTO http_log (method, url, request_headers, request_body)
             VALUES (:method, :url, :headers, :body) RETURNING id',
        );
        $stmt->execute([
            'method' => $request->method->value,
            'url' => $request->url,
            'headers' => json_encode($this->redact($request->headers), JSON_THROW_ON_ERROR),
            'body' => $this->cut($request->body),
        ]);
        $id = (int) $stmt->fetchColumn();

        $started = hrtime(true);
        try {
            $response = $this->inner->send($request);
        } catch (TransportException $e) {
            // 2a. No response: record the error.
            $this->finish($id, $started, ['error' => $e->getMessage()]);
            throw $e;
        }

        // 2b. Response received: update the same row.
        $this->finish($id, $started, [
            'status' => $response->status,
            'response_headers' => json_encode($this->redact($response->headers), JSON_THROW_ON_ERROR),
            'response_body' => $this->cut($response->body),
        ]);

        return $response;
    }

    /** @param array<string, mixed> $columns */
    private function finish(int $id, int $startedNs, array $columns): void
    {
        $columns['duration_ms'] = intdiv(hrtime(true) - $startedNs, 1_000_000);

        $set = implode(', ', array_map(static fn (string $c): string => "$c = :$c", array_keys($columns)));
        $this->pdo
            ->prepare("UPDATE http_log SET $set, finished_at = now() WHERE id = :id")
            ->execute($columns + ['id' => $id]);
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private function redact(array $headers): array
    {
        foreach ($headers as $name => $_) {
            if (in_array(strtolower($name), self::SECRET_HEADERS, true)) {
                $headers[$name] = '***';
            }
        }

        return $headers;
    }

    private function cut(?string $body): ?string
    {
        return $body !== null && strlen($body) > self::MAX_BODY
            ? substr($body, 0, self::MAX_BODY) . '…[truncated]'
            : $body;
    }
}

// ---- Usage -----------------------------------------------------------------------------------

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $pdo = new \PDO('pgsql:host=127.0.0.1;port=5432;dbname=app', 'app', getenv('DB_PASSWORD') ?: '', [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    ]);

    $api = new Client(
        'https://api.example.com',
        new DbLoggingTransport(new CurlTransport(), $pdo),   // every call below is logged
    );
    $api = $api->withBearer(getenv('API_TOKEN') ?: 'token');

    $api->post('/orders', ['sku' => 'A-1', 'qty' => 2])->throw();
}
