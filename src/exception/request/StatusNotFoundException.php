<?php

namespace andy87\knock_knock\exception\request;

use Exception;

/**
 * Exception for class `Request`
 *
 *      Unknown status exception
 *
 * @package andy87\knock_knock\exception
 */
class StatusNotFoundException extends Exception
{
    /** @var string Exception message */
    protected $message = 'Unknown status exception';
}