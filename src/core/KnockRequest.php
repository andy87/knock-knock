<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Компонент содержащий параметры запроса
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 0.99c
 */

declare(strict_types=1);

namespace andy87\knock_knock\core;

use Exception;
use andy87\knock_knock\{ lib\LibKnockMethod, interfaces\KnockRequestInterface };

/**
 * Class KnockRequest
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
 * @property-read array $errors
 *
 * Покрытие тестами: 100%. @see KnockRequestTest
 */
class KnockRequest implements KnockRequestInterface
{
    public const URL = 'url';
    public const ERRORS = 'errors';
    public const STATUS_ID = 'status_id';
    public const STATUS_LABEL = 'statusLabel';
    public const CURL_PARAMS = 'curlParams';
    public const PARAMS = 'params';



    public const PROTOCOL_HTTP = 'http';
    public const PROTOCOL_HTTPS = 'https';

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
    /** @var mixed $_data Данные запроса */
    private mixed $_data;

    /** @var array $_errors Ошибки */
    private array $_errors = [];


    /** @var array $curl Параметры curl */
    private array $_curlParams = [
        self::SETUP_CURL_INFO => [],
        self::SETUP_CURL_OPTIONS => []
    ];



    /**
     * KnockRequest конструктор.
     *
     * @param ?string $endpoint
     * @param array $params
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testConstructor()
     *
     * @tag #constructor
     */
    public function __construct( ?string $endpoint , array $params = [] )
    {
        if ( $endpoint ) {
            $this->setEndpoint( $endpoint );
        }

        if ( count($params) ) {
            $this->setupParamsFromArray( $params );
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
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testMagicGet()
     *
     * @tag #magic #get
     */
    public function __get( string $name ): mixed
    {
        return match ( $name ) {

            self::STATUS_ID => $this->getStatusID(),
            self::STATUS_LABEL => $this->getStatusLabel(),

            self::SETUP_PROTOCOL => $this->getProtocol(),
            self::SETUP_HOST => $this->getHost(),
            self::SETUP_ENDPOINT => $this->getEndpoint(),

            self::URL => $this->getUrl(),

            self::SETUP_METHOD => $this->getMethod(),
            self::SETUP_CONTENT_TYPE => $this->getContentType(),
            self::SETUP_HEADERS => $this->getHeaders(),
            self::SETUP_DATA => $this->getData(),
            self::CURL_PARAMS => $this->getCurlParams(),
            self::SETUP_CURL_OPTIONS => $this->getCurlOptions(),
            self::SETUP_CURL_INFO => $this->getCurlInfo(),

            self::SETUP_POST_FIELD => $this->getPostFields(),

            self::PARAMS => $this->getParams(),

            self::ERRORS => $this->getErrors(),

            default => throw new Exception("Property `$name`not found on: " . __CLASS__),
        };
    }


    /**
     * Получение URL
     *
     * @return string
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testConstructUrlOnGet()
     *
     * @tag #get #url
     */
    public function constructUrl(): string
    {
        if ( $this->_protocol && $this->_host && $this->_endpoint )
        {
            $this->prepareHost();
            $this->prepareEndpoint();

            $address = trim($this->_host . '/' . $this->_endpoint);
            $address = str_replace( ['//','///'], '/', $address );

            $query = '';

            if ( isset($this->_data) && LibKnockMethod::GET === $this->_method )
            {
                if ( is_array($this->_data) )
                {
                    $query = http_build_query( $this->_data );

                } elseif ( is_string($this->_data) ){

                    $query = $this->_data;
                }

                if ( $query )
                {
                    $query = trim( $query, '?' );
                    $query = trim( $query, '&' );

                    $symbol = ( str_contains($address, '?') ) ? '&' : '?';

                    $query = $symbol . $query;
                }
            }

            return $this->_protocol . '://' . $address . $query;

        } elseif( !$this->_host ) {

            throw new Exception('Host is not set');

        } elseif( !$this->_endpoint ) {

            throw new Exception('Endpoint is not set');

        } else {

            throw new Exception('Protocol is not set');
        }
    }

    /**
     * Подготовка endpoint
     *
     * @return void
     *
     * Test: @see KnockRequestTest::testPrepareEndpointOnGet()
     *
     * @tag #endpoint #prepare
     */
    public function prepareEndpoint(): void
    {
        if ( isset($this->_data) && count($this->_data) )
        {
            if ( $this->_method === LibKnockMethod::GET )
            {
                $endpoint = $this->_endpoint;

                $query = http_build_query( $this->_data );

                $endpoint = trim( $endpoint, '?' );
                $endpoint = trim( $endpoint, '&' );

                $symbol = ( str_contains($endpoint, '?') ) ? '&' : '?';

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
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testSetProtocol()
     *
     * @tag #set #protocol
     */
    public function setProtocol( string $protocol ): self
    {
        return $this->setParamsOnStatusPrepare( self::SETUP_PROTOCOL, $protocol );
    }

    /**
     * Установка хоста
     *
     * @param string $host
     *
     * @return $this
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testSetHost()
     *
     * @tag #set #host
     */
    public function setHost(string $host ): self
    {
        return $this->setParamsOnStatusPrepare( self::SETUP_HOST, $host );
    }

    /**
     * Установка endpoint запроса
     *
     * @param string $endpoint
     *
     * @return $this
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testSetEndpoint()
     *
     * @tag #set #endpoint
     */
    public function setEndpoint( string $endpoint ): self
    {
        return $this->setParamsOnStatusPrepare( self::SETUP_ENDPOINT, $endpoint );
    }

    /**
     * Установка метода запроса
     *
     * @param string $method
     *
     * @return $this
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testSetMethod()
     *
     * @tag #set #method
     */
    public function setMethod( string $method ): self
    {
        return $this->setParamsOnStatusPrepare( self::SETUP_METHOD, $method );
    }

    /**
     * Установка заголовков
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testSetHeader()
     *
     * @tag #set #headers
     */
    public function setHeader( string $key, string $value ): self
    {
        $this->limiterIsComplete();

        $this->_headers[ $key ] = $value;

        return $this;

    }

    /**
     * Установка заголовка
     *
     * @param array $headers
     *
     * @return $this
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testAddHeaders()
     *
     * @tag #add #headers
     */
    public function addHeaders( array $headers ): self
    {
        foreach ($headers as $key => $value )
        {
            $this->setHeader( $key, $value );
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
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testSetContentType()
     *
     * @tag #set #contentType
     */
    public function setContentType( string $contentType ): self
    {
        return $this->setParamsOnStatusPrepare( self::SETUP_CONTENT_TYPE, $contentType );
    }

    /**
     * Установка данных запроса
     *
     * @param mixed $data
     *
     * @return $this
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testSetData()
     *
     * @tag #set #data
     */
    public function setData( mixed $data ): self
    {
        return $this->setParamsOnStatusPrepare( self::SETUP_DATA, $data );
    }

    /**
     * Установка параметров curl
     *
     * @param array $curlOptions
     *
     * @return $this
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testSetCurlOptions()
     *
     * @tag #set #curlOptions
     */
    public function setCurlOptions( array $curlOptions ): self
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
     * @throws Exception
     *
     * @tag #add #curlOptions
     */
    public function setCurlOption( int $key, mixed $value ): self
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
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testAddCurlOptions()
     *
     * @tag #add #curlOptions
     */
    public function addCurlOptions( array $curlOptions ): self
    {
        foreach ( $curlOptions as $key => $value ) {
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
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testSetCurlInfo()
     *
     * @tag #set #curlInfo
     */
    public function setCurlInfo( array $curlInfo ): self
    {
        $this->limiterIsComplete();

        $this->_curlParams[self::SETUP_CURL_INFO] = $curlInfo;

        return $this;
    }

    /**
     * @param string $curlError
     * @param ?string $key
     *
     * @return $this
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testAddError()
     *
     * @tag #add #errors
     *
     */
    public function addError( string $curlError, ?string $key = null ): self
    {
        $this->limiterIsComplete();

        if ( $key ) {

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
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testSetupStatusComplete()
     *
     * @tag #set #status #processing
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
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testSetupStatusComplete()
     *
     * @tag #set #status #complete
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
     * Test: @see KnockRequestTest::testStatusIsComplete()
     *
     * @tag #knockKnock #status #not_complete
     */
    public function statusIsComplete(): bool
    {
        return $this->statusIs( self::STATUS_COMPLETE );
    }

    /**
     * Проверка состояния запроса на значении "подготовка"
     *
     * @return bool
     *
     * Test: @see KnockRequestTest::testStatusIsPrepare()
     *
     * @tag #knockKnock #status #prepare
     */
    public function statusIsPrepare(): bool
    {
        return $this->statusIs( self::STATUS_PREPARE );
    }



    // === SSL ===

    /**
     * Отключение SSL сертификата
     *
     * @param bool $verifyPeer проверка подлинность сертификата сервера
     * @param int  $verifyHost проверка соответствия имени хоста сервера и имени, указанного в сертификате сервера
     *
     * @return $this
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testDisableSSL()
     *
     * @tag #knockKnock #ssl #disable
     */
    public function disableSSL( bool $verifyPeer = false, int $verifyHost = 0 ): self
    {
        $this->setCurlOption( CURLOPT_SSL_VERIFYPEER, $verifyPeer );
        $this->setCurlOption( CURLOPT_SSL_VERIFYHOST, $verifyHost );

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
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testEnableSSL()
     *
     * @tag #knockKnock #ssl #enable
     */
    public function enableSSL( bool $verifyPeer = true, int  $verifyHost = 2 ): self
    {
        $this->setCurlOption( CURLOPT_SSL_VERIFYPEER, $verifyPeer );
        $this->setCurlOption( CURLOPT_SSL_VERIFYHOST, $verifyHost );

        return $this;
    }



    // === Private ===

    /**
     * Проверка на завершённость запроса, если запрос завершён, то выбрасывается исключение
     *
     * @param ?string $message
     *
     * @return void
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testLimiterIsComplete()
     *
     * @tag #knockKnock #limiter #complete
     */
    public function limiterIsComplete( ?string $message = null ): void
    {
        if ( $this->statusIsComplete() )
        {
            $label = $this->getStatusLabel( $this->_statusID );

            $message = $message ?? "Вы не можете изменять параметры запроса в статусе: $label";

            throw new Exception( $message );
        }
    }

    /**
     * Проверка статуса запроса на соответствие переданному значению
     *
     * @param int $status_id
     *
     * @return bool
     *
     * Test: @see KnockRequestTest::testStatusIsComplete()
     * Test: @see KnockRequestTest::testStatusIsPrepare()
     */
    private function statusIs( int $status_id ): bool
    {
        return $this->getStatusID() === $status_id;
    }

    /**
     * Установка статуса запроса, на значение переданное в параметре
     *
     * @param int $status
     *
     * @return $this
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testSetupStatusComplete()
     * Test: @see KnockRequestTest::testSetupStatusComplete()
     */
    private function setStatus( int $status ): self
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
     * @tag #host #prepare
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testPrepareHost()
     */
    private function prepareHost(): void
    {
        if ( isset($this->_host) )
        {
            $separator = '://';

            if ( str_contains($this->_host, $separator) )
            {
                [$this->_protocol, $this->_host] = explode($separator, $this->_host);
            }
        }
    }

    /**
     * Заполнение параметров запроса из массива данных
     *
     * @param array $params
     *
     * @return void
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testSetupParamsFromArray()
     *
     * @tag #setup #paramList #on_status
     */
    private function setupParamsFromArray( array $params ): void
    {
        foreach ( $params as $param => $value ) {
            if ( $value && ( !isset($this->$param) || $this->$param !== $value ) ) {
                $this->setParamsOnStatusPrepare( $param, $value );
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
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testSetParamsOnStatusPrepare()
     *
     * @tag #setup #param #on_status
     */
    private function setParamsOnStatusPrepare( string $param, $value ): self
    {
        $this->limiterIsComplete();

        switch ($param)
        {
            case self::STATUS_ID: $this->_statusID = $value; return $this;
            case self::SETUP_PROTOCOL: $this->_protocol = $value; return $this;
            case self::SETUP_HOST:
                $this->_host = $value;
                $this->prepareHost();
                return $this;

            case self::SETUP_ENDPOINT: $this->_endpoint = $value; return $this;

            case self::SETUP_METHOD:
                $this->setCurlOption( CURLOPT_CUSTOMREQUEST, $value );
                $this->_method = $value;
                return $this;

            case self::SETUP_CONTENT_TYPE: $this->_contentType = $value; return $this;
            case self::SETUP_HEADERS: $this->_headers = $value; return $this;
            case self::SETUP_DATA: $this->_data = $value; return $this;
            case self::CURL_PARAMS: $this->_curlParams = $value; return $this;
            case self::SETUP_CURL_OPTIONS: $this->_curlParams[self::SETUP_CURL_OPTIONS] = $value; return $this;
            case self::SETUP_CURL_INFO: $this->_curlParams[self::SETUP_CURL_INFO] = $value; return $this;

            default:
                throw new Exception("неизвестный параметр запроса `$param`");
        }

    }



    // --- getters 4 magic ---

    /**
     * @return int
     *
     * Test: @see KnockRequestTest::testMagicGet()
     *
     * @tag #get #status
     */
    private function getStatusID(): int
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
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testGetStatusLabel()
     *
     * @tag #get #status
     */
    private function getStatusLabel( ?int $status_id = null ): string
    {
        $status_id = $status_id ?? $this->getStatusID();

        if ( isset(self::LABELS_STATUS[$status_id]) ) {
            return self::LABELS_STATUS[$status_id];
        }

        throw new Exception('Unknown status');
    }


    /**
     * Получение параметров запроса
     *
     * @return array
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testGetParams()
     *
     * @tag #get #params
     */
    private function getParams(): array
    {
        $params = [
            self::SETUP_PROTOCOL => $this->getProtocol(),
            self::SETUP_HOST => $this->getHost(),
            self::SETUP_ENDPOINT => $this->getEndpoint(),

            self::SETUP_METHOD => $this->getMethod(),
            self::SETUP_HEADERS => $this->getHeaders(),
            self::SETUP_CONTENT_TYPE => $this->getContentType(),

            self::SETUP_DATA => $this->getData(),

            self::SETUP_CURL_OPTIONS => $this->getCurlOptions(),
            self::SETUP_CURL_INFO => $this->getCurlInfo(),
        ];

        foreach ( $params as $setupKey => $value ) {
            if ( empty($value) ) {
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
     * Test: @see KnockRequestTest::testMagicGet()
     *
     * @tag #get #protocol
     */
    private function getProtocol(): ?string
    {
        return $this->_protocol ?? null;
    }

    /**
     * Получение хоста
     *
     * @return ?string
     *
     * Test: @see KnockRequestTest::testMagicGet()
     *
     * @tag #get #host
     */
    private function getHost(): ?string
    {
        return $this->_host ?? null;
    }

    /**
     * Получение endpoint запроса
     *
     * @return ?string
     *
     * Test: @see KnockRequestTest::testMagicGet()
     *
     * @tag #get #endpoint
     */
    private function getEndpoint(): ?string
    {
        return $this->_endpoint ?? null;
    }

    /**
     * Получение url
     *
     * @return ?string
     *
     * @throws Exception
     *
     * Test: @see KnockRequestTest::testMagicGet()
     *
     * @tag #get #url
     */
    private function getUrl(): ?string
    {
        return $this->constructUrl();
    }


    /**
     * Получение метода запроса
     *
     * @return ?string
     *
     * Test: @see KnockRequestTest::testMagicGet()
     *
     * @tag #get #method
     */
    private function getMethod(): ?string
    {
        return $this->_method ?? null;
    }

    /**
     * Получение заголовков
     *
     * @return array
     *
     * Test: @see KnockRequestTest::testMagicGet()
     *
     * @tag #get #headers
     */
    private function getHeaders(): array
    {
        return $this->_headers;
    }

    /**
     * Получение типа контента
     *
     * @return ?string
     *
     * Test: @see KnockRequestTest::testMagicGet()
     *
     * @tag #get #contentType
     */
    private function getContentType(): ?string
    {
        return $this->_contentType ?? null;
    }


    /**
     * Получение данных запроса
     *
     * @return mixed
     *
     * Test: @see KnockRequestTest::testMagicGet()
     *
     * @tag #get #data
     */
    private function getData(): mixed
    {
        return $this->_data ?? null;
    }

    /**
     * Получение данных запроса преобразованных компонентом
     *
     * @return mixed
     *
     * Test: @see KnockRequestTest::testMagicGet()
     *
     * @tag #get #data #postFields
     */
    private function getPostFields(): mixed
    {
        $curlOptions = $this->getCurlOptions();

        return $curlOptions[CURLOPT_POSTFIELDS] ?? null;
    }

    /**
     * Получение параметров curl
     *
     * @return array
     *
     * Test: @see KnockRequestTest::testMagicGet()
     *
     * @tag #get #curlParams
     */
    private function getCurlParams(): array
    {
        return $this->_curlParams;
    }

    /**
     * Получение параметров curl
     *
     * @return ?array
     *
     * Test: @see KnockRequestTest::testMagicGet()
     *
     * @tag #get #curlOptions
     */
    private function getCurlOptions(): ?array
    {
        return $this->_curlParams[self::SETUP_CURL_OPTIONS];
    }

    /**
     * Получение информации о запросе
     *
     * @return array
     *
     * Test: @see KnockRequestTest::testMagicGet()
     *
     * @tag #get #curlInfo
     */
    private function getCurlInfo(): array
    {
        return $this->_curlParams[self::SETUP_CURL_INFO];
    }


    /**
     * @return array
     *
     * Test: @see KnockRequestTest::testGetErrors()
     *
     * @tag #get #errors
     */
    private function getErrors(): array
    {
        return $this->_errors;
    }
}