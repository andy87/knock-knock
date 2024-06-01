<?php /**
 * @name: Handler
 * @author Andrey and_y87 Kidin
 * @description Тесты для методов класса KnockKnockSecurity
 * @homepage: https://github.com/andy87/Handler
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.0.2
 */

declare(strict_types=1);

namespace andy87\knock_knock\tests\extensions;

use andy87\knock_knock\interfaces\{RequestInterface, ResponseInterface};
use andy87\knock_knock\KnockKnockSecurity;
use andy87\knock_knock\lib\{ContentType, Method};
use andy87\knock_knock\tests\helpers\{PostmanEcho, UnitTestCore};
use Exception;

/**
 * Class KnockKnockSecurityTest
 *
 * Тесты для методов класса KnockKnockSecurity
 *
 * @package tests
 *
 * @cli vendor/bin/phpunit tests/KnockKnockSecurityTest.php --testdox
 *
 * @tag #test #Handler #security
 */
class KnockKnockSecurityTest extends UnitTestCore
{
    private const TOKEN_BASIC = 'testBasicToken';
    private const TOKEN_BEARER = 'testBearerToken';

    private const OLD = 'old';
    private const NEW = 'new';

    protected const HEADERS = [
        self::OLD => [
            'X-Test-Header-old' => 'testHeaderValueOld',
        ],
        self::NEW => [
            'X-Test-Header-new' => 'testHeaderValueNew',
        ],
    ];

    protected const FAKE_RESPONSE = [
        ResponseInterface::CONTENT => '{"content":"content"}',
        ResponseInterface::HTTP_CODE => 200,
    ];



    /** @var KnockKnockSecurity $KnockKnockSecurity */
    public static KnockKnockSecurity $KnockKnockSecurity;


    /**
     * Вспомогательный метод для получения объекта KnockKnockSecurity
     *
     * @param array $commonRequestParams
     *
     * @return KnockKnockSecurity
     *
     * @throws Exception
     *
     * @tag #security #setup #authorization
     */
    private function getKnockKnockOctopus( array $commonRequestParams = [] ): KnockKnockSecurity
    {
        return new KnockKnockSecurity(
            PostmanEcho::HOST,
            $commonRequestParams
        );
    }

    /**
     * Тест метода setupAuthorization
     *
     *      Ожидается, что `commonRequest->headers` будет содержать ключ `Authorization`
     *      с актуальным значением токена. Проверяется так же перезапись токена.
     *
     * @param string $tokenType
     * @param string $token
     *
     * @return void
     *
     * @throws Exception
     *
     * Source: @see KnockKnockSecurity::setupAuthorization()
     *
     * @dataProvider SetupAuthProvider
     *
     * @cli vendor/bin/phpunit tests/KnockKnockSecurityTest.php --testdox --filter testSetupAuthorization
     *
     * @tag #security #setup #authorization
     */
    public function testSetupAuthorization( string $tokenType, string $token ): void
    {
        $KnockKnockSecurity = $this->getKnockKnockOctopus();

        $response = $KnockKnockSecurity
            ->setupAuthorization( $tokenType, $token )
            ->get(PostmanEcho::ENDPOINT_GET );

        $this->assertArrayHasKey(
            'Authorization',
            $response->request->headers,
            "Ожидается, что в заголовках будет ключ 'Authorization'"
        );
        $this->assertEquals(
            $tokenType . ' ' . $token,
            $response->request->headers[ 'Authorization' ],
            "Ожидается, что значение будет равно '" . $tokenType . ' ' . $token . "'"
        );

        // перезапись токена
        $response = $KnockKnockSecurity
            ->setupAuthorization( $tokenType, ( $token . 'New' ) )
            ->get(PostmanEcho::ENDPOINT_GET );

        $this->assertArrayHasKey(
            'Authorization',
            $response->request->headers,
            "Ожидается, что в заголовках будет ключ 'Authorization'"
        );
        $this->assertEquals(
            $tokenType . ' ' . $token . 'New',
            $response->request->headers[ 'Authorization' ],
            "Ожидается, что значение будет равно '" . $tokenType . ' ' . $token . 'New' . "'"
        );
    }

    /**
     * Данные для теста `testSetupAuthorization`
     *
     * Data: @see KnockKnockSecurityTest::testSetupAuthorization()
     *
     * @return array[]
     *
     * @tag #test #Handler #provider #validate #hostName
     */
    public static function SetupAuthProvider(): array
    {
        return [
            [ KnockKnockSecurity::TOKEN_BASIC, self::TOKEN_BASIC ],
            [ KnockKnockSecurity::TOKEN_BEARER, self::TOKEN_BEARER ],
        ];
    }




