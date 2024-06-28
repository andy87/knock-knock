<?php declare(strict_types=1);

/**
 * @name: knock-knock
 * @author Andrey and_y87 Kidin
 * @description PHP Фасад\Адаптер для отправки запросов через ext cURL
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.3.2
 */

namespace andy87\knock_knock\core;

use andy87\knock_knock\lib\{Method, ContentType};
use andy87\knock_knock\exception\operator\{EventUpdateException, InvalidMethodException};
use andy87\knock_knock\interfaces\{HandlerInterface, RequestInterface, ResponseInterface};
use andy87\knock_knock\exception\{InvalidHostException,
    InvalidEndpointException,
    ParamUpdateException,
    ParamNotFoundException};
use andy87\knock_knock\exception\request\{InvalidHeaderException,
    InvalidRequestException,
    RequestCompleteException,
    StatusNotFoundException};

/**
 * Class Operator
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
class Operator implements HandlerInterface
{
    /** @var ?Operator $_instance Объект для реализации Singleton */
    protected static ?Operator $_instance = null;


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
     * @tag #knockHandler #magic #construct
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
     * @tag #knockHandler #init
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
     * @tag #knockHandler #magic #get
     */
    public function __get(string $paramName): mixed
    {
        return match ($paramName) {
            self::PARAM_HOST => $this->getterHost(),
            self::PARAM_COMMON_REQUEST => $this->getterCommonRequest(),
            self::PARAM_REAL_REQUEST => $this->getterRealRequest(),
            self::PARAM_EVENT_HANDLERS => $this->getterEvents(),
            self::PARAM_LOGS => $this->getterLogs(),
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
     * @tag #knockHandler #get #params
     */
    public function getParams(): array
    {
        return [
            self::PARAM_HOST => $this->_host,
            self::PARAM_COMMON_REQUEST => $this->_commonRequest,
            self::PARAM_REAL_REQUEST => $this->_realRequest,
            self::PARAM_EVENT_HANDLERS => $this->_eventHandlers,
        ];
    }




    // --- Construct ---

    /**
     * Конструктор запроса
     *
     * @param string $method
     * @param string $endpoint
     * @param array $requestConfig
     *
     * @return Request
     *
     * @throws InvalidEndpointException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidMethodException
     *
     * Test: @see KnockKnockTest::testConstruct()
     *
     * @tag #knockHandler #construct #request
     */
    public function constructRequest(string $method, string $endpoint, array $requestConfig = []): Request
    {
        if ($this->validateMethod($method)) {
            if (empty($endpoint)) throw new InvalidEndpointException();

            $requestConfig = array_merge([
                RequestInterface::SETUP_HOST => $this->_host,
                RequestInterface::SETUP_METHOD => $method,
            ], $requestConfig);

            $commonRequestParams = $this->getterCommonRequest()->params;

            $requestParams = array_merge($requestConfig, $commonRequestParams);

            $request = new Request($endpoint, $requestParams);

            $this->event(self::EVENT_CONSTRUCT_REQUEST, [$this, $request]);

            return $request;
        }

        throw new InvalidMethodException();
    }

    /**
     * Конструктор ответа
     *
     * @param array $responseParams
     * @param ?Request $request
     *
     * @return Response
     *
     * Test: @see KnockKnockTest::testConstruct()
     *
     * @tag #knockHandler #construct #response
     */
    public function constructResponse(array $responseParams, ?Request $request = null): Response
    {
        $responseParams = $request->fakeResponse ?? $responseParams;

        $content = $responseParams[ResponseInterface::CONTENT] ?? null;

        $httpCode = $responseParams[ResponseInterface::HTTP_CODE] ?? ResponseInterface::OK;

        $response = new Response($content, $httpCode, $request);

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
    public function setupHost(string $host): static
    {
        $this->_host = $host;

        if (empty($this->_host)) {
            throw new InvalidHostException();
        }

        return $this;
    }

    /**
     * @param Request $request
     * @param array $options
     *
     * @return $this
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException
     *
     * Test: @see KnockKnockTest::testSetupRequest()
     *
     * @tag #knockHandler #setup #request
     */
    public function setupRequest(Request $request, array $options = []): static
    {
        if (count($options)) {
            $request = $this->updateRequestParams($request, $options);
        }

        $this->_realRequest = $request;

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
     * @tag #knockHandler #setup #event #callback #behavior
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
     * @param ?RequestInterface $request
     *
     * @return Response
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException|RequestCompleteException|InvalidRequestException
     *
     * Test: @see KnockKnockTest::testSendRequest()
     *
     * @tag #knockHandler #send #response
     */
    public function send(?RequestInterface $request = null): Response
    {
        if ($request) $this->setupRequest($request);

        if ($this->_realRequest) {
            return $this->sendRequest($this->_realRequest);
        }

        throw new InvalidRequestException();
    }

    /**
     * Получение ответа на отправку запроса через cURL
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testSendRequest()
     *
     * @tag #knockHandler #send #request #query #response
     */
    public function getResponseOnSendCurlRequest(Request $request): Response
    {
        $ch = curl_init($request->url);

        $curlParams = $request->curlParams;

        $options = $curlParams[RequestInterface::SETUP_CURL_OPTIONS];

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        $curlInfoList = $curlParams[RequestInterface::SETUP_CURL_INFO];

        if (count($curlInfoList)) {
            $curlInfo = [];

            foreach ($curlInfoList as $info) {
                $curlInfo[$info] = curl_getinfo($ch, $info);
            }

            $request->setCurlInfo($curlInfo);
        }

        $response = [
            ResponseInterface::CONTENT => $response,
            ResponseInterface::HTTP_CODE => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        ];

        $response = $this->constructResponse($response, $request);

        if ($error = curl_error($ch)) {
            $response->addError($error);
        }

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
     * @tag #knockHandler #behavior #event #callback
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
     * @tag #knockHandler #behavior #event #callback
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
     * @tag #knockHandler #behavior #event #callback
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
     * @tag #knockHandler #behavior #event #callback
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
     * @tag #knockHandler #disable #ssl
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
     * @tag #knockHandler #enable #ssl
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
     * @tag #knockHandler #enable #redirect
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
     * @tag #knockHandler #use #cookie
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
     * @tag #knockHandler #validate #host
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
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testGetInstance()
     *
     * @tag #knockHandler #get #instance
     */
    public static function getInstance(string $host = null, array $commonRequestParams = []): static
    {
        if (static::$_instance === null) {
            static::$_instance = new Operator($host, $commonRequestParams);
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
     * @tag #knockHandler #get #host
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
     * @tag #knockHandler #request #common
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
     * @tag #knockHandler #request #real
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
     * @param Request $request
     *
     * @return Response
     *
     * @throws RequestCompleteException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testSendRequest()
     *
     * @tag #knockHandler #send #request
     */
    protected function sendRequest(Request $request): Response
    {
        if ($request->statusIsComplete()) {
            throw new RequestCompleteException();

        } else {

            $request->setupStatusProcessing();

            $this
                ->updatePostFields($request)
                ->updateMethod($request)
                ->event(self::EVENT_BEFORE_SEND, [$this, $request]);

            $response = ($request->fakeResponse)
                ? $this->constructResponse($request->fakeResponse, $request)
                : $this->getResponseOnSendCurlRequest($request);

            $request->setupStatusComplete();

            return $response;
        }
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
     * @tag #knockHandler #behavior #event #callback
     */
    public function event(string $eventKey, array $args = []): void
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
     * @param Request $request
     * @param array $params
     *
     * @return Request
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidHeaderException
     *
     * Test: @see KnockKnockTest::testUpdateRequestParams()
     *
     * @tag #knockHandler #update #request
     */
    private function updateRequestParams(Request $request, array $params): Request
    {
        foreach ($params as $key => $value) {
            match ($key) {
                RequestInterface::SETUP_PROTOCOL => $request->setProtocol($value),
                RequestInterface::SETUP_HOST => $request->setHost($value),
                RequestInterface::SETUP_METHOD => $request->setMethod($value),
                RequestInterface::SETUP_HEADERS => $request->addHeaders($value),
                RequestInterface::SETUP_DATA => $request->setData($value),
                RequestInterface::SETUP_CURL_OPTIONS => $request->setCurlOptions($value),
                RequestInterface::SETUP_CURL_INFO => $request->setCurlInfo($value),
                RequestInterface::SETUP_CONTENT_TYPE => $request->setContentType($value),
            };
        }

        return $request;
    }

    /**
     * Установка POST-полей
     *
     * @param Request $request
     *
     * @return $this
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testUpdatePostFields()
     *
     * @tag #knockHandler #update #post
     */
    private function updatePostFields(Request $request): static
    {
        $data = $request->data;

        if ($data && count($data)) {
            if ($request->method === Method::GET) {
                $request->prepareEndpoint();

            } else {

                $data = match ($request->contentType) {
                    ContentType::JSON => json_encode($data),
                    ContentType::FORM => http_build_query($data),
                    default => null,
                };

                if ($data) {
                    $request->setCurlOption(CURLOPT_POSTFIELDS, $data);
                }
            }
        }

        return $this;
    }

    /**
     * Установка метода запроса
     *
     * @param Request $request
     *
     * @return $this
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockTest::testUpdateMethod()
     *
     * @tag #knockHandler #update #method
     */
    private function updateMethod(Request $request): static
    {
        $method = $request->method;

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
     * @tag #knockHandler #prepare #common #request
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

        if (self::validateHostName($this->_host)) {
            $commonRequestParams = array_merge(
                [
                    RequestInterface::SETUP_PROTOCOL => Request::PROTOCOL_HTTP,
                    RequestInterface::SETUP_HOST => $this->_host,
                ],
                $commonRequestParams
            );

            return new Request(null, $commonRequestParams);
        }

        throw new InvalidHostException("Хост `$this->_host` не валиден");

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