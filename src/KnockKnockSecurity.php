<?php /**
 * KnockKnockSecurity
 *
 * @author Andrey and_y87 Kidin
 * @description Класс предоставляет доступ к "функциональным" методам для простой реализации авторизации и отправки запросов через ext cURL
 *
 * @date 2024-05-22
 *
 * @version 0.87
 */

namespace andy87\knock_knock;

use andy87\knock_knock\core\KnockKnock;
use andy87\knock_knock\core\KnockRequest;
use andy87\knock_knock\core\KnockResponse;
use Exception;

/**
 * Class KnockAuthorization
 *
 * @package andy87\knock_knock
 *
 * Fix not used:
 * - @see KnockKnockSecurity::TOKEN_BEARER;
 * - @see KnockKnockSecurity::TOKEN_BASIC;
 *
 * - @see KnockKnockSecurity::useAuthorization();
 * - @see KnockKnockSecurity::useHeaders();
 * - @see KnockKnockSecurity::useContentType();
 */
class KnockKnockSecurity extends KnockKnockOctopus
{
    /** @var string  */
    public const TOKEN_BEARER = 'Bearer';
    /** @var string  */
    public const TOKEN_BASIC = 'Basic';



    /** @var array Массив с кастомными данными для следующего запроса */
    private array $use = [];



    // === Setup ===

    /**
     * @param string $token
     * @param string $authType
     *
     * @return $this
     *
     * @throws Exception
     *
     * @tag #security #setup #authorization
     */
    public function setupAuthorization( string $authType, string $token ): KnockKnock
    {
        if ( in_array( $authType, [ self::TOKEN_BEARER, self::TOKEN_BASIC ] ) )
        {
            $this->getCommonKnockRequest()->addHeaders( 'Authorization', "$authType $token" );

            return $this;
        }

        throw new Exception( 'Invalid authorization type' );
    }

    /**
     * @param array $headers
     *
     * @return $this
     *
     * @throws Exception
     *
     * @tag #security #setup #headers
     */
    public function setupHeaders( array $headers ): KnockKnock
    {
        $headers = array_merge( $this->getCommonKnockRequest()->getHeaders(), $headers );

        $this->getCommonKnockRequest()->setHeaders( $headers );

        return $this;
    }

    /**
     * @param string $ContentType
     *
     * @return $this
     *
     * @throws Exception
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
     * @param array $headers
     *
     * @return $this
     *
     * @tag #security #use #headers
     */
    public function useHeaders( array $headers ): KnockKnock
    {
        $this->use[ interfaces\KnockRequestInterface::SETUP_HEADERS ] = $headers;

        return $this;
    }

    /**
     * @param string $ContentType
     *
     * @return $this
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
     * @tag #security #use #send
     */
    public function send( array $fakeResponse = [] ): KnockResponse
    {
        $realKnockRequest = $this->getRealKnockRequest();

        if ( count($this->use) ) {
            $realKnockRequest = $this->modifyRequest( $realKnockRequest );
        }

        return $this->sendRequest( $realKnockRequest, $fakeResponse );
    }

    /**
     * Применение кастомных данных(array $use) к запросу `$knockRequest`
     *
     * @param KnockRequest $knockRequest
     *
     * @return KnockRequest
     *
     * @throws Exception
     *
     * @tag #security #use #request #modify
     */
    private function modifyRequest( KnockRequest $knockRequest ): KnockRequest
    {
        if ( isset($this->use[ interfaces\KnockRequestInterface::SETUP_HEADERS ]) ) {
            $knockRequest->setHeaders( $this->use[ interfaces\KnockRequestInterface::SETUP_HEADERS ] );
        }

        if ( isset($this->use[ interfaces\KnockRequestInterface::SETUP_CONTENT_TYPE ]) ) {
            $knockRequest->setContentType( $this->use[ interfaces\KnockRequestInterface::SETUP_CONTENT_TYPE ] );
        }

        $this->clearUse();

        return $knockRequest;
    }

    /**
     * Очистка массива с кастомными данными
     *
     * @return void
     *
     * @tag #security #use #clear
     */
    private function clearUse(): void
    {
        $this->use = [];
    }
}