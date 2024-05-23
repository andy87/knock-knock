<?php /**
 * KnockKnock
 *
 * @author Andrey and_y87 Kidin
 * @description PHP Фасад\Адаптер для отправки запросов через ext cURL
 *
 * @date 2024-05-22
 *
 * @version 0.99
 */

namespace andy87\knock_knock\core;

use Exception;
use andy87\knock_knock\lib\{ LibKnockMethod, LibKnockContentType };
use andy87\knock_knock\interfaces\{ KnockKnockInterface, KnockRequestInterface, KnockResponseInterface };

/**
 * Class KnockKnock
 *
 * @package andy87\knock_knock
 *z
 * Fix not used:
 * - @see KnockKnock::getInstance();
 *
 * - @see KnockKnock::setupEventHandlers();
 * - @see KnockKnock::off();
 * - @see KnockKnock::disableSSL();
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
    protected array $_callbacks = [
        self::EVENT_AFTER_INIT => null,
        self::EVENT_CONSTRUCT_REQUEST => null,
        self::EVENT_BEFORE_SEND => null,
        self::EVENT_CONSTRUCT_RESPONSE => null,
        self::EVENT_AFTER_SEND => null,
    ];

    /**
     * Хост, на который будет отправляться запросы по умолчанию (без перенастройки)
     *
     * @var string $_host
     */
    protected string $_host;



    /**
     * KnockKnock конструктор.
     * Принимает хост как обязательный параметр и массив параметров для всех запросов, как опциональный
     *
     * @param string $host
     * @param array $commonKnockRequestParams
     *
     * @throws Exception
     *
     * @tag #knockKnock #magic
     */
    public function __construct( string $host, array $commonKnockRequestParams = [] )
    {
        $this->_host = $host;

        $this->_commonKnockRequest = new KnockRequest( null, $commonKnockRequestParams );

        $this->init();

        $this->event( self::EVENT_AFTER_INIT, $this );
    }

    /**
     * Получение экземпляра класса, используя паттерн Singleton
     *
     * @param array $commonKnockRequestParams
     *
     * @return self
     *
     * @tag #knockKnock #get #instance
     */
    public static function getInstance( array $commonKnockRequestParams ): self
    {
        if ( static::$_instance === null )
        {
            $classname = static::class;

            static::$_instance = new $classname( $commonKnockRequestParams );
        }

        return static::$_instance;
    }

    /**
     * Пользовательская инициализация
     *
     * @return void
     *
     * @tag #knockKnock #init
     */
    public function init(): void {}


    // === Construct ===

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
     * @tag #knockKnock #construct #request
     */
    public function constructRequest( string $method, string $endpoint, array $knockRequestConfig = [] ): KnockRequest
    {
        $knockRequestConfig = array_merge([
            KnockRequestInterface::SETUP_HOST => $this->_host,
            KnockRequestInterface::SETUP_METHOD => $method,
        ], $knockRequestConfig );

        $commonKnockRequestParams = $this->getCommonKnockRequest()->getParams();

        $knockRequestParams = array_merge( $knockRequestConfig, $commonKnockRequestParams );

        $knockRequest = new KnockRequest( $endpoint, $knockRequestParams );

        $this->event( self::EVENT_CONSTRUCT_REQUEST, $knockRequest );

        return $knockRequest;
    }

    /**
     * @param array $responseParams
     * @param ?KnockRequest $knockRequest
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * @tag #knockKnock #construct #response
     */
    public function constructResponse( array $responseParams, ?KnockRequest $knockRequest = null ): KnockResponse
    {
        $knockRequest->setStatusProcessing();

        $content = $responseParams[KnockResponseInterface::CONTENT] ?? null;

        $httpCode = $responseParams[KnockResponseInterface::HTTP_CODE] ?? KnockResponseInterface::OK;

        $knockResponse = new KnockResponse( $content, $httpCode, $knockRequest );

        $knockRequest->setStatusComplete();

        $this->event( self::EVENT_CONSTRUCT_RESPONSE, $knockResponse );

        return $knockResponse;
    }



    // === Setup ===

    /**
     * @param KnockRequest $knockRequest
     * @param array $options
     *
     * @return $this
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
     * @tag #knockKnock #setup #event #callback #behavior
     */
    public function setupEventHandlers( array $callbacks ): array
    {
        foreach ( $callbacks as $event => $callback )
        {
            $this->on( $event, $callback );
        }

        return $this->_callbacks;
    }



    // === Response ===

    /**
     * Отправка запроса
     *
     * @param array $fakeResponse
     *
     * @return KnockResponse
     *
     * @throws Exception
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
     * @tag #knockKnock #send #request #query #response
     */
    public function getResponseOnSendCurlRequest( KnockRequest $knockRequest ): KnockResponse
    {
        $knockRequest->setStatusProcessing();

        $url = $knockRequest->getUrl();

        $ch = curl_init($url);

        $curlParams = $knockRequest->getCurlParams();

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

        $knockRequest->addErrors( curl_error($ch) );

        $knockResponse = $this->constructResponse( $knockResponseParams, $knockRequest );

        curl_close( $ch );

        $knockRequest->setStatusComplete();

        $this->event( self::EVENT_AFTER_SEND, $knockResponse );

        return $knockResponse;
    }



    // === Обработчики событий === Event === Behavior === Callbacks ===

    /**
     * Вызов обработчика события
     *
     * @param string $event
     * @param mixed $data
     *
     * @return mixed
     *
     * @tag #knockKnock #behavior #event #callback
     */
    public function event( string $event, mixed $data ): mixed
    {
        if ( isset( $this->_callbacks[$event] ) )
        {
            $callback = $this->_callbacks[$event];

            return $callback( $this, $data );
        }

        return null;
    }

    /**
     * Добавление обработчика события
     *
     * @param string $event
     * @param callable $callbacks
     *
     * @return ?bool
     *
     * @throws Exception
     *
     * @tag #knockKnock #behavior #event #callback
     */
    public function on( string $event, callable $callbacks ): ?bool
    {
        if ( isset( $this->_callbacks[$event] ) )
        {
            if ( $this->_callbacks[$event] === null )
            {
                return $this->change( $event, $callbacks );
            }

            throw new Exception('Event already exists. Use method change() for change event handler');
        }

        return false;
    }

    /**
     * Изменение обработчика события
     *
     * @param string $event
     * @param callable $callback
     *
     * @return bool
     *
     * @tag #knockKnock #behavior #event #callback
     */
    public function change( string $event, callable $callback ): bool
    {
        if ( isset( $this->_callbacks[$event] ) )
        {
            $this->_callbacks[$event] = $callback;

            return true;
        }

        return false;
    }

    /**
     * Удаление обработчика события
     *
     * @param string $event
     *
     * @return bool
     *
     * @tag #knockKnock #behavior #event #callback
     */
    public function off( string $event ): bool
    {
        if ( isset( $this->_callbacks[$event] ) && $this->_callbacks[$event] )
        {
            $this->_callbacks[$event] = null;

            return true;
        }

        return false;
    }



    // === Get ===

    /**
     * Получение объекта запроса с общими параметрами для всех запросов
     *
     * @return ?KnockRequest
     *
     * @tag #knockKnock #request #common
     */
    public function getCommonKnockRequest(): ?KnockRequest
    {
        return $this->_commonKnockRequest ?? null;
    }

    /**
     * Получение объекта запроса с параметрами последнего запроса
     *
     * @return ?KnockRequest
     *
     * @tag #knockKnock #request #real
     */
    public function getRealKnockRequest(): ?KnockRequest
    {
        return $this->_realKnockRequest ?? null;
    }

    /**
     * Получение хоста
     *
     * @return string
     *
     * @tag #knockKnock #get #host
     */
    public function getHost(): string
    {
        return $this->_host;
    }



    // === Other ===

    /**
     * Отключение SSL сертификата
     *
     * @throws Exception
     *
     * @tag #knockKnock #ssl #disable
     */
    public function disableSSL(): self
    {
        $this->_commonKnockRequest->addCurlOptions( CURLOPT_SSL_VERIFYPEER, false );

        return $this;
    }



    // === protected ===

    /**
     * Отправка запроса
     *
     * @param KnockRequest $knockRequest
     * @param array $fakeKnockResponseParams
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * @tag #knockKnock #send #request
     */
    protected function sendRequest( KnockRequest $knockRequest, array $fakeKnockResponseParams = [] ): KnockResponse
    {
        $this
            ->updatePostFields( $knockRequest )
            ->updateMethod( $knockRequest )
            ->event( self::EVENT_BEFORE_SEND, $knockRequest );

        return (count($fakeKnockResponseParams))
            ? $this->constructResponse( $fakeKnockResponseParams, $knockRequest )
            : $this->getResponseOnSendCurlRequest( $knockRequest );
    }



    // === Update ===

    /**
     * Обновление параметров запроса на основе переданных параметров в
     *
     * @param KnockRequest $knockRequest
     * @param array $params
     *
     * @return KnockRequest
     *
     * @tag #knockKnock #update #request
     */
    private function updateRequestParams( KnockRequest $knockRequest, array $params ): KnockRequest
    {
        $functionMapping = [
            KnockRequestInterface::SETUP_PROTOCOL => 'setProtocol',
            KnockRequestInterface::SETUP_HOST => 'setHost',
            KnockRequestInterface::SETUP_METHOD => 'setMethod',
            KnockRequestInterface::SETUP_HEADERS => 'setHeaders',
            KnockRequestInterface::SETUP_DATA => 'setData',
            KnockRequestInterface::SETUP_CURL_OPTIONS => 'setCurlOptions',
            KnockRequestInterface::SETUP_CURL_INFO => 'setCurlInfo',
            KnockRequestInterface::SETUP_CONTENT_TYPE => 'setContentType',
        ];

        foreach ( $params as $setupKey => $value )
        {
            if ( $value && isset( $functionMapping[$setupKey] ) )
            {
                $func = $functionMapping[$setupKey];

                $knockRequest->$func( $value );
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
     * @tag #knockKnock #update #post
     */
    private function updatePostFields( KnockRequest $knockRequest ): self
    {
        if ( $knockRequest->getMethod() !== LibKnockMethod::GET )
        {
            $data = $knockRequest->getData();

            if ( $data && count( $data ) )
            {
                switch( $knockRequest->getContentType() )
                {
                    case LibKnockContentType::JSON: $data = json_encode( $data ); break;

                    case LibKnockContentType::FORM:
                    case LibKnockContentType::MULTIPART:
                    case LibKnockContentType::XML:
                    case LibKnockContentType::TEXT:
                    default: break;
                }

                $knockRequest->addCurlOptions( CURLOPT_POSTFIELDS, $data );
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
     * @tag #knockKnock #update #method
     */
    private function updateMethod(KnockRequest $knockRequest ): self
    {
        $method = $knockRequest->getMethod();

        if ( $method !== LibKnockMethod::GET )
        {
            $knockRequest->addCurlOptions( CURLOPT_CUSTOMREQUEST, $method );
        }

        return $this;
    }
}