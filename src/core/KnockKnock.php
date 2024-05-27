<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description PHP Фасад\Адаптер для отправки запросов через ext cURL
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.0.2
 */

declare(strict_types=1);

namespace andy87\knock_knock\core;

use Exception;
use andy87\knock_knock\lib\{ LibKnockMethod, LibKnockContentType };
use andy87\knock_knock\interfaces\{ KnockKnockInterface, KnockRequestInterface, KnockResponseInterface };

/**
 * Class KnockKnock
 *
 * @package andy87\knock_knock
 *
 * @property-read string $host
 * @property-read KnockRequest $commonKnockRequest
 * @property-read KnockRequest $realKnockRequest
 * @property-read callable[] $eventHandlers
 * @property-read array $logs
 *
 * Покрытие тестами: 100%. @see KnockKnockTest
 */
class KnockKnock implements KnockKnockInterface
{
    /** @var ?KnockKnock $_instance Singleton */
    protected static ?KnockKnock $_instance = null;


    /** @var ?KnockRequest $_commonKnockRequest Общие параметры, назначаемые всем исходящим запросам */
    protected ?KnockRequest $_commonKnockRequest = null;

    /** @var ?KnockRequest $_realKnockRequest Последний используемый запрос */
    protected ?KnockRequest $_realKnockRequest = null;


    /** @var callable[] Список callback функций, обработчиков событий */
    protected array $_eventHandlers = [
        self::EVENT_AFTER_INIT => null,
        self::EVENT_CONSTRUCT_REQUEST => null,
        self::EVENT_BEFORE_SEND => null,
        self::EVENT_CURL_HANDLER => null,
        self::EVENT_CONSTRUCT_RESPONSE => null,
        self::EVENT_AFTER_SEND => null,
    ];

    /**
     * Хост, на который будет отправляться запросы по умолчанию (без перенастройки)
     *
     * @var string $_host
     */
    protected string $_host;

    /** @var array $_log Список логов*/
    protected array $_log = [];



    /**
     * KnockKnock конструктор.
     * Принимает хост как обязательный параметр и массив параметров для всех запросов, как опциональный
     *
     * @param string $host
     * @param array $commonKnockRequestParams
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testConstructor()
     *
     * @tag #knockKnock #magic #construct
     */
    public function __construct( string $host, array $commonKnockRequestParams = [] )
    {
        $this->_host = $host;

        $this->_commonKnockRequest = $this->prepareCommonKnockRequestParams( $commonKnockRequestParams );

        $this->init();

        $this->event( self::EVENT_AFTER_INIT, [ $this ] );
    }

    /**
     * Пользовательская инициализация
     *
     * @return void
     *
     * Test: @see KnockKnockTest::testEventInit()
     *
     * @tag #knockKnock #init
     */
    public function init(): void {}


    // --- Getters ---

    /**
     * Магический метод для получения свойств
     *
     * @param string $paramName
     *
     * @return mixed
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testGetter()
     *
     * @tag #knockKnock #magic #get
     */
    public function __get( string $paramName ): mixed
    {
        return match ($paramName) {
            'host' => $this->getHost(),
            'commonKnockRequest' => $this->getCommonKnockRequest(),
            'realKnockRequest' => $this->getRealKnockRequest(),
            'eventHandlers' => $this->getEvents(),
            'logs' => $this->getLogs(),
            default => throw new Exception("Свойство `$paramName` не найдено в классе " . __CLASS__),
        };
    }

    /**
     * Возвращает данные компонента
     *
     * @return array
     *
     * Test: @see KnockKnockTest::testGetParams()
     *
     * @tag #knockKnock #get #params
     */
    public function getParams(): array
    {
        return [
            'host' => $this->getHost(),
            'commonKnockRequest' => $this->getCommonKnockRequest(),
            'realKnockRequest' => $this->getRealKnockRequest(),
            'events' => $this->getEvents(),
        ];
    }




