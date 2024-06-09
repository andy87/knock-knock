<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Интерфейс класса ответа
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.3.0
 */

declare(strict_types=1);

namespace andy87\knock_knock\interfaces;

/**
 * Interface ResponseInterface
 *
 * @package andy87\knock_knock\interfaces
 *
 * Fix not used:
 * - @see ResponseInterface::OK
 * - @see ResponseInterface::ERROR
 */
interface ResponseInterface
{
    /** @var int */
    public const OK = 200;
    /** @var int */
    public const ERROR = 500;


    /** @var string */
    public const CONTENT = 'content';
    /** @var string */
    public const HTTP_CODE = 'httpCode';
}