    /**
     * Тест метода setupHeaders
     *
     *      Ожидается, что `realRequest->headers` будет содержать ключи из массива `$headers`
     *      с актуальными значениями. Проверяется так же перезапись заголовков.
     *
     * @return void
     *
     * @throws Exception
     *
     * Source: @see KnockKnockSecurity::setupHeaders()
     *
     * @cli vendor/bin/phpunit tests/KnockKnockSecurityTest.php --testdox --filter testSetupHeaders
     *
     * @tag #security #setup #headers
     */
    public function testSetupHeaders(): void
    {
        $KnockKnockSecurity = $this->getKnockKnockOctopus();

        $KnockKnockSecurity->setupHeaders(self::HEADERS[ self::OLD ]);

        $response = $KnockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

        $this->assertArrayHasKey(
            'X-Test-Header-old',
            $response->request->headers,
            "Ожидается, что в заголовках будет ключ 'X-Test-Header-old'"
        );
        $this->assertEquals(
            self::HEADERS[ self::OLD ][ 'X-Test-Header-old' ],
            $response->request->headers[ 'X-Test-Header-old' ],
            "Ожидается, что значение будет равно 'testHeaderValue'"
        );

        // перезапись заголовков
        $KnockKnockSecurity->setupHeaders(self::HEADERS[ self::NEW ]);

        $response = $KnockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

        $this->assertArrayHasKey(
            'X-Test-Header-new',
            $response->request->headers,
            "Ожидается, что в заголовках будет ключ 'X-Test-Header-new'"
        );
        $this->assertEquals(
            self::HEADERS[ self::NEW ][ 'X-Test-Header-new' ],
            $response->request->headers[ 'X-Test-Header-new' ],
            "Ожидается, что значение будет равно 'testHeaderValueNew'"
        );
    }

    /**
     * Тест метода setupContentType
     *
     *      Ожидается, что `realRequest->headers` будет содержать ключ `Content-Type`
     *      с актуальным значением типа контента. Проверяется так же перезапись типа контента.
     *
     * @return void
     *
     * @throws Exception
     *
     * Source: @see KnockKnockSecurity::setupContentType()
     *
     * @cli vendor/bin/phpunit tests/KnockKnockSecurityTest.php --testdox --filter testSetupContentType
     *
     * @tag #security #setup #content-type
     */
    public function testSetupContentType(): void
    {
        $KnockKnockSecurity = $this->getKnockKnockOctopus();

        $response = $KnockKnockSecurity
            ->setupContentType(ContentType::JSON)
            ->get(PostmanEcho::ENDPOINT_GET );

        $this->assertEquals(
            ContentType::JSON,
            $response->request->contentType,
            'Ожидается, что `$response->request->contentType` будет равен' . ContentType::JSON
        );

        // перезапись типа контента
        $response = $KnockKnockSecurity
            ->setupContentType(ContentType::XML)
            ->get(PostmanEcho::ENDPOINT_GET );

        $this->assertEquals(
            ContentType::XML,
            $response->request->contentType,
            'Ожидается, что `$response->request->contentType` будет равен' . ContentType::XML
        );
    }

    /**
     * Тест метода useHeaders
     *
     *      Ожидается, что `realRequest->headers` будет содержать ключи из массива `$headers`
     *      с актуальными значениями. Проверяется так же перезапись заголовков.
     *
     * @return void
     *
     * @throws Exception
     *
     * Source: @see KnockKnockSecurity::useHeaders()
     *
     * @cli vendor/bin/phpunit tests/KnockKnockSecurityTest.php --testdox --filter testUseHeaders
     *
     * @tag #security #use #headers
     */
    public function testUseHeaders(): void
    {
        $KnockKnockSecurity = $this->getKnockKnockOctopus();

        $KnockKnockSecurity->setupHeaders(self::HEADERS[ self::OLD ]);

        $response = $KnockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

        $this->assertArrayHasKey(
            'X-Test-Header-old',
            $response->request->headers,
            "Ожидается, что в заголовках будет ключ 'X-Test-Header-old'"
        );
        $this->assertEquals(
            self::HEADERS[ self::OLD ][ 'X-Test-Header-old' ],
            $response->request->headers[ 'X-Test-Header-old' ],
            "Ожидается, что значение будет равно 'testHeaderValueOld'"
        );

        // перезапись заголовков
        $KnockKnockSecurity->useHeaders(self::HEADERS[ self::NEW ]);

        $response = $KnockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

        $this->assertArrayHasKey(
            'X-Test-Header-new',
            $response->request->headers,
            "Ожидается, что в заголовках будет ключ 'X-Test-Header-new'"
        );
        $this->assertEquals(
            self::HEADERS[ self::NEW ][ 'X-Test-Header-new' ],
            $response->request->headers[ 'X-Test-Header-new' ],
            "Ожидается, что значение будет равно 'testHeaderValueNew'"
        );

        $response = $KnockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

        $this->assertArrayHasKey(
            'X-Test-Header-old',
            $response->request->headers,
            "Ожидается, что в заголовках будет ключ 'X-Test-Header-old'"
        );
        $this->assertEquals(
            self::HEADERS[ self::OLD ][ 'X-Test-Header-old' ],
            $response->request->headers[ 'X-Test-Header-old' ],
            "Ожидается, что значение будет равно 'testHeaderValueOld'"
        );
    }

