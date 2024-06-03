<?php

namespace andy87\knock_knock\exception;

use Exception;

/**
 * Exception for all classes
 *
 *     Update param denied
 *
 * @package andy87\knock_knock\exception
 */
class ParamUpdateException extends Exception
{
    /** @var string Exception message */
    protected $message = 'Update param denied';
}