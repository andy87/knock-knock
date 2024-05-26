<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Тесты для методов класса KnockKnock
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 0.99b
 */

declare(strict_types=1);

namespace tests;

use andy87\knock_knock\interfaces\KnockKnockInterface;
use andy87\knock_knock\interfaces\KnockRequestInterface;
use andy87\knock_knock\lib\LibKnockContentType;
use Exception;
use tests\core\PostmanEcho;
use tests\core\UnitTestCore;
use andy87\knock_knock\core\KnockKnock;
use andy87\knock_knock\core\KnockRequest;
use andy87\knock_knock\core\KnockResponse;
use andy87\knock_knock\interfaces\KnockResponseInterface;
use andy87\knock_knock\lib\LibKnockMethod;
use tests\examples\KnockKnockExample;

/**
 * Class KnockKnockTest
 *
 *  Тесты для методов класса KnockKnock
 *
 * @package tests
 */
class KnockKnockTest extends UnitTestCore
{
    /** @var KnockKnock $knockKnock */
    private KnockKnock $knockKnock;



    /**
     * Установки
     *
     * @return void
     *
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->knockKnock = $this->getKnockKnock();
    }

    /**
     * Тест для метода `__construct`
     *
     * Source: @see KnockKnock::__construct()
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #constructor
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(KnockKnock::class, $this->knockKnock );
    }

    /**
     * Тест для метода `getInstance`
     *
     * Source: @see KnockKnock::getInstance()
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #get #instance
     */
    public function testGetInstance(): void
    {
        $knockKnock = KnockKnock::getInstance(self::HOST );

            $this->assertInstanceOf(KnockKnock::class, $knockKnock );
    }

    /**
     * Тест для метода `validateHostName`
     *
     * Source: @see KnockKnock::validateHostName()
     *
     * @dataProvider hostNameProvider
     *
     * @param string $host
     * @param bool $expected
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #validate #hostName
     */
    public function testValidateHostName( string $host, bool $expected ): void
    {
        $result = KnockKnock::validateHostName($host);

        $this->assertEquals( $expected, $result );
    }

    /**
     * Данные для теста `testValidateHostName`
     *
     * Data: @see KnockKnockTest::testValidateHostName()
     *
     * @return array[]
     *
     * @tag #test #knockKnock #provider #validate #hostName
     */
    public static function hostNameProvider(): array
    {
        return [
            ['example.com', true], // 0 +
            ['subdomain.example.com', true], // 1 +
            ['-example.com', false], // 2 неверный
            ['example-.com', false], // 3 неверный
            ['exa_mple.com', false], // 4 неверный
            ['example..com', false], // 5 неверный
            ['example.c', false], // 6 неверный, tld слишком короткий
            ['ex' . str_repeat('a', 250) . 'mple.com', false], // 7 неверный, слишком длинный
            ['valid-domain.com', true], // 8
            ['sub.valid-domain.com', true], // 9
            ['sub.sub.valid-domain.com', true], // 10
            ['invalid_domain.com', false], // 11 неверный
            ['invalid--domain.com', false], // 12 неверный
            ['invalid.domain-.com', false], // 13 неверный
            ['.invalid.domain.com', false], // 14 неверный
            ['valid-domain.co.uk', true], // 15 +
        ];
    }

    /**
     * Тест для метода `setupResponse`
     *
     * Source: @see KnockKnock::init()
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #event #init
     */
    public function testEventInit()
    {
        $this->getKnockKnockExample();

        $this->assertEquals(KnockKnockExample::INIT_DONE, KnockKnockExample::$initResult );
    }

    /**
     * Тест для метода `__get`
     *
     * Source: @see KnockKnock::__get()
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #get
     */
    public function testGetter()
    {
        $knockKnock = $this->knockKnock;

            $this->assertEquals(self::HOST, $knockKnock->host);
            $this->assertInstanceOf(KnockRequest::class, $knockKnock->commonKnockRequest);

        $knockRequest = $this->getKnockRequest();

            $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $knockKnock->setupRequest( $knockRequest );

            $this->assertInstanceOf(KnockRequest::class, $knockKnock->realKnockRequest );

            $this->assertCount(6, $knockKnock->events);
    }

