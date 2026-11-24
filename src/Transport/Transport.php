<?php

declare(strict_types=1);

namespace KnockKnock\Transport;

use KnockKnock\Exception\TransportException;
use KnockKnock\Http\{Request, Response};

interface Transport
{
    /** @throws TransportException */
    public function send(Request $request): Response;
}
