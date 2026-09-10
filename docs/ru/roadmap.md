# Роадмап

Открытая работа и живой changelog. Как фичи устроены — в `DOCUMENTATION.md`.

## Сделать

- купить домен legacy-cart.com
- аддоны языков
- налоги
- переработать почту OpenCart
- товарные варианты
- очистка кэша в админке
- недавно просмотренные
- SEO URL должен быть уникальным
- баллы
- логи скорости каждого контроллера: старт и конец
- md-файлы в админке
- рефактор интерфейса админки
- route=setting/setting

- выключить авто-maintenance
- брошенные корзины
- переработать или убрать Фільтр
- показать ещё товары (сверить с ocfilter)
- cookies

- windows — linux bind, скорость dev
- button-group https://ui.shadcn.com/docs/components/radix/button-group
- collapsible https://ui.shadcn.com/docs/components/radix/collapsible
- checkbox https://ui.shadcn.com/docs/components/radix/checkbox
- table https://ui.shadcn.com/docs/components/radix/data-table
- hover https://ui.shadcn.com/docs/components/radix/hover-card
- tooltip https://ui.shadcn.com/docs/components/radix/tooltip
- input group https://ui.shadcn.com/docs/components/radix/input-group
- input https://ui.shadcn.com/docs/components/radix/input
- nav https://ui.shadcn.com/docs/components/radix/navigation-menu
- progress https://ui.shadcn.com/docs/components/radix/progress
- popover https://ui.shadcn.com/docs/components/radix/popover
- radio https://ui.shadcn.com/docs/components/radix/radio-group
- select https://ui.shadcn.com/docs/components/radix/select
- sonner https://ui.shadcn.com/docs/components/radix/sonner
- switch https://ui.shadcn.com/docs/components/radix/switch
- tabs https://ui.shadcn.com/docs/components/radix/tabs
- typography https://ui.shadcn.com/docs/components/radix/typography

## Changelog

Что набор добавляет поверх ocStore. Это не датированный релиз-лог: в git один большой коммит плюс незакоммиченная работа. Детали — в `DOCUMENTATION.md` и связанных гайдах.

### Тема и витрина

- Тема Default на Tailwind (`catalog/view/theme/default`): общие `layout` / `head`, настройки темы
- Рестиль витрины: товар, листинги, шапка, футер, модалка корзины, кабинет, сравнение, закладки
- Live search и анонс-бар в шапке
- Модуль карусели (баннеры desktop/mobile, Embla)
- Товарные модули (featured / latest / special / bestseller): рестиль и опции админки
- Рестиль блога и страницы авторов (сущность в админке, витрина, sitemap, микроразметка)
- Гостевые закладки — настройка темы
- Разрез CSS (`ui` / `catalog` / `product`) и JS (`ui` / `catalog` / `product` / `live-search` / `phone-login` / `checkout`)
- Галерея компонентов `/index.php?route=docs/components`

### SEO

- Свой роутер (`system/library/custom/router.php`)
- Префиксы языка в URL и hreflang
- Авто-шаблоны meta title / description / H1
- Микроразметка (OpenGraph, JSON-LD) плюс вкладка Microdata
- Модуль FAQ (товар / категория / производитель, FAQPage JSON-LD) — `faq.md`
- Ветвящийся XML sitemap (`extension/feed/sitemap`) с настройками в админке
- Страница 410 Gone
- Redirect Manager (301 / 410, импорт, CLI) — `redirect_manager.md`
- Семейства страниц витрины можно выключить (404 + скрытие из sitemap/футера)

### Чекаут и кабинет

- Одностраничный чекаут (модуль полей, доставки, оплаты)
- Доставка Nova Poshta (отделение / поштомат / курьер) — см. Обзор
- Вход по телефону и фасад SMS (TurboSMS / HTTP / log) — `sms.md`
- События GA4 ecommerce на каталоге и чекауте

### Медиа и скорость

- WebP из `model/tool/image`, если браузер принимает
- Бандлер CSS/JS (`Custom\Minifier`), вкл/выкл в админке

### Ops и DX

- Docker Compose (PHP 7.4, MySQL 5.7, phpMyAdmin)
- Закрытый URL админки
- Auto Backup (БД + `image/catalog`, cron, Drive / FTP) — `backup.md`
- Import / Export (CSV/JSON каталога, шаблоны, preview, CLI) — `import_export.md`
- Markdown-сайт документации (`/index.php?route=docs`), языки `docs/en` и `docs/ru`
- Prettier (Twig + PHP)
- Хелпер `dev_dump`
- Убраны лишние стоковые модули (slideshow, banner, сайдбар категории/информации, неиспользуемые оплата/доставка)

Дальше: когда to-do уезжает в прод, переносите пункт сюда в той же сессии. Не собирайте changelog из чатов заново, если не пропущена целая область.
