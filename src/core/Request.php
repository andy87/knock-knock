<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Компонент содержащий параметры запроса
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.2.1
 */

declare(strict_types=1);

namespace andy87\knock_knock\core;

use andy87\knock_knock\lib\Method;
use andy87\knock_knock\interfaces\RequestInterface;
use andy87\knock_knock\exception\request\{ InvalidHeaderException, InvalidProtocolException, StatusNotFoundException };
use andy87\knock_knock\exception\{ InvalidEndpointException, InvalidHostException, ParamUpdateException, ParamNotFoundException };

/**
 * Class Request
 *
 * @package andy87\knock_knock\query
 *
 * @property-read array $status_id
 * @property-read array $statusLabel
 *
 * @property-read array $params
 *
 * @property-read string $protocol
 * @property-read string $host
 * @property-read string $endpoint
 * @property-read string $url
 * @property-read string $method
 * @property-read array $headers
 * @property-read string $contentType
 * @property-read mixed $data
 * @property-read mixed $postFields
 * @property-read array $curlParams
 * @property-read array $curlOptions
 * @property-read array $curlInfo
 *
 * @property-read array $fakeResponse
 * @property-read array $errors
 *
 * Покрытие тестами: 100%. @see RequestTest
 */
class Request implements RequestInterface
{
    /** @var string Протокол */
    public const PROTOCOL_HTTP = 'http';
    /** @var string Безопасный протокол*/
    public const PROTOCOL_HTTPS = 'https';


    public const STATUS_ID = 'status_id';
    public const STATUS_LABEL = 'statusLabel';
    public const CURL_PARAMS = 'curlParams';
    public const PARAMS = 'params';


    /** @var array */
    public const LABELS_STATUS = [
        self::STATUS_PREPARE => 'новый запрос',
        self::STATUS_PROCESSING => 'запрос отправляется',
        self::STATUS_COMPLETE => 'ответ получен'
    ];


    /** @var int $_statusID Статус запроса */
    private int $_statusID = self::STATUS_PREPARE;


    /** @var string $_protocol Протокол */
    private string $_protocol;

    /** @var string $_host Хост */
    private string $_host;


    /** @var string $_endpoint endpoint запроса */
    private string $_endpoint;

    /** @var string $_method Метод запроса */
    private string $_method;
    /** @var string $_contentType Тип контента */
    private string $_contentType;
    /** @var array $_headers Заголовки */
    private array $_headers = [];
    /** @var mixed $_data Данные передаваемые запросом */
    private mixed $_data;

    /** @var ?array $_fakeResponse Фэйковые данные ответа */
    private ?array $_fakeResponse = null;

    /** @var array $_errors Ошибки */
    private array $_errors = [];


    /** @var array $curl Параметры curl */
    private array $_curlParams = [
        self::SETUP_CURL_INFO => [],
        self::SETUP_CURL_OPTIONS => []
    ];


    /**
     * Request конструктор.
     *
     * @param ?string $endpoint
     * @param array $params
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testConstructor()
     *
     * @tag #request #constructor
     */
    public function __construct(?string $endpoint, array $params = [])
    {
        if ($endpoint) {
            $this->setEndpoint($endpoint);
        }

        if (count($params)) {
            $this->setupParamsFromArray($params);
        }

        $this->prepareHost();
    }

