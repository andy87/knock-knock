<?php

namespace andy87\knock_knock\helpers;

/**
 * Class KnockContentType
 *
 * @package andy87\knock_knock\query
 *
 * Fix not used:
 * - @see KnockContentType::JSON;
 * - @see KnockContentType::XML;
 * - @see KnockContentType::FORM;
 * - @see KnockContentType::MULTIPART;
 * - @see KnockContentType::TEXT;
 * - @see KnockContentType::HTML;
 * - @see KnockContentType::JAVASCRIPT;
 * - @see KnockContentType::CSS;
 * - @see KnockContentType::CSV;
 * - @see KnockContentType::PDF;
 * - @see KnockContentType::ZIP;
 * - @see KnockContentType::GZIP;
 * - @see KnockContentType::TAR;
 * - @see KnockContentType::RAR;
 * - @see KnockContentType::SEVEN_ZIP;
 * - @see KnockContentType::IMAGE;
 * - @see KnockContentType::AUDIO;
 * - @see KnockContentType::VIDEO;
 * - @see KnockContentType::FONT;
 * - @see KnockContentType::ANY;
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
    public const SEVEN_ZIP = 'application/x-7z-compressed';
    public const IMAGE = 'image/*';
    public const AUDIO = 'audio/*';
    public const VIDEO = 'video/*';
    public const FONT = 'font/*';
    public const ANY = '*/*';
}