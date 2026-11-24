<?php

declare(strict_types=1);

namespace KnockKnock;

use KnockKnock\Exception\{InvalidRequestException, TransportException};
use KnockKnock\Http\{ContentType, Method, Request, Response};
use KnockKnock\Transport\{CurlTransport, Transport};

/**
 * HTTP client facade. Immutable: every `with*`/`on` call returns a configured copy,
 * so a base client can be safely shared and specialised per call site.
 *
 *     $api = (new Client('https://api.example.com'))->withBearer($token);
 *     $user = $api->get('/users/1', ['expand' => 'roles'])->throw()->json();
 */
final class Client
{
    private readonly string $baseUrl;
    private readonly Transport $transport;

    /**
     * @param array<string, string> $headers default headers for every request
     * @param array<string, list<callable>> $listeners
     */
    public function __construct(
        string $baseUrl,
        ?Transport $transport = null,
        private readonly array $headers = [],
        private readonly ContentType $contentType = ContentType::Json,
        private readonly float $timeout = 30.0,
        private readonly float $connectTimeout = 10.0,
        private readonly bool $verifySsl = true,
        private readonly bool $followRedirects = false,
        private readonly array $listeners = [],
    ) {
        $this->baseUrl = self::normalizeBaseUrl($baseUrl);
        $this->transport = $transport ?? new CurlTransport();
    }

    // --- Configuration (all return a modified copy) ---

    /** @param array<string, string> $headers */
    public function withHeaders(array $headers): self
    {
        // Request de-duplicates names case-insensitively, the later value wins.
        return $this->copy(headers: array_merge($this->headers, $headers));
    }

    public function withBearer(string $token): self
    {
        return $this->withHeaders(['Authorization' => "Bearer $token"]);
    }

    public function withBasic(string $user, string $password): self
    {
        return $this->withHeaders(['Authorization' => 'Basic ' . base64_encode("$user:$password")]);
    }

    public function withContentType(ContentType $type): self
    {
        return $this->copy(contentType: $type);
    }

    public function withTimeout(float $seconds, ?float $connectSeconds = null): self
    {
        return $this->copy(timeout: $seconds, connectTimeout: $connectSeconds ?? $this->connectTimeout);
    }

    public function withSslVerification(bool $verify): self
    {
        return $this->copy(verifySsl: $verify);
    }

    public function withRedirects(bool $follow = true): self
    {
        return $this->copy(followRedirects: $follow);
    }

    /** Registers a listener; see {@see Event} for what each event passes and accepts back. */
    public function on(Event $event, callable $listener): self
    {
        $listeners = $this->listeners;
        $listeners[$event->value][] = $listener;

        return $this->copy(listeners: $listeners);
    }

    // --- Verbs ---

    /** @param array<string, mixed> $query */
    public function get(string $path, array $query = [], array $headers = []): Response
    {
        return $this->request(Method::Get, $path, $query, $headers);
    }

    /** @param array<string, mixed>|string $data */
    public function post(string $path, array|string $data = [], array $headers = []): Response
    {
        return $this->request(Method::Post, $path, $data, $headers);
    }

    /** @param array<string, mixed>|string $data */
    public function put(string $path, array|string $data = [], array $headers = []): Response
    {
        return $this->request(Method::Put, $path, $data, $headers);
    }

    /** @param array<string, mixed>|string $data */
    public function patch(string $path, array|string $data = [], array $headers = []): Response
    {
        return $this->request(Method::Patch, $path, $data, $headers);
    }

    /** @param array<string, mixed>|string $data */
    public function delete(string $path, array|string $data = [], array $headers = []): Response
    {
        return $this->request(Method::Delete, $path, $data, $headers);
    }

    /** @param array<string, mixed> $query */
    public function head(string $path, array $query = [], array $headers = []): Response
    {
        return $this->request(Method::Head, $path, $query, $headers);
    }