    /**
     * Магия для получения read-only свойств
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws StatusNotFoundException|InvalidHostException|InvalidEndpointException|InvalidProtocolException|ParamNotFoundException
     *
     * Test: @see RequestTest::testMagicGet()
     *
     * @tag #request #magic #get
     */
    public function __get(string $name): mixed
    {
        return match ($name) {

            self::STATUS_ID => $this->getterStatusID(),
            self::STATUS_LABEL => $this->getterStatusLabel(),

            self::SETUP_PROTOCOL => $this->getterProtocol(),
            self::SETUP_HOST => $this->getterHost(),
            self::SETUP_ENDPOINT => $this->getterEndpoint(),

            'url' => $this->getterUrl(),

            self::SETUP_METHOD => $this->getterMethod(),
            self::SETUP_CONTENT_TYPE => $this->getterContentType(),
            self::SETUP_HEADERS => $this->getterHeaders(),
            self::SETUP_DATA => $this->getterData(),
            self::CURL_PARAMS => $this->getterCurlParams(),
            self::SETUP_CURL_OPTIONS => $this->getterCurlOptions(),
            self::SETUP_CURL_INFO => $this->getterCurlInfo(),

            self::SETUP_POST_FIELD => $this->getterPostFields(),

            self::PARAMS => $this->getterParams(),

            'fakeResponse' => $this->getterFakeResponse(),
            'errors' => $this->getterErrors(),

            default => throw new ParamNotFoundException("Свойство `$name` не найдено в классе " . __CLASS__),
        };
    }


    /**
     * Получение URL
     *
     * @return string
     *
     * @throws InvalidHostException|InvalidEndpointException|InvalidProtocolException
     *
     * Test: @see RequestTest::testConstructUrlOnGet()
     *
     * @tag #request #get #url
     */
    public function constructUrl(): string
    {
        if ($this->_protocol && $this->_host && $this->_endpoint) {
            $this->prepareHost();
            $this->prepareEndpoint();

            $address = trim($this->_host . '/' . $this->_endpoint);
            $address = str_replace(['//', '///'], '/', $address);

            $query = '';

            if (isset($this->_data) && Method::GET === $this->_method) {
                if (is_array($this->_data)) {
                    $query = http_build_query($this->_data);

                } elseif (is_string($this->_data)) {

                    $query = $this->_data;
                }

                if ($query) {
                    $query = trim($query, '?');
                    $query = trim($query, '&');

                    $symbol = (str_contains($address, '?')) ? '&' : '?';

                    $query = $symbol . $query;
                }
            }

            return $this->_protocol . '://' . $address . $query;

        } elseif (empty($this->_host)) {

            throw new InvalidHostException('Host must be set');

        } elseif (empty($this->_endpoint)) {

            throw new InvalidEndpointException('Endpoint must be set');

        } else {

            throw new InvalidProtocolException('Protocol must be set');
        }
    }

    /**
     * Подготовка endpoint
     *
     * @return void
     *
     * Test: @see RequestTest::testPrepareEndpointOnGet()
     *
     * @tag #request #endpoint #prepare
     */
    public function prepareEndpoint(): void
    {
        if (isset($this->_data) && count($this->_data)) {
            if ($this->_method === Method::GET) {
                $endpoint = $this->_endpoint;

                $query = http_build_query($this->_data);

                $endpoint = trim($endpoint, '?');
                $endpoint = trim($endpoint, '&');

                $symbol = (str_contains($endpoint, '?')) ? '&' : '?';

                $this->_endpoint = $endpoint . $symbol . $query;
                $this->_data = null;
            }
        }
    }



    // === Setters ===

    /**
     * Установка протокола
     *
     * @param string $protocol
     *
     * @return $this
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testSetProtocol()
     *
     * @tag #request #set #protocol
     */
    public function setProtocol(string $protocol): self
    {
        return $this->setParamsOnStatusPrepare(self::SETUP_PROTOCOL, $protocol);
    }

    /**
     * Установка хоста
     *
     * @param string $host
     *
     * @return $this
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testSetHost()
     *
     * @tag #request #set #host
     */
    public function setHost(string $host): self
    {
        return $this->setParamsOnStatusPrepare(self::SETUP_HOST, $host);
    }

    /**
     * Установка endpoint запроса
     *
     * @param string $endpoint
     *
     * @return $this
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testSetEndpoint()
     *
     * @tag #request #set #endpoint
     */
    public function setEndpoint(string $endpoint): self
    {
        return $this->setParamsOnStatusPrepare(self::SETUP_ENDPOINT, $endpoint);
    }

    /**
     * Установка метода запроса
     *
     * @param string $method
     *
     * @return $this
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testSetMethod()
     *
     * @tag #request #set #method
     */
    public function setMethod(string $method): self
    {
        return $this->setParamsOnStatusPrepare(self::SETUP_METHOD, $method);
    }

