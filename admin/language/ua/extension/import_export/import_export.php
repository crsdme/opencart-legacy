<?php
$_['heading_title'] = 'Import / Export';

$_['text_home'] = 'Головна';
$_['text_extension'] = 'Доповнення';
$_['text_success'] = 'Налаштування збережено';
$_['text_edit'] = 'Імпорт / експорт каталогу';
$_['text_enabled'] = 'Увімкнено';
$_['text_disabled'] = 'Вимкнено';
$_['text_yes'] = 'Так';
$_['text_no'] = 'Ні';
$_['text_all'] = 'Усі';
$_['text_or'] = 'Або вставте JSON';
$_['text_recent'] = 'Останні завдання';
$_['text_no_results'] = 'Завдань ще немає.';
$_['text_confirm'] = 'Ви впевнені?';
$_['text_deleted'] = 'Завдання видалено.';
$_['text_imported'] = 'Імпорт завершено. Створено: %s, оновлено: %s, пропущено: %s, помилок: %s';
$_['text_pagination'] = 'Показано %d–%d з %d (%d сторінок)';

$_['text_tab_dashboard'] = 'Налаштування';
$_['text_tab_template'] = 'Шаблони';
$_['text_tab_export'] = 'Експорт';
$_['text_tab_import'] = 'Імпорт';
$_['text_tab_job'] = 'Історія';

$_['text_template'] = 'Завантажити шаблон';
$_['text_export'] = 'Експорт каталогу';
$_['text_import'] = 'Імпорт каталогу';
$_['text_csv_hint'] = 'Для CSV потрібен тип сутності. JSON може бути однією сутністю або повним бандлом каталогу.';

$_['text_entity_auto'] = 'Авто (JSON)';
$_['text_entity_bundle'] = 'Весь каталог (бандл)';
$_['text_entity_product'] = 'Товари';
$_['text_entity_category'] = 'Категорії';
$_['text_entity_manufacturer'] = 'Виробники';
$_['text_entity_attribute'] = 'Атрибути';
$_['text_entity_attribute_group'] = 'Групи атрибутів';

$_['text_key_sku'] = 'SKU';
$_['text_key_model'] = 'Модель';
$_['text_missing_create'] = 'Створювати відсутні посилання';
$_['text_missing_error'] = 'Зупиняти з помилкою';
$_['text_missing_skip'] = 'Пропускати рядок';

$_['text_create'] = 'Створити';
$_['text_update'] = 'Оновити';
$_['text_skip'] = 'Пропустити';
$_['text_error'] = 'Помилка';
$_['text_total'] = 'Разом';

$_['text_action_import'] = 'Імпорт';
$_['text_action_export'] = 'Експорт';
$_['text_status_success'] = 'OK';
$_['text_status_error'] = 'Помилка';

$_['entry_status'] = 'Статус';
$_['entry_product_key'] = 'Ідентичність товару';
$_['entry_on_missing'] = 'Відсутні категорії / атрибути / бренди';
$_['entry_download_images'] = 'Завантажувати віддалені зображення';
$_['entry_delete_data'] = 'Видаляти історію при деінсталяції';
$_['entry_entity'] = 'Сутність';
$_['entry_format'] = 'Формат';
$_['entry_file'] = 'Файл';
$_['entry_category'] = 'Категорія';
$_['entry_manufacturer'] = 'Виробник';

$_['help_status'] = 'Вимкнення лише для майбутнього cron. Сторінки адмінки працюють і так.';
$_['help_product_key'] = 'Якщо product_id немає: шукати за SKU, або за model, якщо SKU порожній. product_id 0 завжди створює. Відʼємні id (наприклад -1) — псевдоніми лише всередині цього файлу.';
$_['help_on_missing'] = 'Коли товар посилається на категорію, атрибут або виробника, яких ще немає.';
$_['help_download_images'] = 'URL http(s) зберігаються в image/catalog/import/. Локальні шляхи не змінюються. Невдале завантаження пропускає це фото. JPEG, PNG, GIF, WebP, до 8 МБ.';
$_['help_delete_data'] = 'Якщо увімкнено, деінсталяція дропає таблицю історії. Дані каталогу не видаляються.';
$_['help_template'] = 'Порожній CSV/JSON зі схеми. Краще експорт, щоб ID були живі. Нові рядки: 0 або відʼємні псевдоніми (-1, -2) в JSON-бандлі, щоб звʼязати їх до того, як магазин видасть id.';
$_['help_export'] = 'Фільтри товарів діють на products. Бандл вивантажує всі підтримувані сутності.';
$_['help_import'] = 'Спочатку превʼю. Живий додатний id оновлює рядок. 0 = створити. Відʼємні id (-1, -2) створюють і дають іншим рядкам цього файлу на них посилатись (JSON-бандл для категорії + товару). Зображення: локальний шлях image/catalog або http(s) URL.';

$_['column_id'] = 'ID';
$_['column_date'] = 'Дата';
$_['column_action'] = 'Дія';
$_['column_entity'] = 'Сутність';
$_['column_format'] = 'Формат';
$_['column_file'] = 'Файл';
$_['column_status'] = 'Статус';
$_['column_created'] = 'Створено';
$_['column_updated'] = 'Оновлено';
$_['column_skipped'] = 'Пропущено';
$_['column_errors'] = 'Помилки';
$_['column_message'] = 'Лог';
$_['column_row'] = 'Рядок';
$_['column_identity'] = 'Ключ';

$_['button_save'] = 'Зберегти';
$_['button_cancel'] = 'Скасувати';
$_['button_download'] = 'Завантажити';
$_['button_preview'] = 'Превʼю';
$_['button_import'] = 'Імпортувати';
$_['button_delete'] = 'Видалити';

$_['error_permission'] = 'Немає прав змінювати Import / Export.';
$_['error_file'] = 'Завантажте CSV/JSON або вставте JSON.';
