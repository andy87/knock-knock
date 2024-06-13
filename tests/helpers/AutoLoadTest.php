<?php declare(strict_types=1);

/**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Класс для тестов
 * @homepage: https://github.com/andy87/Handler
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.3.0
 */

namespace andy87\knock_knock\tests\helpers;

use andy87\knock_knock\core\Operator;
use andy87\knock_knock\exception\InvalidEndpointException;
use andy87\knock_knock\exception\operator\InvalidMethodException;
use andy87\knock_knock\exception\ParamNotFoundException;
use andy87\knock_knock\exception\ParamUpdateException;
use andy87\knock_knock\exception\request\InvalidHeaderException;
use andy87\knock_knock\exception\request\InvalidRequestException;
use andy87\knock_knock\exception\request\RequestCompleteException;
use andy87\knock_knock\exception\request\StatusNotFoundException;
use andy87\knock_knock\interfaces\RequestInterface;
use andy87\knock_knock\lib\Method;

/**
 * Class AutoLoadTest
 *
 * Тесты автоподключения через файл `autoload.php`
 *
 * @package andy87\knock_knock\tests\helpers
 *
 * @cli vendor/bin/phpunit tests/helpers/AutoLoadTest.php --testdox
 *
 * @tag #test #autoload
 */
class AutoLoadTest extends UnitTestCore
{
    /**
     * Тест работы библиотеки с подключением через файл `autoload.php`
     *
     * @return void
     *
     * @throws ParamNotFoundException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     * @throws InvalidEndpointException|StatusNotFoundException|ParamUpdateException|InvalidMethodException
     *
     * @cli vendor/bin/phpunit tests/helpers/AutoLoadTest.php --testdox
     *
     * @tag #test #autoload
     */
    public function testAutoLoad(): void
    {
        require_once __DIR__ . '/../../autoload.php';

        $this->assertTrue(class_exists('andy87\knock_knock\core\Operator'));

        $operator = new Operator(PostmanEcho::HOST);
        $operator->disableSSL();

        $request = $operator->constructRequest(Method::GET, PostmanEcho::ENDPOINT_GET);
        $request->setCurlOptions([
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true
        ]);

        $response = $operator->send($request);

        $content = json_decode($response->content, true);

        $this->assertArrayHasKey('args', $content);
        $this->assertArrayHasKey('headers', $content);
        $this->assertArrayHasKey('url', $content);

        $this->assertEquals($response->request->url, $content['url']);
    }
}