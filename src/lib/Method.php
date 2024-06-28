<?php declare(strict_types=1);

/**
 * @name: knock-knock
 * @author Andrey and_y87 Kidin
 * @description Библиотека содержащая константы методов запросов
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.3.2
 */

namespace andy87\knock_knock\lib;

/**
 * Class KnockMethod
 *
 * @package andy87\knock_knock\query
 */
class Method
{
    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const DELETE = 'DELETE';
    public const PATCH = 'PATCH';
    public const OPTIONS = 'OPTIONS';
    public const HEAD = 'HEAD';
    public const TRACE = 'TRACE';
}