<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Расширяет класс KnockKnockOctopus и предоставляет доступ к функционалу для простой и быстрой реализации авторизации и настройки запросов.
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 0.99c
 */

declare(strict_types=1);

namespace andy87\knock_knock;

use Exception;
use andy87\knock_knock\core\{ KnockKnock, KnockRequest, KnockResponse };

/**
 * Class KnockAuthorization
 *
 * @package andy87\knock_knock
 *
 * Покрытие тестами: 100%. @see KnockRequestTest
 */
class KnockKnockSecurity extends KnockKnockOctopus
{
    public const HEADERS_AUTH_KEY = 'Authorization';

    /** @var string  */
    public const TOKEN_BEARER = 'Bearer';
    /** @var string  */
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
     * @throws Exception
     *
     * Test: @see KnockKnockSecurityTest::testSetupAuthorization()
     *
     * @tag #security #setup #authorization
     */
    public function setupAuthorization( string $authType, string $token ): KnockKnock
    {
        if ( in_array( $authType, [ self::TOKEN_BEARER, self::TOKEN_BASIC ] ) )
        {
            $this->getCommonKnockRequest()->setHeader( self::HEADERS_AUTH_KEY, "$authType $token" );

            return $this;
        }

        throw new Exception( 'Invalid authorization type' );
    }

    /**
     * Задаёт для всех запросов, данные которые обязательно должны быть в заголовках запроса
     *
     * @param array $headers
     *
     * @return $this
     *
     * @throws Exception
     *
     * Test: @see KnockKnockSecurityTest::testSetupHeaders()
     *
     * @tag #security #setup #headers
     */
    public function setupHeaders( array $headers ): KnockKnock
    {
        $headers = array_merge( $this->getCommonKnockRequest()->headers, $headers );

        $this->getCommonKnockRequest()->addHeaders( $headers );

        return $this;
    }

    /**
     * Задаёт для всех запросов, тип контента
     *
     * @param string $ContentType
     *
     * @return $this
     *
     * @throws Exception
     *
     * Test: @see KnockKnockSecurityTest::testSetupContentType()
     *
     * @tag #security #setup #content-type
     */
    public function setupContentType( string $ContentType ): KnockKnock
    {
        $this->getCommonKnockRequest()->setContentType( $ContentType );

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
    public function useHeaders( array $headers ): KnockKnock
    {
        $this->use[ interfaces\KnockRequestInterface::SETUP_HEADERS ] = $headers;

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
    public function useContentType( string $ContentType ): KnockKnock
    {
        $this->use[ interfaces\KnockRequestInterface::SETUP_CONTENT_TYPE ] = $ContentType;

        return $this;
    }



    // === ReWrite ===

    /**
     * Модификация метода send для отправки запроса с кастомными данными (array $use)
     *
     * @param array $fakeResponse
     *
     * @return KnockResponse
     *
     * @throws Exception
     *
     * Test: @see KnockKnockSecurityTest::testSend()
     *
     * @tag #security #use #send
     */
    public function send( array $fakeResponse = [] ): KnockResponse
    {
        if ( count( $this->use ) ) {
            $this->modifyRequestByUse( $this->getRealKnockRequest() );
        }

        return $this->sendRequest( $this->getRealKnockRequest(), $fakeResponse );
    }

    /**
     * Применение кастомных данных(array $use) к запросу `$knockRequest`
     *
     * @param KnockRequest $knockRequest
     *
     * @return void
     *
     * @throws Exception
     *
     * Test: @see KnockKnockSecurityTest::testModifyRequestByUse()
     *
     * @tag #security #use #request #modify
     */
    protected function modifyRequestByUse( KnockRequest $knockRequest ): void
    {
        if ( isset($this->use[ interfaces\KnockRequestInterface::SETUP_HEADERS ]) ) {
            $knockRequest->addHeaders( $this->use[ interfaces\KnockRequestInterface::SETUP_HEADERS ] );
        }

        if ( isset($this->use[ interfaces\KnockRequestInterface::SETUP_CONTENT_TYPE ]) ) {
            $knockRequest->setContentType( $this->use[ interfaces\KnockRequestInterface::SETUP_CONTENT_TYPE ] );
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