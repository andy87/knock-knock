<?php

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
     */
    public function useAuthorization( string $token, string $authType ): KnockKnock
    {
        $this->getCommonObjectRequest()->addHeaders( 'Authorization', "$authType $token" );

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return $this
     *
     * @throws Exception
     */
    public function useHeaders( array $headers ): KnockKnock
    {
        $headers = array_merge( $this->getCommonObjectRequest()->getHeaders(), $headers );

        $this->getCommonObjectRequest()->setHeaders( $headers );

        return $this;
    }

    /**
     * @param string $ContentType
     *
     * @return $this
     *
     * @throws Exception
     */
    public function useContentType( string $ContentType ): KnockKnock
    {
        $this->getCommonObjectRequest()->setContentType( $ContentType );

        return $this;
    }
}