    // --- Construct ---

    /**
     * Конструктор запроса
     *
     * @param string $method
     * @param string $endpoint
     * @param array $knockRequestConfig
     *
     * @return KnockRequest
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testConstruct()
     *
     * @tag #knockKnock #construct #request
     */
    public function constructRequest( string $method, string $endpoint, array $knockRequestConfig = [] ): KnockRequest
    {
        if (!$this->validateMethod($method)) throw new Exception('Ошибка в методе');

        if (empty($endpoint)) throw new Exception('Endpoint не может быть пустым.');

        $knockRequestConfig = array_merge([
            KnockRequestInterface::SETUP_HOST => $this->_host,
            KnockRequestInterface::SETUP_METHOD => $method,
        ], $knockRequestConfig );

        $commonKnockRequestParams = $this->getCommonKnockRequest()->params;

        $knockRequestParams = array_merge( $knockRequestConfig, $commonKnockRequestParams );

        $knockRequest = new KnockRequest( $endpoint, $knockRequestParams );

        $this->event( self::EVENT_CONSTRUCT_REQUEST, [ $this, $knockRequest ] );

        return $knockRequest;
    }

    /**
     * Конструктор ответа
     *
     * @param array $responseParams
     * @param ?KnockRequest $knockRequest
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testConstruct()
     *
     * @tag #knockKnock #construct #response
     */
    public function constructResponse( array $responseParams, ?KnockRequest $knockRequest = null ): KnockResponse
    {
        $content = $responseParams[ KnockResponseInterface::CONTENT ] ?? null;

        $httpCode = $responseParams[ KnockResponseInterface::HTTP_CODE ] ?? KnockResponseInterface::OK;

        $knockResponse = new KnockResponse( $content, $httpCode, $knockRequest );

        $this->event( self::EVENT_CONSTRUCT_RESPONSE, [ $this, $knockResponse ] );

        return $knockResponse;
    }


    // --- Setup ---

    /**
     * @param KnockRequest $knockRequest
     * @param array $options
     *
     * @return $this
     * 
     * @throws Exception
     * 
     * Test: @see KnockKnockTest::testSetupRequest()
     *
     * @tag #knockKnock #setup #request
     */
    public function setupRequest( KnockRequest $knockRequest, array $options = [] ): self
    {
        if ( count( $options ) ) {
            $knockRequest = $this->updateRequestParams( $knockRequest, $options );
        }

        $this->_realKnockRequest = $knockRequest;

        return $this;
    }

    /**
     * Установка обработчиков событий
     *
     * @param callable[] $callbacks
     *
     * @return array
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testSetupEventHandlers()
     *
     * @tag #knockKnock #setup #event #callback #behavior
     */
    public function setupEventHandlers( array $callbacks ): array
    {
        $events = [];

        if ( count($callbacks) )
        {
            foreach ( $callbacks as $event => $callback ) {
                $events[$event] = $callback;
            }
        }

        $this->_eventHandlers = $events;

        return $this->_eventHandlers;
    }


    // --- Response ---

    /**
     * Отправка запроса
     *
     * @param array $fakeResponse
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testSendRequest()
     *
     * @tag #knockKnock #send #response
     */
    public function send( array $fakeResponse = [] ): KnockResponse
    {
        return $this->sendRequest( $this->getRealKnockRequest(), $fakeResponse );
    }

