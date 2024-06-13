<?php declare(strict_types=1);

/**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Тесты для методов класса KnockKnockOctopus
 * @homepage: https://github.com/andy87/Handler
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.3.0
 */

namespace andy87\knock_knock\tests\extensions;

use andy87\knock_knock\lib\ContentType;
use andy87\knock_knock\KnockKnockOctopus;
use andy87\knock_knock\tests\helpers\{PostmanEcho, UnitTestCore};
use andy87\knock_knock\interfaces\{RequestInterface, ResponseInterface};
use andy87\knock_knock\exception\{InvalidEndpointException, ParamNotFoundException, ParamUpdateException};
use andy87\knock_knock\exception\{operator\InvalidMethodException,
    request\InvalidHeaderException,
    request\StatusNotFoundException};

/**
 * Class KnockKnockOctopusTest
 *
 * Тесты для методов класса KnockKnockOctopus
 *
 * @package tests
 *
 * @cli vendor/bin/phpunit tests/extensions/KnockKnockOctopusTest.php --testdox
 *
 * @tag #test #Handler #octopus
 */
class KnockKnockOctopusTest extends UnitTestCore
{
    /** @var KnockKnockOctopus $KnockKnockOctopus */
    public static KnockKnockOctopus $KnockKnockOctopus;


    /**
     * Вспомогательный метод для получения объекта KnockKnockOctopus
     *
     * @return KnockKnockOctopus
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * @tag #extension #octopus #get
     */
    private function getKnockKnockOctopus(): KnockKnockOctopus
    {
        $KnockKnockOctopus = new KnockKnockOctopus(PostmanEcho::HOST);

        $KnockKnockOctopus->disableSSL();

        return $KnockKnockOctopus;
    }

    /**
     * Проверка запуска метод init через событие EVENT_AFTER_INIT
     *
     *      Ожидается, что значения свойства curlOptions объекта commonRequest будут равны `KnockKnockOctopus::HEADERS`
     *
     * Source : @return void
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/extensions/KnockKnockOctopusTest.php --testdox --filter testInit
     *
     * @tag #test #Handler #octopus #init
     * @see KnockKnockOctopus::init()
     *
     */
    public function testInit(): void
    {
        $KnockKnockOctopus = $this->getKnockKnockOctopus();

        $this->assertArrayHasKey(
            CURLOPT_HEADER,
            $KnockKnockOctopus->commonRequest->curlOptions,
            "Ожидается, что свойство `CURLOPT_HEADER` объекта `commonRequest->curlOptions` будет существовать"
        );
        $this->assertArrayHasKey(
            CURLOPT_RETURNTRANSFER,
            $KnockKnockOctopus->commonRequest->curlOptions,
            "Ожидается, что свойство `CURLOPT_RETURNTRANSFER` объекта `commonRequest->curlOptions` будет существовать"
        );
        $this->assertArrayHasKey(
            CURLOPT_SSL_VERIFYPEER,
            $KnockKnockOctopus->commonRequest->curlOptions,
            "Ожидается, что свойство `CURLOPT_SSL_VERIFYPEER` объекта `commonRequest->curlOptions` будет существовать"
        );
        $this->assertArrayHasKey(
            CURLOPT_SSL_VERIFYHOST,
            $KnockKnockOctopus->commonRequest->curlOptions,
            "Ожидается, что свойство `CURLOPT_SSL_VERIFYHOST` объекта `commonRequest->curlOptions` будет существовать"
        );
    }

    /**
     * Проверка отправки GET запроса
     *
     *      Ожидается, что метод вернет объект Response с актуальными данными
     *
     * Source : @return void
     *
     * @throws StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException|ParamNotFoundException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/extensions/KnockKnockOctopusTest.php --testdox --filter testGet
     *
     * @tag #test #Handler #octopus #get
     * @see KnockKnockOctopus::get()
     *
     */
    public function testGet(): void
    {
        $KnockKnockOctopus = $this->getKnockKnockOctopus();

        $response = $KnockKnockOctopus->get(PostmanEcho::ENDPOINT_GET, [
            RequestInterface::SETUP_DATA => PostmanEcho::DATA
        ]);

        $content = json_decode($response->content, true);

        $this->assertArrayHasKey('args', $content);
        $this->assertArrayHasKey('headers', $content);
        $this->assertArrayHasKey('url', $content);

        $this->assertEquals($response->request->url, $content['url']);

        $this->assertEquals(PostmanEcho::DATA, $content['args']['data']);
    }

    /**
     * Проверка отправки POST запроса
     *
     *      Ожидается, что метод вернет объект Response с актуальными данными
     *
     * Source : @return void
     *
     * @throws StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException|ParamNotFoundException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/extensions/KnockKnockOctopusTest.php --testdox --filter testPost
     *
     * @tag #test #Handler #octopus #post
     * @see KnockKnockOctopus::post()
     *
     */
    public function testPost(): void
    {
        $KnockKnockOctopus = $this->getKnockKnockOctopus();

        $KnockKnockOctopus->commonRequest->setContentType(ContentType::FORM);

        $response = $KnockKnockOctopus->post(PostmanEcho::ENDPOINT_POST, PostmanEcho::DATA);

        $content = json_decode($response->content, true);

        //throw new Exception( print_r($response) );

        /** @see PostmanEcho::ENDPOINT_POST */
        $this->assertArrayHasKey('args', $content);
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('files', $content);
        $this->assertArrayHasKey('form', $content);
        $this->assertArrayHasKey('headers', $content);
        $this->assertArrayHasKey('json', $content);
        $this->assertArrayHasKey('url', $content);

        $this->assertEquals($response->request->url, $content['url']);

        $this->assertEquals(PostmanEcho::DATA, $content['json']);
    }


    /**
     * Проверка возврата фейквого ответа
     *
     *      Ожидается, что метод вернет объект Response с актуальными фейковыми данными
     *      переданными в параметре $fakeResponse
     *
     * Source : @return void
     *
     * @throws StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException|ParamNotFoundException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/extensions/KnockKnockOctopusTest.php --testdox --filter testFakeResponse
     *
     * @tag #test #Handler #octopus #get #fakeResponse
     * @see KnockKnockOctopus::fakeResponse()
     *
     */
    public function testFakeResponse(): void
    {
        $KnockKnockOctopus = $this->getKnockKnockOctopus();

        $fakeResponse = [
            ResponseInterface::CONTENT => 'fake content',
            ResponseInterface::HTTP_CODE => ResponseInterface::OK,
        ];

        $response = $KnockKnockOctopus->fakeResponse($fakeResponse);

        $this->assertEquals($fakeResponse['content'], $response->content);
        $this->assertEquals($fakeResponse['httpCode'], $response->httpCode);
    }
}