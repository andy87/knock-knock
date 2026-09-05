<?php declare(strict_types=1);

namespace andy87\knock_knock\tests\integration;

use PHPUnit\Framework\TestCase;
use andy87\knock_knock\core\{Operator, Response};
use andy87\knock_knock\KnockKnockSecurity;
use andy87\knock_knock\lib\ContentType;
use andy87\knock_knock\tests\helpers\LocalHttpServer;
use andy87\knock_knock\exception\request\InvalidHeaderException;

/** Проверяет HTTP-контракт на реальном локальном сервере без зависимости от внешних API. */
final class HttpTransportTest extends TestCase
{
    /** @var LocalHttpServer Сервер, существующий только во время данного набора тестов. */
    private static LocalHttpServer $server;

    /** Запускает HTTP fixture до выполнения тестов.
     * @return void
     * @throws \RuntimeException Если локальный сервер не запускается.
     */
    public static function setUpBeforeClass(): void
    {
        self::$server = new LocalHttpServer();
        self::$server->start();
    }

    /** Освобождает процесс сервера; исключения не выбрасываются.
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

    /** Создаёт клиент с ограничением времени и прямым подключением к loopback.
     * @param array $options Дополнительные cURL-опции, заменяющие тестовые значения.
     * @return KnockKnockSecurity Клиент локального сервера.
     * @throws \Exception При неожиданной ошибке конфигурации.
     */
    private function client(array $options = []): KnockKnockSecurity
    {
        return new KnockKnockSecurity('http://localhost', ['curlOptions' => array_replace([
            CURLOPT_PORT => self::$server->port,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_PROXY => '',
            CURLOPT_TIMEOUT_MS => 2000,
        ], $options)]);
    }

    /** Проверяет отправку авторизации, пользовательских заголовков и JSON-тела.
     * @return void
     * @throws \Exception При неожиданной ошибке запроса.
     */
    public function testAuthorizationAndJsonReachTheServer(): void
    {
        $client = $this->client();
        $client->setupAuthorization('Bearer', 'test-token');
        $client->setupHeaders(['X-Custom' => 'expected']);
        $client->setupContentType(ContentType::JSON);
        $response = $client->post('/echo', ['message' => 'hello']);
        $received = $response->asArray()->content;
        self::assertTrue($response->validate());
        self::assertSame('Bearer test-token', $received['headers']['authorization'] ?? null);
        self::assertSame('expected', $received['headers']['x-custom'] ?? null);
        self::assertSame(ContentType::JSON, $received['headers']['content-type'] ?? null);
        self::assertSame(['message' => 'hello'], json_decode($received['body'], true));
    }

    /** Сохраняет приоритет явных cURL-заголовков, включая подавление заголовка.
     * @return void
     * @throws \Exception При неожиданной ошибке запроса.
     */
    public function testExplicitCurlHeadersKeepTheirPrecedence(): void
    {
        $client = $this->client([CURLOPT_HTTPHEADER => [
            'authorization: Bearer legacy-token',
            'Content-Type: application/custom+json',
            'X-Suppressed:',
            'X-Repeated: first',
            'X-Repeated: second',
        ]]);
        $client->setupAuthorization('Bearer', 'facade-token');
        $client->setupContentType(ContentType::JSON);
        $client->setupHeaders(['X-Suppressed' => 'must not arrive', 'X-Additional' => 'yes']);
        $response = $client->post('/echo', ['a' => 1]);
        $received = $response->asArray()->content;
        self::assertSame('Bearer legacy-token', $received['headers']['authorization']);
        self::assertSame('application/custom+json', $received['headers']['content-type']);
        self::assertArrayNotHasKey('x-suppressed', $received['headers']);
        self::assertSame('yes', $received['headers']['x-additional'] ?? null);
        self::assertContains('X-Repeated: first', $response->request->curlOptions[CURLOPT_HTTPHEADER]);
        self::assertContains('X-Repeated: second', $response->request->curlOptions[CURLOPT_HTTPHEADER]);
    }

    /** Проверяет, что явно заданный Content-Type фасада не заменяется автоматически.
     * @return void
     * @throws \Exception При неожиданной ошибке отправки.
     */
    public function testExplicitFacadeContentTypeWinsOverAutomaticHeader(): void
    {
        $client = $this->client();
        $client->setupContentType(ContentType::JSON);
        $client->setupHeaders(['content-type' => 'application/vendor+json']);
        $received = $client->post('/echo', ['a' => 1])->asArray()->content;
        self::assertSame('application/vendor+json', $received['headers']['content-type']);
    }