    /**
     * Получение ответа на отправку запроса через cURL
     *
     * @param KnockRequest $knockRequest
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testSendRequest()
     *
     * @tag #knockKnock #send #request #query #response
     */
    public function getResponseOnSendCurlRequest( KnockRequest $knockRequest ): KnockResponse
    {
        $ch = curl_init( $knockRequest->url );

        $curlParams = $knockRequest->curlParams;

        $options = $curlParams[KnockRequestInterface::SETUP_CURL_OPTIONS];

        curl_setopt_array( $ch, $options );

        $response = curl_exec( $ch );

        $curlInfoList = $curlParams[KnockRequestInterface::SETUP_CURL_INFO];

        if ( count($curlInfoList) )
        {
            $curlInfo = [];

            foreach ( $curlInfoList as $info ) {
                $curlInfo[$info] = curl_getinfo( $ch, $info );
            }

            $knockRequest->setCurlInfo( $curlInfo );
        }

        $knockResponseParams = [
            KnockResponseInterface::CONTENT => $response,
            KnockResponseInterface::HTTP_CODE => curl_getinfo( $ch, CURLINFO_HTTP_CODE ),
        ];

        $knockResponse = $this->constructResponse( $knockResponseParams, $knockRequest );

        $knockResponse->addError( curl_error($ch) );

        $this->event( self::EVENT_CURL_HANDLER, [ $this, $ch ] );

        curl_close( $ch );

        $this->event( self::EVENT_AFTER_SEND, [ $this, $knockResponse ] );

        return $knockResponse;
    }


    // --- Events ---

    /**
     * Вызов обработчика события снаружи класса
     *
     * @param string $eventKey
     * @param array $args
     *
     * @return mixed
     *
     * Test: @see KnockKnockTest::testEventCall()
     *
     * @tag #knockKnock #behavior #event #callback
     */
    public function callEventHandler( string $eventKey, array $args = [] ): mixed
    {
        $this->event( $eventKey, $args );

        return null;
    }

    /**
     * Добавление обработчика события
     *
     * @param string $eventKey
     * @param callable $callbacks
     *
     * @return ?bool
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testEventsOn()
     *
     * @tag #knockKnock #behavior #event #callback
     */
    public function on( string $eventKey, callable $callbacks ): ?bool
    {
        if ( !isset($this->_eventHandlers[$eventKey]) || $this->_eventHandlers[$eventKey] === null )
        {
            return $this->changeEvent( $eventKey, $callbacks );
        }

        throw new Exception('Event already exists. Use method change() for change event handler');
    }

    /**
     * Изменение обработчика события
     *
     * @param string $eventKey
     * @param callable $callback
     *
     * @return bool
     *
     * Test: @see KnockKnockTest::testEventChange()
     *
     * @tag #knockKnock #behavior #event #callback
     */
    public function changeEvent( string $eventKey, callable $callback ): bool
    {
        $this->_eventHandlers[$eventKey] = $callback;

        return true;
    }

    /**
     * Удаление обработчика события
     *
     * @param string $eventKey
     *
     * @return bool
     *
     * Test: @see KnockKnockTest::testEventOff()
     *
     * @tag #knockKnock #behavior #event #callback
     */
    public function off( string $eventKey ): bool
    {
        if ( isset( $this->_eventHandlers[$eventKey] ) )
        {
            unset( $this->_eventHandlers[$eventKey] );

            return true;
        }

        return false;
    }



    // --- Features ---

    /**
     * Добавление Записи в лог ошибок
     *
     * @param string $error
     * @param ?string $key
     *
     * @return $this
     */
    public function addLog( string $error, ?string $key = null ): self
    {
        if ( $key )
        {
            $this->_log[$key] = $error;

        } else {

            $this->_log[] = $error;
        }

        return $this;
    }

    /**
     * Отключение SSL сертификата
     *
     * @param bool $verifyPeer проверка подлинность сертификата сервера
     * @param int  $verifyHost проверка соответствия имени хоста сервера и имени, указанного в сертификате сервера
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testDisableSsl()
     *
     * @tag #knockKnock #disable #ssl
     */
    public function disableSSL( bool $verifyPeer = false, int $verifyHost = 0 ): self
    {
        $this->_commonKnockRequest->setCurlOption( CURLOPT_SSL_VERIFYPEER, $verifyPeer );
        $this->_commonKnockRequest->setCurlOption( CURLOPT_SSL_VERIFYHOST, $verifyHost );

        return $this;
    }

