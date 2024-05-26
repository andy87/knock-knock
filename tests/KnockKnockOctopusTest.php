<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Тесты для методов класса KnockKnockOctopus
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 0.99c
 */

declare(strict_types=1);

namespace tests;

use Exception;
use andy87\knock_knock\KnockKnockOctopus;
use tests\core\{ PostmanEcho, UnitTestCore };
use andy87\knock_knock\lib\LibKnockContentType;
use andy87\knock_knock\interfaces\KnockRequestInterface;

/**
 * Class KnockKnockOctopusTest
 *
 * Тесты для методов класса KnockKnockOctopus
 *
 * @package tests
 *
 * @cli vendor/bin/phpunit tests/KnockKnockOctopusTest.php --testdox
 *
 * @tag #test #knockKnock #octopus
 */
class KnockKnockOctopusTest extends UnitTestCore
{
    /** @var KnockKnockOctopus $knockKnockOctopus */
    public static KnockKnockOctopus $knockKnockOctopus;


    /**
     * Вспомогательный метод для получения объекта KnockKnockOctopus
     *
     * @return KnockKnockOctopus
     *
     * @throws Exception
     */
    private function getKnockKnockOctopus(): KnockKnockOctopus
    {
        $knockKnockOctopus = new KnockKnockOctopus(PostmanEcho::HOST);

        $knockKnockOctopus->disableSSL();

        return $knockKnockOctopus;
    }

    /**
     * Проверка запуска метод init через событие EVENT_AFTER_INIT
     *
     *      Ожидается, что значения свойства curlOptions объекта commonKnockRequest будут равны `KnockKnockOctopus::HEADERS`
     *
     * Source : @see KnockKnockOctopus::init()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockOctopusTest.php --filter testInit
     *
     * @tag #test #knockKnock #octopus #init
     */
    public function testInit(): void
    {
        $knockKnockOctopus = $this->getKnockKnockOctopus();

        $this->assertArrayHasKey(
            CURLOPT_HEADER,
            $knockKnockOctopus->commonKnockRequest->curlOptions,
            "Ожидается, что свойство `CURLOPT_HEADER` объекта `commonKnockRequest->curlOptions` будет существовать"
        );
        $this->assertArrayHasKey(
            CURLOPT_RETURNTRANSFER,
            $knockKnockOctopus->commonKnockRequest->curlOptions,
            "Ожидается, что свойство `CURLOPT_RETURNTRANSFER` объекта `commonKnockRequest->curlOptions` будет существовать"
        );
        $this->assertArrayHasKey(
            CURLOPT_SSL_VERIFYPEER,
            $knockKnockOctopus->commonKnockRequest->curlOptions,
            "Ожидается, что свойство `CURLOPT_SSL_VERIFYPEER` объекта `commonKnockRequest->curlOptions` будет существовать"
        );
        $this->assertArrayHasKey(
            CURLOPT_SSL_VERIFYHOST,
            $knockKnockOctopus->commonKnockRequest->curlOptions,
            "Ожидается, что свойство `CURLOPT_SSL_VERIFYHOST` объекта `commonKnockRequest->curlOptions` будет существовать"
        );
    }

    /**
     * Проверка отправки GET запроса
     *
     *      Ожидается, что метод вернет объект KnockResponse с актуальными данными
     *
     * Source : @see KnockKnockOctopus::get()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockOctopusTest.php --filter testGet
     *
     * @tag #test #knockKnock #octopus #get
     */
    public function testGet(): void
    {
        $knockKnockOctopus = $this->getKnockKnockOctopus();

        $knockResponse = $knockKnockOctopus->get(PostmanEcho::ENDPOINT_GET, [
            KnockRequestInterface::SETUP_DATA => PostmanEcho::DATA
        ]);

        $response = json_decode( $knockResponse->content, true );

        $this->assertArrayHasKey('args', $response);
        $this->assertArrayHasKey('headers', $response);
        $this->assertArrayHasKey('url', $response);

        $this->assertEquals( $knockResponse->request->url, $response['url'] );

        $this->assertEquals( PostmanEcho::DATA, $response['args']['data']);
    }

    /**
     * Проверка отправки POST запроса
     *
     *      Ожидается, что метод вернет объект KnockResponse с актуальными данными
     *
     * Source : @see KnockKnockOctopus::post()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockOctopusTest.php --filter testPost
     *
     * @tag #test #knockKnock #octopus #post
     */
    public function testPost(): void
    {
        $knockKnockOctopus = $this->getKnockKnockOctopus();

        $knockKnockOctopus->commonKnockRequest->setContentType(LibKnockContentType::FORM);

        $knockResponse = $knockKnockOctopus->post(PostmanEcho::ENDPOINT_POST, PostmanEcho::DATA);

        $response = json_decode( $knockResponse->content, true );

        //throw new Exception( print_r($response) );

        /** @see PostmanEcho::ENDPOINT_POST */
        $this->assertArrayHasKey('args', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('files', $response);
        $this->assertArrayHasKey('form', $response);
        $this->assertArrayHasKey('headers', $response);
        $this->assertArrayHasKey('json', $response);
        $this->assertArrayHasKey('url', $response);

        $this->assertEquals( $knockResponse->request->url, $response['url'] );

        $this->assertEquals( PostmanEcho::DATA, $response['json']);
    }


    /**
     * Проверка возврата фейквого ответа
     *
     *      Ожидается, что метод вернет объект KnockResponse с актуальными фейковыми данными
     *      переданными в параметре $fakeResponse
     *
     * Source : @see KnockKnockOctopus::fakeResponse()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockOctopusTest.php --filter testFakeResponse
     *
     * @tag #test #knockKnock #octopus #get #fakeResponse
     */
    public function testFakeResponse(): void
    {
        $knockKnockOctopus = $this->getKnockKnockOctopus();

        $fakeResponse = [
            'content' => 'fake content',
            'httpCode' => 200,
        ];

        $knockResponse = $knockKnockOctopus->fakeResponse($fakeResponse);

        $this->assertEquals( $fakeResponse['content'], $knockResponse->content );
        $this->assertEquals( $fakeResponse['httpCode'], $knockResponse->httpCode );
    }
}