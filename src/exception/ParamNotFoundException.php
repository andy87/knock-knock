<?php

namespace andy87\knock_knock\exception;

use Exception;

/**
 *  Exception for all classes
 *
 * @package andy87\knock_knock\exception\base
 */
class ParamNotFoundException extends Exception
{
    /** @var string Exception message */
    protected $message = 'Param not found';

    /** @var int Exception code */
    protected $code = 502;
}