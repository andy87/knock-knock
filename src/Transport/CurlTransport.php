<?php

declare(strict_types=1);

namespace KnockKnock\Transport;

use KnockKnock\Exception\TransportException;
use KnockKnock\Http\{Method, Request, Response};

/**
 * cURL transport. One handle is reused across requests so connections
 * (keep-alive, TLS sessions, DNS cache) are shared.
 */
final class CurlTransport implements Transport
{
    private ?\CurlHandle $handle = null;

    /** @param array<int, mixed> $defaults CURLOPT_* applied to every request */
    public function __construct(private readonly array $defaults = [])
    {
    }

    public function send(Request $request): Response
    {
        $ch = $this->handle ??= curl_init();
        curl_reset($ch);

        $lines = [];
        foreach ($request->headers as $name => $value) {
            $lines[] = $value === '' ? "$name;" : "$name: $value"; // "Name;" sends an empty header
        }

        $headers = [];
        $options = [
            CURLOPT_URL => $request->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_HEADERFUNCTION => static function (\CurlHandle $_, string $line) use (&$headers): int {
                if (str_starts_with($line, 'HTTP/')) {
                    $headers = []; // a new response block: redirect or 100-continue
                } elseif (($pos = strpos($line, ':')) > 0) {
                    $name = strtolower(trim(substr($line, 0, $pos)));
                    $value = trim(substr($line, $pos + 1));
                    $headers[$name] = isset($headers[$name]) ? "$headers[$name], $value" : $value;
                }

                return strlen($line);
            },
        ] + $this->defaults;

        match ($request->method) {
            Method::Get => $options[CURLOPT_HTTPGET] = true,
            Method::Head => $options[CURLOPT_NOBODY] = true,
            default => $options[CURLOPT_CUSTOMREQUEST] = $request->method->value,
        };
        if ($request->body !== null && $request->method !== Method::Head) {
            $options[CURLOPT_POSTFIELDS] = $request->body;
        }

        curl_setopt_array($ch, $request->curlOptions + $options);

        $body = curl_exec($ch);
        if ($body === false) {
            throw new TransportException(curl_error($ch) ?: 'Unknown cURL error', curl_errno($ch));
        }

        return new Response(
            (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
            (string) $body,
            $request,
            $headers,
            [
                'total_time' => curl_getinfo($ch, CURLINFO_TOTAL_TIME),
                'primary_ip' => curl_getinfo($ch, CURLINFO_PRIMARY_IP),
                'size_download' => curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD),
                'effective_url' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
            ],
        );
    }
}
