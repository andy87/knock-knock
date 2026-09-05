<?php declare(strict_types=1);

namespace andy87\knock_knock\tests\integration;

use PHPUnit\Framework\TestCase;
use andy87\knock_knock\core\{Operator, Request};
use andy87\knock_knock\tests\helpers\LocalHttpServer;
use andy87\knock_knock\exception\request\RequestCompleteException;

/** Проверяет запрет повторного HTTP-вызова и совместимость тел клонированных запросов. */
final class RequestLifecycleCompatibilityTest extends TestCase
{
    /** @var LocalHttpServer HTTP-сервер данного набора тестов. */
    private static LocalHttpServer $server;

    /** Запускает локальный HTTP-сервер.
     * @return void
     * @throws \RuntimeException Если сервер не запущен.
     */
    public static function setUpBeforeClass(): void
    {
        self::$server = new LocalHttpServer();
        self::$server->start();
    }

    /** Останавливает сервер; исключения не выбрасываются.
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

    /** Создаёт изолированный клиент с ограничением времени ответа.
     * @return Operator Клиент локального HTTP-сервера.
     * @throws \Exception При ошибке настройки клиента.
     */
    private function client(): Operator
    {
        return new Operator('http://localhost', ['curlOptions' => [
            CURLOPT_PORT => self::$server->port, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, CURLOPT_PROXY => '', CURLOPT_TIMEOUT_MS => 2000,
        ]]);
    }

    /** Проверяет исходное исключение и запрет повторного POST после каждого события транспорта.
     * @return void
     * @throws \Exception При неожиданной ошибке отправки.
     */
    public function testExceptionsAfterHttpExecutionPreventResending(): void
    {
        foreach ([Operator::EVENT_CONSTRUCT_RESPONSE, Operator::EVENT_CURL_HANDLER, Operator::EVENT_AFTER_SEND] as $event) {
            $client = $this->client();
            $request = $client->constructRequest('POST', '/echo', ['data' => 'operation=create']);
            $headers = 0;
            $request->setCurlOption(CURLOPT_HEADERFUNCTION, static function ($handle, string $header) use (&$headers): int {
                if (str_starts_with($header, 'HTTP/')) {
                    $headers++;
                }
                return strlen($header);
            });
            $failure = new \RuntimeException('callback failed');
            $client->on($event, static function () use ($failure): void { throw $failure; });
            try {
                $client->send($request);
                self::fail('Expected callback exception');
            } catch (\RuntimeException $error) {
                self::assertSame($failure, $error);
            }
            self::assertTrue($request->statusIsComplete(), $event);
            $client->off($event);
            try {
                $client->send($request);
                self::fail('A completed request must not be resent');
            } catch (RequestCompleteException $error) {
                self::assertSame(1, $headers, $event);
            }
        }
    }

    /** Проверяет запрет повтора и сохранение исключения непосредственно из cURL-callback.
     * @return void
     * @throws \Exception При неожиданной ошибке отправки.
     */
    public function testCurlCallbackExceptionPreventsResending(): void
    {
        $client = $this->client();
        $request = $client->constructRequest('POST', '/echo', ['data' => 'operation=create']);
        $failure = new \RuntimeException('cURL callback failed');
        $callbacks = 0;
        $request->setCurlOption(CURLOPT_HEADERFUNCTION, static function ($handle, string $header) use ($failure, &$callbacks): int {
            $callbacks++;
            throw $failure;
        });
        try {
            $client->send($request);
            self::fail('Expected cURL callback exception');
        } catch (\RuntimeException $error) {
            self::assertSame($failure, $error);
        }
        self::assertTrue($request->statusIsComplete());
        self::assertGreaterThan(0, $callbacks);
        $previousCallbacks = $callbacks;
        try {
            $client->send($request);
            self::fail('A completed request must not be resent');
        } catch (RequestCompleteException $error) {
            self::assertSame($previousCallbacks, $callbacks);
        }
    }

    /** Проверяет прежние статусы в успешном callback и возможность отправки после ошибки до HTTP.
     * @return void
     * @throws \Exception При неожиданной ошибке отправки.
     */
    public function testFailureBeforeHttpDoesNotCompleteTheRequest(): void
    {
        $client = $this->client();
        $request = $client->constructRequest('POST', '/echo', ['data' => 'operation=create']);
        $client->on(Operator::EVENT_BEFORE_SEND, static function (): void { throw new \RuntimeException('before HTTP'); });
        try {
            $client->send($request);
            self::fail('Expected callback exception');
        } catch (\RuntimeException $error) {
            self::assertFalse($request->statusIsComplete());
        }
        $client->off(Operator::EVENT_BEFORE_SEND);
        $client->on(Operator::EVENT_AFTER_SEND, static function ($client, $response): void {
            self::assertSame(Request::STATUS_PROCESSING, $response->request->status_id);
            $response->addError('callback diagnostic');
        });
        $response = $client->send($request);
        self::assertSame(200, $response->httpCode);
        self::assertSame(['callback diagnostic'], $response->errors);
        self::assertTrue($request->statusIsComplete());
    }

    /** Проверяет удаление подготовленного тела при очистке данных и смене метода клона.
     * @return void
     * @throws \Exception При неожиданной ошибке отправки.
     */
    public function testClearingCloneDataDoesNotResendGeneratedBody(): void
    {
        $client = $this->client();
        $original = $client->constructRequest('POST', '/echo', ['contentType' => 'application/json', 'data' => ['operation' => 'old']]);
        $client->send($original);
        foreach ([[], null, ''] as $data) {
            $clone = $original->clone();
            $clone->setData($data);
            $received = $this->client()->send($clone)->asArray()->content;
            self::assertSame('', $received['body']);
            self::assertSame('POST', $received['method']);
        }
        $clone = $original->clone();
        $clone->setMethod('GET')->setData(['query' => 'new']);
        $received = $client->send($clone)->asArray()->content;
        self::assertSame('GET', $received['method']);
        self::assertSame('', $received['body']);
        self::assertSame('/echo?query=new', $received['uri']);
        self::assertSame('{"operation":"old"}', $original->curlOptions[CURLOPT_POSTFIELDS]);
    }

    /** Проверяет сохранение явно заданного CURLOPT_POSTFIELDS, включая замену подготовленного тела.
     * @return void
     * @throws \Exception При неожиданной ошибке отправки.
     */
    public function testExplicitCurlBodiesRemainOwnedByTheCaller(): void
    {
        $client = $this->client();
        $request = $client->constructRequest('POST', '/echo');
        $request->setCurlOption(CURLOPT_POSTFIELDS, 'explicit=first');
        $client->send($request);
        $clone = $request->clone();
        $clone->setData([]);
        self::assertSame('explicit=first', $client->send($clone)->asArray()->content['body']);

        $generated = $client->constructRequest('POST', '/echo', ['data' => 'generated']);
        $client->send($generated);
        foreach (['single', 'array'] as $setter) {
            $clone = $generated->clone();
            $clone->setData(null);
            if ($setter === 'single') {
                $clone->setCurlOption(CURLOPT_POSTFIELDS, 'explicit=replacement');
            } else {
                $clone->setCurlOptions(array_replace($clone->curlOptions, [CURLOPT_POSTFIELDS => 'explicit=replacement']));
            }
            self::assertSame('explicit=replacement', $client->send($clone)->asArray()->content['body']);
        }
    }
}