    /**
     * Тест метода send
     *
     *      Ожидается, что метод вернёт объект Response с актуальными данными.
     *
     * @return void
     *
     * @throws Exception
     *
     * Source: @see KnockKnockSecurity::send()
     *
     * @cli vendor/bin/phpunit tests/KnockKnockSecurityTest.php --testdox --filter testSend
     *
     * @tag #security #send
     */
    public function testSend(): void
    {
        $KnockKnockSecurity = $this->getKnockKnockOctopus();

        // Проверка отправки запроса без фейкового ответа
        $Request = $KnockKnockSecurity
            ->constructRequest(
                Method::GET,
                PostmanEcho::ENDPOINT_GET,
                [
                    RequestInterface::SETUP_DATA => [
                        'test' => 'test',
                    ],
                ]
            );
        $Response = $KnockKnockSecurity->setupRequest($Request)->send();

        $content = json_decode($Response->content, true);

        $this->assertArrayHasKey( 'args', $content,"Ожидается, что в ответе будет ключ 'args'");
        $this->assertArrayHasKey( 'headers', $content,"Ожидается, что в ответе будет ключ 'headers'");
        $this->assertArrayHasKey( 'url', $content,"Ожидается, что в ответе будет значение 'url'");

        // Проверка отправки запроса с фейковым ответом
        $Request = $KnockKnockSecurity
            ->constructRequest(
                Method::GET,
                PostmanEcho::ENDPOINT_GET,
                [
                    RequestInterface::SETUP_DATA => [
                        'test' => 'test',
                    ],
                ]
            );
        $Response = $KnockKnockSecurity
            ->setupRequest($Request)
            ->send( self::FAKE_RESPONSE );

        $this->assertEquals(
            self::FAKE_RESPONSE[ ResponseInterface::CONTENT ],
            $Response->content,
            "Ожидается, что контент будет равен '" . self::FAKE_RESPONSE[ ResponseInterface::CONTENT ] . "'"
        );
        $this->assertEquals(
            self::FAKE_RESPONSE[ ResponseInterface::HTTP_CODE ],
            $Response->httpCode,
            "Ожидается, что код ответа будет равен '" . self::FAKE_RESPONSE[ ResponseInterface::HTTP_CODE ] . "'"
        );
    }

