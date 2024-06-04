<?php /**
 * @name: Handler
 * @author Andrey and_y87 Kidin
 * @description Тесты для методов класса Handler
 * @homepage: https://github.com/andy87/Handler
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.2.0
 */

declare(strict_types=1);

namespace andy87\knock_knock\tests\core;

use Exception;
use ReflectionClass;
use andy87\knock_knock\lib\{ ContentType, Method };
use andy87\knock_knock\tests\helpers\OperatorExample;
use andy87\knock_knock\core\{ Operator, Request, Response };
use andy87\knock_knock\tests\helpers\{ PostmanEcho, UnitTestCore };
use andy87\knock_knock\interfaces\{ HandlerInterface, RequestInterface, ResponseInterface };
use andy87\knock_knock\exception\{ InvalidHostException, InvalidEndpointException, ParamNotFoundException, ParamUpdateException };
use andy87\knock_knock\exception\{operator\EventUpdateException,
    operator\InvalidMethodException,
    request\InvalidHeaderException,
    request\InvalidRequestException,
    request\RequestCompleteException,
    request\StatusNotFoundException};

/**
 * Class HandlerTest
 *
 *  Тесты для методов класса Handler
 *
 * @package tests
 *
 * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox
 *
 * @tag #test #Handler
 */
class OperatorTest extends UnitTestCore
{
    /**
     * Установки
     *
     * @return void
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @tag #test #Handler #setUp
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->operator = $this->getHandler();
    }

    /**
     *  Проверка конструктора
     *      Тест ожидает что будет создан объект/ экземпляр класса Handler
     *
     * Source: @return void
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testConstructor
     *
     * @tag #test #Handler #constructor
     *@see Operator::__construct()
     *
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(Operator::class, $this->operator );
    }

    /**
     * Проверка работы Singleton
     *      Тест ожидает что метод вернет объект/ экземпляр класса Handler
     *
     * Source: @return void
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testGetInstance
     *
     * @tag #test #Handler #get #instance
     *@see Operator::getInstance()
     *
     */
    public function testGetInstance(): void
    {
        $operator = Operator::getInstance(self::HOST );

        $this->assertInstanceOf(Operator::class, $operator );

        $operator->disableSSL();

        // переназначаем переменную взяв ее из статического метода
        // -> статический метод должен вернуть тот же объект
        $operator = Operator::getInstance();

        $this->assertInstanceOf(Operator::class, $operator );

        $request = $operator->commonRequest;

        $this->assertArrayHasKey(CURLOPT_SSL_VERIFYPEER, $request->curlOptions );
        $this->assertArrayHasKey(CURLOPT_SSL_VERIFYHOST, $request->curlOptions );
    }

    /**
     *  Проверка работы валидации имени хоста
     *      Ожидается, что метод вернет true если переданное имя хоста валидно
     *
     * Source: @param string $host
     * @param bool $expected
     *
     * @return void
     *
     *
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testValidateHostName
     *
     * @tag #test #Handler #validate #hostName
     *@see Operator::validateHostName()
     *
     * @dataProvider hostNameProvider
     *
     */
    public function testValidateHostName( string $host, bool $expected ): void
    {
        $result = Operator::validateHostName($host);

        $this->assertEquals( $expected, $result );
    }

    /**
     * Данные для теста `testValidateHostName`
     *
     * Data: @return array[]
     *
     * @tag #test #Handler #provider #validate #hostName
     *@see OperatorTest::testValidateHostName()
     *
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
     *      Ожидается что `Handler` выполнит метод init().
     *
     * Source: @return void
     *
     *
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testEventInit
     *
     * @tag #test #Handler #event #init
     *@see Operator::init()
     *
     */
    public function testEventInit()
    {
        $this->getOperatorExample();

        $this->assertEquals(OperatorExample::INIT_DONE, OperatorExample::$initResult );
    }

