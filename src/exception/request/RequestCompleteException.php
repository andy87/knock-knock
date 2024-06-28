<?php

/**
 * @name: knock-knock
 * @author Andrey and_y87 Kidin
 * @description Исключение
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.3.2
 */

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