<?php

declare(strict_types=1);

namespace KnockKnock\Exception;

use KnockKnock\Http\Response;

/** Thrown by {@see Response::throw()} for 4xx/5xx responses. */
final class HttpException extends KnockKnockException
{
    public function __construct(public readonly Response $response)
    {
        parent::__construct(
            sprintf('HTTP %d for %s %s', $response->status, $response->request->method->value, $response->request->url),
            $response->status,
        );
    }
}
