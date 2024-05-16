<?php

namespace andy87\knock_knock\helpers;

/**
 * Class KnockContentType
 *
 * @package andy87\knock_knock\query
 */
class KnockContentType
{
    public const JSON = 'application/json';
    public const XML = 'application/xml';
    public const FORM = 'application/x-www-form-urlencoded';
    public const MULTIPART = 'multipart/form-data';
    public const TEXT = 'text/plain';
    public const HTML = 'text/html';
    public const JAVASCRIPT = 'application/javascript';
    public const CSS = 'text/css';
    public const CSV = 'text/csv';
    public const PDF = 'application/pdf';
    public const ZIP = 'application/zip';
    public const GZIP = 'application/gzip';
    public const TAR = 'application/x-tar';
    public const RAR = 'application/x-rar-compressed';
    public const SEVENZIP = 'application/x-7z-compressed';
    public const IMAGE = 'image/*';
    public const AUDIO = 'audio/*';
    public const VIDEO = 'video/*';
    public const FONT = 'font/*';
    public const ANY = '*/*';
}