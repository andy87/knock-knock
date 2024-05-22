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


    /**
     * @param string $token
     * @param string $authType
     *
     * @return $this
     *
     * @throws Exception
     *
     * @tag #security #authorization
     */
    public function useAuthorization( string $token, string $authType ): KnockKnock
    {
        $this->getCommonKnockRequest()->addHeaders( 'Authorization', "$authType $token" );

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return $this
     *
     * @throws Exception
     *
     * @tag #security #headers
     */
    public function useHeaders( array $headers ): KnockKnock
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
     * @tag #security #content-type
     */
    public function useContentType( string $ContentType ): KnockKnock
    {
        $this->getCommonKnockRequest()->setContentType( $ContentType );

        return $this;
    }
}