    /**
     * Тест для метода `getParams`
     *
     * Source: @see KnockKnock::getParams()
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #get #params
     */
    public function testGetParams()
    {
        $protocol = 'wss';
        $host = 'getParams.Host';
        $apiUrl = "$protocol://$host";

        $knockRequestCommon = [
            KnockRequestInterface::SETUP_METHOD => LibKnockMethod::PUT,
        ];

        $knockKnock = $this->getKnockKnock( $apiUrl, $knockRequestCommon );

        $events = [
            KnockKnockInterface::EVENT_AFTER_INIT => function() {
                return KnockKnockInterface::EVENT_AFTER_INIT;
            },
        ];

        $knockKnock->setupEventHandlers($events);

        $knockRequestRealParams = [
            KnockRequestInterface::SETUP_METHOD => LibKnockMethod::POST,
            KnockRequestInterface::SETUP_CURL_INFO => [ 'info' => 'real' ]
        ];

        $knockRequestReal = $knockKnock->constructRequest(LibKnockMethod::POST, '/endpointReal', $knockRequestRealParams );

        $knockKnock->setupRequest( $knockRequestReal );

        $params = $knockKnock->getParams();

        $this->assertArrayHasKey('host', $params );
        $this->assertEquals( $host, $params['host'] );
        $this->assertEquals( $protocol, $knockRequestReal->protocol );

        $this->assertArrayHasKey('commonKnockRequest', $params );
        $this->assertInstanceOf(KnockRequest::class, $params['commonKnockRequest'] );

        $this->assertArrayHasKey('realKnockRequest', $params );
        $this->assertInstanceOf(KnockRequest::class, $params['realKnockRequest'] );

        $this->assertArrayHasKey('events', $params );
        $this->assertSameSize($events, $params['events']);
    }

    /**
     * Тест для методов `construct...`
     *
     * Source: @see KnockKnock::constructRequest()
     * Source: @see KnockKnock::constructResponse()
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #construct
     */
    public function testConstruct():void
    {
        $knockKnock = $this->knockKnock;

            $this->assertEquals(self::HOST, $knockKnock->host);

        $knockRequest = $knockKnock->constructRequest(
            LibKnockMethod::GET,
            self::ENDPOINT
        );

            $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $knockResponse = $knockKnock->constructResponse([
            KnockResponseInterface::CONTENT => 'content',
            KnockResponseInterface::HTTP_CODE => 200,
        ], $knockRequest );

            $this->assertInstanceOf(KnockResponse::class, $knockResponse );
    }

    /**
     * Тест для метода `setupRequest`
     *
     * Source: @see KnockKnock::setupRequest()
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #setup #request
     */
    public function testSetupRequest()
    {
        $knockKnock = $this->knockKnock;

        $knockKnock->setupRequest( $this->getKnockRequest() );

        $this->assertInstanceOf( KnockRequest::class, $knockKnock->realKnockRequest );
    }

    /**
     * Тест для метода `setupEventHandlers`
     *
     * Source: @see KnockKnock::setupEventHandlers()
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #setup #eventHandlers
     */
    public function testSetupEventHandlers()
    {
        $knockKnock = $this->getKnockKnock();

        $knockKnock->setupEventHandlers([]);

        $this->assertIsArray( $knockKnock->events );
        $this->assertCount(0, $knockKnock->events);

        $eventList = [
            KnockKnockInterface::EVENT_AFTER_INIT => function() {
                return KnockKnockInterface::EVENT_AFTER_INIT;
            },
            KnockKnockInterface::EVENT_CONSTRUCT_REQUEST => function() {
                return KnockKnockInterface::EVENT_CONSTRUCT_REQUEST;
            },
            KnockKnockInterface::EVENT_CONSTRUCT_RESPONSE => function() {
                return KnockKnockInterface::EVENT_CONSTRUCT_RESPONSE;
            },
        ];

        $callBackList = $knockKnock->setupEventHandlers($eventList);

        $this->assertSameSize( $callBackList, $eventList );

        $this->assertIsArray( $knockKnock->events );
        $this->assertSameSize( $eventList, $knockKnock->events );
    }

