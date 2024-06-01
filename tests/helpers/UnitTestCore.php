<?php /**
 * @name: Handler
 * @author Andrey and_y87 Kidin
 * @description Базовый клас для Тестов
 * @homepage: https://github.com/andy87/Handler
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.0.2
 */

declare(strict_types=1);

namespace andy87\knock_knock\tests\helpers;

use Exception;
use PHPUnit\Framework\TestCase;
use andy87\knock_knock\interfaces\RequestInterface;
use andy87\knock_knock\lib\{ Method, ContentType };
use andy87\knock_knock\core\{ Handler, Request, Response };

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
    protected const PROTOCOL = Request::PROTOCOL_HTTP;
    protected const ENDPOINT = '/endpoint';
    protected const METHOD = Method::GET;
    protected const CONTENT_TYPE = ContentType::JSON;
    protected const DATA = ['dataKey' => 'dataValue'];
    protected const POST_FIELD = ['postFieldKey' => 'postFieldValue'];
    protected const HEADERS = ['headerKey' => 'headerValue'];
    protected const CURL_OPTIONS = [CURLOPT_TIMEOUT => 30];
    protected const CURL_INFO = [ CURLINFO_HEADER_OUT => true ];

    protected const PARAMS = [
        RequestInterface::SETUP_PROTOCOL  => self::PROTOCOL,
        RequestInterface::SETUP_HOST => self::HOST,
        RequestInterface::SETUP_ENDPOINT => self::ENDPOINT,

        RequestInterface::SETUP_METHOD => self::METHOD,
        RequestInterface::SETUP_HEADERS => self::HEADERS,
        RequestInterface::SETUP_CONTENT_TYPE => self::CONTENT_TYPE,

        RequestInterface::SETUP_DATA => self::DATA,

        RequestInterface::SETUP_CURL_OPTIONS => self::CURL_OPTIONS,
        RequestInterface::SETUP_CURL_INFO => self::CURL_INFO,
    ];

    public const DATA_A = [ 'dataA' => 'dataA' ];
    public const DATA_B = [ 'dataB' => 'dataB' ];


    /**
     * @param ?string $host
     * @param ?array $params
     *
     * @return Handler
     *
     * @throws Exception
     */
    public function getHandler( ?string $host = null, ?array $params = null ): Handler
    {
        return new Handler($host ?? self::HOST, $params ?? self::PARAMS );
    }

    /**
     * @param ?string $endpoint
     * @param ?array $params
     *
     * @return Request
     *
     * @throws Exception
     */
    public function getRequest( ?string $endpoint = null, ?array $params = null ): Request
    {
        return new Request($endpoint ?? self::ENDPOINT, $params ?? self::PARAMS );
    }

    /**
     * @param string $content
     * @param int $httpCode
     * @param ?Handler $Handler
     * @return Response
     *
     * @throws Exception
     */
    public function getResponse( string $content = self::CONTENT, int $httpCode = self::HTTP_CODE_OK, ?Handler $Handler = null ): Response
    {
        $Handler = $Handler ?? $this->getHandler();

        return new Response( $content, $httpCode, $Handler->commonRequest );
    }
}