    /**
     * Проверка работы геттеров
     *      Ожидается, что метод вернет значение свойств
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testGetter
     *
     * @tag #test #Handler #get
     *@see Operator::__get()
     *
     */
    public function testGetter()
    {
        $operator = $this->operator;
        $this->assertEquals(self::HOST, $operator->host);
        $this->assertInstanceOf(Request::class, $operator->commonRequest);

        $request = $this->getRequest();
        $this->assertInstanceOf(Request::class, $request );

        $operator->setupRequest( $request );
        $this->assertInstanceOf(Request::class, $operator->realRequest );
        $this->assertCount(6, $operator->eventHandlers);

        $operator->addLog('test');
        $this->assertIsArray( $operator->logs );
        $this->assertCount(1, $operator->logs );
    }

    /**
     * Проверка метода возвращающего все геттеры
     *      Ожидается, что метод вернет массив с актуальными значениями свойств
     *
     * Source: @return void
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testGetParams
     *
     * @tag #test #Handler #get #params
     *@see Operator::getParams()
     *
     */
    public function testGetParams()
    {
        $protocol = 'wss';
        $host = 'getParams.Host';
        $apiUrl = "$protocol://$host";

        $requestCommon = [
            RequestInterface::SETUP_METHOD => Method::PUT,
        ];

        $operator = $this->getHandler( $apiUrl, $requestCommon );

        $events = [
            HandlerInterface::EVENT_AFTER_INIT => function() {
                return HandlerInterface::EVENT_AFTER_INIT;
            },
        ];

        $operator->setupEventHandlers($events);

        $requestRealParams = [
            RequestInterface::SETUP_METHOD => Method::POST,
            RequestInterface::SETUP_CURL_INFO => [ 'info' => 'real' ]
        ];

        $requestReal = $operator->constructRequest(Method::POST, '/endpointReal', $requestRealParams );

        $operator->setupRequest( $requestReal );

        $params = $operator->getParams();

        $this->assertArrayHasKey(HandlerInterface::PARAM_HOST, $params );
        $this->assertEquals( $host, $params[HandlerInterface::PARAM_HOST] );
        $this->assertEquals( $protocol, $requestReal->protocol );

        $this->assertArrayHasKey(HandlerInterface::PARAM_COMMON_REQUEST, $params );
        $this->assertInstanceOf(Request::class, $params[HandlerInterface::PARAM_COMMON_REQUEST] );

        $this->assertArrayHasKey(HandlerInterface::PARAM_REAL_REQUEST, $params );
        $this->assertInstanceOf(Request::class, $params[HandlerInterface::PARAM_REAL_REQUEST] );

        $this->assertArrayHasKey(HandlerInterface::PARAM_EVENT_HANDLERS, $params );
        $this->assertSameSize($events, $params[HandlerInterface::PARAM_EVENT_HANDLERS]);
    }

    /**
     * Проверка методов отвечающихз за создание объектов `Request` и `Response`
     *      Ожидается, что методы `construct` вернут объекты классов `Request` и `Response`
     *      Ожидается, что сработают эвенты `EVENT_CONSTRUCT_REQUEST` и `EVENT_CONSTRUCT_RESPONSE` и в лог запишутся данные
     *
     * Source: @return void
     *
     * @throws EventUpdateException|InvalidEndpointException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidMethodException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testConstruct
     *
     * @tag #test #Handler #construct
     *@see Operator::constructRequest()
     * Source: @see Operator::constructResponse()
     *
     */
    public function testConstruct():void
    {
        $operator = $this->operator;

            $this->assertEquals(self::HOST, $operator->host);

        $operator->on(HandlerInterface::EVENT_CONSTRUCT_REQUEST, function(Operator $operator) {
            $operator->addLog(HandlerInterface::EVENT_CONSTRUCT_REQUEST);
        });
        $operator->on(HandlerInterface::EVENT_CONSTRUCT_RESPONSE, function(Operator $operator) {
            $operator->addLog(HandlerInterface::EVENT_CONSTRUCT_RESPONSE);
        });

        $request = $operator->constructRequest( Method::GET, self::ENDPOINT );
        $this->assertInstanceOf(Request::class, $request );
        $this->assertTrue(in_array(HandlerInterface::EVENT_CONSTRUCT_REQUEST, $operator->logs));

        $response = $operator->constructResponse([
            ResponseInterface::CONTENT => 'content',
            ResponseInterface::HTTP_CODE => 200,
        ], $request );

        $this->assertInstanceOf(Response::class, $response );

        $this->assertTrue(in_array(HandlerInterface::EVENT_CONSTRUCT_RESPONSE, $operator->logs));
    }