    /**
     * Тест для метода `on`
     *
     * Source: @see KnockKnock::on()
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #event #on
     */
    public function testEventsOn()
    {
        $knockKnockExample = $this->getKnockKnockExample();

        $eventsStart = (count($knockKnockExample->events));

        $knockKnockExample->on(KnockKnockExample::MY_EVENT_1, function() {
            return KnockKnockExample::MY_EVENT_1;
        });

        $this->assertCount(($eventsStart + 1), $knockKnockExample->events);
        $this->assertArrayHasKey(KnockKnockExample::MY_EVENT_1, $knockKnockExample->events );
    }

    /**
     * Тест для метода `event`
     *
     * Source: @see KnockKnock::event()
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #event #call
     */
    public function testEventCall()
    {
        $knockKnockExample = $this->getKnockKnockExample();

        $knockKnockExample->on(KnockKnockExample::MY_EVENT_1, function() {
            return KnockKnockExample::MY_EVENT_1;
        });

        $this->assertEquals(
            KnockKnockExample::MY_EVENT_1,
            $knockKnockExample->event(KnockKnockExample::MY_EVENT_1)
        );
    }

    /**
     * Тест для метода `off`
     *
     * Source: @see KnockKnock::off()
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #event #off
     */
    public function testEventOff()
    {
        $knockKnockExample = $this->getKnockKnockExample();

        $knockKnockExample->on(KnockKnockExample::MY_EVENT_1, function() {
            return KnockKnockExample::MY_EVENT_1;
        });

        $this->assertEquals(KnockKnockExample::MY_EVENT_1, $knockKnockExample->event(KnockKnockExample::MY_EVENT_1) );

        $knockKnockExample->off(KnockKnockExample::MY_EVENT_1);

        $this->assertEquals(null, $knockKnockExample->event(KnockKnockExample::MY_EVENT_1));
    }

    /**
     * Тест для метода `changeEvent`
     *
     * Source: @see KnockKnock::changeEvent()
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #event #change
     */
    public function testEventChange()
    {
        $knockKnockExample = $this->getKnockKnockExample();

        $knockKnockExample->on(KnockKnockExample::MY_EVENT_1, function() {
            return KnockKnockExample::MY_EVENT_1;
        });

        $this->assertEquals(
            KnockKnockExample::MY_EVENT_1,
            $knockKnockExample->event(KnockKnockExample::MY_EVENT_1)
        );

        $this->expectException(Exception::class);
        $knockKnockExample->on(KnockKnockExample::MY_EVENT_1, function() {
            return 67890;
        });

        $this->assertEquals(
            KnockKnockExample::MY_EVENT_1,
            $knockKnockExample->event(KnockKnockExample::MY_EVENT_1)
        );

        $knockKnockExample->changeEvent(KnockKnockExample::MY_EVENT_1, function() {
            return KnockKnockExample::MY_EVENT_2;
        });

        $this->assertEquals(
            KnockKnockExample::MY_EVENT_2,
            $knockKnockExample->event(KnockKnockExample::MY_EVENT_1)
        );
    }

    /**
     * Тест для метода `disableSSL`
     *
     * Source: @see KnockKnock::disableSSL()
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #ssl #disable
     */
    public function testDisableSsl()
    {
        $knockKnock = $this->getKnockKnock();

        $knockKnock->disableSSL();

        $knockRequest = $knockKnock->constructRequest(
            LibKnockMethod::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $knockRequest->curlOptions[CURLOPT_SSL_VERIFYPEER] === false );
        $this->assertTrue( $knockRequest->curlOptions[CURLOPT_SSL_VERIFYHOST] === 0 );
    }

