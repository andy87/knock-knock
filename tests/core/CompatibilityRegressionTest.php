<?php declare(strict_types=1);

namespace andy87\knock_knock\tests\core;

use PHPUnit\Framework\TestCase;
use andy87\knock_knock\core\{Operator, Request, Response};
use andy87\knock_knock\{KnockKnockOctopus, KnockKnockSecurity};
use andy87\knock_knock\lib\ContentType;
use andy87\knock_knock\exception\request\InvalidRequestException;

/** Защищает совместимость публичного API и воспроизводит ошибки без внешней сети. */
final class CompatibilityRegressionTest extends TestCase
{
    /** Проверяет прежний приоритет и замену целых массивов общей конфигурацией.
     * @return void
     * @throws \Exception Если создание запроса неожиданно завершилось ошибкой.
     */
    public function testCommonConfigurationKeepsItsExistingPrecedence(): void
    {
        $client = new Operator('https://example.com', [
            'headers' => ['X-Common' => 'yes'],
            'curlOptions' => [CURLOPT_TIMEOUT => 2],
        ]);
        $request = $client->constructRequest('GET', '/echo', [
            'host' => 'other.example.com', 'protocol' => 'http',
            'headers' => ['X-Local' => 'yes'],
            'curlOptions' => [CURLOPT_TIMEOUT => 1, CURLOPT_RETURNTRANSFER => true],
        ]);
        self::assertSame('https://example.com/echo', $request->url);
        self::assertSame(['X-Common' => 'yes'], $request->headers);
        self::assertSame([CURLOPT_TIMEOUT => 2], $request->curlOptions);
        $request->setHeader('X-Local', 'yes')->setCurlOption(CURLOPT_TIMEOUT, 1);
        self::assertSame('yes', $request->headers['X-Local']);
        self::assertSame(1, $request->curlOptions[CURLOPT_TIMEOUT]);
    }

    /** Проверяет применение разовых настроек к переданному объекту и их очистку.
     * @return void
     * @throws \Exception Если отправка подставного ответа завершилась ошибкой.
     */
    public function testOneShotSettingsApplyToTheExplicitRequest(): void
    {
        $client = new KnockKnockSecurity('https://example.com');
        foreach (['first', 'second'] as $value) {
            $request = $client->constructRequest('POST', '/echo');
            $request->setFakeResponse(['content' => '{}', 'httpCode' => 200]);
            $client->useHeaders(['X-Once' => $value])->useContentType(ContentType::JSON);
            $response = $client->send($request);
            self::assertSame($request, $response->request);
            self::assertSame($value, $request->headers['X-Once']);
            self::assertSame(ContentType::JSON, $request->contentType);
        }
        $next = $client->constructRequest('GET', '/echo');
        $next->setFakeResponse(['content' => '{}', 'httpCode' => 200]);
        $client->send($next);
        self::assertArrayNotHasKey('X-Once', $next->headers);
        self::assertNull($next->contentType);
    }

    /** Проверяет прежнее исключение и сохранение настроек при отсутствии запроса.
     * @return void
     * @throws \Exception При неожиданном сбое создания запроса.
     */
    public function testMissingRequestDoesNotConsumeOneShotSettings(): void
    {
        $client = new KnockKnockSecurity('https://example.com');
        $client->useHeaders(['X-Once' => 'yes']);
        try {
            $client->send();
            self::fail('Expected InvalidRequestException');
        } catch (InvalidRequestException $error) {
            $request = $client->constructRequest('GET', '/echo');
            $request->setFakeResponse(['content' => '', 'httpCode' => 200]);
            $client->setupRequest($request)->send();
            self::assertSame('yes', $request->headers['X-Once']);
        }
    }

    /** Проверяет независимость Singleton классов и повторное получение без хоста.
     * @return void
     * @throws \Exception При неожиданной ошибке конструктора.
     */
    public function testSingletonsAreIsolatedByClass(): void
    {
        foreach ([KnockKnockSecurity::class, Operator::class, KnockKnockOctopus::class] as $class) {
            $instance = $class::getInstance('https://example.com');
            self::assertSame($class, get_class($instance));
            self::assertSame($instance, $class::getInstance());
        }
        foreach ([KnockKnockOctopus::class, Operator::class, KnockKnockSecurity::class] as $class) {
            self::assertSame($class, get_class($class::getInstance()));
        }
    }

    /** Проверяет сохранение пути/query и однократное добавление строковых данных GET.
     * @return void
     * @throws \Exception При неожиданной ошибке сборки URL.
     */
    public function testUrlPreservesPathAndRawQuery(): void
    {
        $client = new Operator('https://example.com');
        $request = $client->constructRequest('GET', '/a//b?redirect=https://other.example/a//b');
        $request->setData('x=1&y=2');
        $expected = 'https://example.com/a//b?redirect=https://other.example/a//b&x=1&y=2';
        self::assertSame($expected, $request->url);
        self::assertSame($expected, $request->url);
    }

    /** Проверяет ошибки PROCESSING при сохранении публичного запрета после COMPLETE.
     * @return void
     * @throws \Exception При неожиданной ошибке перехода состояния.
     */
    public function testErrorsDuringProcessingAndCompletedRequestCompatibility(): void
    {
        $request = new Request('/echo');
        $request->setupStatusProcessing();
        $response = new Response(false, 0, $request);
        $response->addError('transport failed');
        self::assertSame(['transport failed'], $response->errors);
        self::assertFalse($response->validate());
        $request->setupStatusComplete();
        $response->addError('must remain ignored');
        self::assertSame(['transport failed'], $response->errors);
        self::assertTrue((new Response('not found', 404))->validate());
    }

    /** Проверяет fallback asArray для скаляров, null и некорректного JSON.
     * @return void
     * @throws \Exception При неожиданной ошибке состояния запроса.
     */
    public function testArrayConversionRetainsUnsupportedContentWithoutTypeErrors(): void
    {
        foreach (['42', 'true', 'false', 'null', '"text"', '{invalid'] as $body) {
            $request = new Request('/echo');
            $request->setupStatusComplete();
            $response = new Response($body, 200, $request);
            self::assertSame($body, $response->asArray()->content);
            self::assertFalse($response->validate());
            self::assertNotEmpty($response->errors);
        }
        self::assertSame(['a' => 1], (new Response('{"a":1}', 200))->asArray()->content);
        self::assertSame([1, 2], (new Response('[1,2]', 200))->asArray()->content);
        $standalone = new Response('42', 200);
        self::assertSame('42', $standalone->asArray()->content);
        self::assertFalse($standalone->validate());
    }
}