    /**
     * Проверка что задаётся свойство `realRequest`
     *      Ожидается что метод задаст свойство `realRequest` объектом класса `Request`
     *
     * Source: @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testSetupRequest
     *
     * @tag #test #Handler #setup #request
     *@see Operator::setupRequest()
     *
     */
    public function testSetupRequest()
    {
        $operator = $this->operator;

        $request = $this->getRequest(null, [
            RequestInterface::SETUP_DATA => self::DATA_A
        ]);

        $operator->setupRequest( $request );
        $this->assertInstanceOf( Request::class, $operator->realRequest );
        $this->assertEquals( json_encode(self::DATA_A), json_encode($operator->realRequest->data) );

        // Проверка с перезаписью и добавлением свойств
        $operator->setupRequest( $request, [
            RequestInterface::SETUP_DATA => self::DATA_B,
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::RAR
        ]);

        $this->assertEquals( json_encode(self::DATA_B), json_encode($operator->realRequest->data) );
        $this->assertEquals( ContentType::RAR, $operator->realRequest->contentType );
    }

    /**
     * Ожидается что метод задаст свойство `events` массивом с колбеками
     *
     * Source: @return void
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testSetupEventHandlers
     *
     * @tag #test #Handler #setup #eventHandlers
     *@see Operator::setupEventHandlers()
     *
     */
    public function testSetupEventHandlers()
    {
        $operator = $this->getHandler();

        $operator->setupEventHandlers([]);

        $this->assertIsArray( $operator->eventHandlers );
        $this->assertCount(0, $operator->eventHandlers);

        $eventList = [
            HandlerInterface::EVENT_AFTER_INIT => function() {
                return HandlerInterface::EVENT_AFTER_INIT;
            },
            HandlerInterface::EVENT_CONSTRUCT_REQUEST => function() {
                return HandlerInterface::EVENT_CONSTRUCT_REQUEST;
            },
            HandlerInterface::EVENT_CONSTRUCT_RESPONSE => function() {
                return HandlerInterface::EVENT_CONSTRUCT_RESPONSE;
            },
        ];

        $callBackList = $operator->setupEventHandlers($eventList);

        $this->assertSameSize( $callBackList, $eventList );

        $this->assertIsArray( $operator->eventHandlers );
        $this->assertSameSize( $eventList, $operator->eventHandlers );
    }

    /**
     * Ожидается что метод добавит callBack в массив `events`
     *
     * Source: @return void
     *
     * @throws EventUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testEventsOn
     *
     * @tag #test #Handler #event #on
     *@see Operator::on()
     *
     */
    public function testEventsOn()
    {
        $operatorExample = $this->getOperatorExample();

        $operatorExample->on(OperatorExample::MY_EVENT, function() {});

        $this->assertArrayHasKey(OperatorExample::MY_EVENT, $operatorExample->eventHandlers );
    }

    /**
     * Ожидается что метод event приватный
     *
     * Source: @return void
     *
     * @throws EventUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testEventCall
     *
     * @tag #test #Handler #event #call
     *@see Operator::event()
     *
     */
    public function testEventCall()
    {
        $reflection = new ReflectionClass(Operator::class);
        $method = $reflection->getMethod('event');

        $this->assertTrue($method->isPublic());

        $operatorExample = $this->getOperatorExample();

        $operatorExample->on(OperatorExample::MY_EVENT, function(Operator $operator) {
            $operator->addLog(OperatorExample::MY_EVENT);
        });

        /** Проверка на вызов `event()` через `callEventHandler` */
        $operatorExample->callEventHandler(OperatorExample::MY_EVENT);

        $this->assertCount(1, $operatorExample->logs, "Ожидается что после вызова `callEventHandler` в лог запишутся данные " );
        $this->assertEquals(OperatorExample::MY_EVENT, $operatorExample->logs[0], "Ожидается что значение в `logs[0]` будет равно значению `HandlerExample::MY_EVENT` " );
    }