    /**
     * Тест для метода `enableSSL`
     *
     * Source: @see KnockKnock::enableSSL()
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #ssl #enable
     */
    public function testEnableSsl()
    {
        $knockKnock = $this->getKnockKnock();

        $knockKnock->disableSSL();
        $knockKnock->enableSSL();

        $knockRequest = $knockKnock->constructRequest(
            LibKnockMethod::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $knockRequest->curlOptions[CURLOPT_SSL_VERIFYPEER] === true );
        $this->assertTrue( $knockRequest->curlOptions[CURLOPT_SSL_VERIFYHOST] === 2 );
    }

    /**
     * Тест для метода `enableRedirect`
     *
     * Source: @see KnockKnock::enableRedirect()
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #redirect #enable
     */
    public function testEnableRedirect()
    {
        $knockKnock = $this->getKnockKnock();

        $knockKnock->enableRedirect();

        $knockRequest = $knockKnock->constructRequest(
            LibKnockMethod::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $knockRequest->curlOptions[CURLOPT_FOLLOWLOCATION] === true );
    }

    /**
     * Тест для метода `UseCookie`
     *
     * Source: @see KnockKnock::UseCookie()
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #cookie
     */
    public function testUseCookie()
    {
        $knockKnock = $this->getKnockKnock();

        $cookie = 'cookie=cookie';
        $jar = 'jar.txt';
        $file = 'file.txt';

        $knockKnock->useCookie( $cookie, $jar );

        $knockRequest = $knockKnock->constructRequest(
            LibKnockMethod::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $knockRequest->curlOptions[CURLOPT_COOKIE] === $cookie );
        $this->assertTrue( $knockRequest->curlOptions[CURLOPT_COOKIEJAR] === $jar );
        $this->assertTrue( $knockRequest->curlOptions[CURLOPT_COOKIEFILE] === $jar );

        $knockKnock->useCookie( $cookie, $jar, $file );

        $knockRequest = $knockKnock->constructRequest(
            LibKnockMethod::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $knockRequest->curlOptions[CURLOPT_COOKIE] === $cookie );
        $this->assertTrue( $knockRequest->curlOptions[CURLOPT_COOKIEJAR] === $jar );
        $this->assertTrue( $knockRequest->curlOptions[CURLOPT_COOKIEFILE] === $file );
    }

    /**
     * Тест для метода `send` и дальнейших методов вызывающихся последовательно
     *
     * Source: @see KnockKnock::send()
     * Source: @see KnockKnock::SendRequest()
     * Source: @see KnockKnock::getResponseOnSendCurlRequest()
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #send
     */
    public function testSendRequest()
    {
        $knockKnock = PostmanEcho::getKnockKnockInstance();

        $knockRequest = PostmanEcho::constructKnockRequestMethodGet([
            KnockRequestInterface::SETUP_DATA => PostmanEcho::DATA
        ]);

        $knockResponse = $knockKnock->setupRequest( $knockRequest )->send();

        $response = json_decode( $knockResponse->content, true );

        $this->assertArrayHasKey('args', $response);
        $this->assertArrayHasKey('headers', $response);
        $this->assertArrayHasKey('url', $response);

        $this->assertEquals( PostmanEcho::DATA, $response['args'] );

        $this->assertEquals( $knockResponse->request->url, $response['url'] );
    }

    /**
     * Тест для метода `send` и дальнейших методов вызывающихся последовательно
     *
     * Source: @see KnockKnock::send()
     * Source: @see KnockKnock::SendRequest()
     * Source: @see KnockKnock::constructResponse()
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #send #fakeResponse
     */
    public function testSendRequestWithFakeResponse(): void
    {
        $knockKnock = PostmanEcho::getKnockKnockInstance();
        $this->assertInstanceOf(KnockKnock::class, $knockKnock );

        $knockRequest = PostmanEcho::constructKnockRequestMethodGet();
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $fakeResponse = [
            KnockResponseInterface::CONTENT => json_encode(PostmanEcho::DATA),
            KnockResponseInterface::HTTP_CODE => 777,
        ];

        $knockResponse = $knockKnock->setupRequest( $knockRequest )->send( $fakeResponse );
        $this->assertInstanceOf(KnockResponse::class, $knockResponse );

        $this->assertEquals($fakeResponse[ KnockResponseInterface::CONTENT ], $knockResponse->content );
        $this->assertEquals($fakeResponse[ KnockResponseInterface::HTTP_CODE ], $knockResponse->httpCode );
    }

