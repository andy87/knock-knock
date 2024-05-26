
# Полный список изменений.

## 99b
24/05/2024
- Добавления:
  - `knockKnock`
    - Новые методы:
      - useCookie( string $cookie, string $jar, ?string $file = null ) Использование cookie, добавляя в запрос параметры: CURLOPT_COOKIE, CURLOPT_COOKIEFILE, CURLOPT_COOKIEJAR
      - enableRedirect() - включает редиректы, добавляет CURLOPT_FOLLOWLOCATION true
      - validateHostName
    - Новые события:
      - EVENT_CURL_HANDLER - вызывается перед выполнением curl запроса, для взаимодействия с $ch
  - `KnockResponse`
    - Новые методы:
      - asArray() - преобразует ответ(content) в массив
      - validate() - валидация ответа, возвращает true или false
  - `KnockRequest`
    - добавлены readonly свойства

- Изменения:
  - `knockKnock`:
    - disableSSL($verifyPeer = false, $verifyHost = 0) - теперь может принимать аргументы
    - enableSSL($verifyPeer = false, $verifyHost = 0) - теперь может принимать аргументы
    - переделаны setter & getter
    - добавлены readonly свойства:
      - $host
      - $commonKnockRequest
      - $realKnockRequest
  - `KnockRequest`:
    - disableSSL($verifyPeer = true, $verifyHost = 2) - теперь может принимать аргументы
    - enableSSL($verifyPeer = true, $verifyHost = 2) - теперь может принимать аргументы
    - переделаны setter & getter
  - `KnockResponse`:
    - __construct() - $request стал обязательным параметром
    - getErrors() - теперь возвращает массив собственных ошибок
    - get() - удалено
    - validate() - удалено
    - переделаны setter & getter
    - добавлены readonly свойства
