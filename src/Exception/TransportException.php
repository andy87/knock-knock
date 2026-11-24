<?php

declare(strict_types=1);

namespace KnockKnock\Exception;

/** Network-level failure (DNS, connection, timeout, TLS). No HTTP response exists. */
final class TransportException extends KnockKnockException
{
}