    /**
     * Тест для метода `send` при отправке запросов методом POST
     *
     * Source: @see KnockKnock::send()
     * Source: @see KnockKnock::SendRequest()
     * Source: @see KnockKnock::constructResponse()
     *
     * @return void
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #send #post
     */
    public function testSendRequestOnMethodPost(): void
    {
        $knockKnock = PostmanEcho::getKnockKnockInstance();
        $this->assertInstanceOf(KnockKnock::class, $knockKnock );

        $data = PostmanEcho::DATA;

        $knockRequest = PostmanEcho::constructKnockRequestMethodPost([
            KnockRequestInterface::SETUP_DATA => $data,
            KnockRequestInterface::SETUP_CONTENT_TYPE => LibKnockContentType::JSON
        ]);
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $knockResponse = $knockKnock->setupRequest( $knockRequest )->send();

        $this->assertInstanceOf(KnockResponse::class, $knockResponse );

        $response = json_decode( $knockResponse->content, true );

        /** @see PostmanEcho::ENDPOINT_POST */
        $this->assertArrayHasKey('args', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('files', $response);
        $this->assertArrayHasKey('form', $response);
        $this->assertArrayHasKey('headers', $response);
        $this->assertArrayHasKey('json', $response);
        $this->assertArrayHasKey('url', $response);

        $this->assertEquals( $knockResponse->request->url, $response['url'] );
        $this->assertEquals( LibKnockMethod::POST, $knockResponse->request->method );
    }



    /**
     * Тест для метода `updateRequestParams`
     *
     * Source: @see KnockKnock::updateRequestParams()
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #update #requestParams
     */
    public function testUpdateRequestParams()
    {
        $knockKnock = $this->getKnockKnock(self::HOST );
        $this->assertInstanceOf(KnockKnock::class, $knockKnock );

        $oldData = [
            KnockRequestInterface::SETUP_PROTOCOL => KnockRequest::PROTOCOL_HTTP,
            KnockRequestInterface::SETUP_HOST => self::HOST,
            KnockRequestInterface::SETUP_METHOD => LibKnockMethod::GET,
            KnockRequestInterface::SETUP_HEADERS => [
                'Content-Type' => LibKnockContentType::JSON,
            ],
            KnockRequestInterface::SETUP_DATA => ['state' => 'old'],
            KnockRequestInterface::SETUP_CURL_OPTIONS => [
                CURLOPT_HEADER => false,
            ],
            KnockRequestInterface::SETUP_CURL_INFO => [
                'info' => 'old',
            ],
            KnockRequestInterface::SETUP_CONTENT_TYPE => LibKnockContentType::JSON,
        ];


        $knockRequest = $knockKnock->constructRequest( LibKnockMethod::GET, '/tyda', $oldData );
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );

        $newData = [
            KnockRequestInterface::SETUP_PROTOCOL => KnockRequest::PROTOCOL_HTTPS,
            KnockRequestInterface::SETUP_HOST => self::HOST . '/new',
            KnockRequestInterface::SETUP_METHOD => LibKnockMethod::POST,
            KnockRequestInterface::SETUP_HEADERS => [
                'Content-Type' => LibKnockContentType::FORM,
            ],
            KnockRequestInterface::SETUP_DATA => ['state' => 'old'],
            KnockRequestInterface::SETUP_CURL_OPTIONS => [
                CURLOPT_HEADER => true,
            ],
            KnockRequestInterface::SETUP_CURL_INFO => [
                'info' => 'new',
            ],
            KnockRequestInterface::SETUP_CONTENT_TYPE => LibKnockContentType::XML,
        ];

        $knockRequest = $knockKnock->setupRequest( $knockRequest, $newData )->realKnockRequest;

        $this->assertEquals( $newData[KnockRequestInterface::SETUP_PROTOCOL], $knockRequest->protocol );
        $this->assertEquals( $newData[KnockRequestInterface::SETUP_HOST], $knockRequest->host );
        $this->assertEquals( $newData[KnockRequestInterface::SETUP_METHOD], $knockRequest->method );
        $this->assertEquals( $newData[KnockRequestInterface::SETUP_HEADERS]['Content-Type'], $knockRequest->headers['Content-Type'] );
        $this->assertEquals( $newData[KnockRequestInterface::SETUP_DATA]['state'], $knockRequest->data['state'] );
        $this->assertEquals( $newData[KnockRequestInterface::SETUP_CURL_OPTIONS][CURLOPT_HEADER], $knockRequest->curlOptions[CURLOPT_HEADER] );
        $this->assertEquals( $newData[KnockRequestInterface::SETUP_CURL_INFO]['info'], $knockRequest->curlInfo['info'] );
        $this->assertEquals( $newData[KnockRequestInterface::SETUP_CONTENT_TYPE], $knockRequest->contentType );
    }

