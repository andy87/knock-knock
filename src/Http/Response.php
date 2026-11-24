<?php

declare(strict_types=1);

namespace KnockKnock\Http;

use KnockKnock\Exception\HttpException;
use KnockKnock\Exception\InvalidRequestException;

/** Immutable HTTP response. */
final readonly class Response
{
    /** @var array<string, string> Single-value view. Set-Cookie exposes the first cookie. */
    public array $headers;

    /** @var array<string, list<string>> Individual header fields in arrival order. */
    private array $fields;

    /**
     * @param array<string, string|list<string>> $headers response fields, including repeated values
     * @param array<string, mixed> $info transport diagnostics (timings, sizes, ...)
     */
    public function __construct(
        public int $status,
        public string $body,
        public Request $request,
        array $headers = [],
        public array $info = [],
    ) {
        $fields = [];
        foreach ($headers as $name => $values) {
            foreach ((array) $values as $value) {
                $fields[strtolower($name)][] = $value;
            }
        }
        $this->fields = $fields;
        $single = [];
        foreach ($fields as $name => $values) {
            $single[$name] = $name === 'set-cookie' ? $values[0] : implode(', ', $values);
        }
        $this->headers = $single;
    }

    /**
     * Returns individual values in arrival order.
     * @param string $name Case-insensitive field name.
     * @return list<string> Empty when the header is absent.
     */
    public function headerValues(string $name): array
    {
        return $this->fields[strtolower($name)] ?? [];
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function failed(): bool
    {
        return $this->status >= 400;
    }

    /**
     * @return array<mixed>
     * @throws InvalidRequestException when the body is not a valid JSON object/array
     */
    public function json(): array
    {
        try {
            $data = json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidRequestException('Response body is not valid JSON: ' . $e->getMessage(), 0, $e);
        }

        return is_array($data) ? $data : throw new InvalidRequestException('Response JSON is not an object or array.');
    }

    /** @throws HttpException on 4xx/5xx; returns $this otherwise, for chaining */
    public function throw(): self
    {
        return $this->failed() ? throw new HttpException($this) : $this;
    }
}
