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