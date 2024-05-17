<?php

namespace andy87\knock_knock;

use andy87\knock_knock\core\KnockKnock;

/**
 * Class KnockAuthorization
 *
 * @package andy87\knock_knock
 *
 * Fix not used:
 * - @see KnockKnockAuthorization::TOKEN_BEARER;
 * - @see KnockKnockAuthorization::TOKEN_BASIC;
 *
 * - @see KnockKnockAuthorization::useAuthorization();
 * - @see KnockKnockAuthorization::useHeaders();
 * - @see KnockKnockAuthorization::useContentType();
 */
class KnockKnockAuthorization extends KnockKnock
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
     */
    public function useAuthorization( string $token, string $authType ): KnockKnock
    {
        $this->commonKnockRequest->addHeaders( 'Authorization', "$authType $token" );

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return $this
     */
    public function useHeaders( array $headers ): KnockKnock
    {
        $headers = array_merge( $this->commonKnockRequest->getHeaders(), $headers );

        $this->commonKnockRequest->setHeaders( $headers );

        return $this;
    }

    /**
     * @param string $ContentType
     *
     * @return $this
     */
    public function useContentType( string $ContentType ): KnockKnock
    {
        $this->commonKnockRequest->setContentType( $ContentType );

        return $this;
    }
}