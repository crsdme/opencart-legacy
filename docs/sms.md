# SMS

Facade `$this->sms`. Controllers send a phone and a text. The gateway behind it is chosen in store settings. Catalog only: the object is registered in `catalog/controller/startup/startup.php`.

## Send

```php
$this->sms->send($phone, $message);
```

- Strips the phone to digits (`380991234567`).
- Trims the message.
- Empty phone or message → `false`, no request.
- Returns `true` / `false` from the gateway.

Do not build provider URLs in controllers. Call `send()` and handle `false`.

Phone login uses this path: `Custom\Account::sendLoginCode()` → OTP → `send()`. SMS text is the theme field **Login SMS text** (`theme_default_sms_message`), `{code}` is the one-time code. Empty template falls back to `{code}`.

## Settings

Admin → System → Settings → tab **SMS**.

| Setting | Key | Role |
| --- | --- | --- |
| Gateway | `config_sms_gateway` | Filename stem under `sms/gateway/`. Default `log`. |
| Sender | `config_sms_sender` | Alpha name. Required for TurboSMS. |
| API token | `config_sms_http_token` | Bearer token (TurboSMS HTTP API, optional for generic HTTP). |
| HTTP URL | `config_sms_http_url` | Generic HTTP gateway only. |
| HTTP body | `config_sms_http_body` | Generic HTTP JSON body. Empty → `{"phone","message","sender"}`. |

`Custom\Setting::get($config, 'sms_gateway')` reads `config_*` first, then legacy `theme_default_*`.

Unknown or missing class → **log** gateway.

## Gateways

Interface: `public_html/system/library/sms/gateway.php` (`Sms\Gateway`). One method: `send($phone, $message)`.

Autoload: `Sms\Gateway\Turbosms` → `system/library/sms/gateway/turbosms.php` (OpenCart `library()`: namespace path, lowercase).

Admin dropdown is `glob` of that folder. Label key: `text_sms_gateway_{code}` in admin `setting/setting.php`.

### log

Writes `SMS to {phone}: {message}` to the OpenCart log (`system/storage/logs/error.log`). Always `true`. Default for local Docker.

### turbosms

POST `https://api.turbosms.ua/message/send.json`. Token + sender required. Accepts response codes `0`, `800`–`803`. Failures are logged, `send()` returns `false`.

### http

POST JSON to `config_sms_http_url`. Placeholders in URL and body: `{phone}` `{message}` `{sender}` `{token}`. Optional `Authorization: Bearer {token}`. HTTP 2xx = success.

## Add a provider

1. `public_html/system/library/sms/gateway/{code}.php`
2. `namespace Sms\Gateway;` class name = code with underscores removed and each part capitalized (`turbosms` → `Turbosms`).
3. `implements \Sms\Gateway`, constructor `($registry)`.
4. Admin language `text_sms_gateway_{code}`.
5. Pick it in **SMS** settings.

Do not add a new OpenCart module for a new SMS vendor.
