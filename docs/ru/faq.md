# FAQ

FAQ для страниц товара, категории и производителя. Вопросы правятся в админке. Витрина рисует доступный аккордеон и тот же `FAQPage` JSON-LD.

## Установка

1. Файлы уже в проекте.
2. Админка → Дополнения → Дополнения → Модули → FAQ → Установить.
3. Включите модуль (установка включает сама).
4. Выдайте `access` / `modify` для:
   - `extension/module/faq`
   - `extension/faq`

Деинсталл **не** дропает вопросы, пока не включён **Delete FAQ data on uninstall**.

## Где править

- **Настройки модуля:** глобально вкл/выкл, по типам, только первая страница листинга, заголовки блока, вопросы на все страницы типа.
- **Формы каталога:** вкладка **FAQ** у товара, категории и производителя.
  - Вкл/выкл на этой странице (прячет свои и глобальные вопросы).
  - Вопросы этой страницы.

## Витрина

- Товар: после характеристик.
- Категория / производитель: после списка товаров.
- FAQ листинга скрыт на странице 2+, если включено **First listing page only**.
- Отфильтрованные категории (`filter=`) FAQ не выводят.

Видимая вёрстка — нативный аккордеон `<details>` (`default/components/accordion.twig`). Схема только JSON-LD (`FAQPage` в `common/microdata`). JSON-LD и HTML-микродата не смешиваются, чтобы у Google не было двух FAQPage.

## Плейсхолдеры

Подставляются на витрине.

Товар: `{name}` `{product_name}` `{heading_title}` `{meta_title}` `{price}` `{product_price}` `{manufacturer}` `{model}` `{sku}` `{category}` `{category_name}` `{month}` `{year}`

Категория: `{name}` `{category_name}` `{heading_title}` `{meta_title}` `{month}` `{year}`

Производитель: `{name}` `{manufacturer_name}` `{heading_title}` `{meta_title}` `{month}` `{year}`

## База (`DB_PREFIX`)

- `faq` — entity_type (`product` / `category` / `manufacturer`), entity_id (`0` = глобально), scope (`page`), sort_order, status
- `faq_description` — вопрос и ответ на язык
- `faq_page` — вкл/выкл на странице

## Схема

`FAQPage` с `mainEntity` → `Question` → `acceptedAnswer` → `Answer`. `name` / `text` — обычный текст. Видимый аккордеон должен совпадать с этими вопросами. Автосписки «последние товары как FAQ» не используются: они не проходят гайдлайны FAQ.
