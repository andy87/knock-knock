<?php declare(strict_types=1);

/**
 * @name: knock-knock
 * @author Andrey and_y87 Kidin
 * @description Расширяет класс KnockKnockOctopus и предоставляет доступ к функционалу для простой и быстрой реализации авторизации и настройки запросов.
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.3.2
 */

namespace andy87\knock_knock;

use andy87\knock_knock\interfaces\RequestInterface;
use andy87\knock_knock\core\{Operator, Request, Response};
use andy87\knock_knock\exception\{ParamNotFoundException, ParamUpdateException, extensions\InvalidAuthException};
use andy87\knock_knock\exception\request\{InvalidHeaderException,
    InvalidRequestException,
    RequestCompleteException,
    StatusNotFoundException};

/**
 * Class KnockAuthorization
 *
 * @package andy87\knock_knock
 *
 * Покрытие тестами: 100%. @see RequestTest
 */
class KnockKnockSecurity extends KnockKnockOctopus
{
    public const HEADERS_AUTH_KEY = 'Authorization';

    /** @var string */
    public const TOKEN_BEARER = 'Bearer';
    /** @var string */
    public const TOKEN_BASIC = 'Basic';


    /** @var array Массив с кастомными данными для следующего запроса */
    private array $use = [];


    // === Setup ===

    /**
     * Задаёт для всех запросов, тип авторизации и токен
     *
     * @param string $token
     * @param string $authType
     *
     * @return $this
     *
     * @throws InvalidHeaderException|StatusNotFoundException|ParamUpdateException|InvalidAuthException
     *
     * Test: @see KnockKnockSecurityTest::testSetupAuthorization()
     *
     * @tag #security #setup #authorization
     */
    public function setupAuthorization(string $authType, string $token): Operator
    {
        if (in_array($authType, [self::TOKEN_BEARER, self::TOKEN_BASIC])) {
            $this->getterCommonRequest()->setHeader(self::HEADERS_AUTH_KEY, "$authType $token");

            return $this;
        }

        throw new InvalidAuthException();
    }

    /**
     * Задаёт для всех запросов, данные которые обязательно должны быть в заголовках запроса
     *
     * @param array $headers
     *
     * @return $this
     *
     * @throws InvalidHeaderException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockSecurityTest::testSetupHeaders()
     *
     * @tag #security #setup #headers
     */
    public function setupHeaders(array $headers): Operator
    {
        $headers = array_merge($this->getterCommonRequest()->headers, $headers);

        $this->getterCommonRequest()->addHeaders($headers);

        return $this;
    }

    /**
     * Задаёт для всех запросов, тип контента
     *
     * @param string $ContentType
     *
     * @return $this
     *
     * @throws ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     * Test: @see KnockKnockSecurityTest::testSetupContentType()
     *
     * @tag #security #setup #content-type
     */
    public function setupContentType(string $ContentType): Operator
    {
        $this->getterCommonRequest()->setContentType($ContentType);

        return $this;
    }



    // === Use ===

    /**
     * Задаёт кастомные данные для заголовков следующего запроса
     *
     * @param array $headers
     *
     * @return $this
     *
     * Test: @see KnockKnockSecurityTest::testUseHeaders()
     *
     * @tag #security #use #headers
     */
    public function useHeaders(array $headers): Operator
    {
        $this->use[interfaces\RequestInterface::SETUP_HEADERS] = $headers;

        return $this;
    }

    /**
     * Задаёт кастомный тип контента для следующего запроса
     *
     * @param string $ContentType
     *
     * @return $this
     *
     * Test: @see KnockKnockSecurityTest::testUseContentType()
     *
     * @tag #security #use #content-type
     */
    public function useContentType(string $ContentType): Operator
    {
        $this->use[interfaces\RequestInterface::SETUP_CONTENT_TYPE] = $ContentType;

        return $this;
    }



    // === ReWrite ===

    /**
     * Модификация метода send для отправки запроса с кастомными данными (array $use)
     *
     * @param ?interfaces\RequestInterface $request
     *
     * @return Response
     *
     * @throws InvalidHeaderException|StatusNotFoundException|ParamUpdateException|ParamNotFoundException|RequestCompleteException|InvalidRequestException
     *
     * Test: @see KnockKnockSecurityTest::testSend()
     *
     * @tag #security #use #send
     */
    public function send(?RequestInterface $request = null): Response
    {
        if (count($this->use)) {
            $this->modifyRequestByUse($this->getterRealRequest());
        }

        return parent::send($request);
    }

    /**
     * Применение кастомных данных(array $use) к запросу `$request`
     *
     * @param Request $request
     *
     * @return void
     *
     * @throws InvalidHeaderException|StatusNotFoundException|ParamUpdateException|ParamNotFoundException
     *
     * Test: @see KnockKnockSecurityTest::testModifyRequestByUse()
     *
     * @tag #security #use #request #modify
     */
    protected function modifyRequestByUse(Request $request): void
    {
        if (isset($this->use[interfaces\RequestInterface::SETUP_HEADERS])) {
            $request->addHeaders($this->use[interfaces\RequestInterface::SETUP_HEADERS]);
        }

        if (isset($this->use[interfaces\RequestInterface::SETUP_CONTENT_TYPE])) {
            $request->setContentType($this->use[interfaces\RequestInterface::SETUP_CONTENT_TYPE]);
        }

        $this->clearUse();
    }

    /**
     * Очистка массива с кастомными данными
     *
     * @return void
     *
     * Test: @see KnockKnockSecurityTest::testModifyRequestByUse()
     *
     * @tag #security #use #clear
     */
    private function clearUse(): void
    {
        $this->use = [];
    }
}