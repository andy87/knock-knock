# KnockKnockSecurity

PHP Фасад для класса `KnockKnock` отправляющего запросы через ext cURL

<p align="center"><img src="../../assets/docs/KnockKnockSecurity_280.png" height="164" width="auto" alt="KnockKnock php curl facade"/></p>

Расширяет класс [KnockKnockOctopus](KnockKnockOctopus.md) и предоставляет доступ к функционалу для простой и быстрой реализации авторизации и другим полезным методам.

```php
$KnockKnockAuthorization = new KnockKnockSecurity([
    KnockRequest::HOST => 'https://api.example.com',
]);

// Настройка параметров запроса по умолчанию
$KnockKnockAuthorization
    ->useAuthorization( 'token', KnockKnockSecurity::TOKEN_BEARER )
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

---
> [!Альфа версия]  
> Возможно наличие багов
---