# api_requester

PHP Фасад\Адаптер для отправки запросов через ext cURL

![IN PROGRESS](http://www.bc-energy.it/wp-content/uploads/2013/08/work-in-progress.png)

```php

use andy87\api_requester\ApiRequester;
use andy87\api_requester\query\Request;
use andy87\api_requester\query\Response;

$apiRequester = new ApiRequester([
    ApiRequester::HOST => 'https://api.url',
    ApiRequester::CONTENT_TYPE => Request::CONTENT_TYPE_JSON,
]);

$apiRequester->callback([
    ApiRequester::BEFORE_SEND => function( Request $request ) {
        // Действия перед отправкой запроса
    },
    ApiRequester::AFTER_SEND => function( Response $response ) {
        // Действия после отправки запроса
    }
]);

$apiRequester->useBearer('Bearer');
$apiRequester->setupHeaders([ 'api-secret' => 'secret']);
$apiRequester->setupContentType(Request::CONTENT_TYPE_MULTIPART);


// Создание объекта с параметрами запроса
$request = $apiRequester
    ->useBearer('Bearer')
    ->setupContentType(Request::CONTENT_TYPE_JSON)
    ->constructRequest('info/me', [
        Request::METHOD => Request::POST,
        Request::DATA => [
            'client_id' => 12345
        ],
        Request::HEADERS => [
            'api-secret-key' => "secret key"
        ],
        Request::CURL_OPTIONS => [
            CURLOPT_TIMEOUT => 10
        ],
        Request::CURL_INFO => [
            CURLINFO_CONTENT_TYPE,
            CURLINFO_HEADER_SIZE,
            CURLINFO_TOTAL_TIME
        ],
        Request::CONTENT_TYPE => Request::CONTENT_TYPE_FORM_DATA,
    ]); // return Request

// Для взаимодействия с отдельными параметрами запроса доступны set/get методы:
$request->setUrl('info/me');
$request->setMethod(Request::GET);
$request->setData(['client_id' => 12345]);
$request->setHeaders(['api-secret-key' => "secret"]);
$request->setCurlOptions([CURLOPT_TIMEOUT => 10]);
$request->setCurlInfo([
    CURLINFO_CONTENT_TYPE,
    CURLINFO_HEADER_SIZE,
    CURLINFO_TOTAL_TIME
]);
$request->setContentType(Request::CONTENT_TYPE_JSON);


// Отправка запроса без обновления параметров
$response = $apiRequester->sendRequest( $request ); // return Response

// Отправка запроса с обновлением параметров
$response = $apiRequester->sendRequest( $request, [
    ApiRequester::HOST => 'https://api.url',
    ApiRequester::BEARER => 'Bearer2',
    ApiRequester::HEADERS => [
        'api-secret' => 'secret2'
    ],
]); // return Response


// Данные для фэйкового ответа
$apiRequester->setFakeResponse([
    Response::HTTP_CODE => 200,
    Response::CONTENT => [
        'id' => 12345,
        'name' => 'Test'
    ],

]);
// Отправка запроса с фэйковым ответом
$response = $apiRequester->sendRequest( $request ); // return Response

// Отправка запроса уже без фэйкового ответа
$apiRequester->sendRequest( $request ); // return Response
$response = $request->getResponse(); // return Response

$response
    ->setup(Response::HTTP_CODE, 200)
    ->setup(Response::CONTENT, [
    'id' => 12345,
    'name' => 'Test'
]);

// Получение данных из ответа
$curlOptions =  $response->get(Request::CURL_OPTIONS); // return array

// Получение данных из ответа
$curlInfo =  $response->get(Request::CURL_INFO); // return array

```
