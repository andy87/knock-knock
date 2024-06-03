<?php

namespace andy87\knock_knock\exception\handler;

use Exception;

/**
 * Exception for class `Handler`
 *
 *      Invalid method
 *
 * @package andy87\knock_knock\exception
 */
class InvalidMethodException extends Exception
{
    /** @var string Exception message */
    protected $message = 'Invalid method';
}