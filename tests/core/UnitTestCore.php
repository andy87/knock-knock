<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Базовый клас для Тестов
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 0.99c
 */

declare(strict_types=1);

namespace tests\core;

use Exception;
use PHPUnit\Framework\TestCase;
use andy87\knock_knock\interfaces\KnockRequestInterface;
use andy87\knock_knock\lib\{ LibKnockMethod, LibKnockContentType };
use andy87\knock_knock\core\{ KnockKnock, KnockRequest, KnockResponse };

/**
 * Class UnitTestCore
 *
 * @package tests\core
 */
abstract class UnitTestCore extends TestCase
{
    protected const CONTENT = '{"content":"content"}';
    protected const HTTP_CODE_OK = 200;
    protected const HTTP_CODE_ERR = 500;

    protected const HOST = 'host.name';
    protected const PROTOCOL = KnockRequest::PROTOCOL_HTTP;
    protected const ENDPOINT = '/endpoint';
    protected const METHOD = LibKnockMethod::GET;
    protected const CONTENT_TYPE = LibKnockContentType::JSON;
    protected const DATA = ['dataKey' => 'dataValue'];
    protected const POST_FIELD = ['postFieldKey' => 'postFieldValue'];
    protected const HEADERS = ['headerKey' => 'headerValue'];
    protected const CURL_OPTIONS = [CURLOPT_TIMEOUT => 30];
    protected const CURL_INFO = [ CURLINFO_HEADER_OUT => true ];

    protected const PARAMS = [
        KnockRequestInterface::SETUP_PROTOCOL  => self::PROTOCOL,
        KnockRequestInterface::SETUP_HOST => self::HOST,
        KnockRequestInterface::SETUP_ENDPOINT => self::ENDPOINT,

        KnockRequestInterface::SETUP_METHOD => self::METHOD,
        KnockRequestInterface::SETUP_HEADERS => self::HEADERS,
        KnockRequestInterface::SETUP_CONTENT_TYPE => self::CONTENT_TYPE,

        KnockRequestInterface::SETUP_DATA => self::DATA,

        KnockRequestInterface::SETUP_CURL_OPTIONS => self::CURL_OPTIONS,
        KnockRequestInterface::SETUP_CURL_INFO => self::CURL_INFO,
    ];

    public const DATA_A = [ 'dataA' => 'dataA' ];
    public const DATA_B = [ 'dataB' => 'dataB' ];


    /**
     * @param ?string $host
     * @param ?array $params
     *
     * @return KnockKnock
     *
     * @throws Exception
     */
    public function getKnockKnock( ?string $host = null, ?array $params = null ): KnockKnock
    {
        return new KnockKnock($host ?? self::HOST, $params ?? self::PARAMS );
    }

    /**
     * @param ?string $endpoint
     * @param ?array $params
     *
     * @return KnockRequest
     *
     * @throws Exception
     */
    public function getKnockRequest( ?string $endpoint = null, ?array $params = null ): KnockRequest
    {
        return new KnockRequest($endpoint ?? self::ENDPOINT, $params ?? self::PARAMS );
    }

    /**
     * @param string $content
     * @param int $httpCode
     * @param ?KnockKnock $knockKnock
     * @return KnockResponse
     *
     * @throws Exception
     */
    public function getKnockResponse( string $content = self::CONTENT, int $httpCode = self::HTTP_CODE_OK, ?KnockKnock $knockKnock = null ): KnockResponse
    {
        $knockKnock = $knockKnock ?? $this->getKnockKnock();

        return new KnockResponse( $content, $httpCode, $knockKnock->commonKnockRequest );
    }
}