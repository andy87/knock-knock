<?php

namespace andy87\knock_knock\helpers;

/**
 * Class KnockMethod
 *
 * @package andy87\knock_knock\query
 *
 * Fix not used:
 * - @see KnockMethod::GET;
 * - @see KnockMethod::POST;
 * - @see KnockMethod::PUT;
 * - @see KnockMethod::DELETE;
 * - @see KnockMethod::PATCH;
 * - @see KnockMethod::OPTIONS;
 * - @see KnockMethod::HEAD;
 * - @see KnockMethod::TRACE;
 */
class KnockMethod
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