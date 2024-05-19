<?php

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