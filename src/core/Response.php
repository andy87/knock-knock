<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Компонент содержащий параметры ответа
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.3.0
 */

declare(strict_types=1);

namespace andy87\knock_knock\core;

use andy87\knock_knock\interfaces\{RequestInterface, ResponseInterface};
use andy87\knock_knock\exception\{ParamNotFoundException, ParamUpdateException};

/**
 * Class Response
 *
 * @package andy87\knock_knock\query
 *
 * @property-read int $httpCode
 * @property-read mixed $content
 *
 * @property-read Request $request
 *
 * @property-read array $errors
 *
 * Покрытие тестами: 100%. @see ResponseTest
 */
class Response implements ResponseInterface
{
    /** @var array $_errors */
    protected array $_errors = [];


    /** @var bool $isArray */
    protected bool $isArray = false;


    /**
     * Response constructor.
     *
     * @param mixed $_data
     * @param int $_httpCode
     * @param ?Request $_request
     *
     * @tag #response #constructor #response
     */
    public function __construct(
        protected mixed    $_data,
        protected int      $_httpCode,
        protected ?Request $_request = null
    ){}

    /**
     * Магия для получения read-only свойств
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws ParamNotFoundException
     *
     * Test: @see ResponseTest::testMagicGet()
     *
     * @tag #response #magic #get
     */
    public function __get(string $name): mixed
    {
        return match ($name) {

            self::HTTP_CODE => $this->getterHttpCode(),
            self::CONTENT => $this->getterData(),

            'request' => $this->getterRequest(),

            RequestInterface::SETUP_CURL_OPTIONS => $this->request->curlOptions,
            RequestInterface::SETUP_CURL_INFO => $this->request->curlInfo,

            'errors' => $this->getterErrors(),

            default => throw new ParamNotFoundException("Свойство `$name` не найдено в классе " . __CLASS__),
        };
    }



    // === PUBLIC ===

    /**
     * Установка HTTP кода ответа
     *
     * @param int $httpCode
     *
     * @return void
     *
     * @throws ParamUpdateException|ParamNotFoundException
     *
     * Test: @see ResponseTest::testSetupHttpCode()
     *
     * @tag #response #setter #httpCode
     */
    public function setupHttpCode(int $httpCode): void
    {
        $this->setter('_httpCode', $httpCode);
    }

    /**
     * Установка данных ответа
     *
     * @param string $data
     *
     * @return void
     *
     * @throws ParamUpdateException|ParamNotFoundException
     *
     * Test: @see ResponseTest::setupData()
     *
     * @tag #response #setter #data
     */
    public function setupData(string $data): void
    {
        $this->setter('_data', $data);
    }

    /**
     * Установка объекта запроса
     *
     * @param Request $request
     *
     * @return void
     *
     * @throws ParamUpdateException|ParamNotFoundException
     *
     * Test: @see ResponseTest::testSetupRequest()
     *
     * @tag #response #setter #request
     */
    public function setupRequest(Request $request): void
    {
        $this->setter('_request', $request);
    }

    /**
     * Замена значений в свойствах
     *
     * @param string $key
     * @param mixed $value
     *
     * @return Response
     *
     * @throws ParamNotFoundException
     *
     * Test: @see ResponseTest::testReplace()
     *
     * @tag #response #replace #content #httpCode
     */
    public function replace(string $key, mixed $value): Response
    {
        match ($key) {
            self::HTTP_CODE => $this->_httpCode = $value,
            self::CONTENT => $this->_data = $value,
            default => throw new ParamNotFoundException('Bad key'),
        };

        return $this;
    }

    /**
     * Задаёт ответ в виде массива
     *
     * @return $this
     *
     * Test: @see ResponseTest::testAsArray()
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
     * Test: @see ResponseTest::testGetErrors()
     *
     * @tag #response #error #add
     */
    public function addError(string $errorMessage): self
    {
        if ($this->request->statusIsPrepare()) {
            $this->_errors[] = $errorMessage;
        }

        return $this;
    }

    /**
     * Получение Trace лог истории вызовов методов
     *
     * @return array
     *
     * Test: @see ResponseTest::testGetErrors()
     *
     * @tag #response #getter #error
     */
    private function getterErrors(): array
    {
        return $this->_errors;
    }

    /**
     * Валидация ошибок, если ошибок нет, то возвращает true
     *
     * @return bool
     *
     * Test: @see ResponseTest::testValidate()
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
     * @throws ParamUpdateException|ParamNotFoundException
     *
     * @tag #response #setter
     */
    private function setter(string $key, mixed $value): void
    {
        if (isset($this->$key)) {
            $error = "`$key` is already set";

            $this->addError($error);

            throw new ParamUpdateException($error);
        }

        if ($this->_request && $this->_request->statusIsComplete()) {
            throw new ParamNotFoundException("Запрос уже был отправлен: нельзя изменить данные ответа `$key`");
        }

        $this->$key = $value;
    }



    // --- Getters ---

    /**
     * Возвращает объект запроса
     *
     * @return Request
     *
     * Test: @see ResponseTest::testSetupRequest()
     *
     * @tag #response #getter #request
     */
    private function getterRequest(): Request
    {
        return $this->_request;
    }


    /**
     * Возвращает HTTP код ответа
     *
     * @return int
     *
     * Test: @see ResponseTest::testSetupHttpCode()
     *
     * @tag #response #getter #httpCode
     */
    private function getterHttpCode(): int
    {
        return $this->_httpCode;
    }

    /**
     * Возвращает данные ответа
     *
     * @return mixed
     *
     * Test: @see ResponseTest::testSetupData()
     *
     * @tag #response #getter #data
     */
    private function getterData(): mixed
    {
        if ($this->isArray) {
            $content = $this->convertDataToArray($this->_data);

            if ($content === null) {
                $content = $this->_data;

                $this->addError('Unknown data type: ' . gettype($content));
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
     * Test: @see ResponseTest::testSetupData()
     *
     * @tag #response #data #array
     */
    private function convertDataToArray(mixed $data): ?array
    {
        $resp = null;

        if (is_array($data) || is_object($data)) {
            $resp = (array)$data;

        } elseif (is_string($data)) {

            $resp = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $resp = null;

                $this->addError('JSON decode error: ' . json_last_error_msg());
            }
        }

        return $resp;
    }
}