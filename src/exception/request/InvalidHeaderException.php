<?php

namespace andy87\knock_knock\exception\request;

use Exception;

/**
 * Exception for class `Request`
 *
 *     Invalid headers value
 *
 * @package andy87\knock_knock\exception
 */
class InvalidHeaderException extends Exception
{
    /** @var string Exception message */
    protected $message = 'Invalid `headers` value`';
}