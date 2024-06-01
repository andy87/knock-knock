<?php

namespace andy87\knock_knock\exception;

use Exception;

/**
 * Exception for all classes
 *
 *     invalid host value
 *
 * @package andy87\knock_knock\exception

 */
class InvalidEndpointException extends Exception
{
    /** @var string Exception message */
    protected $message = 'Invalid `host` value`';
}