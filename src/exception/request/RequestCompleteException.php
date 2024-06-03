<?php

namespace andy87\knock_knock\exception\request;

use Exception;

/**
 * Exception for class `Request`
 *
 *     Request is already complete
 *
 * @package andy87\knock_knock\exception
 */
class RequestCompleteException extends Exception
{
    /** @var string Exception message */
    protected $message = 'Request is already complete';
}