    /**
     * Установка заголовков
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     *
     * @throws InvalidHeaderException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testSetHeader()
     *
     * @tag #request #set #headers
     */
    public function setHeader(string $key, string $value): self
    {
        if (empty($key) || empty($value)) throw new InvalidHeaderException('Ключ и значение заголовка не могут быть пустыми.');

        $this->limiterIsComplete();

        $this->_headers[$key] = $value;

        return $this;

    }

    /**
     * Установка заголовка
     *
     * @param array $headers
     *
     * @return $this
     *
     * @throws InvalidHeaderException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testAddHeaders()
     *
     * @tag #request #add #headers
     */
    public function addHeaders(array $headers): self
    {
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }

        return $this;
    }

    /**
     * Установка типа контента
     *
     * @param string $contentType
     *
     * @return $this
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testSetContentType()
     *
     * @tag #request #set #contentType
     */
    public function setContentType(string $contentType): self
    {
        return $this->setParamsOnStatusPrepare(self::SETUP_CONTENT_TYPE, $contentType);
    }

    /**
     * Установка данных запроса
     *
     * @param mixed $data
     *
     * @return $this
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testSetData()
     *
     * @tag #request #set #data
     */
    public function setData(mixed $data): self
    {
        return $this->setParamsOnStatusPrepare(self::SETUP_DATA, $data);
    }

    /**
     * Установка параметров curl
     *
     * @param array $curlOptions
     *
     * @return $this
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testSetCurlOptions()
     *
     * @tag #request #set #curlOptions
     */
    public function setCurlOptions(array $curlOptions): self
    {
        $this->limiterIsComplete();

        $this->_curlParams[self::SETUP_CURL_OPTIONS] = $curlOptions;

        return $this;
    }

    /**
     * Добавление параметра curl
     *
     * @param int $key
     * @param mixed $value
     *
     * @return $this
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * @tag #request #add #curlOptions
     */
    public function setCurlOption(int $key, mixed $value): self
    {
        $this->limiterIsComplete();

        $this->_curlParams[self::SETUP_CURL_OPTIONS][$key] = $value;

        return $this;
    }

    /**
     * Добавление curl параметров
     *
     * @param array $curlOptions
     *
     * @return $this
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testAddCurlOptions()
     *
     * @tag #request #add #curlOptions
     */
    public function addCurlOptions(array $curlOptions): self
    {
        foreach ($curlOptions as $key => $value) {
            $this->setCurlOption($key, $value);
        }

        return $this;
    }

    /**
     * Установка информации о запросе
     *
     * @param array $curlInfo
     *
     * @return $this
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testSetCurlInfo()
     *
     * @tag #request #set #curlInfo
     */
    public function setCurlInfo(array $curlInfo): self
    {
        $this->limiterIsComplete();

        $this->_curlParams[self::SETUP_CURL_INFO] = $curlInfo;

        return $this;
    }

    /**
     * @param array $array
     *
     * @return $this
     *
     * @throws ParamUpdateException|StatusNotFoundException
     *
     * Test: @see RequestTest::testSetFakeResponse()
     *
     * @tag #request #setup #fakeResponse
     */
    public function setFakeResponse(array $array): static
    {
        $this->limiterIsComplete();

        $this->_fakeResponse = $array;

        return $this;
    }

    /**
     * @param string $curlError
     * @param ?string $key
     *
     * @return $this
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testAddError()
     *
     * @tag #request #add #errors
     *
     */
    public function addError(string $curlError, ?string $key = null): self
    {
        $this->limiterIsComplete();

        if ($key) {

            $this->_errors[$key] = $curlError;

        } else {

            $this->_errors[] = $curlError;
        }

        return $this;
    }

    // --- Status ---

    /**
     * Маркировка запроса как выполненного
     *
     * @return $this
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testSetupStatusComplete()
     *
     * @tag #request #set #status #processing
     */
    public function setupStatusProcessing(): self
    {
        return $this->setStatus(self::STATUS_PROCESSING);
    }

    /**
     * Маркировка запроса как выполненного
     *
     * @return $this
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testSetupStatusComplete()
     *
     * @tag #request #set #status #complete
     */
    public function setupStatusComplete(): self
    {
        return $this->setStatus(self::STATUS_COMPLETE);
    }

    /**
     * Проверка состояния запроса на значении не равное "завершён"
     *
     * @return bool
     *
     * Test: @see RequestTest::testStatusIsComplete()
     *
     * @tag #request #status #not_complete
     */
    public function statusIsComplete(): bool
    {
        return $this->statusIs(self::STATUS_COMPLETE);
    }

    /**
     * Проверка состояния запроса на значении "подготовка"
     *
     * @return bool
     *
     * Test: @see RequestTest::testStatusIsPrepare()
     *
     * @tag #request #status #prepare
     */
    public function statusIsPrepare(): bool
    {
        return $this->statusIs(self::STATUS_PREPARE);
    }



    // === SSL ===

    /**
     * Отключение SSL сертификата
     *
     * @param bool $verifyPeer проверка подлинность сертификата сервера
     * @param int $verifyHost проверка соответствия имени хоста сервера и имени, указанного в сертификате сервера
     *
     * @return $this
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testDisableSSL()
     *
     * @tag #request #ssl #disable
     */
    public function disableSSL(bool $verifyPeer = false, int $verifyHost = 0): self
    {
        $this->setCurlOption(CURLOPT_SSL_VERIFYPEER, $verifyPeer);
        $this->setCurlOption(CURLOPT_SSL_VERIFYHOST, $verifyHost);

        return $this;
    }

    /**
     * Включение SSL сертификата
     *
     * @param bool $verifyPeer проверка подлинность сертификата сервера
     * @param int $verifyHost проверка соответствия имени хоста сервера и имени, указанного в сертификате сервера
     *
     * @return $this
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testEnableSSL()
     *
     * @tag #request #ssl #enable
     */
    public function enableSSL(bool $verifyPeer = true, int $verifyHost = 2): self
    {
        $this->setCurlOption(CURLOPT_SSL_VERIFYPEER, $verifyPeer);
        $this->setCurlOption(CURLOPT_SSL_VERIFYHOST, $verifyHost);

        return $this;
    }

    /**
     * Получение копии объекта в статусе "подготовка"
     *
     * @return Request
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testClone()
     *
     * @tag #request #clone
     */
    public function clone(): Request
    {
        return new Request($this->_endpoint, $this->getterParams());
    }



    // === Private ===

    /**
     * Проверка на завершённость запроса, если запрос завершён, то выбрасывается исключение
     *
     * @param ?string $message
     *
     * @return void
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testLimiterIsComplete()
     *
     * @tag #request #limiter #complete
     */
    public function limiterIsComplete(?string $message = null): void
    {
        if ($this->statusIsComplete()) {
            $label = $this->getterStatusLabel($this->_statusID);

            $message = $message ?? "Вы не можете изменять параметры запроса в статусе: $label";

            throw new ParamUpdateException($message);
        }
    }

    /**
     * Заполнение параметров запроса из массива данных
     *
     * @param array $params
     *
     * @return void
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testSetupParamsFromArray()
     *
     * @tag #request #setup #paramList #on_status
     */
    private function setupParamsFromArray(array $params): void
    {
        foreach ($params as $param => $value) {
            if ($value && (!isset($this->$param) || $this->$param !== $value)) {
                $this->setParamsOnStatusPrepare($param, $value);
            }
        }
    }

    /**
     * Заполнение параметра запроса при условии, что запрос ещё не выполнялся
     *
     * @param string $param
     * @param $value
     *
     * @return self
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testSetParamsOnStatusPrepare()
     *
     * @tag #request #set #param #on_status
     */
    private function setParamsOnStatusPrepare(string $param, $value): self
    {
        $this->limiterIsComplete();

        switch ($param) {

            case self::SETUP_HOST:
                $this->_host = $value;
                $this->prepareHost();
                break;

            case self::SETUP_METHOD:
                $this->setCurlOption(CURLOPT_CUSTOMREQUEST, $value);
                $this->_method = $value;
                break;

            case self::STATUS_ID: $this->_statusID = $value; break;
            case self::SETUP_PROTOCOL: $this->_protocol = $value; break;
            case self::SETUP_ENDPOINT: $this->_endpoint = $value; break;
            case self::SETUP_CONTENT_TYPE: $this->_contentType = $value; break;
            case self::SETUP_HEADERS: $this->_headers = $value; break;
            case self::SETUP_DATA:$this->_data = $value; break;
            case self::CURL_PARAMS: $this->_curlParams = $value; break;
            case self::SETUP_CURL_OPTIONS: $this->_curlParams[self::SETUP_CURL_OPTIONS] = $value; break;
            case self::SETUP_CURL_INFO: $this->_curlParams[self::SETUP_CURL_INFO] = $value; break;

            default:
                throw new ParamNotFoundException("неизвестный параметр запроса `$param`");
        }

        return $this;
    }

    /**
     * Проверка статуса запроса на соответствие переданному значению
     *
     * @param int $status_id
     *
     * @return bool
     *
     * Test: @see RequestTest::testStatusIsComplete()
     * Test: @see RequestTest::testStatusIsPrepare()
     */
    private function statusIs(int $status_id): bool
    {
        return $this->getterStatusID() === $status_id;
    }

    /**
     * Установка статуса запроса, на значение переданное в параметре
     *
     * @param int $status
     *
     * @return $this
     *
     * @throws StatusNotFoundException|ParamUpdateException
     *
     * Test: @see RequestTest::testSetupStatusComplete()
     *
     * @tag #request #set #status
     */
    private function setStatus(int $status): self
    {
        $this->limiterIsComplete("Запрос уже был отправлен: вы не можете изменять статус запроса на `$status`");

        $this->_statusID = $status;

        return $this;
    }

    /**
     * Подготовка хоста
     *
     * @return void
     *
     * Test: @see RequestTest::testPrepareHost()
     *
     * @tag #request #host #prepare
     */
    private function prepareHost(): void
    {
        if (isset($this->_host)) {
            $separator = '://';

            if (str_contains($this->_host, $separator)) {
                [$this->_protocol, $this->_host] = explode($separator, $this->_host);
            }
        }
    }


    // --- getters 4 magic ---

    /**
     * @return int
     *
     * Test: @see RequestTest::testMagicGet()
     *
     * @tag #request #get #status
     */
    private function getterStatusID(): int
    {
        return $this->_statusID;
    }

    /**
     * Возвращает метку статуса
     *
     * @param ?int $status_id
     *
     * @return string
     *
     * @throws StatusNotFoundException
     *
     * Test: @see RequestTest::testGetStatusLabel()
     *
     * @tag #request #get #status
     */
    private function getterStatusLabel(?int $status_id = null): string
    {
        $status_id = $status_id ?? $this->getterStatusID();

        if (isset(self::LABELS_STATUS[$status_id])) {
            return self::LABELS_STATUS[$status_id];
        }

        throw new StatusNotFoundException('Unknown status');
    }


    /**
     * Получение параметров запроса
     *
     * @return array
     *
     *
     *
     * Test: @see RequestTest::testGetParams()
     *
     * @tag #request #get #params
     */
    private function getterParams(): array
    {
        $params = [
            self::SETUP_PROTOCOL => $this->getterProtocol(),
            self::SETUP_HOST => $this->getterHost(),
            self::SETUP_ENDPOINT => $this->getterEndpoint(),

            self::SETUP_METHOD => $this->getterMethod(),
            self::SETUP_HEADERS => $this->getterHeaders(),
            self::SETUP_CONTENT_TYPE => $this->getterContentType(),

            self::SETUP_DATA => $this->getterData(),

            self::SETUP_CURL_OPTIONS => $this->getterCurlOptions(),
            self::SETUP_CURL_INFO => $this->getterCurlInfo(),
        ];

        foreach ($params as $setupKey => $value) {
            if (empty($value)) {
                unset($params[$setupKey]);
            }
        }

        return $params;
    }


    /**
     * Получение протокола
     *
     * @return ?string
     *
     * Test: @see RequestTest::testMagicGet()
     *
     * @tag #request #get #protocol
     */
    private function getterProtocol(): ?string
    {
        return $this->_protocol ?? null;
    }

    /**
     * Получение хоста
     *
     * @return ?string
     *
     * Test: @see RequestTest::testMagicGet()
     *
     * @tag #request #get #host
     */
    private function getterHost(): ?string
    {
        return $this->_host ?? null;
    }

    /**
     * Получение endpoint запроса
     *
     * @return ?string
     *
     * Test: @see RequestTest::testMagicGet()
     *
     * @tag #request #get #endpoint
     */
    private function getterEndpoint(): ?string
    {
        return $this->_endpoint ?? null;
    }

    /**
     * Получение url
     *
     * @return ?string
     *
     * @throws InvalidHostException|InvalidEndpointException|InvalidProtocolException
     *
     * Test: @see RequestTest::testMagicGet()
     *
     * @tag #request #get #url
     */
    private function getterUrl(): ?string
    {
        return $this->constructUrl();
    }


    /**
     * Получение метода запроса
     *
     * @return ?string
     *
     * Test: @see RequestTest::testMagicGet()
     *
     * @tag #request #get #method
     */
    private function getterMethod(): ?string
    {
        return $this->_method ?? null;
    }

    /**
     * Получение заголовков
     *
     * @return array
     *
     * Test: @see RequestTest::testMagicGet()
     *
     * @tag #request #get #headers
     */
    private function getterHeaders(): array
    {
        return $this->_headers;
    }

    /**
     * Получение типа контента
     *
     * @return ?string
     *
     * Test: @see RequestTest::testMagicGet()
     *
     * @tag #request #get #contentType
     */
    private function getterContentType(): ?string
    {
        return $this->_contentType ?? null;
    }


    /**
     * Получение данных запроса
     *
     * @return mixed
     *
     * Test: @see RequestTest::testMagicGet()
     *
     * @tag #request #get #data
     */
    private function getterData(): mixed
    {
        return $this->_data ?? null;
    }

    /**
     * Получение данных запроса преобразованных компонентом
     *
     * @return mixed
     *
     * Test: @see RequestTest::testMagicGet()
     *
     * @tag #request #get #data #postFields
     */
    private function getterPostFields(): mixed
    {
        $curlOptions = $this->getterCurlOptions();

        return $curlOptions[CURLOPT_POSTFIELDS] ?? null;
    }

    /**
     * Получение параметров curl
     *
     * @return array
     *
     * Test: @see RequestTest::testMagicGet()
     *
     * @tag #request #get #curlParams
     */
    private function getterCurlParams(): array
    {
        return $this->_curlParams;
    }

    /**
     * Получение параметров curl
     *
     * @return ?array
     *
     * Test: @see RequestTest::testMagicGet()
     *
     * @tag #request #get #curlOptions
     */
    private function getterCurlOptions(): ?array
    {
        return $this->_curlParams[self::SETUP_CURL_OPTIONS];
    }

    /**
     * Получение информации о запросе
     *
     * @return array
     *
     * Test: @see RequestTest::testMagicGet()
     *
     * @tag #request #get #curlInfo
     */
    private function getterCurlInfo(): array
    {
        return $this->_curlParams[self::SETUP_CURL_INFO];
    }

    /**
     * Получение фэйковых данных ответа
     *
     * @return ?array
     *
     * Test: @see RequestTest::testMagicGet()
     */
    private function getterFakeResponse(): ?array
    {
        return $this->_fakeResponse;
    }

    /**
     * @return array
     *
     * Test: @see RequestTest::testGetErrors()
     *
     * @tag #request #get #errors
     */
    private function getterErrors(): array
    {
        return $this->_errors;
    }
}