    /**
     * Тест для метода `updatePostFields`
     *
     * Source: @see KnockKnock::updatePostFields()
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #update #postFields
     */
    public function testUpdatePostFields()
    {
        $knockKnock = $this->getKnockKnock(self::HOST, []);
        $this->assertInstanceOf(KnockKnock::class, $knockKnock );

        $postFields = [
            'a' => 1,
            'b' => 2,
        ];

        $knockRequest = $knockKnock->constructRequest(LibKnockMethod::POST,self::ENDPOINT, [
            KnockRequestInterface::SETUP_DATA => $postFields,
            KnockRequestInterface::SETUP_CONTENT_TYPE => LibKnockContentType::JSON
        ]);

        $knockResponse = $knockKnock->setupRequest( $knockRequest )->send([
            KnockResponseInterface::CONTENT => self::CONTENT,
            KnockResponseInterface::HTTP_CODE => self::HTTP_CODE_OK,
        ]);

        $responsePostFields = $knockResponse->request->curlOptions[CURLOPT_POSTFIELDS];

        $this->assertEquals( json_encode($postFields), $responsePostFields );

        $knockRequest = $knockKnock->constructRequest(LibKnockMethod::PUT,self::ENDPOINT, [
            KnockRequestInterface::SETUP_DATA => $postFields,
            KnockRequestInterface::SETUP_CONTENT_TYPE => LibKnockContentType::FORM
        ]);

        $knockResponse = $knockKnock->setupRequest( $knockRequest )->send([
            KnockResponseInterface::CONTENT => self::CONTENT,
            KnockResponseInterface::HTTP_CODE => self::HTTP_CODE_OK,
        ]);

        $responsePostFields = $knockResponse->request->curlOptions[CURLOPT_POSTFIELDS];

        $this->assertEquals( http_build_query($postFields), $responsePostFields );
    }

    /**
     * Тест для метода `updateMethod`
     *
     * Source: @see KnockKnock::updateMethod()
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #update #method
     */
    public function testUpdateMethod()
    {
        $knockKnock = $this->getKnockKnock(self::HOST, []);

        $methodList = [
            LibKnockMethod::GET,
            LibKnockMethod::POST,
            LibKnockMethod::PUT,
            LibKnockMethod::PATCH,
            LibKnockMethod::DELETE,
        ];

        $fakeResponse = [
            KnockResponseInterface::CONTENT => self::CONTENT,
            KnockResponseInterface::HTTP_CODE => self::HTTP_CODE_OK,
        ];

        foreach ( $methodList as $method )
        {
            $knockRequest = $knockKnock->constructRequest( $method,self::ENDPOINT );

            $knockResponse = $knockKnock->setupRequest( $knockRequest )->send( $fakeResponse );

            $this->assertEquals( $method, $knockResponse->request->method );
        }
    }




    // === Private ===

    /**
     * @return KnockKnockExample
     *
     * @throws Exception
     *
     * @tag #test #knockKnock #example
     */
    private function getKnockKnockExample(): KnockKnockExample
    {
        return new KnockKnockExample(self::HOST );
    }
}