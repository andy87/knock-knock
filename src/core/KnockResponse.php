<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Компонент содержащий параметры ответа
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 0.99b
 */

declare(strict_types=1);

namespace andy87\knock_knock\core;

use Exception;
use andy87\knock_knock\interfaces\{ KnockRequestInterface, KnockResponseInterface };
use tests\KnockResponseTest;

/**
 * Class KnockRequest
 *
 * @package andy87\knock_knock\query
 *
 * @property-read int $httpCode
 * @property-read mixed $content
 *
 * @property-read KnockRequest $request
 *
 * @property-read array $curlOptions
 * @property-read array $curlInfo
 *
 * @property-read array $errors
 *
 * Покрытие тестами: 100%. @see KnockResponseTest
 */
class KnockResponse implements KnockResponseInterface
{
    /** @var mixed $_data */
    protected mixed $_data;

    /** @var int $_httpCode */
    protected int $_httpCode;


    /** @var ?KnockRequest $_knockRequest */
    protected ?KnockRequest $_knockRequest = null;

    /** @var array $_errors */
    protected array $_errors = [];


    /** @var bool $isArray */
    protected bool $isArray = false;



    /**
     * KnockResponse constructor.
     *
     * @param string $data
     * @param int $httpCode
     * @param KnockRequest $knockRequest
     *
     * @return void
     * @throws Exception
     *
     * Test: @see KnockResponseTest::testConstructor()
     *
     * @tag #constructor #response
     */
    public function __construct( string $data, int $httpCode, KnockRequest $knockRequest )
    {
        $this->setupData( $data );

        $this->setupHttpCode( $httpCode );

        $this->setupRequest( $knockRequest );
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
     * Test: @see KnockResponseTest::testMagicGet()
     *
     * @tag #magic #get
     */
    public function __get( string $name ): mixed
    {
        return match ( $name ) {

            self::HTTP_CODE => $this->getHttpCode(),
            self::CONTENT => $this->getData(),

            self::REQUEST => $this->getRequest(),

            KnockRequestInterface::SETUP_CURL_OPTIONS => $this->getRequest()->curlOptions,
            KnockRequestInterface::SETUP_CURL_INFO => $this->getRequest()->curlInfo,

            self::ERRORS => $this->getErrors(),

            default => throw new Exception("Property `$name`not found on: " . __CLASS__),
        };
    }



    // === PUBLIC ===

    /**
     * Замена значений в свойствах
     *
     * @param string $key
     * @param mixed $value
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * Test: @see KnockResponseTest::testReplace()
     *
     * @tag #response #replace #content #httpCode
     */
    public function replace( string $key, mixed $value ): KnockResponse
    {
        switch ( $key )
        {
            case self::HTTP_CODE:
                $this->_httpCode = $value;
                break;

            case self::CONTENT:
                $this->_data = $value;
                break;

            default:
                throw new Exception('Bad key');
        }

        return $this;
    }

    /**
     * Задаёт ответ в виде массива
     *
     * @return $this
     *
     * Test: @see KnockResponseTest::testAsArray()
     *
     * @tag #response #array #set
     */
    public function asArray(): self
    {
        $this->isArray = true;

        return $this;
    }


    // --- Errors ---

    /**
     * Добавление ошибки в массив ошибок
     *
     * @param string $errorMessage
     *
     * @return $this
     *
     * Test: @see KnockResponseTest::testGetErrors()
     *
     * @tag #response #error #add
     */
    public function addError( string $errorMessage ): self
    {
        if ( $this->request->statusIsPrepare() ) {
            $this->_errors[] = $errorMessage;
        }

        return $this;
    }

    /**
     * Получение Trace лог истории вызовов методов
     *
     * @return array
     *
     * Test: @see KnockResponseTest::testGetErrors()
     *
     *  @tag #response #getter #error
     */
    private function getErrors(): array
    {
        return $this->_errors;
    }

    /**
     * Валидация ошибок, если ошибок нет, то возвращает true
     *
     * @return bool
     *
     * Test: @see KnockResponseTest::testValidate()
     *
     * @tag #response #validate #error
     */
    public function validate(): bool
    {
        return empty($this->_errors);
    }



    // === PRIVATE ===


    // --- Setters ---

    /**
     * Общий метод для установки значений в свойства
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     *
     * @throws Exception
     */
    public function setter( string $key, mixed $value ): void
    {
        if ( isset($this->$key) )
        {
            $error = "`$key` is already set";

            $this->addError( $error );

            throw new Exception( $error );
        }

        if ( $this->_knockRequest && $this->_knockRequest->statusIsComplete() ) {
            throw new Exception("Запрос уже был отправлен: нельзя изменить данные ответа `$key`");
        }

        $this->$key = $value;
    }

    /**
     * Установка HTTP кода ответа
     *
     * @param int $httpCode
     *
     * @return void
     *
     * @throws Exception
     *
     * Test: @see KnockResponseTest::testSetupHttpCode()
     *
     * @tag #setter #httpCode
     */
    private function setupHttpCode( int $httpCode ): void
    {
        $this->setter( '_httpCode', $httpCode );
    }

    /**
     * Установка данных ответа
     *
     * @param string $data
     *
     * @return void
     *
     * @throws Exception
     *
     * Test: @see KnockResponseTest::setupData()
     *
     * @tag #setter #data
     */
    private function setupData( string $data ): void
    {
        $this->setter( '_data', $data );
    }


    /**
     * Установка объекта запроса
     *
     * @param KnockRequest $knockRequest
     *
     * @return void
     *
     * @throws Exception
     *
     * Test: @see KnockResponseTest::testSetupRequest()
     *
     * @tag #setter #request
     */
    private function setupRequest( KnockRequest $knockRequest ): void
    {
        $this->setter( '_knockRequest', $knockRequest );
    }


    // --- Getters ---

    /**
     * Возвращает данные ответа
     *
     * @return mixed
     *
     * @throws Exception
     *
     * Test: @see KnockResponseTest::testSetupData()
     *
     * @tag #getter #data
     */
    private function getData(): mixed
    {
        if ( $this->isArray )
        {
            $content = $this->convertDataToArray($this->_data);

            if ( $content === null )
            {
                $content = $this->_data;

                $this->addError('Unknown data type: ' . gettype($content) );
            }

        } else {

            $content = $this->_data;
        }

        return $content;
    }

    /**
     * Преобразование данных в массив
     *
     * @param mixed $data
     *
     * @return ?array
     *
     * @throws Exception
     *
     * Test: @see KnockResponseTest::testSetupData()
     *
     * @tag #response #data #array
     */
    private function convertDataToArray( mixed $data ): ?array
    {
        $resp = null;

        if ( is_array($data) || is_object($data) )
        {
            $resp = (array) $data;

        } elseif ( is_string($data) ) {

            $resp =  json_decode( $data, true );

            if ( json_last_error() !== JSON_ERROR_NONE )
            {
                $resp = null;

                $this->addError('JSON decode error: ' . json_last_error_msg());
            }
        }

        return $resp;
    }

    /**
     * Возвращает объект запроса
     *
     * @return KnockRequest
     *
     * @throws Exception
     *
     * Test: @see KnockResponseTest::testSetupRequest()
     *
     * @tag #getter #request
     */
    private function getRequest(): KnockRequest
    {
        return $this->_knockRequest;
    }


    /**
     * Возвращает HTTP код ответа
     *
     * @return int
     *
     * @throws Exception
     *
     * Test: @see KnockResponseTest::testSetupHttpCode()
     *
     * @tag #getter #httpCode
     */
    private function getHttpCode(): int
    {
        return $this->_httpCode;
    }
}