<?php /**
 * KnockResponse
 *
 * @author Andrey and_y87 Kidin
 * @description Компонент содержащий параметры ответа
 *
 * @date 2024-05-22
 *
 * @version 0.98
 */

namespace andy87\knock_knock\core;

use Exception;
use andy87\knock_knock\interfaces\{ KnockRequestInterface, KnockResponseInterface };

/**
 * Class KnockRequest
 *
 * @package andy87\knock_knock\query
 *
 * @property-read KnockRequest $request
 * @property-read int $httpCode
 * @property-read string $content
 * @property-read array $trace
 * @property-read array $curlOptions
 * @property-read array $curlInfo
 *
 * Fix not used:
 * - @see KnockResponse::replace();
 */
class KnockResponse implements KnockResponseInterface
{
    /** @var string $_data */
    private string $_data;

    /** @var int $_httpCode */
    private int $_httpCode = 0;


    /** @var ?KnockRequest $knockRequest */
    private ?KnockRequest $knockRequest = null;



    /**
     * KnockResponse constructor.
     *
     * @param string $data
     * @param int $httpCode
     * @param ?KnockRequest $knockRequest
     *
     * @throws Exception
     */
    public function __construct( string $data, int $httpCode, ?KnockRequest $knockRequest = null  )
    {
        $this->setData( $data );

        $this->setHttpCode( $httpCode );

        $this->setRequest( $knockRequest );
    }

    /**
     * @param $name
     *
     * @return string|array|int|self
     *
     * @throws Exception
     */
    public function __get($name): string|array|int|self
    {
        return match ($name) {
            self::REQUEST => $this->getRequest(),
            self::HTTP_CODE => $this->getHttpCode(),
            self::CONTENT => $this->getData(),
            self::TRACE => $this->getTrace(),
            KnockRequestInterface::SETUP_CURL_OPTIONS, KnockRequestInterface::SETUP_CURL_INFO => $this->get($name),
            default => throw new Exception("Property `$name`not found on: " . __CLASS__),
        };
    }



    // === PUBLIC ===

    /**
     * @param int $httpCode
     *
     * @return self
     *
     * @throws Exception
     */
    public function setHttpCode( int $httpCode ): self
    {
        if ( $this->_httpCode )
        {
            throw new Exception('Request is already set');
        }

        $this->_httpCode = $httpCode;

        return $this;
    }

    /**
     * @return int
     */
    private function getHttpCode(): int
    {
        return $this->_httpCode;
    }

    /**
     * @param string $data
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setData( string $data ): self
    {
        if ( isset($this->_data) )
        {
            throw new Exception('`_data` is already set');
        }

        $this->_data = $data;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function replace( string $key, mixed $value ): KnockResponse
    {
        switch ( $key )
        {
            case self::HTTP_CODE:
                $this->setHttpCode( $value );
                break;

            case self::CONTENT:
                $this->setData( $value );
                break;

            default:
                throw new Exception('Bad key');
        }

        return $this;
    }

    /**
     * @param string $key
     * @return array
     *
     * @throws Exception
     */
    public function get( string $key ): array
    {
        $resp = null;

        $access = [ KnockRequestInterface::SETUP_CURL_OPTIONS, KnockRequestInterface::SETUP_CURL_INFO ];

        if ( in_array( $key, $access ) )
        {
            $curlParams = $this->knockRequest->getCurlParams();

            switch ( $key )
            {
                case KnockRequestInterface::SETUP_CURL_OPTIONS:
                    $resp = $curlParams[KnockRequestInterface::SETUP_CURL_OPTIONS];
                    break;

                case KnockRequestInterface::SETUP_CURL_INFO:
                    $resp = $curlParams[KnockRequestInterface::SETUP_CURL_INFO];
                    break;
            }

            if ( $resp ) return $resp;
        }

        throw new Exception('Bad key');
    }



    // === PRIVATE ===

    /**
     * @return string
     */
    private function getData(): string
    {
        return $this->_data;
    }

    /**
     * @param KnockRequest $knockRequest
     *
     * @return void
     *
     * @throws Exception
     */
    private function setRequest( KnockRequest $knockRequest ): void
    {
        if ( $this->knockRequest )
        {
            throw new Exception('Request is already set');
        }

        $this->knockRequest = $knockRequest;
    }

    /**
     * @return KnockRequest
     */
    private function getRequest(): KnockRequest
    {
        return $this->knockRequest;
    }

    /**
     * Получение Trace лог истории вызовов методов
     *
     * @return array
     */
    private function getErrors(): array
    {
        return $this->request->getErrors();
    }

    /**
     * Получение Trace лог истории вызовов методов
     */
    private function getTrace(): array
    {
        return debug_backtrace();
    }
}