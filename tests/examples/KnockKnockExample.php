<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Класс для тестов
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 0.99c
 */

declare(strict_types=1);

namespace andy87\knock_knock\tests\examples;

use Exception;
use andy87\knock_knock\core\KnockKnock;
use andy87\knock_knock\interfaces\KnockKnockInterface;

/**
 *  Class KnockKnockExample
 *
 * @package tests\examples
 */
class KnockKnockExample extends KnockKnock
{
    public const INIT_INIT = 'init_Stay';
    public const INIT_DONE = 'init_Done';

    public static string $initResult = self::INIT_INIT;



    public const MY_EVENT = 'my_event';



    /**
     * @throws Exception
     */
    public function init(): void
    {
        self::$initResult = self::INIT_DONE;

        parent::init();
    }
}