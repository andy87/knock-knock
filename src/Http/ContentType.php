<?php

declare(strict_types=1);

namespace KnockKnock\Http;

enum ContentType: string
{
    case Json = 'application/json';
    case Form = 'application/x-www-form-urlencoded';
    case Text = 'text/plain';
    case Xml = 'application/xml';
    case Html = 'text/html';

    /**
     * @throws \JsonException
     */
    public function encode(array|string $data): string
    {
        if (is_string($data)) {
            return $data;
        }

        return match ($this) {
            self::Json => json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            self::Form => http_build_query($data, '', '&', PHP_QUERY_RFC3986),
            default => throw new \InvalidArgumentException(
                "Cannot encode an array as {$this->value}; pass a pre-encoded string.",
            ),
        };
    }
}
