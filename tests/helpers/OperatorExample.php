<?php /**
 * @name: Handler
 * @author Andrey and_y87 Kidin
 * @description Класс для тестов
 * @homepage: https://github.com/andy87/Handler
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.3.0
 */

declare(strict_types=1);

namespace andy87\knock_knock\tests\helpers;

use Exception;
use andy87\knock_knock\core\Operator;

/**
 *  Class HandlerExample
 *
 * @package tests\examples
 */
class OperatorExample extends Operator
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