    /** @param array<string, mixed> $query */
    public function options(string $path, array $query = [], array $headers = []): Response
    {
        return $this->request(Method::Options, $path, $query, $headers);
    }

    /** @param array<string, mixed> $query */
    public function trace(string $path, array $query = [], array $headers = []): Response
    {
        return $this->request(Method::Trace, $path, $query, $headers);
    }

    // --- Core ---

    /**
     * Builds, sends and returns the response for a request.
     * GET/HEAD/OPTIONS/TRACE data goes to the query string; other methods send it as the body.
     *
     * @param array<string, mixed>|string $data
     * @param array<string, string> $headers per-request headers (override client defaults)
     * @throws InvalidRequestException|TransportException
     */
    public function request(Method $method, string $path, array|string $data = [], array $headers = []): Response
    {
        $request = $this->build($method, $path, $data, $headers);

        foreach ($this->listeners[Event::BeforeSend->value] ?? [] as $listener) {
            $request = $listener($request) ?? $request;
        }

        try {
            $response = $this->transport->send($request);
        } catch (TransportException $e) {
            foreach ($this->listeners[Event::Failure->value] ?? [] as $listener) {
                $listener($e, $request);
            }
            throw $e;
        }

        foreach ($this->listeners[Event::AfterSend->value] ?? [] as $listener) {
            $response = $listener($response) ?? $response;
        }

        return $response;
    }

    // --- Internals ---

    private function build(Method $method, string $path, array|string $data, array $headers): Request
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path) === 1) {
            throw new InvalidRequestException('Pass a relative path; the host is fixed by the client base URL.');
        }

        $url = $this->baseUrl . '/' . ltrim($path, '/');
        $body = null;
        $hasData = $data !== [] && $data !== '';

        if ($hasData && $method->usesQuery()) {
            $query = is_array($data) ? http_build_query($data, '', '&', PHP_QUERY_RFC3986) : ltrim($data, '?&');
            $url .= (str_contains($url, '?') ? '&' : '?') . $query;
        } elseif ($hasData) {
            try {
                $body = $this->contentType->encode($data);
            } catch (\JsonException | \InvalidArgumentException $e) {
                throw new InvalidRequestException($e->getMessage(), 0, $e);
            }
            $headers = $this->hasHeader($headers, 'content-type')
                ? $headers
                : $headers + ['Content-Type' => $this->contentType->value];
        }

        return (new Request($method, $url, $this->headers))
            ->withHeaders($headers)
            ->withBody($body)
            ->withCurlOptions([
                CURLOPT_TIMEOUT_MS => (int) ($this->timeout * 1000),
                CURLOPT_CONNECTTIMEOUT_MS => (int) ($this->connectTimeout * 1000),
                CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
                CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
                CURLOPT_FOLLOWLOCATION => $this->followRedirects,
            ]);
    }

    /** @param array<string, string> $headers */
    private function hasHeader(array $headers, string $lowerName): bool
    {
        return array_key_exists($lowerName, array_change_key_case($headers));
    }

    private static function normalizeBaseUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');
        if (!str_contains($url, '://')) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);
        if ($parts === false || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            throw new InvalidRequestException("Invalid base URL `$url`.");
        }

        return $url;
    }

    /**
     * @param array<string, string>|null $headers
     * @param array<string, list<callable>>|null $listeners
     */
    private function copy(
        ?array $headers = null,
        ?ContentType $contentType = null,
        ?float $timeout = null,
        ?float $connectTimeout = null,
        ?bool $verifySsl = null,
        ?bool $followRedirects = null,
        ?array $listeners = null,
    ): self {
        return new self(
            $this->baseUrl,
            $this->transport,
            $headers ?? $this->headers,
            $contentType ?? $this->contentType,
            $timeout ?? $this->timeout,
            $connectTimeout ?? $this->connectTimeout,
            $verifySsl ?? $this->verifySsl,
            $followRedirects ?? $this->followRedirects,
            $listeners ?? $this->listeners,
        );
    }
}
