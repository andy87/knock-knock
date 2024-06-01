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

use andy87\knock_knock\lib\{ContentType, Method};
use andy87\knock_knock\interfaces\{HandlerInterface, RequestInterface, ResponseInterface};
use andy87\knock_knock\exception\handler\{ EventUpdateException, InvalidMethodException };
use andy87\knock_knock\exception\{InvalidHostException,
    InvalidEndpointException,
    ParamUpdateException,
    ParamNotFoundException,
    request\InvalidHeaderException,
    request\StatusNotFoundException};

/**
 * Class Handler
 *
 * @package andy87\knock_knock
 *
 * @property-read string $host
 * @property-read Request $commonRequest
 * @property-read Request $realRequest
 * @property-read callable[] $eventHandlers
 * @property-read array $logs
 *
 * Покрытие тестами: 100%. @see KnockKnockTest
 */
class Handler implements HandlerInterface
{
    /** @var ?Handler $_instance Объект для реализации Singleton */
    protected static ?Handler $_instance = null;

    /** @var string $host Хост, на который будут отправляться запросы */
    protected string $host;

    /** @var ?Request $_commonRequest Объект содержащий параметры, назначаемые всем исходящим запросам */
    protected ?Request $_commonRequest = null;

    /** @var ?Request $_realRequest Используемый запрос */
    protected ?Request $_realRequest = null;


    /** @var callable[] Список обработчиков событий */
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

    /** @var array $_log Список логов */
    protected array $_log = [];


