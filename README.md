# KnockKnock

PHP Фасад\Адаптер для отправки запросов через ext cURL

![IN PROGRESS](http://www.bc-energy.it/wp-content/uploads/2013/08/work-in-progress.png)

# Knock: Получение объекта/экземпляра класса и его настройка

Нативный вариант
```php
$knockKnock = new KnockKnock([
    KnockRequest::HOST => 'some.domain',
    KnockRequest::CONTENT_TYPE => KnockRequest::CONTENT_TYPE_FORM,
]);
```
вариант Singleton
```php
$knockKnock = KnockKnock::getInstance([
    KnockRequest::HOST => 'domain.zone',
    KnockRequest::PROTOCOL => 'http',
    KnockRequest::HEADER => KnockRequest::CONTENT_TYPE_JSON,
])->useAuthorization( 'myToken', KnockKnock::TOKEN_BEARER );
```
`getInstance( array $knockKnockConfig = [] ): self`

## Использование настроек для параметров запроса
Доступно 3 отдельных метода для взаимодействия с некоторыми, отдельными, свойствами,
которые в дальнейшем будут передаваться запросам отправляемыми объектом `$knockKnock` 
```php
$knockKnock->useAuthorization( 'myToken', KnockKnock::TOKEN_BEARER );
$knockKnock->useConfigHeaders(['api-secret' => 'secretKey12']);
$knockKnock->useConfigContentType(KnockRequest::CONTENT_TYPE_MULTIPART);
```

Доступна цепочка вызовов:
```php
$knockKnock
    ->useAuthorization('token', KnockKnock::TOKEN_BASIC )
    ->useConfigHeaders(['api-secret' => 'secretKey23'])
    ->useConfigContentType(KnockRequest::CONTENT_TYPE_MULTIPART);

$bearer = $knockKnock->getAuthorization(); // string
```
`setConfigAuthorization( string $token, string $method = self::TOKEN_BASIC ): self`

Все подобные методы возвращают `static` объект / экземпляр класса `KnockKnock`

# Обработчики событий

Установить обработчики событий
```php
$knockKnock->setupCallback([
    KnockKnock::EVENT_AFTER_CONSTRUCT => fn( static $knockKnock ) => {
        // Действия после создания объекта
    },
    KnockKnock::EVENT_AFTER_CREATE_REQUEST => fn( static $knockKnock ) => {
        // Действия после создания объекта запроса
    },
    KnockKnock::EVENT_BEFORE_SEND => fn(  static $knockKnock, KnockRequest $knockRequest ) => {
        // Действия перед отправкой запроса
    },
    KnockKnock::EVENT_AFTER_SEND => fn( static $knockKnock, KnockResponse $knockResponse ) => {
        // Действия после отправки запроса и получения ответа
    }
]);
```
`setupCallback( array $callbacks ): self`

# KnockRequest: Создание запроса

Нативное создание объекта / экземпляра класса с данными конкретного запроса
```php
$knockRequest = new KnockRequest( 'info/me', [
    KnockRequest::METHOD => KnockMethod::POST,
    KnockRequest::DATA => [ 'client_id' => 34 ],
    KnockRequest::HEADERS => [ 'api-secret-key' => 'secretKey34' ],
    KnockRequest::CURL_OPTIONS => [ CURLOPT_TIMEOUT => 10 ],
    KnockRequest::CURL_INFO => [
        CURLINFO_CONTENT_TYPE,
        CURLINFO_HEADER_SIZE,
        CURLINFO_TOTAL_TIME
    ],
    KnockRequest::CONTENT_TYPE => KnockContentType::FORM_DATA,
]);
```

Доступно создание объекта / экземпляра класса с данными конкретного запроса - через метод фасада,
с вызовом callback функции, если она установлена
```php
$knockRequest = $knockKnock->constructKnockRequest( 'info/me', [
    KnockRequest::METHOD => KnockMethod::POST,
    KnockRequest::DATA => [ 'client_id' => 45 ],
    KnockRequest::HEADERS => [ 'api-secret-key' => 'secretKey45' ],
    KnockRequest::CURL_OPTIONS => [ CURLOPT_TIMEOUT => 10 ],
    KnockRequest::CURL_INFO => [
        CURLINFO_CONTENT_TYPE,
        CURLINFO_HEADER_SIZE,
        CURLINFO_TOTAL_TIME
    ],
    KnockRequest::CONTENT_TYPE => KnockContentType::FORM_DATA,
]);
```
`constructKnockRequest( string $url, array $paramsKnockRequest = [] ): KnockRequest`

## Изменение общих настроек компонента на частные 
Если надо выполнить запрос, с другими параметрами
```php
$knockRequest = $knockKnock
    ->constructKnockRequest('info/me',[
        KnockRequest::CONTENT_TYPE => KnockContentType::FORM_DATA,
    ]);
```

### Назначение/Изменение отдельных параметров запроса (set/get)

```php
$knockRequest = $knockKnock->constructKnockRequest('info/me');

// set/get методы для взаимодействия с отдельными свойствами
$knockRequest->setUrl('info/me');
$knockRequest->setMethod(KnockMethod::GET);
$knockRequest->setData(['client_id' => 67]);
$knockRequest->setHeaders(['api-secret-key' => 'secretKey67']);
$knockRequest->setCurlOptions([CURLOPT_TIMEOUT => 10]);
$knockRequest->setCurlInfo([
    CURLINFO_CONTENT_TYPE,
    CURLINFO_HEADER_SIZE,
    CURLINFO_TOTAL_TIME
]);
$knockRequest->setContentType(KnockContentType::JSON);

$method = $knockRequest->getMethod(); // string
// ... аналогичным образом доступны и другие подобные методы для получения свойств запроса
```

### Обновление свойств созданного запроса, данными переданными опционально

```php
$knockKnock->setRequest( $knockRequest, [
    KnockRequest::HOST => 'domain.zone',
    KnockKnock::BEARER => 'token-bearer-2',
    KnockKnock::HEADERS => [
        'api-secret' => 'secretKey78'
    ],
]);
```
`setRequest( KnockRequest $knockRequest, array $tempParamsKnockKnock = [] ): self`

## KnockResponse: Ответ

Конструктор KnockResponse с вызовом callback функции, если она установлена
```php
$knockResponse = $knockKnock->constructKnockResponse([
    'id' => 806034,
    'name' => 'and_y87'
]);
```
`constructKnockResponse( array $paramsKnockResponse, int $httpCode = 200 ): KnockResponse`

## KnockResponse: Получением ответа при отправке запроса

Получение ответа с отправкой запроса и вызовом callback функции, если она установлена
```php
$knockKnock->setRequest( $knockRequest );
$knockResponse = $knockKnock->send();
```
`send( array $prepareKnockResponseParams = [] ): KnockResponse`
возвращает объект/экземпляр класса KnockResponse

Получение ответа с отправкой запроса - цепочкой вызовов
```php
$knockResponse = $knockKnock->setRequest( $knockRequest )->send(); // return KnockResponse
```

## Отправка запроса с фэйковым ответом

Цепочка вызовов, возвращает подготовленный ответ и вызывает callback функцию, если она установлена
```php
// параметры возвращаемого ответа
$prepareFakeKnockResponseParams = [
    KnockResponse::HTTP_CODE => 200,
    KnockResponse::CONTENT => [
        'id' => 806034,
        'name' => 'and_y87'
    ],
];

$knockResponse = $knockKnock->setRequest( $knockRequest )->send( $prepareFakeKnockResponseParams );
```
объект `$knockResponse` будет содержать данные переданные в аргументе `$prepareFakeKnockResponseParams`

## Подмена данных в ответе

В полученном ответе подменяются данные
```php
$knockResponse = $knockKnock->setRequest( $knockRequest )->send();

$knockResponse
    ->setHttpCode(200)
    ->setContent('{"id" => 8060345, "nickName" => "and_y87"}');
```
`replace( string $property, mixed $value ): self`


## Извлечение полезных данных из запроса

Получение массива с данными ответа
```php
// Получение опций запроса (  KnockRequest::CURL_OPTIONS )
$curlOptions =  $knockResponse->get( KnockResponse::CURL_OPTIONS ); // return array

// Получение данных о запросе ( KnockRequest::CURL_INFO )
$curlInfo =  $knockResponse->get( KnockResponse::CURL_INFO ); // return array

```

# Custom реализация

Custom реализация Базового класса, к примеру с добавлением логирования работающим "под капотом"
```php
class KnockKnockYandex implements KnockKnockInterface
{
    private const AFTER_CREATE_REQUEST = 'afterCreateRequest';
    private const LOGGER = 'logger';



    private string $host = 'https://api.yandex.ru/'
    private string $contentType = KnockContentType::JSON

    private YandexLogger $logger;



    public function init()
    {
        $this->event[self::AFTER_CREATE_REQUEST] = fn( KnockRequest $knockRequest ) => 
        {
            $this->addYandexLog([
                'url' => $knockRequest->getUrl(),
                'method' => $knockRequest->getMethod(),
                'data' => $knockRequest->getData(),
                'headers' => $knockRequest->getHeaders(),
            ]);
        };

        $this->event[self::EVENT_AFTER_SEND] = fn( KnockResponse $knockResponse ) => 
        {
            $knockRequest = $knockResponse->getRequest();

            $this->addYandexLog([
                'url' => $knockRequest->getUrl(),
                'method' => $knockRequest->getMethod(),
                'data' => $knockRequest->getData(),
                'headers' => $knockRequest->getHeaders(),
            ]);
        };
    }

    public function createRequest( string $url, array $requestParams ): KnockRequest
    {
        $knockRequest = new KnockRequest( $url, $requestParams );

        $this->event( self::AFTER_CREATE_REQUEST, $knockRequest );

        return $knockRequest;
    }

    private function addYandexLog( array $params ) 
    {
        $logger->log($params);
    }

}

```
Пример использования custom реализации
```php

$knockKnockYandex = KnockKnockYandex::getInstanse([
    KnockKnock::LOGGER => new YandexLogger(),
]);

$knockResponse = $knockKnockYandex->setRequest('profile', [ 
    KnockRequest::METHOD => KnockMethod::PATCH,
    KnockRequest::DATA => [ 'city' => 'Moscow' ],
]); // Логирование `afterCreateRequest`

$knockResponse = $knockKnockYandex->send(); // Логирование `afterSend`

```

# Расширения

Расширения работают через "магию", поэтому лучше описывать их в анотациях класса

Реализация расширения
```php
/**
 * @method static setupCorrectHost( KnockKnock $knockKnock )
 */
class VkontakteKnockKnock extends KnockKnock
{
    /** @var callable[] */
    private array $extensions = [];



    public function init()
    {
        $this->addExtension( 'setupCorrectHost', fn( $knockKnock ) => 
        {
            switch ($knockKnock->host)
            {
                case 'vk.com':
                    $knockKnock->useHeaders(['Host' => 'client.ru']);
                    break;

                case 'api.vk.com':
                    $knockKnock->useAuthorization('myToken', KnockKnock::TOKEN_BEARER );
                    break;
            }
        });
    }
}
```

Использование расширения
```php
$vkontakteKnockKnock = VkontakteKnockKnock::getInstance([
    KnockRequest::HOST => 'api.vk.com',
]);

$vkontakteKnockKnock->setupCorrectHost();

$knockResponse = $vkontakteKnockKnock->setRequest('profile', [
    KnockRequest::METHOD => KnockMethod::PATCH,
    KnockRequest::DATA => [ 'homepage' => 'www.andy87.ru' ],
])->send();

```
