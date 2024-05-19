<?php

namespace andy87\knock_knock\core;

use andy87\knock_knock\interfaces\KnockRequestInterface;
use Exception;

/**
 * Class KnockRequest
 *
 * @package andy87\knock_knock\query
 */
class KnockRequest implements KnockRequestInterface
{
    /** @var int $status Статус запроса */
    public int $status = self::STATUS_PREPARE;

    /** @var string $url Адрес запроса */
    private string $url;


    /** @var string $protocol Протокол */
    private string $protocol;
    /** @var string $host Хост */
    private string $host;


    /** @var string $endpoint endpoint запроса */
    private string $endpoint;

    /** @var string $method Метод запроса */
    private string $method;
    /** @var string $contentType Тип контента */
    private string $contentType;
    /** @var array $headers Заголовки */
    private array $headers = [];
    /** @var mixed $data Данные запроса */
    private $data;


    /** @var array $curl Параметры curl */
    private array $curlParams = [
        self::CURL_INFO => [],
        self::CURL_OPTIONS => []
    ];






    /**
     * KnockRequest конструктор.
     *
     * @param string $url
     * @param array $params
     *
     * @throws Exception
     */
    public function __construct( string $url , array $params = [] )
    {
        $this->setEndpoint( $url );

        $this->setupParamsFromArray($params);
    }



    // === Setters & Getters ===


    // --- Url ---

    /**
     * Установка endpoint запроса
     *
     * @param string $endpoint
     *
     * @return void
     *
     * @throws Exception
     */
    private function setEndpoint(string $endpoint ): void
    {
        $this->updateUrl();

        $this->setParamsWithConditionCompleted( self::ENDPOINT, $endpoint );
    }

    /**
     * Получение endpoint запроса
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Обновление URL
     *
     * @return void
     */
    private function updateUrl(): void
    {
        $address = str_replace( ['//','///'], '/', $this->host . '/' . $this->endpoint );

        $this->url = "$this->protocol://$address";
    }

    /**
     * Получение URL
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
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
     */
    public function setProtocol( string $protocol ): self
    {
        $this->setParamsWithConditionCompleted( self::PROTOCOL, $protocol);

        $this->updateUrl();

        return $this;
    }

    /**
     * Получение протокола
     *
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
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
     */
    public function setHost( string $host ): self
    {
        $this->setParamsWithConditionCompleted( self::HOST, $host);

        $this->updateUrl();

        return $this;
    }

    /**
     * Получение хоста
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }


    // --- Methods ---

    /**
     * Установка метода запроса
     *
     * @param string $method
     *
     * @return void
     *
     * @throws Exception
     */
    public function setMethod( string $method ): self
    {
        return $this->setParamsWithConditionCompleted( self::METHOD, $method);
    }

    /**
     * Получение метода запроса
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }


    // --- ContentType ---

    /**
     * Установка типа контента
     *
     * @param string $contentType
     *
     * @return void
     *
     * @throws Exception
     */
    public function setContentType( string $contentType ): self
    {
        return $this->setParamsWithConditionCompleted( self::CONTENT_TYPE, $contentType);
    }

