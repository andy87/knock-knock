<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Библиотека содержащая константы методов запросов
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 1.0.0
 */

declare(strict_types=1);

namespace andy87\knock_knock\lib;

/**
 * Class KnockMethod
 *
 * @package andy87\knock_knock\query
 *
 * Fix not used:
 * - @see LibKnockMethod::GET;
 */
class LibKnockMethod
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