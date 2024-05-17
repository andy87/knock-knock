<?php

namespace andy87\knock_knock\interfaces;

/**
 * Interface KnockResponseInterface
 *
 * @package andy87\knock_knock\interfaces
 */
interface KnockResponseInterface
{
    /**
     * @param mixed $content
     *
     * @return self
     */
    public function setContent( $content ): self;

    /**
     * @return mixed
     */
    public function getContent();

    /**
     * @param int $httpCode
     *
     * @return self
     */
    public function setHttpCode( int $httpCode ): self;

    /**
     * @return int
     */
    public function getHttpCode(): int;
}