    /**
     * Тест ожидает что после вызова метода `off` callBack не будет вызван
     *
     * Source: @return void
     *
     * @throws EventUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testEventOff
     *
     * @tag #test #Handler #event #off
     *@see Operator::off()
     *
     */
    public function testEventOff()
    {
        $operatorExample = $this->getOperatorExample();

        $operatorExample->on(OperatorExample::MY_EVENT,
            function( Operator $operator) {
                $operator->addLog(OperatorExample::MY_EVENT);
            }
        );

        $operatorExample->callEventHandler(OperatorExample::MY_EVENT);

        $this->assertCount(1, $operatorExample->logs );
        $this->assertEquals(OperatorExample::MY_EVENT, $operatorExample->logs[0] );

        $operatorExample->off(OperatorExample::MY_EVENT);

        $operatorExample->callEventHandler(OperatorExample::MY_EVENT);

        $this->assertCount(1, $operatorExample->logs );
    }

    /**
     * Тест ожидает что после вызова метода `changeEvent` callBack будет изменен
     *
     * Source: @return void
     *
     * @throws EventUpdateException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testEventChange
     *
     * @tag #test #Handler #event #change
     *@see Operator::changeEvent()
     *
     */
    public function testEventChange()
    {
        $operatorExample = $this->getOperatorExample();

        $operatorExample->on(OperatorExample::MY_EVENT,
            function( Operator $operator) {
                $operator->addLog(OperatorExample::MY_EVENT);
            }
        );

        $operatorExample->callEventHandler(OperatorExample::MY_EVENT);

        $this->assertCount(1, $operatorExample->logs );
        $this->assertEquals(OperatorExample::MY_EVENT, $operatorExample->logs[0] );


        $this->expectException(Exception::class);

        $operatorExample->on(OperatorExample::MY_EVENT,
            function( Operator $operator) {
            $operator->addLog(OperatorExample::MY_EVENT);
            $operator->addLog(OperatorExample::MY_EVENT);
            $operator->addLog(OperatorExample::MY_EVENT);
        });

        $operatorExample->callEventHandler(OperatorExample::MY_EVENT);

        $this->assertCount(2, $operatorExample->logs );
        $this->assertEquals(OperatorExample::MY_EVENT, $operatorExample->logs[1] );

        $operatorExample->changeEvent(OperatorExample::MY_EVENT,
            function( Operator $operator) {
                $operator->addLog(OperatorExample::MY_EVENT . '3' );
                $operator->addLog(OperatorExample::MY_EVENT . '4' );
            });

        $operatorExample->callEventHandler(OperatorExample::MY_EVENT);

        $this->assertCount(4, $operatorExample->logs );
        $this->assertEquals(OperatorExample::MY_EVENT . '3', $operatorExample->logs[3] );
        $this->assertEquals(OperatorExample::MY_EVENT . '4', $operatorExample->logs[4] );

    }

