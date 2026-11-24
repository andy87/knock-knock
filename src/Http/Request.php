<?php

declare(strict_types=1);

namespace KnockKnock\Http;

use KnockKnock\Exception\InvalidRequestException;

/** Immutable HTTP request. Every `with*` call returns a modified copy. */
final readonly class Request
{
    /** @var array<string, string> header name (as given) => value, unique case-insensitively */
    public array $headers;

    /**
     * @param array<string, string> $headers
     * @param array<int, mixed> $curlOptions raw CURLOPT_* overrides, applied last
     */
    public function __construct(
        public Method $method,
        public string $url,
        array $headers = [],
        public ?string $body = null,
        public array $curlOptions = [],
    ) {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true) || parse_url($url, PHP_URL_HOST) === null) {
            throw new InvalidRequestException("Invalid URL `$url`: an absolute http(s) URL is required.");
        }

        $clean = [];
        foreach ($headers as $name => $value) {
            $name = self::checkName((string) $name);
            $clean[strtolower($name)] = [$name, self::checkValue((string) $value)];
        }
        $this->headers = array_column($clean, 1, 0);
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    public function withHeader(string $name, string $value): self
    {
        return $this->withHeaders([$name => $value]);
    }

    /** @param array<string, string> $headers new values replace existing ones, case-insensitively */
    public function withHeaders(array $headers): self
    {
        $replaced = array_change_key_case($headers);
        $kept = array_filter(
            $this->headers,
            static fn (string $name): bool => !array_key_exists(strtolower($name), $replaced),
            ARRAY_FILTER_USE_KEY,
        );

        return new self($this->method, $this->url, $kept + $headers, $this->body, $this->curlOptions);
    }

    public function withBody(?string $body): self
    {
        return new self($this->method, $this->url, $this->headers, $body, $this->curlOptions);
    }

    /** @param array<int, mixed> $options */
    public function withCurlOptions(array $options): self
    {
        return new self($this->method, $this->url, $this->headers, $this->body, $options + $this->curlOptions);
    }

    private static function checkName(string $name): string
    {
        if (preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/D', $name) !== 1) {
            throw new InvalidRequestException("Invalid header name `$name`.");
        }

        return $name;
    }

    private static function checkValue(string $value): string
    {
        if (strpbrk($value, "\r\n\0") !== false) {
            throw new InvalidRequestException('Header values must not contain CR, LF or NUL.');
        }

        return $value;
    }
}
