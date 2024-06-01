<?php

namespace andy87\knock_knock\exception\request;

use Exception;

/**
 * Exception for class `Request`
 *
 *     Invalid protocol value
 *
 * @package andy87\knock_knock\exception

 */
class InvalidProtocolException extends Exception
{
    /** @var string Exception message */
    protected $message = 'Invalid `protocol` value`';
}