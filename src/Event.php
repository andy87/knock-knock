<?php

declare(strict_types=1);

namespace KnockKnock;

/**
 * Client lifecycle events and the argument each listener receives.
 *
 *  - BeforeSend(Request): may return a replacement Request
 *  - AfterSend(Response): may return a replacement Response
 *  - Failure(TransportException, Request): observation only
 */
enum Event: string
{
    case BeforeSend = 'beforeSend';
    case AfterSend = 'afterSend';
    case Failure = 'failure';
}
