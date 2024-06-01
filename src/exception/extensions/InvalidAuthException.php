<?php

namespace andy87\knock_knock\exception\extensions;

use Exception;

/**
 * Exception for class `KnockKnockSecurity`
 *
 *     Invalid authorization type
 *
 * @package andy87\knock_knock\exception

 */
class InvalidAuthException extends Exception
{
    /** @var string Exception message */
    protected $message = 'Invalid authorization type';
}