    /**
     * Включение SSL сертификата
     *
     * @param bool $verifyPeer проверка подлинность сертификата сервера
     * @param int $verifyHost проверка соответствия имени хоста сервера и имени, указанного в сертификате сервера
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testEnableSsl()
     *
     * @tag #knockKnock #enable #ssl
     */
    public function enableSSL( bool $verifyPeer = true, int  $verifyHost = 2 ): self
    {
        $this->_commonKnockRequest->setCurlOption( CURLOPT_SSL_VERIFYPEER, $verifyPeer );
        $this->_commonKnockRequest->setCurlOption( CURLOPT_SSL_VERIFYHOST, $verifyHost );

        return $this;
    }

    /**
     * Включение редиректа, добавляя `CURLOPT_FOLLOWLOCATION` = true
     * если сервер возвращает код 301 или 302
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testEnableRedirect()
     *
     * @tag #knockKnock #enable #redirect
     */
    public function enableRedirect(): self
    {
        $this->_commonKnockRequest->setCurlOption( CURLOPT_FOLLOWLOCATION, true );

        return $this;
    }


    /**
     * Использование cookie, добавляя в запрос параметры:
     *  - `CURLOPT_COOKIE`
     *  - `CURLOPT_COOKIEJAR`
     *  - `CURLOPT_COOKIEFILE`
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testUseCookie()
     *
     * @tag #knockKnock #use #cookie
     */
    public function useCookie( string $cookie, string $jar, ?string $file = null ): self
    {
        $file = $file ?? $jar;

        $this->_commonKnockRequest->addCurlOptions([
            CURLOPT_COOKIE      => $cookie,
            CURLOPT_COOKIEJAR   => $jar,
            CURLOPT_COOKIEFILE  => $file,
        ]);

        return $this;
    }


    // ---static ---

    /**
     * Проверка валидности хоста
     *
     * @param string $host
     *
     * @return bool
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testValidateHostName()
     *
     * @tag #knockKnock #validate #host
     */
    public static function validateHostName( string $host ): bool
    {
        if ( strlen($host) < 4 OR strlen($host) > 253  ) return false;

        if ( str_contains($host, '--') OR str_contains($host, '..') ) return false;

        $parts = ( str_contains( $host, '.' ) ) ? explode('.', $host) : [$host];
        $parts = array_reverse($parts);

        $regexName = '/^(?!-)[A-Za-z0-9-]{1,63}(?<!-)$/';
        $regexZone = '/^(?!-)[A-Za-z0-9-]{2,63}(?<!-)$/';

        foreach ( $parts as $index => $part )
        {
            if ( $index )
            {
                if ( !preg_match($regexName, $part)) return false;

            } elseif ( strlen($part) < 2 ){ // проверка доменной зоны

                if ( !preg_match($regexZone, $part)) return false;
            }
        }

        return true;
    }

    /**
     * Получение экземпляра класса, используя паттерн Singleton
     *
     * @param ?string $host
     * @param array $commonKnockRequestParams
     *
     * @return self
     *
     * Test: @see KnockKnockTest::testGetInstance()
     *
     * @tag #knockKnock #get #instance
     */
    public static function getInstance( string $host = null, array $commonKnockRequestParams = [] ): self
    {
        if ( static::$_instance === null )
        {
            $classname = static::class;

            static::$_instance = new $classname( $host, $commonKnockRequestParams );
        }

        return static::$_instance;
    }



    // === Protected ===

    // --- Getters ---

    /**
     * Получение объекта запроса с общими параметрами для всех запросов
     *
     * @return ?KnockRequest
     *
     * Test: @see KnockKnockTest::testGetter()
     *
     * @tag #knockKnock #request #common
     */
    protected function getCommonKnockRequest(): ?KnockRequest
    {
        return $this->_commonKnockRequest ?? null;
    }

