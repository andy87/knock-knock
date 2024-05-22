<?php /**
 * KnockRequest
 *
 * @author Andrey and_y87 Kidin
 * @description Компонент содержащий параметры запроса
 *
 * @date 2024-05-22
 *
 * @version 0.99
 */

namespace andy87\knock_knock\core;

use Exception;
use andy87\knock_knock\interfaces\KnockRequestInterface;

/**
 * Class KnockRequest
 *
 * @package andy87\knock_knock\query
 *
 * Fix not used:
 * - @see KnockRequest::getStatusLabel()
 */
class KnockRequest implements KnockRequestInterface
{
    public const SET = 'set';
    public const GET = 'get';

    public const PROTOCOL_HTTP = 'http';
    public const PROTOCOL_HTTPS = 'https';

    /** @var array */
    public const STATUS_LABELS = [
        self::STATUS_PREPARE => 'новый запрос',
        self::STATUS_PROCESSING => 'запрос отправляется',
        self::STATUS_COMPLETE => 'ответ получен'
    ];



    /** @var int $status Статус запроса */
    public int $status = self::STATUS_PREPARE;


    /** @var string $_protocol Протокол */
    private string $_protocol = self::PROTOCOL_HTTPS;

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
    private mixed $_errors;


    /** @var array $curl Параметры curl */
    private array $curlParams = [
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
     * @tag #constructor
     */
    public function __construct( ?string $endpoint , array $params = [] )
    {
        if ( $endpoint ) {
            $this->setEndpoint( $endpoint );
        }

        $this->setupParamsFromArray( $params );

        $this->prepareHost();
    }



    // === Setters & Getters ===


    // --- Url ---

    /**
     * Установка endpoint запроса
     *
     * @param string $endpoint
     *
     * @return $this
     *
     * @throws Exception
     *
     * @tag #set #endpoint
     */
    public function setEndpoint( string $endpoint ): self
    {
        return $this->setParamsOnStatusPrepare( self::SETUP_ENDPOINT, $endpoint );
    }

    /**
     * Получение endpoint запроса
     *
     * @return ?string
     *
     * @tag #get #endpoint
     */
    public function getEndpoint(): ?string
    {
        return $this->_endpoint ?? null;
    }


    /**
     * Получение URL
     *
     * @return ?string
     *
     * @tag #get #url
     */
    public function getUrl(): ?string
    {
        if ( ($this->_host ?? false ) && ($this->_endpoint ?? false) )
        {
            $address = str_replace( ['//','///'], '/', $this->_host . '/' . $this->_endpoint );

            return "$this->_protocol://$address";
        }

        return null;
    }


    // --- Protocol ---

    /**
     * Установка протокола
     *
     * @param string $protocol
     *
     * @return $this
     *
     * @throws Exception
     *
     * @tag #set #protocol
     */
    public function setProtocol( string $protocol ): self
    {
        return $this->setParamsOnStatusPrepare( self::SETUP_PROTOCOL, $protocol );
    }

    /**
     * Получение протокола
     *
     * @return ?string
     *
     * @tag #get #protocol
     */
    public function getProtocol(): ?string
    {
        return $this->_protocol ?? null;
    }


    // --- Host ---

    /**
     * Установка хоста
     *
     * @param string $host
     *
     * @return $this
     *
     * @throws Exception
     *
     * @tag #set #host
     */
    public function setHost( string $host ): self
    {
        return $this->setParamsOnStatusPrepare( self::SETUP_HOST, $host );
    }

    /**
     * Получение хоста
     *
     * @return ?string
     *
     * @tag #get #host
     */
    public function getHost(): ?string
    {
        return $this->_host ?? null;
    }


    // --- Methods ---

    /**
     * Установка метода запроса
     *
     * @param string $method
     *
     * @return $this
     *
     * @throws Exception
     *
     * @tag #set #method
     */
    public function setMethod( string $method ): self
    {
        return $this->setParamsOnStatusPrepare( self::SETUP_METHOD, $method );
    }

    /**
     * Получение метода запроса
     *
     * @return ?string
     *
     * @tag #get #method
     */
    public function getMethod(): ?string
    {
        return $this->_method ?? null;
    }


    // --- ContentType ---

    /**
     * Установка типа контента
     *
     * @param string $contentType
     *
     * @return $this
     *
     * @throws Exception
     *
     * @tag #set #contentType
     */
    public function setContentType( string $contentType ): self
    {
        return $this->setParamsOnStatusPrepare( self::SETUP_CONTENT_TYPE, $contentType );
    }

    /**
     * Получение типа контента
     *
     * @return ?string
     *
     * @tag #get #contentType
     */
    public function getContentType(): ?string
    {
        return $this->_contentType ?? null;
    }


    // --- Headers ---

    /**
     * Установка заголовков
     *
     * @param array $headers
     *
     * @return $this
     *
     * @throws Exception
     *
     * @tag #set #headers
     */
    public function setHeaders( array $headers ): self
    {
        foreach ($headers as $key => $value ) {
            $this->addHeaders($key, $value);
        }

        return $this;
    }

    /**
     * Установка заголовка
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     *
     * @throws Exception
     *
     * @tag #add #headers
     */
    public function addHeaders( string $key, string $value ): self
    {
        if ( $this->status === self::STATUS_COMPLETE ){
            throw new Exception('Request is completed');
        }

        $this->_headers[$key] = $value;

        return $this;
    }

    /**
     * Получение заголовков
     *
     * @return array
     *
     * @tag #get #headers
     */
    public function getHeaders(): array
    {
        return $this->_headers;
    }


    // --- Data ---

    /**
     * Установка данных запроса
     *
     * @param mixed $data
     *
     * @return $this
     *
     * @throws Exception
     *
     * @tag #set #data
     */
    public function setData( mixed $data ): self
    {
        return $this->setParamsOnStatusPrepare( self::SETUP_DATA, $data );
    }

    /**
     * Получение данных запроса
     *
     * @return mixed
     *
     * @tag #get #data
     */
    public function getData(): mixed
    {
        return $this->_data ?? null;
    }

    /**
     * Получение данных запроса преобразованных компонентом
     *
     * @return mixed
     *
     * @tag #get #data #postFields
     */
    public function getPostFields(): mixed
    {
        $curlOptions = $this->getCurlOptions();

        return $curlOptions[CURLOPT_POSTFIELDS] ?? null;
    }



    // --- Curl ---

    /**
     * Установка параметров curl
     *
     * @param array $curlOptions
     *
     * @return $this
     *
     * @throws Exception
     *
     * @tag #set #curlOptions
     */
    public function setCurlOptions( array $curlOptions ): self
    {
        if ( $this->status === self::STATUS_COMPLETE ){
            throw new Exception('Request is completed');
        }

        $this->curlParams[self::SETUP_CURL_OPTIONS] = $curlOptions;

        return $this;
    }

    /**
     * Добавление параметра curl
     *
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     *
     * @throws Exception
     *
     * @tag #add #curlOptions
     */
    public function addCurlOptions( string $key, mixed $value ): self
    {
        if ( $this->status === self::STATUS_PREPARE )
        {
            $this->curlParams[self::SETUP_CURL_OPTIONS][$key] = $value;

            return $this;
        }

        throw new Exception('Request is completed');
    }

    /**
     * Получение параметров curl
     *
     * @param ?string $key
     *
     * @return ?array
     *
     * @tag #get #curlOptions
     */
    public function getCurlOptions( string $key = null ): ?array
    {
        $output = $this->curlParams[self::SETUP_CURL_OPTIONS];

        if ( $key ) {
            $output = $curlOptions[$key] ?? null;
        }

        return $output;
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
     * @tag #set #curlInfo
     */
    public function setCurlInfo( array $curlInfo ): self
    {
        if ( $this->status === self::STATUS_COMPLETE ){
            throw new Exception('Request is completed');
        }

        $this->curlParams[self::SETUP_CURL_INFO] = $curlInfo;

        return $this;
    }

    /**
     * Получение информации о запросе
     *
     * @return array
     *
     * @tag #get #curlInfo
     */
    public function getCurlInfo(): array
    {
        return $this->curlParams[self::SETUP_CURL_INFO];
    }

    /**
     * Получение параметров curl
     *
     * @return array
     *
     * @tag #get #curlParams
     */
    public function getCurlParams(): array
    {
        return $this->curlParams;
    }


    // --- Status ---

    /**
     * Маркировка запроса как выполненного
     *
     * @return $this
     *
     * @tag #set #status #processing
     */
    public function setStatusProcessing(): self
    {
        $this->status = self::STATUS_PROCESSING;

        return $this;
    }

    /**
     * Маркировка запроса как выполненного
     *
     * @return $this
     *
     * @tag #set #status #complete
     */
    public function setStatusComplete(): self
    {
        $this->status = self::STATUS_COMPLETE;

        return $this;
    }

    // --- Errors ---

    /**
     * @param string $curlError
     *
     * @return $this
     *
     * @tag #add #errors
     */
    public function addErrors( string $curlError ): self
    {
        $this->_errors[] = $curlError;

        return $this;
    }

    /**
     * @return array
     *
     * @tag #get #errors
     */
    public function getErrors(): array
    {
        return $this->_errors;
    }


    // --- Common ---

    /**
     * Получение параметров запроса
     *
     * @return array
     *
     * @throws Exception
     *
     * @tag #get #params
     */
    public function getParams(): array
    {
        $params = [
            self::SETUP_PROTOCOL => $this->setGet(self::GET, self::SETUP_PROTOCOL),
            self::SETUP_HOST => $this->setGet(self::GET, self::SETUP_HOST),
            self::SETUP_ENDPOINT => $this->setGet(self::GET, self::SETUP_ENDPOINT),
            self::SETUP_METHOD => $this->setGet(self::GET, self::SETUP_METHOD),
            self::SETUP_CONTENT_TYPE => $this->setGet(self::GET, self::SETUP_CONTENT_TYPE),
            self::SETUP_HEADERS => $this->setGet(self::GET, self::SETUP_HEADERS),
            self::SETUP_DATA => $this->setGet(self::GET, self::SETUP_DATA ),
            self::SETUP_CURL_OPTIONS => $this->setGet(self::GET, self::SETUP_CURL_OPTIONS),
            self::SETUP_CURL_INFO => $this->setGet(self::GET, self::SETUP_CURL_INFO),
            self::SETUP_POST_FIELD => $this->setGet(self::GET, self::SETUP_POST_FIELD),
        ];

        foreach ( $params as $setupKey => $value ) {
            if ( empty($value) ) {
                unset($params[$setupKey]);
            }
        }

        return $params;
    }

    /**
     * @param string $key
     *
     * @return string
     *
     * @throws Exception
     *
     * @tag #get #status
     */
    public function getStatusLabel( string $key ): string
    {
        if ( isset(self::STATUS_LABELS[$key]) ) {
            return self::STATUS_LABELS[$key];
        }

        throw new Exception('Unknown status');
    }

    /**
     * Отключение SSL сертификата
     *
     * @throws Exception
     *
     * @tag #security #ssl
     */
    public function disableSSL(): self
    {
        $this->addCurlOptions( CURLOPT_SSL_VERIFYPEER, false );

        return $this;
    }

    /**
     * Отключение SSL сертификата
     *
     * @throws Exception
     *
     * @tag #security #ssl
     */
    public function enableSSL(): self
    {
        $this->addCurlOptions( CURLOPT_SSL_VERIFYPEER, true );

        return $this;
    }



    // === Private ===

    /**
     * Подготовка хоста
     *
     * @return void
     *
     * @tag #host #prepare
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
     * @tag #setup #param #on_status
     */
    private function setParamsOnStatusPrepare(string $param, $value ): self
    {
        if ( $this->status === self::STATUS_PREPARE )
        {
            switch ($param)
            {
                case self::SETUP_PROTOCOL: $this->_protocol = $value; return $this;
                case self::SETUP_HOST: $this->_host = $value; return $this;
                case self::SETUP_ENDPOINT: $this->_endpoint = $value; return $this;
                case self::SETUP_METHOD: $this->_method = $value; return $this;
                case self::SETUP_CONTENT_TYPE: $this->_contentType = $value; return $this;
                case self::SETUP_HEADERS: $this->_headers = $value; return $this;
                case self::SETUP_DATA: $this->_data = $value; return $this;
                case self::SETUP_CURL_OPTIONS: $this->curlParams[self::SETUP_CURL_OPTIONS] = $value; return $this;
                case self::SETUP_CURL_INFO: $this->curlParams[self::SETUP_CURL_INFO] = $value; return $this;

                default:
                    throw new Exception("неизвестный параметр запроса `$param`");
            }

        } else {

            $mapping = [
                self::STATUS_PROCESSING => 'processing',
                self::STATUS_COMPLETE => 'complete'
            ];

            throw new Exception('Вы не можете изменять параметры запроса в статусе: ' . $mapping[$this->status]);
        }
    }

    /**
     * @param string $method
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     *
     * @throws Exception
     *
     * @tag #setup #get #set
     */
    private function setGet( string $method, string $key, mixed $value = null ): mixed
    {
        if ( $method === self::SET )
        {
            switch ($key)
            {
                case self::SETUP_PROTOCOL: $this->setProtocol($value); return $this;
                case self::SETUP_HOST: $this->setHost($value); return $this;
                case self::SETUP_ENDPOINT: $this->setEndpoint($value); return $this;
                case self::SETUP_METHOD: $this->setMethod($value); return $this;
                case self::SETUP_CONTENT_TYPE: $this->setContentType($value); return $this;
                case self::SETUP_HEADERS: $this->setHeaders($value); return $this;
                case self::SETUP_DATA: $this->setData($value); return $this;
                case self::SETUP_CURL_OPTIONS: $this->setCurlOptions($value); return $this;
                case self::SETUP_CURL_INFO: $this->setCurlInfo($value); return $this;

                default:
                    throw new Exception("неизвестный параметр запроса `$key`");
            }

        } elseif ( $method === self::GET ) {

            return match ($key) {
                self::SETUP_PROTOCOL => $this->getProtocol(),
                self::SETUP_HOST => $this->getHost(),
                self::SETUP_ENDPOINT => $this->getEndpoint(),
                self::SETUP_METHOD => $this->getMethod(),
                self::SETUP_CONTENT_TYPE => $this->getContentType(),
                self::SETUP_HEADERS => $this->getHeaders(),
                self::SETUP_DATA => $this->getData(),
                self::SETUP_CURL_OPTIONS => $this->getCurlOptions(),
                self::SETUP_CURL_INFO => $this->getCurlInfo(),
                self::SETUP_POST_FIELD => $this->getPostFields(),
                default => throw new Exception("неизвестный параметр запроса `$key`"),
            };

        } else {
            throw new Exception("неизвестный метод `$method`");
        }
    }
}