    /**
     * Получение типа контента
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }


    // --- Headers ---

    /**
     * Установка заголовков
     *
     * @param array $headers
     *
     * @return void
     *
     * @throws Exception
     */
    public function setHeaders( array $headers ): self
    {
        foreach ( $headers as $key => $value ) {
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
     */
    public function addHeaders( string $key, string $value ): self
    {
        if ( $this->status === self::STATUS_COMPLETE ){
            throw new Exception('Request is completed');
        }

        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * Получение заголовков
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }


    // --- Data ---

    /**
     * Установка данных запроса
     *
     * @param $data
     *
     * @return void
     *
     * @throws Exception
     */
    public function setData( $data ): self
    {
        return $this->setParamsWithConditionCompleted( self::DATA, $data);
    }

    /**
     * Получение данных запроса
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Получение данных запроса преобразованных компонентом
     *
     * @return mixed
     */
    public function getPostFields()
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
     * @return void
     *
     * @throws Exception
     */
    public function setCurlOptions( array $curlOptions ): self
    {
        if ( $this->status === self::STATUS_COMPLETE ){
            throw new Exception('Request is completed');
        }

        $this->curlParams[self::CURL_OPTIONS] = $curlOptions;

        return $this;
    }

    /**
     * Добавление параметра curl
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     *
     * @throws Exception
     */
    public function addCurlOptions( string $key, string  $value ): self
    {
        if ( $this->status === self::STATUS_COMPLETE ){
            throw new Exception('Request is completed');
        }

        $this->curlParams[self::CURL_OPTIONS][$key] = $value;

        return $this;
    }

    /**
     * Получение параметров curl
     *
     * @param ?string $key
     *
     * @return ?array
     */
    public function getCurlOptions( string $key = null ): ?array
    {
        $output = $this->curlParams[self::CURL_OPTIONS];

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
     * @return void
     *
     * @throws Exception
     */
    public function setCurlInfo( array $curlInfo ): self
    {
        if ( $this->status === self::STATUS_COMPLETE ){
            throw new Exception('Request is completed');
        }

        $this->curlParams[self::CURL_INFO] = $curlInfo;

        return $this;
    }

    /**
     * Получение информации о запросе
     *
     * @return array
     */
    public function getCurlInfo(): array
    {
        return $this->curlParams[self::CURL_INFO];
    }

    /**
     * Получение параметров curl
     *
     * @return array
     */
    public function getCurlParams(): array
    {
        return $this->curlParams;
    }


    // --- Params ---

    /**
     * Маркировка запроса как выполненного
     *
     * @return void
     */
    public function setStatusProcessing()
    {
        $this->status = self::STATUS_PROCESSING;
    }

    /**
     * Маркировка запроса как выполненного
     *
     * @return void
     */
    public function setStatusComplete()
    {
        $this->status = self::STATUS_COMPLETE;
    }

    /**
     * Получение параметров запроса
     *
     * @return array
     */
    public function getParams(): array
    {
        return [
            self::PROTOCOL => $this->getProtocol(),
            self::HOST => $this->getHost(),
            self::ENDPOINT => $this->getEndpoint(),
            self::URL => $this->getUrl(),
            self::METHOD => $this->getMethod(),
            self::CONTENT_TYPE => $this->getContentType(),
            self::HEADERS => $this->getHeaders(),
            self::CURL_OPTIONS => $this->getCurlOptions(),
            self::CURL_INFO => $this->getCurlInfo(),
            self::DATA => $this->getData(),
            self::POST_FIELD => $this->getPostFields(),
        ];
    }



    // === Private ===

    /**
     * Заполнение параметров запроса из массива данных
     *
     * @param array $params
     *
     * @return void
     *
     * @throws Exception
     */
    private function setupParamsFromArray( array $params )
    {
        foreach ( $params as $param => $value ) {
            $this->setParamsWithConditionCompleted( $param, $value );
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
     */
    private function setParamsWithConditionCompleted(string $param, $value ): self
    {
        if ( $this->status === self::STATUS_PREPARE ) {
            switch ($param)
            {
                case self::PROTOCOL:
                case self::HOST:
                case self::ENDPOINT:
                case self::METHOD:
                case self::CONTENT_TYPE:
                case self::HEADERS:
                case self::DATA:
                case self::CURL_OPTIONS:
                case self::CURL_INFO:
                    $this->$param = $value;
                    return $this;

                default:
                    throw new Exception('Unknown param');
            }

        } else {

            $mapping = [
                self::STATUS_PROCESSING => 'processing',
                self::STATUS_COMPLETE => 'complete'
            ];

            throw new Exception('Request is ' . $mapping[$this->status]);
        }
    }
}