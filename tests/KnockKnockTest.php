<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Тесты для методов класса KnockKnock
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 1.0.0
 */

declare(strict_types=1);

namespace andy87\knock_knock\tests;

use Exception;
use ReflectionClass;
use andy87\knock_knock\tests\examples\KnockKnockExample;
use andy87\knock_knock\tests\core\{ UnitTestCore, PostmanEcho };
use andy87\knock_knock\lib\{ LibKnockMethod, LibKnockContentType };
use andy87\knock_knock\core\{ KnockKnock, KnockRequest, KnockResponse };
use andy87\knock_knock\interfaces\{ KnockKnockInterface, KnockRequestInterface, KnockResponseInterface };

/**
 * Class KnockKnockTest
 *
 *  Тесты для методов класса KnockKnock
 *
 * @package tests
 *
 * @cli vendor/bin/phpunit tests/KnockKnockTest.php --testdox
 *
 * @tag #test #knockKnock
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
     *  Проверка конструктора
     *      Тест ожидает что будет создан объект/ экземпляр класса KnockKnock
     *
     * Source: @see KnockKnock::__construct()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testConstructor
     *
     * @tag #test #knockKnock #constructor
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(KnockKnock::class, $this->knockKnock );
    }

    /**
     * Проверка работы Singleton
     *      Тест ожидает что метод вернет объект/ экземпляр класса KnockKnock
     *
     * Source: @see KnockKnock::getInstance()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testGetInstance
     *
     * @tag #test #knockKnock #get #instance
     */
    public function testGetInstance(): void
    {
        $knockKnock = KnockKnock::getInstance(self::HOST );

        $this->assertInstanceOf(KnockKnock::class, $knockKnock );

        $knockKnock->disableSSL();

        // переназначаем переменную взяв ее из статического метода
        // -> статический метод должен вернуть тот же объект
        $knockKnock = KnockKnock::getInstance();

        $this->assertInstanceOf(KnockKnock::class, $knockKnock );

        $knockRequest = $knockKnock->commonKnockRequest;

        $this->assertArrayHasKey(CURLOPT_SSL_VERIFYPEER, $knockRequest->curlOptions );
        $this->assertArrayHasKey(CURLOPT_SSL_VERIFYHOST, $knockRequest->curlOptions );
    }

    /**
     *  Проверка работы валидации имени хоста
     *      Ожидается, что метод вернет true если переданное имя хоста валидно
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
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testValidateHostName
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
     * Проверка работы события INIT вызывающегося после __construct
     *      Ожидается что `KnockKnock` выполнит метод init().
     *
     * Source: @see KnockKnock::init()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testEventInit
     *
     * @tag #test #knockKnock #event #init
     */
    public function testEventInit()
    {
        $this->getKnockKnockExample();

        $this->assertEquals(KnockKnockExample::INIT_DONE, KnockKnockExample::$initResult );
    }

    /**
     * Проверка работы геттеров
     *      Ожидается, что метод вернет значение свойств
     *
     * Source: @see KnockKnock::__get()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testGetter
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
        $this->assertCount(6, $knockKnock->eventHandlers);

        $knockKnock->addLog('test');
        $this->assertIsArray( $knockKnock->logs );
        $this->assertCount(1, $knockKnock->logs );
    }

    /**
     * Проверка метода возвращающего все геттеры
     *      Ожидается, что метод вернет массив с актуальными значениями свойств
     *
     * Source: @see KnockKnock::getParams()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testGetParams
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
     * Проверка методов отвечающихз за создание объектов `KnockRequest` и `KnockResponse`
     *      Ожидается, что методы `construct` вернут объекты классов `KnockRequest` и `KnockResponse`
     *      Ожидается, что сработают эвенты `EVENT_CONSTRUCT_REQUEST` и `EVENT_CONSTRUCT_RESPONSE` и в лог запишутся данные
     *
     * Source: @see KnockKnock::constructRequest()
     * Source: @see KnockKnock::constructResponse()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testConstruct
     *
     * @tag #test #knockKnock #construct
     */
    public function testConstruct():void
    {
        $knockKnock = $this->knockKnock;

            $this->assertEquals(self::HOST, $knockKnock->host);

        $knockKnock->on(KnockKnockInterface::EVENT_CONSTRUCT_REQUEST, function(KnockKnock $knockKnock) {
            $knockKnock->addLog(KnockKnockInterface::EVENT_CONSTRUCT_REQUEST);
        });
        $knockKnock->on(KnockKnockInterface::EVENT_CONSTRUCT_RESPONSE, function(KnockKnock $knockKnock) {
            $knockKnock->addLog(KnockKnockInterface::EVENT_CONSTRUCT_RESPONSE);
        });

        $knockRequest = $knockKnock->constructRequest( LibKnockMethod::GET, self::ENDPOINT );
        $this->assertInstanceOf(KnockRequest::class, $knockRequest );
        $this->assertTrue(in_array(KnockKnockInterface::EVENT_CONSTRUCT_REQUEST, $knockKnock->logs));

        $knockResponse = $knockKnock->constructResponse([
            KnockResponseInterface::CONTENT => 'content',
            KnockResponseInterface::HTTP_CODE => 200,
        ], $knockRequest );

        $this->assertInstanceOf(KnockResponse::class, $knockResponse );

        $this->assertTrue(in_array(KnockKnockInterface::EVENT_CONSTRUCT_RESPONSE, $knockKnock->logs));
    }

    /**
     * Проверка что задаётся свойство `realKnockRequest`
     *      Ожидается что метод задаст свойство `realKnockRequest` объектом класса `KnockRequest`
     *
     * Source: @see KnockKnock::setupRequest()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testSetupRequest
     *
     * @tag #test #knockKnock #setup #request
     */
    public function testSetupRequest()
    {
        $knockKnock = $this->knockKnock;

        $knockRequest = $this->getKnockRequest(null, [
            KnockRequestInterface::SETUP_DATA => self::DATA_A
        ]);

        $knockKnock->setupRequest( $knockRequest );
        $this->assertInstanceOf( KnockRequest::class, $knockKnock->realKnockRequest );
        $this->assertEquals( json_encode(self::DATA_A), json_encode($knockKnock->realKnockRequest->data) );

        // Проверка с перезаписью и добавлением свойств
        $knockKnock->setupRequest( $knockRequest, [
            KnockRequestInterface::SETUP_DATA => self::DATA_B,
            KnockRequestInterface::SETUP_CONTENT_TYPE => LibKnockContentType::RAR
        ]);

        $this->assertEquals( json_encode(self::DATA_B), json_encode($knockKnock->realKnockRequest->data) );
        $this->assertEquals( LibKnockContentType::RAR, $knockKnock->realKnockRequest->contentType );
    }

    /**
     * Ожидается что метод задаст свойство `events` массивом с колбеками
     *
     * Source: @see KnockKnock::setupEventHandlers()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testSetupEventHandlers
     *
     * @tag #test #knockKnock #setup #eventHandlers
     */
    public function testSetupEventHandlers()
    {
        $knockKnock = $this->getKnockKnock();

        $knockKnock->setupEventHandlers([]);

        $this->assertIsArray( $knockKnock->eventHandlers );
        $this->assertCount(0, $knockKnock->eventHandlers);

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

        $this->assertIsArray( $knockKnock->eventHandlers );
        $this->assertSameSize( $eventList, $knockKnock->eventHandlers );
    }

    /**
     * Ожидается что метод добавит callBack в массив `events`
     *
     * Source: @see KnockKnock::on()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testEventsOn
     *
     * @tag #test #knockKnock #event #on
     */
    public function testEventsOn()
    {
        $knockKnockExample = $this->getKnockKnockExample();

        $knockKnockExample->on(KnockKnockExample::MY_EVENT, function() {});

        $this->assertArrayHasKey(KnockKnockExample::MY_EVENT, $knockKnockExample->eventHandlers );
    }

    /**
     * Ожидается что метод event приватный
     *
     * Source: @see KnockKnock::event()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testEventCall
     *
     * @tag #test #knockKnock #event #call
     */
    public function testEventCall()
    {
        $reflection = new ReflectionClass(KnockKnock::class);
        $method = $reflection->getMethod('event');

        // проверка на приватность метода
        $this->assertTrue($method->isPrivate());

        $knockKnockExample = $this->getKnockKnockExample();

        $knockKnockExample->on(KnockKnockExample::MY_EVENT, function(KnockKnock $knockKnock) {
            $knockKnock->addLog(KnockKnockExample::MY_EVENT);
        });

        /** Проверка на вызов `event()` через `callEventHandler` */
        $knockKnockExample->callEventHandler(KnockKnockExample::MY_EVENT);

        $this->assertCount(1, $knockKnockExample->logs, "Ожидается что после вызова `callEventHandler` в лог запишутся данные " );
        $this->assertEquals(KnockKnockExample::MY_EVENT, $knockKnockExample->logs[0], "Ожидается что значение в `logs[0]` будет равно значению `KnockKnockExample::MY_EVENT` " );
    }

    /**
     * Тест ожидает что после вызова метода `off` callBack не будет вызван
     *
     * Source: @see KnockKnock::off()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testEventOff
     *
     * @tag #test #knockKnock #event #off
     */
    public function testEventOff()
    {
        $knockKnockExample = $this->getKnockKnockExample();

        $knockKnockExample->on(KnockKnockExample::MY_EVENT,
            function( KnockKnock $knockKnock ) {
                $knockKnock->addLog(KnockKnockExample::MY_EVENT );
            }
        );

        $knockKnockExample->callEventHandler(KnockKnockExample::MY_EVENT );

        $this->assertCount(1, $knockKnockExample->logs );
        $this->assertEquals(KnockKnockExample::MY_EVENT, $knockKnockExample->logs[0] );

        $knockKnockExample->off(KnockKnockExample::MY_EVENT );

        $knockKnockExample->callEventHandler(KnockKnockExample::MY_EVENT );

        $this->assertCount(1, $knockKnockExample->logs );
    }

    /**
     * Тест ожидает что после вызова метода `changeEvent` callBack будет изменен
     *
     * Source: @see KnockKnock::changeEvent()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testEventChange
     *
     * @tag #test #knockKnock #event #change
     */
    public function testEventChange()
    {
        $knockKnockExample = $this->getKnockKnockExample();

        $knockKnockExample->on(KnockKnockExample::MY_EVENT,
            function( KnockKnock $knockKnock ) {
                $knockKnock->addLog(KnockKnockExample::MY_EVENT );
            }
        );

        $knockKnockExample->callEventHandler(KnockKnockExample::MY_EVENT );

        $this->assertCount(1, $knockKnockExample->logs );
        $this->assertEquals(KnockKnockExample::MY_EVENT, $knockKnockExample->logs[0] );


        $this->expectException(Exception::class);

        $knockKnockExample->on(KnockKnockExample::MY_EVENT,
            function( KnockKnock $knockKnock ) {
            $knockKnock->addLog(KnockKnockExample::MY_EVENT );
            $knockKnock->addLog(KnockKnockExample::MY_EVENT );
            $knockKnock->addLog(KnockKnockExample::MY_EVENT );
        });

        $knockKnockExample->callEventHandler(KnockKnockExample::MY_EVENT );

        $this->assertCount(2, $knockKnockExample->logs );
        $this->assertEquals(KnockKnockExample::MY_EVENT, $knockKnockExample->logs[1] );

        $knockKnockExample->changeEvent(KnockKnockExample::MY_EVENT,
            function( KnockKnock $knockKnock ) {
                $knockKnock->addLog(KnockKnockExample::MY_EVENT . '3' );
                $knockKnock->addLog(KnockKnockExample::MY_EVENT . '4' );
            });

        $knockKnockExample->callEventHandler(KnockKnockExample::MY_EVENT );

        $this->assertCount(4, $knockKnockExample->logs );
        $this->assertEquals(KnockKnockExample::MY_EVENT . '3', $knockKnockExample->logs[3] );
        $this->assertEquals(KnockKnockExample::MY_EVENT . '4', $knockKnockExample->logs[4] );

    }

    /**
     * Ожидается что метод, задаст значения false и 0
     * для `CURLOPT_SSL_VERIFYPEER` и `CURLOPT_SSL_VERIFYHOST` в запросе
     *
     * Source: @see KnockKnock::disableSSL()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testDisableSsl
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
     * Ожидается что метод, задаст значения true и 2
     * для `CURLOPT_SSL_VERIFYPEER` и `CURLOPT_SSL_VERIFYHOST` в запросе
     *
     * Source: @see KnockKnock::enableSSL()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testEnableSsl
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
     * Ожидается что метод, задаст значение true для `CURLOPT_FOLLOWLOCATION` в запросе
     *
     * Source: @see KnockKnock::enableRedirect()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testEnableRedirect
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
     * Ожидается что метод, задаст значения для `CURLOPT_COOKIE`, `CURLOPT_COOKIEJAR` и `CURLOPT_COOKIEFILE` в запросе
     *
     * Source: @see KnockKnock::UseCookie()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testUseCookie
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
     * Ожидается что метод `send` вернет объект класса `KnockResponse` с заданными свойствами
     * и что в свойстве `content` будет содержимое ответа
     *
     * Source: @see KnockKnock::send()
     * Source: @see KnockKnock::SendRequest()
     * Source: @see KnockKnock::getResponseOnSendCurlRequest()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testSendRequest
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
     * Ожидается что метод `send` вернет объект класса `KnockResponse` с заданными фейковыми свойствами
     *
     * Source: @see KnockKnock::send()
     * Source: @see KnockKnock::SendRequest()
     * Source: @see KnockKnock::constructResponse()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testSendRequestWithFakeResponse
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
     * Ожидается что метод `send` вернет `KnockResponse` ответ на запрос методом `POST`
     *
     * Source: @see KnockKnock::send()
     * Source: @see KnockKnock::SendRequest()
     * Source: @see KnockKnock::constructResponse()
     *
     * @return void
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testSendRequestOnMethodPost
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
     * Ожидается что метод `updateRequestParams` обновит параметры запроса.
     * Вызов метода `updateRequestParams` с новыми параметрами произойдет внутри метода `setupRequest`
     *
     * Source: @see KnockKnock::updateRequestParams()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testUpdateRequestParams
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
     * Ожидается что метод `updatePostFields` задаст `CURLOPT_POSTFIELDS` свойство запроса
     *
     * Source: @see KnockKnock::updatePostFields()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testUpdatePostFields
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
     * Ожидается что метод `updateMethod` задаст `CURLOPT_CUSTOMREQUEST` свойство запроса
     *
     * Source: @see KnockKnock::updateMethod()
     *
     * @throws Exception
     *
     * @cli vendor/bin/phpunit tests/KnockKnockTest.php --filter testUpdateMethod
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
     * Вспомогательный метод для создания объекта класса `KnockKnockExample`
     *
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