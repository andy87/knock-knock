
# Полный список изменений.

## 99b
24/05/2024 (Глобальная переработка + Покрытие тестами)
- Добавления:
  - `knockKnock`
    - добавлены readonly свойства
    - Новые методы:
      - useCookie()
      - enableRedirect()
      - validateHostName()
    - Новые события:
      - EVENT_CURL_HANDLER
  - `KnockResponse`
    - добавлены readonly свойства
    - Новые методы:
      - asArray()
      - validate()
  - `KnockRequest`
    - добавлены readonly свойства

- Изменения:
  - `knockKnock`:
    - переделаны setter & getter
    - disableSSL()
    - enableSSL()
  - `KnockRequest`:
    - переделаны setter & getter
    - disableSSL() - теперь может принимать аргументы
    - enableSSL() - теперь может принимать аргументы
  - `KnockResponse`:
    - переделаны setter & getter
    - __construct() - $request стал обязательным
    - getErrors() - теперь возвращает массив собственных ошибок
    - get() - удалено