    /**
     *  Проверка что запрос получит кастомные данные из свойства use
     *
     *      Ожидается, что данные запроса в ответе будут содержать все данные из свойства use
     *      и не будут содержать данные в запросе ответа последующих запросов.
     *
     * @return void
     *
     * @throws Exception
     *
     * Source: @see KnockKnockSecurity::modifyRequestByUse()
     * Source: @see KnockKnockSecurity::clearUse()
     *
     * @cli vendor/bin/phpunit tests/KnockKnockSecurityTest.php --testdox --filter testModifyRequestByUse
     *
     * @tag #security #use #request #modify
     */
    public function testModifyRequestByUse(): void
    {
        $KnockKnockSecurity = $this
            ->getKnockKnockOctopus([
                RequestInterface::SETUP_CONTENT_TYPE => ContentType::JSON,
                RequestInterface::SETUP_HEADERS => self::HEADERS[ self::OLD ],
            ])
            ->setupAuthorization(KnockKnockSecurity::TOKEN_BASIC, self::TOKEN_BASIC );

        $this->assertInstanceOf(KnockKnockSecurity::class,
            $KnockKnockSecurity,
            "Ожидается, что объект будет типа KnockKnockSecurity"
        );
        $this->assertEquals(ContentType::JSON,
            $KnockKnockSecurity->commonRequest->contentType,
            "Ожидается, что тип контента БУДЕТ равен " . ContentType::MULTIPART
        );
        $this->assertArrayHasKey('X-Test-Header-old',
            $KnockKnockSecurity->commonRequest->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'X-Test-Header-old'"
        );
        $this->assertArrayNotHasKey('X-Test-Header-new',
            $KnockKnockSecurity->commonRequest->headers,
            "Ожидается, что в заголовках НЕ будет ключ 'X-Test-Header-new'"
        );
        $this->assertEquals(self::HEADERS[ self::OLD ][ 'X-Test-Header-old' ],
            $KnockKnockSecurity->commonRequest->headers[ 'X-Test-Header-old' ],
            "Ожидается, что значение будет равно 'testHeaderValueOld'"
        );
        $this->assertArrayHasKey(KnockKnockSecurity::HEADERS_AUTH_KEY,
            $KnockKnockSecurity->commonRequest->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'Authorization'"
        );
        $this->assertEquals(KnockKnockSecurity::TOKEN_BASIC . ' ' . self::TOKEN_BASIC,
            $KnockKnockSecurity->commonRequest->headers[ KnockKnockSecurity::HEADERS_AUTH_KEY ],
            "Ожидается, что значение БУДЕТ равно '" . KnockKnockSecurity::TOKEN_BASIC . ' ' . self::TOKEN_BASIC . "'"
        );

        $Response = $KnockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

        $this->assertEquals(ContentType::JSON,
            $Response->request->contentType,
            "Ожидается, что тип контента БУДЕТ равен " . ContentType::JSON
        );
        $this->assertArrayHasKey('X-Test-Header-old',
            $Response->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключа 'X-Test-Header-old'"
        );
        $this->assertArrayHasKey(KnockKnockSecurity::HEADERS_AUTH_KEY,
            $Response->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключа 'Authorization'"
        );
        $this->assertArrayNotHasKey('X-Test-Header-new',
            $Response->request->headers,
            "Ожидается, что в заголовках НЕ будет ключ 'X-Test-Header-new'"
        );
        $this->assertArrayHasKey(KnockKnockSecurity::HEADERS_AUTH_KEY,
            $Response->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'Authorization'"
        );
        $this->assertEquals(KnockKnockSecurity::TOKEN_BASIC . ' ' . self::TOKEN_BASIC,
            $Response->request->headers[ KnockKnockSecurity::HEADERS_AUTH_KEY ],
            "Ожидается, что значение БУДЕТ равно '" . KnockKnockSecurity::TOKEN_BASIC . ' ' . self::TOKEN_BASIC . "'"
        );

        $KnockKnockSecurity->useHeaders(self::HEADERS[ self::NEW ]);
        $KnockKnockSecurity->useContentType(ContentType::XML);
        $KnockKnockSecurity->setupAuthorization(KnockKnockSecurity::TOKEN_BEARER, self::TOKEN_BEARER );

        $Response = $KnockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

        $this->assertEquals(ContentType::XML,
            $Response->request->contentType,
            "Ожидается, что тип контента БУДЕТ равен " . ContentType::XML
        );
        $this->assertArrayHasKey('X-Test-Header-new',
            $Response->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'X-Test-Header-new'"
        );
        $this->assertArrayHasKey(KnockKnockSecurity::HEADERS_AUTH_KEY,
            $Response->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'Authorization'"
        );
        $this->assertArrayHasKey('X-Test-Header-old',
            $Response->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'X-Test-Header-old'"
        );
        $this->assertArrayHasKey(KnockKnockSecurity::HEADERS_AUTH_KEY,
            $Response->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'Authorization'"
        );
        $this->assertEquals(KnockKnockSecurity::TOKEN_BEARER . ' ' . self::TOKEN_BEARER,
            $Response->request->headers[ KnockKnockSecurity::HEADERS_AUTH_KEY ],
            "Ожидается, что значение БУДЕТ равно '" . KnockKnockSecurity::TOKEN_BEARER . ' ' . self::TOKEN_BEARER . "'"
        );

        $KnockKnockSecurity->setupAuthorization(KnockKnockSecurity::TOKEN_BASIC, self::TOKEN_BASIC );

        $Response = $KnockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

        $this->assertEquals(ContentType::JSON,
            $Response->request->contentType,
            "Ожидается, что тип контента БУДЕТ равен " . ContentType::JSON
        );
        $this->assertArrayHasKey('X-Test-Header-old',
            $Response->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'X-Test-Header-old'"
        );
        $this->assertArrayNotHasKey('X-Test-Header-new',
            $Response->request->headers,
            "Ожидается, что в заголовках НЕ будет ключ 'X-Test-Header-new'"
        );
        $this->assertArrayHasKey(KnockKnockSecurity::HEADERS_AUTH_KEY,
            $Response->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'Authorization'"
        );
        $this->assertEquals(KnockKnockSecurity::TOKEN_BASIC . ' ' . self::TOKEN_BASIC,
            $Response->request->headers[ KnockKnockSecurity::HEADERS_AUTH_KEY ],
            "Ожидается, что значение БУДЕТ равно '" . KnockKnockSecurity::TOKEN_BASIC . ' ' . self::TOKEN_BASIC . "'"
        );
    }
}