<?php

namespace andy87\knock_knock\lib;

/**
 * Class KnockContentType
 *
 * @package andy87\knock_knock\query
 *
 * Fix not used:
 * - @see LibKnockContentType::HTML;
 * - @see LibKnockContentType::JAVASCRIPT;
 * - @see LibKnockContentType::CSS;
 * - @see LibKnockContentType::CSV;
 * - @see LibKnockContentType::PDF;
 * - @see LibKnockContentType::ZIP;
 * - @see LibKnockContentType::GZIP;
 * - @see LibKnockContentType::TAR;
 * - @see LibKnockContentType::RAR;
 * - @see LibKnockContentType::SEVEN_ZIP;
 * - @see LibKnockContentType::IMAGE;
 * - @see LibKnockContentType::AUDIO;
 * - @see LibKnockContentType::VIDEO;
 * - @see LibKnockContentType::FONT;
 * - @see LibKnockContentType::ANY;
 */
class LibKnockContentType
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
    public const SEVEN_ZIP = 'application/x-7z-compressed';
    public const IMAGE = 'image/*';
    public const AUDIO = 'audio/*';
    public const VIDEO = 'video/*';
    public const FONT = 'font/*';
    public const ANY = '*/*';
}