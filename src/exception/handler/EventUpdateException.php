<?php

namespace andy87\knock_knock\exception\handler;

use Exception;

/**
 * Exception for class `Handler`
 *
 *      Event already exists
 *
 * @package andy87\knock_knock\exception

 */
class EventUpdateException extends Exception
{
    /** @var string Exception message */
    protected $message = 'Event already exists';
}