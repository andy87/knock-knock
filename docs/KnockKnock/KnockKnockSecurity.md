# KnockKnockSecurity

PHP Фасад для класса `KnockKnock` отправляющего запросы через ext cURL

---

> [!NOTE]
> ![IN PROGRESS](http://www.bc-energy.it/wp-content/uploads/2013/08/work-in-progress.png)

---

# KnockKnockAuthorization

Расширяет класс [KnockKnock](../../README.md) и предоставляет доступ к "функциональным" методам для простой реализации авторизации и отправки запросов через ext cURL

```php
$KnockKnockAuthorization = new KnockKnockAuthorization([
    KnockRequest::HOST => 'https://api.example.com',
]);

// Настройка параметров запроса по умолчанию
$KnockKnockAuthorization
    ->useAuthorization( 'token', KnockKnockAuthorization::TOKEN_BEARER )
    ->useHeaders( [ 'X-Api-Key' => 'key' ] )
    ->useContentType( 'application/json' );

// И дальнейшее использование

// Пример: Обновление с необязательным логированием, через использование event callback функции  
$KnockKnockAuthorization
    ->on( KnockKnock::EVENT_AFTER_SEND, fn( KnockKnock $knockKnock, KnockResponse $knockResponse ) => 
    {
        file_put_contents( 'log.txt', json_encode( $knockResponse->content ) );
    })
    ->patch( 'product', [
        'price' => 1000
    ]);

// Пример: Создание с необязательной, установкой Content-Type  
$KnockResponse = $KnockKnockAuthorization
    ->setContentType( 'application/x-www-form-urlencoded' )
    ->post( 'category', [
        'name' => 'Фреймворки'
    ]);

$category_id = $KnockResponse->content['id'];

```