    /** Проверяет все обычные методы и точность необработанного строкового тела.
     * @return void
     * @throws \Exception При неожиданной ошибке отправки.
     */
    public function testMethodsAndRawBodiesReachTheServer(): void
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE'] as $method) {
            $client = $this->client();
            $request = $client->constructRequest($method, '/echo');
            if ($method !== 'GET') {
                $request->setData('{"raw":true}')->setContentType(ContentType::JSON);
            }
            $response = $client->send($request);
            $received = $response->asArray()->content;
            self::assertTrue($response->validate(), $method);
            self::assertSame($method, $received['method']);
            self::assertSame($method === 'GET' ? '' : '{"raw":true}', $received['body']);
        }
    }

    /** Проверяет form-массив, пустую строку и строковый ноль без изменения данных.
     * @return void
     * @throws \Exception При неожиданной ошибке отправки.
     */
    public function testFormAndEmptyRawBodies(): void
    {
        $client = $this->client();
        $client->setupContentType(ContentType::FORM);
        $received = $client->post('/echo', ['value' => 'a b'])->asArray()->content;
        self::assertSame(['value' => 'a b'], $received['form']);
        foreach (['', '0'] as $body) {
            $request = $client->constructRequest('POST', '/echo');
            $request->setData($body);
            $received = $client->send($request)->asArray()->content;
            self::assertSame($body, $received['body']);
        }
    }

    /** Проверяет HEAD без ожидания тела при наличии и отсутствии Content-Length.
     * @return void
     * @throws \Exception При неожиданной ошибке отправки.
     */
    public function testHeadDoesNotExpectAResponseBody(): void
    {
        foreach (['/echo', '/no-length'] as $endpoint) {
            $client = $this->client();
            $curlError = null;
            $client->on(Operator::EVENT_CURL_HANDLER, static function (Operator $operator, $handle) use (&$curlError): void {
                $curlError = curl_errno($handle);
            });
            $response = $client->head($endpoint);
            self::assertSame(0, $curlError);
            self::assertSame(200, $response->httpCode);
            self::assertSame('', $response->content);
            self::assertTrue($response->validate());
        }
    }

    /** Проверяет сохранение сетевого тайм-аута в Response и запрет автоматического retry.
     * @return void
     * @throws \Exception При неожиданной ошибке запроса.
     */
    public function testTimeoutIsReportedAsAnError(): void
    {
        $response = $this->client([CURLOPT_TIMEOUT_MS => 50])->get('/slow');
        self::assertFalse($response->content);
        self::assertSame(0, $response->httpCode);
        self::assertNotEmpty($response->errors);
        self::assertFalse($response->validate());
        self::assertTrue($response->request->statusIsComplete());
    }

    /** Проверяет диагностику ошибки TLS при HTTPS-соединении с обычным HTTP-сервером.
     * @return void
     * @throws \Exception При неожиданной ошибке запроса.
     */
    public function testTlsFailureIsReportedAsAnError(): void
    {
        $client = $this->client();
        $request = $client->constructRequest('GET', '/echo');
        $request->setProtocol('https');
        $response = $client->send($request);
        self::assertFalse($response->content);
        self::assertNotEmpty($response->errors);
        self::assertFalse($response->validate());
    }

    /** Проверяет запрет CR/LF в заголовках фасада перед передачей в cURL.
     * @return void
     * @throws \Exception При ошибке запроса; ожидается InvalidHeaderException.
     */
    public function testFacadeHeadersCannotInjectAdditionalHeaderLines(): void
    {
        $client = $this->client();
        $client->setupHeaders(['X-Test' => "value\r\nX-Injected: yes"]);
        $this->expectException(InvalidHeaderException::class);
        $client->get('/echo');
    }

    /** Проверяет изменение заголовка и метода у клона уже отправленного HEAD.
     * @return void
     * @throws \Exception При неожиданной ошибке клонирования или отправки.
     */
    public function testClonedRequestCanChangeHeadersAndMethod(): void
    {
        $client = $this->client();
        $request = $client->constructRequest('HEAD', '/echo');
        $request->setHeader('X-Value', 'first');
        $client->send($request);
        $clone = $request->clone();
        $clone->setMethod('GET')->setHeader('X-Value', 'second');
        $received = $client->send($clone)->asArray()->content;
        self::assertSame('GET', $received['method']);
        self::assertSame('second', $received['headers']['x-value']);
    }

    /** Проверяет готовую строку query на сервере и отсутствие её дублирования при чтении URL.
     * @return void
     * @throws \Exception При неожиданной ошибке отправки.
     */
    public function testRawQueryReachesTheServerUnchanged(): void
    {
        $client = $this->client();
        $request = $client->constructRequest('GET', '/echo?redirect=https://example.com/a//b');
        $request->setData('x=1');
        $url = $request->url;
        $response = $client->send($request);
        self::assertSame($url, $request->url);
        self::assertSame('/echo?redirect=https://example.com/a//b&x=1', $response->asArray()->content['uri']);
    }
}
