<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Тесты для методов класса KnockKnockSecurity
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 0.99c
 */

declare(strict_types=1);

namespace andy87\knock_knock\tests;

use Exception;
use andy87\knock_knock\KnockKnockSecurity;
use andy87\knock_knock\tests\core\{ UnitTestCore, PostmanEcho };
use andy87\knock_knock\lib\{ LibKnockMethod, LibKnockContentType };
use andy87\knock_knock\interfaces\{ KnockRequestInterface, KnockResponseInterface };

/**
 * Class KnockKnockSecurityTest
 *
 * Тесты для методов класса KnockKnockSecurity
 *
 * @package tests
 *
 * @cli vendor/bin/phpunit tests/KnockKnockSecurityTest.php --testdox
 *
 * @tag #test #knockKnock #security
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
        KnockResponseInterface::CONTENT => '{"content":"content"}',
        KnockResponseInterface::HTTP_CODE => 200,
    ];



    /** @var KnockKnockSecurity $knockKnockSecurity */
    public static KnockKnockSecurity $knockKnockSecurity;


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
     *      Ожидается, что `commonKnockRequest->headers` будет содержать ключ `Authorization`
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
     * @cli vendor/bin/phpunit tests/KnockKnockSecurityTest.php --filter testSetupAuthorization
     *
     * @tag #security #setup #authorization
     */
    public function testSetupAuthorization( string $tokenType, string $token ): void
    {
        $knockKnockSecurity = $this->getKnockKnockOctopus();

        $response = $knockKnockSecurity
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
        $response = $knockKnockSecurity
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
     * @tag #test #knockKnock #provider #validate #hostName
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
     *      Ожидается, что `realKnockRequest->headers` будет содержать ключи из массива `$headers`
     *      с актуальными значениями. Проверяется так же перезапись заголовков.
     *
     * @return void
     *
     * @throws Exception
     *
     * Source: @see KnockKnockSecurity::setupHeaders()
     *
     * @cli vendor/bin/phpunit tests/KnockKnockSecurityTest.php --filter testSetupHeaders
     *
     * @tag #security #setup #headers
     */
    public function testSetupHeaders(): void
    {
        $knockKnockSecurity = $this->getKnockKnockOctopus();

        $knockKnockSecurity->setupHeaders(self::HEADERS[ self::OLD ]);

        $response = $knockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

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
        $knockKnockSecurity->setupHeaders(self::HEADERS[ self::NEW ]);

        $response = $knockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

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
     *      Ожидается, что `realKnockRequest->headers` будет содержать ключ `Content-Type`
     *      с актуальным значением типа контента. Проверяется так же перезапись типа контента.
     *
     * @return void
     *
     * @throws Exception
     *
     * Source: @see KnockKnockSecurity::setupContentType()
     *
     * @cli vendor/bin/phpunit tests/KnockKnockSecurityTest.php --filter testSetupContentType
     *
     * @tag #security #setup #content-type
     */
    public function testSetupContentType(): void
    {
        $knockKnockSecurity = $this->getKnockKnockOctopus();

        $response = $knockKnockSecurity
            ->setupContentType(LibKnockContentType::JSON)
            ->get(PostmanEcho::ENDPOINT_GET );

        $this->assertEquals(
            LibKnockContentType::JSON,
            $response->request->contentType,
            'Ожидается, что `$response->request->contentType` будет равен' . LibKnockContentType::JSON
        );

        // перезапись типа контента
        $response = $knockKnockSecurity
            ->setupContentType(LibKnockContentType::XML)
            ->get(PostmanEcho::ENDPOINT_GET );

        $this->assertEquals(
            LibKnockContentType::XML,
            $response->request->contentType,
            'Ожидается, что `$response->request->contentType` будет равен' . LibKnockContentType::XML
        );
    }

    /**
     * Тест метода useHeaders
     *
     *      Ожидается, что `realKnockRequest->headers` будет содержать ключи из массива `$headers`
     *      с актуальными значениями. Проверяется так же перезапись заголовков.
     *
     * @return void
     *
     * @throws Exception
     *
     * Source: @see KnockKnockSecurity::useHeaders()
     *
     * @cli vendor/bin/phpunit tests/KnockKnockSecurityTest.php --filter testUseHeaders
     *
     * @tag #security #use #headers
     */
    public function testUseHeaders(): void
    {
        $knockKnockSecurity = $this->getKnockKnockOctopus();

        $knockKnockSecurity->setupHeaders(self::HEADERS[ self::OLD ]);

        $response = $knockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

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
        $knockKnockSecurity->useHeaders(self::HEADERS[ self::NEW ]);

        $response = $knockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

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

        $response = $knockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

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
     *      Ожидается, что метод вернёт объект KnockResponse с актуальными данными.
     *
     * @return void
     *
     * @throws Exception
     *
     * Source: @see KnockKnockSecurity::send()
     *
     * @cli vendor/bin/phpunit tests/KnockKnockSecurityTest.php --filter testSend
     *
     * @tag #security #send
     */
    public function testSend(): void
    {
        $knockKnockSecurity = $this->getKnockKnockOctopus();

        // Проверка отправки запроса без фейкового ответа
        $knockRequest = $knockKnockSecurity
            ->constructRequest(
                LibKnockMethod::GET,
                PostmanEcho::ENDPOINT_GET,
                [
                    KnockRequestInterface::SETUP_DATA => [
                        'test' => 'test',
                    ],
                ]
            );
        $knockResponse = $knockKnockSecurity->setupRequest($knockRequest)->send();

        $content = json_decode($knockResponse->content, true);

        $this->assertArrayHasKey( 'args', $content,"Ожидается, что в ответе будет ключ 'args'");
        $this->assertArrayHasKey( 'headers', $content,"Ожидается, что в ответе будет ключ 'headers'");
        $this->assertArrayHasKey( 'url', $content,"Ожидается, что в ответе будет значение 'url'");

        // Проверка отправки запроса с фейковым ответом
        $knockRequest = $knockKnockSecurity
            ->constructRequest(
                LibKnockMethod::GET,
                PostmanEcho::ENDPOINT_GET,
                [
                    KnockRequestInterface::SETUP_DATA => [
                        'test' => 'test',
                    ],
                ]
            );
        $knockResponse = $knockKnockSecurity
            ->setupRequest($knockRequest)
            ->send( self::FAKE_RESPONSE );

        $this->assertEquals(
            self::FAKE_RESPONSE[ KnockResponseInterface::CONTENT ],
            $knockResponse->content,
            "Ожидается, что контент будет равен '" . self::FAKE_RESPONSE[ KnockResponseInterface::CONTENT ] . "'"
        );
        $this->assertEquals(
            self::FAKE_RESPONSE[ KnockResponseInterface::HTTP_CODE ],
            $knockResponse->httpCode,
            "Ожидается, что код ответа будет равен '" . self::FAKE_RESPONSE[ KnockResponseInterface::HTTP_CODE ] . "'"
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
     * @cli vendor/bin/phpunit tests/KnockKnockSecurityTest.php --filter testModifyRequestByUse
     *
     * @tag #security #use #request #modify
     */
    public function testModifyRequestByUse(): void
    {
        $knockKnockSecurity = $this
            ->getKnockKnockOctopus([
                KnockRequestInterface::SETUP_CONTENT_TYPE => LibKnockContentType::JSON,
                KnockRequestInterface::SETUP_HEADERS => self::HEADERS[ self::OLD ],
            ])
            ->setupAuthorization(KnockKnockSecurity::TOKEN_BASIC, self::TOKEN_BASIC );

        $this->assertInstanceOf(KnockKnockSecurity::class,
            $knockKnockSecurity,
            "Ожидается, что объект будет типа KnockKnockSecurity"
        );
        $this->assertEquals(LibKnockContentType::JSON,
            $knockKnockSecurity->commonKnockRequest->contentType,
            "Ожидается, что тип контента БУДЕТ равен " . LibKnockContentType::MULTIPART
        );
        $this->assertArrayHasKey('X-Test-Header-old',
            $knockKnockSecurity->commonKnockRequest->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'X-Test-Header-old'"
        );
        $this->assertArrayNotHasKey('X-Test-Header-new',
            $knockKnockSecurity->commonKnockRequest->headers,
            "Ожидается, что в заголовках НЕ будет ключ 'X-Test-Header-new'"
        );
        $this->assertEquals(self::HEADERS[ self::OLD ][ 'X-Test-Header-old' ],
            $knockKnockSecurity->commonKnockRequest->headers[ 'X-Test-Header-old' ],
            "Ожидается, что значение будет равно 'testHeaderValueOld'"
        );
        $this->assertArrayHasKey(KnockKnockSecurity::HEADERS_AUTH_KEY,
            $knockKnockSecurity->commonKnockRequest->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'Authorization'"
        );
        $this->assertEquals(KnockKnockSecurity::TOKEN_BASIC . ' ' . self::TOKEN_BASIC,
            $knockKnockSecurity->commonKnockRequest->headers[ KnockKnockSecurity::HEADERS_AUTH_KEY ],
            "Ожидается, что значение БУДЕТ равно '" . KnockKnockSecurity::TOKEN_BASIC . ' ' . self::TOKEN_BASIC . "'"
        );

        $knockResponse = $knockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

        $this->assertEquals(LibKnockContentType::JSON,
            $knockResponse->request->contentType,
            "Ожидается, что тип контента БУДЕТ равен " . LibKnockContentType::JSON
        );
        $this->assertArrayHasKey('X-Test-Header-old',
            $knockResponse->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключа 'X-Test-Header-old'"
        );
        $this->assertArrayHasKey(KnockKnockSecurity::HEADERS_AUTH_KEY,
            $knockResponse->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключа 'Authorization'"
        );
        $this->assertArrayNotHasKey('X-Test-Header-new',
            $knockResponse->request->headers,
            "Ожидается, что в заголовках НЕ будет ключ 'X-Test-Header-new'"
        );
        $this->assertArrayHasKey(KnockKnockSecurity::HEADERS_AUTH_KEY,
            $knockResponse->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'Authorization'"
        );
        $this->assertEquals(KnockKnockSecurity::TOKEN_BASIC . ' ' . self::TOKEN_BASIC,
            $knockResponse->request->headers[ KnockKnockSecurity::HEADERS_AUTH_KEY ],
            "Ожидается, что значение БУДЕТ равно '" . KnockKnockSecurity::TOKEN_BASIC . ' ' . self::TOKEN_BASIC . "'"
        );

        $knockKnockSecurity->useHeaders(self::HEADERS[ self::NEW ]);
        $knockKnockSecurity->useContentType(LibKnockContentType::XML);
        $knockKnockSecurity->setupAuthorization(KnockKnockSecurity::TOKEN_BEARER, self::TOKEN_BEARER );

        $knockResponse = $knockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

        $this->assertEquals(LibKnockContentType::XML,
            $knockResponse->request->contentType,
            "Ожидается, что тип контента БУДЕТ равен " . LibKnockContentType::XML
        );
        $this->assertArrayHasKey('X-Test-Header-new',
            $knockResponse->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'X-Test-Header-new'"
        );
        $this->assertArrayHasKey(KnockKnockSecurity::HEADERS_AUTH_KEY,
            $knockResponse->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'Authorization'"
        );
        $this->assertArrayHasKey('X-Test-Header-old',
            $knockResponse->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'X-Test-Header-old'"
        );
        $this->assertArrayHasKey(KnockKnockSecurity::HEADERS_AUTH_KEY,
            $knockResponse->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'Authorization'"
        );
        $this->assertEquals(KnockKnockSecurity::TOKEN_BEARER . ' ' . self::TOKEN_BEARER,
            $knockResponse->request->headers[ KnockKnockSecurity::HEADERS_AUTH_KEY ],
            "Ожидается, что значение БУДЕТ равно '" . KnockKnockSecurity::TOKEN_BEARER . ' ' . self::TOKEN_BEARER . "'"
        );

        $knockKnockSecurity->setupAuthorization(KnockKnockSecurity::TOKEN_BASIC, self::TOKEN_BASIC );

        $knockResponse = $knockKnockSecurity->get(PostmanEcho::ENDPOINT_GET );

        $this->assertEquals(LibKnockContentType::JSON,
            $knockResponse->request->contentType,
            "Ожидается, что тип контента БУДЕТ равен " . LibKnockContentType::JSON
        );
        $this->assertArrayHasKey('X-Test-Header-old',
            $knockResponse->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'X-Test-Header-old'"
        );
        $this->assertArrayNotHasKey('X-Test-Header-new',
            $knockResponse->request->headers,
            "Ожидается, что в заголовках НЕ будет ключ 'X-Test-Header-new'"
        );
        $this->assertArrayHasKey(KnockKnockSecurity::HEADERS_AUTH_KEY,
            $knockResponse->request->headers,
            "Ожидается, что в заголовках БУДЕТ ключ 'Authorization'"
        );
        $this->assertEquals(KnockKnockSecurity::TOKEN_BASIC . ' ' . self::TOKEN_BASIC,
            $knockResponse->request->headers[ KnockKnockSecurity::HEADERS_AUTH_KEY ],
            "Ожидается, что значение БУДЕТ равно '" . KnockKnockSecurity::TOKEN_BASIC . ' ' . self::TOKEN_BASIC . "'"
        );
    }
}