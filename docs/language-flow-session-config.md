# Сценарий: session['language'] и config_language по шагам запроса

Порядок выполнения `action_pre_action` (catalog):

1. **startup/session**
2. **startup/startup**
3. startup/error
4. startup/event
5. startup/maintenance
6. **startup/multilang**
7. startup/seo_url
8. **startup/multilang_rewrite**
9. далее — router, основной контроллер, view

---

## 1. startup/session

- Стартует сессия (если не была), читаются данные из хранилища (DB).
- **session** и **config** не меняются на этом шаге.
- В `session->data['language']` приходит значение с прошлого запроса (если было) или пусто.

---

## 2. startup/startup (блок Language)

**Вход:** `session->data['language']` (из хранилища), cookie `language`, заголовок Accept-Language, `config_language` из БД.

Определяется `$code` по приоритету:

1. Если есть `session->data['language']` и это валидный код → `$code = session`
2. Иначе если есть cookie `language` и код валидный → `$code = cookie`
3. Иначе попытка автоопределения по Accept-Language
4. Иначе `$code = config_language` из БД

Дальше:

- `config_language_main = config_language` (из БД) — язык «без префикса» в URL.
- Если session не задан или session ≠ code → **session = $code**
- Если cookie не задана или cookie ≠ code → ставится cookie `language = $code`
- Создаётся объект `Language($code)`, кладётся в registry.
- **config_language = $code**
- **config_language_id** = id из справочника языков.

**Выход:** session и config приведены к одному выбранному языку (session/cookie/браузер/БД).

---

## 3. startup/multilang

**Вход:** `REQUEST_URI`, `_route_` (из .htaccess), уже установленные session и config из startup.

Логика:

- Берётся путь из URL: `path = parse_url(REQUEST_URI, path)` без ведущего/концевого `/`.
- Если путь заканчивается на статику (css, js, картинки и т.д.) → **return**, ничего не меняем.
- Если заголовок X-Requested-With: XMLHttpRequest → **return**, ничего не меняем.
- `route = _route_` или, если пусто, `route = path`.
- Первый сегмент пути: `prefix = parts[0]` (в нижнем регистре).

### Вариант A: в пути есть префикс языка (prefix ∈ коды языков, например `ru`, `ua`, `en`)

- Из `_route_` убирается префикс (например `ru/category` → `category`), обновляются `$_GET['_route_']` и `$this->request->get['_route_']`.
- **session->data['language'] = prefix**
- **config_language = prefix**
- **config_language_id** = id этого языка.
- Ставится cookie `language = prefix`.
- Создаётся новый `Language(prefix)` и подменяется в registry.

**Выход:** язык страницы = язык из URL, session и config совпадают с prefix.

### Вариант B: префикса нет или он не язык (prefix пустой или не из списка кодов)

- Если **path === 'index.php'** (прямой вызов или AJAX к index.php?route=...) → **return без изменений**.  
  Session и config остаются такими, как выставил startup (так мы не затираем язык при AJAX/прямых запросах).

- Иначе (запрос к `/` или к ЧПУ без префикса, например `/category`):
  - `main = config_language_main` (язык по умолчанию из БД).
  - **session = main**
  - **config_language = main**
  - **config_language_id** = id main.
  - cookie и объект Language переустанавливаются на main.

**Выход:** язык = основной язык магазина, session и config снова совпадают.

---

## 4. startup/seo_url

- Разбирает `_route_` в нормальный `route` (и query), **session и config не трогает**.

---

## 5. startup/multilang_rewrite

- В **index()**: только регистрируется callback перезаписи URL: `$this->url->addRewrite($this)`.
- **session и config здесь не меняются.**

Далее при каждом вызове `$this->url->link(...)`:

- Вызывается **rewrite($link)**.
- Берётся **config_language** (только чтение).
- Если язык = main → для главной возвращается `/`, иначе ссылка не меняется.
- Если язык ≠ main → в путь ссылки добавляется префикс: `/$code/...` (если его ещё нет).
- **session не читается и не пишется** — для построения ссылок используется только **config_language**.

---

## 6. Дальше по запросу (router, контроллеры, view)

- Контроллеры и шаблоны используют:
  - `$this->config->get('config_language')` — текущий язык страницы (уже согласован с URL в multilang).
  - `$this->session->data['language']` — после описанной цепочки должен совпадать с config_language.
- Переключатель языков в `controller_tw/common/language` берёт текущий язык как `config_language ?: session`, чтобы активная вкладка всегда соответствовала отображаемой версии (ru/ua/en).

---

## Краткая таблица «кто что делает»

| Этап                    | session['language']     | config_language        |
|-------------------------|-------------------------|-------------------------|
| startup/session         | загрузка из хранилища   | не трогает              |
| startup/startup         | = code (session/cookie/браузер/БД) | = code         |
| startup/multilang       | = prefix или main или без изменений (index.php) | = prefix или main или без изменений |
| startup/multilang_rewrite | не трогает            | только читает для ссылок |
| Дальше                  | читают контроллеры/view | читают контроллеры/view  |

Итог: после startup + multilang язык страницы задаётся URL (или main при `/` и ЧПУ без префикса); для запросов к `index.php` без префикса язык не перезаписывается, поэтому session и config_language не расходятся.
