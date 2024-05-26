<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Класс для тестов
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 0.99b
 */

declare(strict_types=1);

namespace tests\examples;

use andy87\knock_knock\core\KnockKnock;
use andy87\knock_knock\interfaces\KnockKnockInterface;
use Exception;

/**
 *  Class KnockKnockExample
 *
 * @package tests\examples
 */
class KnockKnockExample extends KnockKnock
{
    public const INIT_INIT = 'init_stay';
    public const INIT_DONE = 'init_done';

    public static string $initResult = self::INIT_INIT;


    public const EVENT_INIT = 'event_Init';
    public const EVENT_DONE = 'event_Done';

    public array $eventResultList = [
        KnockKnockInterface::EVENT_AFTER_INIT => self::EVENT_INIT,
        KnockKnockInterface::EVENT_CONSTRUCT_REQUEST => self::EVENT_INIT,
        KnockKnockInterface::EVENT_BEFORE_SEND => self::EVENT_INIT,
        KnockKnockInterface::EVENT_CURL_HANDLER => self::EVENT_INIT,
        KnockKnockInterface::EVENT_CONSTRUCT_RESPONSE => self::EVENT_INIT,
        KnockKnockInterface::EVENT_AFTER_SEND => self::EVENT_INIT,
    ];

    public const MY_EVENT_1 = 'my_event_1';
    public const MY_EVENT_2 = 'my_event_2';

    /**
     * @throws Exception
     */
    public function init(): void
    {
        self::$initResult = self::INIT_DONE;

        parent::init();
    }


}