    /**
     * Получение объекта запроса с параметрами последнего запроса
     *
     * @return ?KnockRequest
     *
     * Test: @see KnockKnockTest::testGetter()
     *
     * @tag #knockKnock #request #real
     */
    protected function getRealKnockRequest(): ?KnockRequest
    {
        return $this->_realKnockRequest ?? null;
    }

    /**
     * Получение хоста
     *
     * @return string
     *
     * Test: @see KnockKnockTest::testGetter()
     *
     * @tag #knockKnock #get #host
     */
    protected function getHost(): string
    {
        return $this->_host;
    }

    /**
     * Получение обработчиков событий
     *
     * Test: @see KnockKnockTest::testGetter()
     *
     * @return callable[]
     */
    protected function getEvents(): array
    {
        return $this->_eventHandlers;
    }

    /**
     * Получение логов
     *
     * Test: @see KnockKnockTest::testGetter()
     *
     * @return array
     */
    protected function getLogs(): array
    {
        return $this->_log;
    }


    // --- Send ---

    /**
     * Отправка запроса
     *
     * @param KnockRequest $knockRequest
     * @param ?array $fakeKnockResponseParams
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testSendRequest()
     *
     * @tag #knockKnock #send #request
     */
    protected function sendRequest( KnockRequest $knockRequest, ?array $fakeKnockResponseParams = null ): KnockResponse
    {
        $knockRequest->setupStatusProcessing();

        $this
            ->updatePostFields( $knockRequest )
            ->updateMethod( $knockRequest )
            ->event( self::EVENT_BEFORE_SEND, [ $this, $knockRequest ] );

        $knockResponse = ( $fakeKnockResponseParams )
            ? $this->constructResponse( $fakeKnockResponseParams, $knockRequest )
            : $this->getResponseOnSendCurlRequest( $knockRequest );

        $knockRequest->setupStatusComplete();

        return $knockResponse;
    }


    // === P R I V A T E ===

    // --- Prepare ---

    /**
     * Подготовка общих параметров запроса
     *
     * @param array $commonKnockRequestParams
     *
     * @return KnockRequest
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testConstructor()
     *
     * @tag #knockKnock #prepare #common #request
     */
    private function prepareCommonKnockRequestParams( array $commonKnockRequestParams ): KnockRequest
    {
        $domainSeparator = '://';

        if ( str_contains( $this->_host, $domainSeparator ) )
        {
            [ $protocol, $this->_host ] = explode( $domainSeparator, $this->_host );

            $commonKnockRequestParams = array_merge(
                [
                    KnockRequestInterface::SETUP_PROTOCOL => $protocol
                ],
                $commonKnockRequestParams
            );
        }

        if ( !self::validateHostName( $this->_host ) )
        {
            throw new Exception("Хост `$this->_host` не валиден");
        }

        $commonKnockRequestParams = array_merge(
            [
                KnockRequestInterface::SETUP_PROTOCOL => KnockRequest::PROTOCOL_HTTP,
                KnockRequestInterface::SETUP_HOST => $this->_host,
            ],
            $commonKnockRequestParams
        );

        return new KnockRequest( null, $commonKnockRequestParams );
    }


    // --- Events ---

    /**
     * Вызов обработчика события внутри класса
     *
     * @param string $eventKey
     * @param array $args
     *
     * @return void
     *
     * Test: @see KnockKnockTest::testEventCall()
     *
     * @tag #knockKnock #behavior #event #callback
     */
    private function event( string $eventKey, array $args = [] ): void
    {
        if ( isset( $this->_eventHandlers[ $eventKey ] ) )
        {
            $callback = $this->_eventHandlers[ $eventKey ];

            if ( empty($args) ) $args = [ $this ];

            call_user_func_array( $callback, $args );
        }
    }


    // --- Update ---