    /**
     * Ожидается что метод, задаст значения false и 0
     * для `CURLOPT_SSL_VERIFYPEER` и `CURLOPT_SSL_VERIFYHOST` в запросе
     *
     * Source: @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testDisableSsl
     *
     * @tag #test #Handler #ssl #disable
     *@see Operator::disableSSL()
     *
     */
    public function testDisableSsl()
    {
        $operator = $this->getHandler();

        $operator->disableSSL();

        $request = $operator->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $request->curlOptions[CURLOPT_SSL_VERIFYPEER] === false );
        $this->assertTrue( $request->curlOptions[CURLOPT_SSL_VERIFYHOST] === 0 );
    }

    /**
     * Ожидается что метод, задаст значения true и 2
     * для `CURLOPT_SSL_VERIFYPEER` и `CURLOPT_SSL_VERIFYHOST` в запросе
     *
     * Source: @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testEnableSsl
     *
     * @tag #test #Handler #ssl #enable
     *@see Operator::enableSSL()
     *
     */
    public function testEnableSsl()
    {
        $operator = $this->getHandler();

        $operator->disableSSL();
        $operator->enableSSL();

        $request = $operator->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $request->curlOptions[CURLOPT_SSL_VERIFYPEER] === true );
        $this->assertTrue( $request->curlOptions[CURLOPT_SSL_VERIFYHOST] === 2 );
    }

    /**
     * Ожидается что метод, задаст значение true для `CURLOPT_FOLLOWLOCATION` в запросе
     *
     * Source: @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testEnableRedirect
     *
     * @tag #test #Handler #redirect #enable
     *@see Operator::enableRedirect()
     *
     */
    public function testEnableRedirect()
    {
        $operator = $this->getHandler();

        $operator->enableRedirect();

        $request = $operator->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $request->curlOptions[CURLOPT_FOLLOWLOCATION] === true );
    }

    /**
     * Ожидается что метод, задаст значения для `CURLOPT_COOKIE`, `CURLOPT_COOKIEJAR` и `CURLOPT_COOKIEFILE` в запросе
     *
     * Source: @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testUseCookie
     *
     * @tag #test #Handler #cookie
     *@see Operator::UseCookie()
     *
     */
    public function testUseCookie()
    {
        $operator = $this->getHandler();

        $cookie = 'cookie=cookie';
        $jar = 'jar.txt';
        $file = 'file.txt';

        $operator->useCookie( $cookie, $jar );

        $request = $operator->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $request->curlOptions[CURLOPT_COOKIE] === $cookie );
        $this->assertTrue( $request->curlOptions[CURLOPT_COOKIEJAR] === $jar );
        $this->assertTrue( $request->curlOptions[CURLOPT_COOKIEFILE] === $jar );

        $operator->useCookie( $cookie, $jar, $file );

        $request = $operator->constructRequest(
            Method::GET,
            self::ENDPOINT
        );

        $this->assertTrue( $request->curlOptions[CURLOPT_COOKIE] === $cookie );
        $this->assertTrue( $request->curlOptions[CURLOPT_COOKIEJAR] === $jar );
        $this->assertTrue( $request->curlOptions[CURLOPT_COOKIEFILE] === $file );
    }

    /**
     * Ожидается что метод `send` вернет объект класса `Response` с заданными свойствами
     * и что в свойстве `content` будет содержимое ответа
     *
     * Source: @return void
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException
     * @throws InvalidMethodException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testSendRequestWithFakeResponse
     *
     * @tag #test #Handler #send #fakeResponse
     */
    public function testSendRequestWithFakeResponse(): void
    {
        $operator = PostmanEcho::getOperatorInstance();
        $this->assertInstanceOf(Operator::class, $operator );

        $request = PostmanEcho::constructRequestMethodGet();
        $this->assertInstanceOf(Request::class, $request );

        $fakeResponse = [
            ResponseInterface::CONTENT => json_encode(PostmanEcho::DATA),
            ResponseInterface::HTTP_CODE => 777,
        ];
        $request->setFakeResponse($fakeResponse);

        $response = $operator->send( $request );
        $this->assertInstanceOf(Response::class, $response );

        $this->assertEquals($fakeResponse[ ResponseInterface::CONTENT ], $response->content );
        $this->assertEquals($fakeResponse[ ResponseInterface::HTTP_CODE ], $response->httpCode );
    }

    /**
     * Ожидается что метод `send` вернет `Response` ответ на запрос методом `POST`
     *
     * Source: @return void
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException
     * @throws InvalidMethodException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testSendRequestOnMethodPost
     *
     * @tag #test #Handler #send #post
     */
    public function testSendRequestOnMethodPost(): void
    {
        $operator = PostmanEcho::getOperatorInstance();
        $this->assertInstanceOf(Operator::class, $operator );

    $data = PostmanEcho::DATA;

        $request = PostmanEcho::constructRequestMethodPost([
            RequestInterface::SETUP_DATA => $data,
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::JSON
        ]);
        $this->assertInstanceOf(Request::class, $request );

        $response = $operator->send($request);

        $this->assertInstanceOf(Response::class, $response );

        $content = json_decode( $response->content, true );

        /** @see PostmanEcho::ENDPOINT_POST */
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_ARGS, $content);
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_DATA, $content);
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_FILES, $content);
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_FORM, $content);
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_HEADERS, $content);
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_JSON, $content);
        $this->assertArrayHasKey(PostmanEcho::GET_KEY_URL, $content);

        $this->assertEquals( $response->request->url, $content[PostmanEcho::GET_KEY_URL] );
        $this->assertEquals( Method::POST, $response->request->method );
    }



    /**
     * Ожидается что метод `updateRequestParams` обновит параметры запроса.
     * Вызов метода `updateRequestParams` с новыми параметрами произойдет внутри метода `setupRequest`
     *
     * Source: @see Operator::updateRequestParams()
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testUpdateRequestParams
     *
     * @tag #test #Handler #update #requestParams
     */
    public function testUpdateRequestParams()
    {
        $operator = $this->getHandler(self::HOST );
        $this->assertInstanceOf(Operator::class, $operator );

        $oldData = [
            RequestInterface::SETUP_PROTOCOL => Request::PROTOCOL_HTTP,
            RequestInterface::SETUP_HOST => self::HOST,
            RequestInterface::SETUP_METHOD => Method::GET,
            RequestInterface::SETUP_HEADERS => [
                'Content-Type' => ContentType::JSON,
            ],
            RequestInterface::SETUP_DATA => ['state' => 'old'],
            RequestInterface::SETUP_CURL_OPTIONS => [
                CURLOPT_HEADER => false,
            ],
            RequestInterface::SETUP_CURL_INFO => [
                'info' => 'old',
            ],
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::JSON,
        ];


        $request = $operator->constructRequest( Method::GET, '/tyda', $oldData );
        $this->assertInstanceOf(Request::class, $request );

        $newData = [
            RequestInterface::SETUP_PROTOCOL => Request::PROTOCOL_HTTPS,
            RequestInterface::SETUP_HOST => self::HOST . '/new',
            RequestInterface::SETUP_METHOD => Method::POST,
            RequestInterface::SETUP_HEADERS => [
                'Content-Type' => ContentType::FORM,
            ],
            RequestInterface::SETUP_DATA => ['state' => 'old'],
            RequestInterface::SETUP_CURL_OPTIONS => [
                CURLOPT_HEADER => true,
            ],
            RequestInterface::SETUP_CURL_INFO => [
                'info' => 'new',
            ],
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::XML,
        ];

        $request = $operator->setupRequest( $request, $newData )->realRequest;

        $this->assertEquals( $newData[RequestInterface::SETUP_PROTOCOL], $request->protocol );
        $this->assertEquals( $newData[RequestInterface::SETUP_HOST], $request->host );
        $this->assertEquals( $newData[RequestInterface::SETUP_METHOD], $request->method );
        $this->assertEquals( $newData[RequestInterface::SETUP_HEADERS]['Content-Type'], $request->headers['Content-Type'] );
        $this->assertEquals( $newData[RequestInterface::SETUP_DATA]['state'], $request->data['state'] );
        $this->assertEquals( $newData[RequestInterface::SETUP_CURL_OPTIONS][CURLOPT_HEADER], $request->curlOptions[CURLOPT_HEADER] );
        $this->assertEquals( $newData[RequestInterface::SETUP_CURL_INFO]['info'], $request->curlInfo['info'] );
        $this->assertEquals( $newData[RequestInterface::SETUP_CONTENT_TYPE], $request->contentType );
    }

    /**
     * Ожидается что метод `updatePostFields` задаст `CURLOPT_POSTFIELDS` свойство запроса
     *
     * Source: @see Operator::updatePostFields()
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException
     * @throws InvalidMethodException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testUpdatePostFields
     *
     * @tag #test #Handler #update #postFields
     */
    public function testUpdatePostFields()
    {
        $operator = $this->getHandler(self::HOST, []);
        $this->assertInstanceOf(Operator::class, $operator );

        $postFields = [
            'a' => 1,
            'b' => 2,
        ];

        $request = $operator->constructRequest(Method::POST,self::ENDPOINT, [
            RequestInterface::SETUP_DATA => $postFields,
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::JSON
        ]);

        $response = $operator->send(
            $request->setFakeResponse([
                ResponseInterface::CONTENT => self::CONTENT,
                ResponseInterface::HTTP_CODE => self::HTTP_CODE_OK,
            ])
        );

        $responsePostFields = $response->request->curlOptions[CURLOPT_POSTFIELDS];

        $this->assertEquals( json_encode($postFields), $responsePostFields );

        $request = $operator->constructRequest(Method::PUT,self::ENDPOINT, [
            RequestInterface::SETUP_DATA => $postFields,
            RequestInterface::SETUP_CONTENT_TYPE => ContentType::FORM
        ]);

        $response = $operator->send(
            $request->setFakeResponse([
                ResponseInterface::CONTENT => self::CONTENT,
                ResponseInterface::HTTP_CODE => self::HTTP_CODE_OK,
            ])
        );

        $responsePostFields = $response->request->curlOptions[CURLOPT_POSTFIELDS];

        $this->assertEquals( http_build_query($postFields), $responsePostFields );
    }

    /**
     * Ожидается что метод `updateMethod` задаст `CURLOPT_CUSTOMREQUEST` свойство запроса
     *
     * @dataProvider methodListProvider
     *
     * Source: @see Operator::updateMethod()
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException
     * @throws InvalidMethodException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testUpdateMethod
     *
     * @tag #test #Handler #update #method
     */
    public function testUpdateMethod( string $method )
    {
        $operator = $this->getHandler(self::HOST, []);

        $fakeResponse = [
            ResponseInterface::CONTENT => self::CONTENT,
            ResponseInterface::HTTP_CODE => self::HTTP_CODE_OK,
        ];

        $request = $operator->constructRequest( $method,self::ENDPOINT );

        $request->setFakeResponse($fakeResponse);

        $response = $operator->send( $request );

        $this->assertEquals( $method, $response->request->method );
    }

    /**
     * Проверка правильного определения валидности метода.
     *
     *      Ожидается что метод `validateMethod` вернет true для валидных методов
     *      Проверка происходит через вызов метода `constructRequest`
     *
     * @dataProvider methodListProvider
     *
     * Source: @see Operator::validateMethod()
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException|InvalidHeaderException
     *
     * @cli vendor/bin/phpunit tests/core/HandlerTest.php --testdox --filter testValidateMethod
     *
     * @tag #test #Handler #validate #method
     */
    public function testValidateMethod( string $method )
    {
        $operator = new Operator(self::HOST);

        $request = $operator->constructRequest( $method,self::ENDPOINT );

        $operator->setupRequest( $request );

        $this->assertEquals( $method, $operator->realRequest->method );

        $this->expectException(Exception::class);

        $operator->constructRequest( 'INVALID_METHOD',self::ENDPOINT );
    }

    /**
     * Возвращает список методов из класса `Method`
     *
     * @return string[]
     *
     */
    public static function methodListProvider(): array
    {
        return [
            [ Method::GET ],
            [ Method::POST ],
            [ Method::PUT ],
            [ Method::DELETE ],
            [ Method::PATCH ],
            [ Method::OPTIONS ],
            [ Method::HEAD ],
            [ Method::TRACE ]
        ];
    }



    // === Private ===

    /**
     * Вспомогательный метод для создания объекта класса `HandlerExample`
     *
     * @return OperatorExample
     *
     *
     *
     * @tag #test #Handler #example
     */
    private function getOperatorExample(): OperatorExample
    {
        return new OperatorExample(self::HOST );
    }
}