    /**
     * KnockKnock конструктор.
     * Принимает хост как обязательный параметр и массив параметров для всех запросов, как опциональный
     *
     * @param string $host
     * @param array $commonRequestParams
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testConstructor()
     *
     * @tag #knockKnock #magic #construct
     */
    public function __construct(string $host, array $commonRequestParams = [])
    {
        $this->setupHost($host);

        $this->_commonRequest = $this->prepareCommonRequestParams($commonRequestParams);

        $this->init();

        $this->event(self::EVENT_AFTER_INIT, [$this]);
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
    public function init(): void
    {
    }


    // --- Getters ---

    /**
     * Магический метод для получения свойств
     *
     * @param string $paramName
     *
     * @return mixed
     *
     * @throws ParamNotFoundException
     *
     * Test: @see KnockKnockTest::testGetter()
     *
     * @tag #knockKnock #magic #get
     */
    public function __get(string $paramName): mixed
    {
        return match ($paramName) {
            'host' => $this->getterHost(),
            'commonRequest' => $this->getterCommonRequest(),
            'realRequest' => $this->getterRealRequest(),
            'eventHandlers' => $this->getterEvents(),
            'logs' => $this->getterLogs(),
            default => throw new ParamNotFoundException("Свойство `$paramName` не найдено в классе " . __CLASS__),
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
            'host' => $this->_host,
            'commonRequest' => $this->_commonRequest,
            'realRequest' => $this->_realRequest,
            'eventHandlers' => $this->_eventHandlers,
        ];
    }




    // --- Construct ---

    /**
     * Конструктор запроса
     *
     * @param string $method
     * @param string $endpoint
     * @param array $RequestConfig
     *
     * @return Request
     *
     * @throws InvalidEndpointException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidMethodException
     *
     * Test: @see KnockKnockTest::testConstruct()
     *
     * @tag #knockKnock #construct #request
     */
    public function constructRequest(string $method, string $endpoint, array $RequestConfig = []): Request
    {
        if ($this->validateMethod($method))
        {
            if (empty($endpoint)) throw new InvalidEndpointException();

            $RequestConfig = array_merge([
                RequestInterface::SETUP_HOST => $this->_host,
                RequestInterface::SETUP_METHOD => $method,
            ], $RequestConfig);

            $commonRequestParams = $this->getterCommonRequest()->params;

            $RequestParams = array_merge($RequestConfig, $commonRequestParams);

            $Request = new Request($endpoint, $RequestParams);

            $this->event(self::EVENT_CONSTRUCT_REQUEST, [$this, $Request]);

            return $Request;
        }

        throw new InvalidMethodException();
    }

    /**
     * Конструктор ответа
     *
     * @param array $responseParams
     * @param ?Request $Request
     *
     * @return Response
     *
     * Test: @see KnockKnockTest::testConstruct()
     *
     * @tag #knockKnock #construct #response
     */
    public function constructResponse(array $responseParams, ?Request $Request = null): Response
    {
        $content = $responseParams[ResponseInterface::CONTENT] ?? null;

        $httpCode = $responseParams[ResponseInterface::HTTP_CODE] ?? ResponseInterface::OK;

        $response = new Response($content, $httpCode, $Request);

        $this->event(self::EVENT_CONSTRUCT_RESPONSE, [$this, $response]);

        return $response;
    }


    /**
     * 
     * @param string $host
     *
     * @return $this
     *
     * @throws InvalidHostException
     */
    public function setupHost( string $host ): static
    {
        $this->_host = $host;

        if ( empty($this->_host) )
        {
            throw new InvalidHostException();
        }

        return $this;
    }
    /**
     * @param Request $Request
     * @param array $options
     *
     * @return $this
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException
     *
     * Test: @see KnockKnockTest::testSetupRequest()
     *
     * @tag #knockKnock #setup #request
     */
    public function setupRequest(Request $Request, array $options = []): static
    {
        if (count($options)) {
            $Request = $this->updateRequestParams($Request, $options);
        }

        $this->_realRequest = $Request;

        return $this;
    }

    /**
     * Установка обработчиков событий
     *
     * @param callable[] $callbacks
     *
     * @return array
     *
     *
     *
     * Test: @see KnockKnockTest::testSetupEventHandlers()
     *
     * @tag #knockKnock #setup #event #callback #behavior
     */
    public function setupEventHandlers(array $callbacks): array
    {
        $events = [];

        if (count($callbacks)) {
            foreach ($callbacks as $event => $callback) {
                $events[$event] = $callback;
            }
        }

        $this->_eventHandlers = $events;

        return $this->_eventHandlers;
    }


    /**
     * Отправка запроса
     *
     * @param array $fakeResponse
     *
     * @return Response
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testSendRequest()
     *
     * @tag #knockKnock #send #response
     */
    public function send(array $fakeResponse = []): Response
    {
        return $this->sendRequest($this->getterRealRequest(), $fakeResponse);
    }

    /**
     * Получение ответа на отправку запроса через cURL
     *
     * @param Request $Request
     *
     * @return Response
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testSendRequest()
     *
     * @tag #knockKnock #send #request #query #response
     */
    public function getResponseOnSendCurlRequest(Request $Request): Response
    {
        $ch = curl_init($Request->url);

        $curlParams = $Request->curlParams;

        $options = $curlParams[RequestInterface::SETUP_CURL_OPTIONS];

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        $curlInfoList = $curlParams[RequestInterface::SETUP_CURL_INFO];

        if (count($curlInfoList)) {
            $curlInfo = [];

            foreach ($curlInfoList as $info) {
                $curlInfo[$info] = curl_getinfo($ch, $info);
            }

            $Request->setCurlInfo($curlInfo);
        }

        $ResponseParams = [
            ResponseInterface::CONTENT => $response,
            ResponseInterface::HTTP_CODE => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        ];

        $response = $this->constructResponse($ResponseParams, $Request);

        $response->addError(curl_error($ch));

        $this->event(self::EVENT_CURL_HANDLER, [$this, $ch]);

        curl_close($ch);

        $this->event(self::EVENT_AFTER_SEND, [$this, $response]);

        return $response;
    }


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
    public function callEventHandler(string $eventKey, array $args = []): mixed
    {
        $this->event($eventKey, $args);

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
     * @throws EventUpdateException
     *
     * Test: @see KnockKnockTest::testEventsOn()
     *
     * @tag #knockKnock #behavior #event #callback
     */
    public function on(string $eventKey, callable $callbacks): ?bool
    {
        if (!isset($this->_eventHandlers[$eventKey]) || $this->_eventHandlers[$eventKey] === null) {
            return $this->changeEvent($eventKey, $callbacks);
        }

        throw new EventUpdateException();
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
    public function changeEvent(string $eventKey, callable $callback): bool
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
    public function off(string $eventKey): bool
    {
        if (isset($this->_eventHandlers[$eventKey])) {
            unset($this->_eventHandlers[$eventKey]);

            return true;
        }

        return false;
    }


    /**
     * Добавление Записи в лог ошибок
     *
     * @param string $error
     * @param ?string $key
     *
     * @return $this
     */
    public function addLog(string $error, ?string $key = null): static
    {
        if ($key) {
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
     * @param int $verifyHost проверка соответствия имени хоста сервера и имени, указанного в сертификате сервера
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testDisableSsl()
     *
     * @tag #knockKnock #disable #ssl
     */
    public function disableSSL(bool $verifyPeer = false, int $verifyHost = 0): static
    {
        $this->_commonRequest->setCurlOption(CURLOPT_SSL_VERIFYPEER, $verifyPeer);
        $this->_commonRequest->setCurlOption(CURLOPT_SSL_VERIFYHOST, $verifyHost);

        return $this;
    }

    /**
     * Включение SSL сертификата
     *
     * @param bool $verifyPeer проверка подлинность сертификата сервера
     * @param int $verifyHost проверка соответствия имени хоста сервера и имени, указанного в сертификате сервера
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testEnableSsl()
     *
     * @tag #knockKnock #enable #ssl
     */
    public function enableSSL(bool $verifyPeer = true, int $verifyHost = 2): static
    {
        $this->_commonRequest->setCurlOption(CURLOPT_SSL_VERIFYPEER, $verifyPeer);
        $this->_commonRequest->setCurlOption(CURLOPT_SSL_VERIFYHOST, $verifyHost);

        return $this;
    }

    /**
     * Включение редиректа, добавляя `CURLOPT_FOLLOWLOCATION` = true
     * если сервер возвращает код 301 или 302
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testEnableRedirect()
     *
     * @tag #knockKnock #enable #redirect
     */
    public function enableRedirect(): static
    {
        $this->_commonRequest->setCurlOption(CURLOPT_FOLLOWLOCATION, true);

        return $this;
    }

    /**
     * Использование cookie, добавляя в запрос параметры:
     *  - `CURLOPT_COOKIE`
     *  - `CURLOPT_COOKIEJAR`
     *  - `CURLOPT_COOKIEFILE`
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testUseCookie()
     *
     * @tag #knockKnock #use #cookie
     */
    public function useCookie(string $cookie, string $jar, ?string $file = null): static
    {
        $file = $file ?? $jar;

        $this->_commonRequest->addCurlOptions([
            CURLOPT_COOKIE => $cookie,
            CURLOPT_COOKIEJAR => $jar,
            CURLOPT_COOKIEFILE => $file,
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
     * Test: @see KnockKnockTest::testValidateHostName()
     *
     * @tag #knockKnock #validate #host
     */
    public static function validateHostName(string $host): bool
    {
        if (strlen($host) < 4 or strlen($host) > 253) return false;

        if (str_contains($host, '--') or str_contains($host, '..')) return false;

        $parts = (str_contains($host, '.')) ? explode('.', $host) : [$host];
        $parts = array_reverse($parts);

        $regexName = '/^(?!-)[A-Za-z0-9-]{1,63}(?<!-)$/';
        $regexZone = '/^(?!-)[A-Za-z0-9-]{2,63}(?<!-)$/';

        foreach ($parts as $index => $part) {
            if ($index) {
                if (!preg_match($regexName, $part)) return false;

            } elseif (strlen($part) < 2) { // проверка доменной зоны

                if (!preg_match($regexZone, $part)) return false;
            }
        }

        return true;
    }

    /**
     * Получение экземпляра класса, используя паттерн Singleton
     *
     * @param ?string $host
     * @param array $commonRequestParams
     *
     * @return self
     *
     * Test: @see KnockKnockTest::testGetInstance()
     *
     * @tag #knockKnock #get #instance
     */
    public static function getInstance(string $host = null, array $commonRequestParams = []): static
    {
        if (static::$_instance === null) {
            $classname = static::class;

            static::$_instance = new $classname($host, $commonRequestParams);
        }

        return static::$_instance;
    }



    // === Protected ===

    /**
     * Получение хоста
     *
     * @return string
     *
     * Test: @see KnockKnockTest::testGetter()
     *
     * @tag #knockKnock #get #host
     */
    protected function getterHost(): string
    {
        return $this->_host;
    }

    /**
     * Получение объекта запроса с общими параметрами для всех запросов
     *
     * @return ?Request
     *
     * Test: @see KnockKnockTest::testGetter()
     *
     * @tag #knockKnock #request #common
     */
    protected function getterCommonRequest(): ?Request
    {
        return $this->_commonRequest ?? null;
    }

    /**
     * Получение объекта запроса с параметрами последнего запроса
     *
     * @return ?Request
     *
     * Test: @see KnockKnockTest::testGetter()
     *
     * @tag #knockKnock #request #real
     */
    protected function getterRealRequest(): ?Request
    {
        return $this->_realRequest ?? null;
    }
    /**
     * Получение обработчиков событий
     *
     * Test: @return callable[]
     * @see KnockKnockTest::testGetter()
     *
     */
    protected function getterEvents(): array
    {
        return $this->_eventHandlers;
    }

    /**
     * Получение логов
     *
     * Test: @return array
     * @see KnockKnockTest::testGetter()
     *
     */
    protected function getterLogs(): array
    {
        return $this->_log;
    }

    /**
     * Отправка запроса
     *
     * @param Request $Request
     * @param ?array $fakeResponseParams
     *
     * @return Response
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testSendRequest()
     *
     * @tag #knockKnock #send #request
     */
    protected function sendRequest(Request $Request, ?array $fakeResponseParams = null): Response
    {
        $Request->setupStatusProcessing();

        $this
            ->updatePostFields($Request)
            ->updateMethod($Request)
            ->event(self::EVENT_BEFORE_SEND, [$this, $Request]);

        $Response = ($fakeResponseParams)
            ? $this->constructResponse($fakeResponseParams, $Request)
            : $this->getResponseOnSendCurlRequest($Request);

        $Request->setupStatusComplete();

        return $Response;
    }

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
    protected function event(string $eventKey, array $args = []): void
    {
        if (isset($this->_eventHandlers[$eventKey])) {
            $callback = $this->_eventHandlers[$eventKey];

            if (empty($args)) $args = [$this];

            call_user_func_array($callback, $args);
        }
    }



    // === P R I V A T E ===

    /**
     * Обновление параметров запроса на основе переданных параметров в
     *
     * @param Request $Request
     * @param array $params
     *
     * @return Request
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException
     *
     * Test: @see KnockKnockTest::testUpdateRequestParams()
     *
     * @tag #knockKnock #update #request
     */
    private function updateRequestParams(Request $Request, array $params): Request
    {
        foreach ($params as $key => $value) {
            match ($key) {
                RequestInterface::SETUP_PROTOCOL => $Request->setProtocol($value),
                RequestInterface::SETUP_HOST => $Request->setHost($value),
                RequestInterface::SETUP_METHOD => $Request->setMethod($value),
                RequestInterface::SETUP_HEADERS => $Request->addHeaders($value),
                RequestInterface::SETUP_DATA => $Request->setData($value),
                RequestInterface::SETUP_CURL_OPTIONS => $Request->setCurlOptions($value),
                RequestInterface::SETUP_CURL_INFO => $Request->setCurlInfo($value),
                RequestInterface::SETUP_CONTENT_TYPE => $Request->setContentType($value),
            };
        }

        return $Request;
    }

    /**
     * Установка POST-полей
     *
     * @param Request $Request
     *
     * @return $this
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testUpdatePostFields()
     *
     * @tag #knockKnock #update #post
     */
    private function updatePostFields(Request $Request): static
    {
        $data = $Request->data;

        if ($data && count($data)) {
            if ($Request->method === Method::GET) {
                $Request->prepareEndpoint();

            } else {

                $data = match ($Request->contentType) {
                    ContentType::JSON => json_encode($data),
                    ContentType::FORM => http_build_query($data),
                    default => null,
                };

                if ($data) {
                    $Request->setCurlOption(CURLOPT_POSTFIELDS, $data);
                }
            }
        }

        return $this;
    }

    /**
     * Установка метода запроса
     *
     * @param Request $Request
     *
     * @return $this
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testUpdateMethod()
     *
     * @tag #knockKnock #update #method
     */
    private function updateMethod(Request $Request): static
    {
        $method = $Request->method;

        if ($method !== Method::GET) {
            $this->getterRealRequest()->setMethod($method);
        }

        return $this;
    }


    // --- Other ---

    /**
     * Подготовка общих параметров запроса
     *
     * @param array $commonRequestParams
     *
     * @return Request
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testConstructor()
     *
     * @tag #knockKnock #prepare #common #request
     */
    private function prepareCommonRequestParams(array $commonRequestParams): Request
    {
        $domainSeparator = '://';

        if (str_contains($this->_host, $domainSeparator)) {
            [$protocol, $this->_host] = explode($domainSeparator, $this->_host);

            $commonRequestParams = array_merge(
                [
                    RequestInterface::SETUP_PROTOCOL => $protocol
                ],
                $commonRequestParams
            );
        }

        if (!self::validateHostName($this->_host)) {
            throw new InvalidHostException("Хост `$this->_host` не валиден");
        }

        $commonRequestParams = array_merge(
            [
                RequestInterface::SETUP_PROTOCOL => Request::PROTOCOL_HTTP,
                RequestInterface::SETUP_HOST => $this->_host,
            ],
            $commonRequestParams
        );

        return new Request(null, $commonRequestParams);
    }

    /**
     * Валидация метода
     *
     * @param string $methodName
     *
     * Test: @return bool
     *
     * @see KnockKnockTest::testValidateMethod()
     *
     * @tag #validate #method
     */
    private function validateMethod(string $methodName): bool
    {
        if (empty($methodName)) return false;

        return in_array($methodName, [
            Method::GET,
            Method::POST,
            Method::PUT,
            Method::DELETE,
            Method::PATCH,
            Method::OPTIONS,
            Method::HEAD,
            Method::TRACE
        ]);
    }
}