    /**
     * Обновление параметров запроса на основе переданных параметров в
     *
     * @param KnockRequest $knockRequest
     * @param array $params
     *
     * @return KnockRequest
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testUpdateRequestParams()
     *
     * @tag #knockKnock #update #request
     */
    private function updateRequestParams( KnockRequest $knockRequest, array $params ): KnockRequest
    {
        foreach ($params as $key => $value )
        {
            switch ($key)
            {
                case KnockRequestInterface::SETUP_PROTOCOL:
                    $knockRequest->setProtocol( $value );
                    break;

                case KnockRequestInterface::SETUP_HOST:
                    $knockRequest->setHost( $value );
                    break;

                case KnockRequestInterface::SETUP_METHOD:
                    $knockRequest->setMethod( $value );
                    break;

                case KnockRequestInterface::SETUP_HEADERS:
                    $knockRequest->addHeaders( $value );
                    break;

                case KnockRequestInterface::SETUP_DATA:
                    $knockRequest->setData( $value );
                    break;

                case KnockRequestInterface::SETUP_CURL_OPTIONS:
                    $knockRequest->setCurlOptions( $value );
                    break;

                case KnockRequestInterface::SETUP_CURL_INFO:
                    $knockRequest->setCurlInfo( $value );
                    break;

                case KnockRequestInterface::SETUP_CONTENT_TYPE:
                    $knockRequest->setContentType( $value );
                    break;
            }
        }

        return $knockRequest;
    }

    /**
     * Установка POST-полей
     *
     * @param KnockRequest $knockRequest
     *
     * @return $this
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testUpdatePostFields()
     *
     * @tag #knockKnock #update #post
     */
    private function updatePostFields( KnockRequest $knockRequest ): self
    {
        $data = $knockRequest->data;

        if ( $data && count( $data ) )
        {
            if ( $knockRequest->method === LibKnockMethod::GET )
            {
                $knockRequest->prepareEndpoint();

            } else {

                switch( $knockRequest->contentType )
                {
                    case LibKnockContentType::JSON:
                        $data = json_encode( $data );
                        break;

                    case LibKnockContentType::FORM:
                        $data = http_build_query($data);
                        break;

                    case LibKnockContentType::PDF:
                    case LibKnockContentType::ZIP:
                    case LibKnockContentType::GZIP:
                    case LibKnockContentType::TAR:
                    case LibKnockContentType::RAR:
                    case LibKnockContentType::SEVEN_ZIP:
                    case LibKnockContentType::IMAGE:
                    case LibKnockContentType::AUDIO:
                    case LibKnockContentType::VIDEO:
                    case LibKnockContentType::FONT:
                        $data = file_get_contents($data);
                        break;

                    default:
                        break;
                }

                $knockRequest->setCurlOption( CURLOPT_POSTFIELDS, $data );
            }
        }

        return $this;
    }

    /**
     * Установка метода запроса
     *
     * @param KnockRequest $knockRequest
     *
     * @return $this
     *
     * @throws Exception
     *
     * Test: @see KnockKnockTest::testUpdateMethod()
     *
     * @tag #knockKnock #update #method
     */
    private function updateMethod( KnockRequest $knockRequest ): self
    {
        $method = $knockRequest->method;

        if ( $method !== LibKnockMethod::GET )
        {
            $this->getRealKnockRequest()->setMethod( $method );
        }

        return $this;
    }

    /**
     * Валидация метода
     *
     * @param string $methodName
     *
     * Test: @see KnockKnockTest::testValidateMethod()
     *
     * @return bool
     *
     * @tag #validate #method
     */
    private function validateMethod( string $methodName ): bool
    {
        if ( empty($methodName) ) return false;

        return in_array( $methodName, [
            LibKnockMethod::GET,
            LibKnockMethod::POST,
            LibKnockMethod::PUT,
            LibKnockMethod::DELETE,
            LibKnockMethod::PATCH,
            LibKnockMethod::OPTIONS,
            LibKnockMethod::HEAD,
            LibKnockMethod::TRACE
        ]);
    }
}