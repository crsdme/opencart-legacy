# SMS

Фасад `$this->sms`. Контроллеры передают телефон и текст. Шлюз выбирается в настройках магазина. Только каталог: объект регистрируется в `catalog/controller/startup/startup.php`.

## Отправка

```php
$this->sms->send($phone, $message);
```

- Телефон режется до цифр (`380991234567`).
- Сообщение обрезается по краям.
- Пустой телефон или текст → `false`, запроса нет.
- Возврат `true` / `false` от шлюза.

Не собирайте URL провайдера в контроллерах. Зовите `send()` и обрабатывайте `false`.

Вход по телефону идёт так: `Custom\Account::sendLoginCode()` → OTP → `send()`. Текст SMS — поле темы **Login SMS text** (`theme_default_sms_message`), `{code}` — одноразовый код. Пустой шаблон падает в `{code}`.

## Настройки

Админка → Система → Настройки → вкладка **SMS**.

| Настройка | Ключ | Роль |
| --- | --- | --- |
| Шлюз | `config_sms_gateway` | Имя файла в `sms/gateway/`. По умолчанию `log`. |
| Отправитель | `config_sms_sender` | Альфа-имя. Нужен для TurboSMS. |
| API-токен | `config_sms_http_token` | Bearer (TurboSMS HTTP API, для общего HTTP опционален). |
| HTTP URL | `config_sms_http_url` | Только общий HTTP-шлюз. |
| HTTP body | `config_sms_http_body` | JSON-тело общего HTTP. Пусто → `{"phone","message","sender"}`. |

`Custom\Setting::get($config, 'sms_gateway')` читает `config_*`, затем старые `theme_default_*`.

Неизвестный или отсутствующий класс → шлюз **log**.

## Шлюзы

Интерфейс: `public_html/system/library/sms/gateway.php` (`Sms\Gateway`). Один метод: `send($phone, $message)`.

Автозагрузка: `Sms\Gateway\Turbosms` → `system/library/sms/gateway/turbosms.php` (OpenCart `library()`: путь неймспейса, нижний регистр).

Выпадашка в админке — `glob` этой папки. Ключ подписи: `text_sms_gateway_{code}` в админском `setting/setting.php`.

### log

Пишет `SMS to {phone}: {message}` в лог OpenCart (`system/storage/logs/error.log`). Всегда `true`. Дефолт для локального Docker.

### turbosms

POST `https://api.turbosms.ua/message/send.json`. Нужны токен и отправитель. Принимает коды ответа `0`, `800`–`803`. Ошибки в лог, `send()` → `false`.

### http

POST JSON на `config_sms_http_url`. Плейсхолдеры в URL и теле: `{phone}` `{message}` `{sender}` `{token}`. Опционально `Authorization: Bearer {token}`. HTTP 2xx = успех.

## Добавить провайдера

1. `public_html/system/library/sms/gateway/{code}.php`
2. `namespace Sms\Gateway;` имя класса = код без подчёркиваний, каждое слово с большой (`turbosms` → `Turbosms`).
3. `implements \Sms\Gateway`, конструктор `($registry)`.
4. Языковой ключ админки `text_sms_gateway_{code}`.
5. Выбрать во вкладке **SMS**.

Не делайте новый модуль OpenCart под нового SMS-вендора.
