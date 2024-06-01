<?php

namespace andy87\knock_knock\exception\request;

use Exception;

/**
 * Exception for class `Request`
 *
 *     Invalid request params
 *
 * @package andy87\knock_knock\exception
 */
class InvalidRequestException extends Exception
{
    /** @var string Exception message */
    protected $message = 'Invalid request params';
}