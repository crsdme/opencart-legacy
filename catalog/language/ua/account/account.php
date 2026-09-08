<?php

$page = !empty($this->data['_account_page']) ? $this->data['_account_page'] : 'account';

// Shared
$_['text_account']        = 'Особистий кабінет';
$_['text_none']           = ' --- Немає --- ';
$_['text_confirm']        = 'Ви впевнені?';
$_['text_no_results']     = 'Немає даних';
$_['text_select']         = ' --- Оберіть --- ';
$_['button_view']         = 'Переглянути';
$_['button_edit']         = 'Змінити';
$_['button_delete']       = 'Видалити';
$_['button_new_address']  = 'Нова адреса';
$_['button_download']     = 'Завантажити';
$_['button_reorder']      = 'Повторити замовлення';
$_['button_return']       = 'Повернення';

// account/account
$_['heading_title']       = 'Особистий кабінет';
$_['text_my_account']     = 'Обліковий запис';
$_['text_my_orders']      = 'Замовлення';
$_['text_my_affiliate']   = 'Партнерський розділ';
$_['text_my_newsletter']  = 'Підписка';
$_['text_edit']           = 'Контактна інформація';
$_['text_password']       = 'Зміна пароля';
$_['text_address']        = 'Зміна адрес';
$_['text_credit_card']    = 'Керування банківськими картками';
$_['text_wishlist']       = 'Закладки';
$_['text_order']          = 'Історія замовлень';
$_['text_download']       = 'Файли для завантаження';
$_['text_reward']         = 'Бонусні бали';
$_['text_return']         = 'Запити на повернення товару';
$_['text_transaction']    = 'Історія платежів';
$_['text_newsletter']     = 'Підписатись або відмовитись від підписки';
$_['text_recurring']      = 'Регулярні платежі';
$_['text_affiliate_add']  = 'Реєстрація партнерського облікового запису';
$_['text_affiliate_edit'] = 'Керування партнерською інформацією';
$_['text_tracking']       = 'Код відстеження партнерів';
$_['text_logout']         = 'Вихід';

// account/password
if ($page === 'password') {
	$_['heading_title']  = 'Зміна пароля';
	$_['text_password']  = 'Поточний пароль';
	$_['text_success']   = 'Ваш пароль успішно змінений';
	$_['entry_password'] = 'Новий пароль';
	$_['entry_confirm']  = 'Підтвердіть пароль';
	$_['error_password'] = 'Пароль має містити від 4 до 20 символів';
	$_['error_confirm']  = 'Паролі не співпадають';
}

// account/forgotten
if ($page === 'forgotten') {
	$_['heading_title']  = 'Відновлення пароля';
	$_['text_forgotten'] = 'Забули пароль?';
	$_['text_your_email'] = 'Ваш E-Mail';
	$_['text_email']     = 'Введіть адресу електронної пошти вашого облікового запису. Натисніть кнопку Продовжити для отримання пароля на електронну пошту.';
	$_['text_success']   = 'Новий пароль був надісланий на вашу адресу електронної пошти';
	$_['entry_email']    = 'E-Mail адреса';
	$_['error_email']    = 'E-Mail адресу не знайдено, перевірте правильність вводу';
	$_['error_approved'] = 'Ваш обліковий запис потребує схвалення адміністрацією перш ніж ви зможете увійти.';
}

// account/reset
if ($page === 'reset') {
	$_['heading_title']  = 'Скинути пароль';
	$_['text_password']  = 'Вкажіть новий пароль';
	$_['text_success']   = 'Пароль успішно змінений';
	$_['entry_password'] = 'Пароль';
	$_['entry_confirm']  = 'Підтвердіть пароль';
	$_['error_password'] = 'Пароль має містити від 4 до 20 символів';
	$_['error_confirm']  = 'Паролі не співпадають';
	$_['error_code']     = 'Код скидання пароля недійсний або був використаний раніше';
}

// account/newsletter
if ($page === 'newsletter') {
	$_['heading_title']    = 'Підписка на новини';
	$_['text_newsletter']  = 'Розсилка';
	$_['text_success']     = 'Ваша підписка оновлена';
	$_['entry_newsletter'] = 'Підписатись';
}

// account/edit
if ($page === 'edit') {
	$_['heading_title']      = 'Обліковий запис';
	$_['text_edit']          = 'Редагувати інформацію';
	$_['text_your_details']  = 'Особисті дані';
	$_['text_success']       = 'Обліковий запис оновлений';
	$_['entry_firstname']    = 'Ім’я';
	$_['entry_lastname']     = 'Прізвище';
	$_['entry_email']        = 'E-Mail';
	$_['entry_telephone']    = 'Телефон';
	$_['entry_fax']          = 'Факс';
	$_['error_exists']       = 'Така адреса E-Mail вже зареєстрована';
	$_['error_firstname']    = 'Ім’я має містити від 1 до 32 символів';
	$_['error_lastname']     = 'Прізвище має містити від 1 до 32 символів';
	$_['error_email']        = 'E-Mail адресу вказано невірно';
	$_['error_telephone']    = 'Номер телефону має містити від 3 до 32 символів';
	$_['error_custom_field'] = 'Вкажіть %s';
}

// account/address
if ($page === 'address') {
	$_['heading_title']      = 'Адреси';
	$_['text_address_book']  = 'Перелік адрес доставки';
	$_['text_address_add']   = 'Додати адресу';
	$_['text_address_edit']  = 'Редагувати адресу';
	$_['text_add']           = 'Адресу додано';
	$_['text_edit']          = 'Адресу змінено';
	$_['text_delete']        = 'Адреса видалена';
	$_['text_empty']         = 'В вашому обліковому записі немає адрес';
	$_['entry_firstname']    = 'Ім’я';
	$_['entry_lastname']     = 'Прізвище';
	$_['entry_company']      = 'Компанія';
	$_['entry_address_1']    = 'Адреса 1';
	$_['entry_address_2']    = 'Адреса 2';
	$_['entry_postcode']     = 'Поштовий індекс';
	$_['entry_city']         = 'Місто';
	$_['entry_country']      = 'Країна';
	$_['entry_zone']         = 'Область / Регіон';
	$_['entry_default']      = 'Основна адреса';
	$_['error_delete']       = 'Має бути не менше однієї адреси';
	$_['error_default']      = 'Ви не можете видалити основну адресу';
	$_['error_firstname']    = 'Ім’я має містити від 1 до 32 символів';
	$_['error_lastname']     = 'Прізвище має містити від 1 до 32 символів';
	$_['error_vat']          = 'Неправильний VAT номер';
	$_['error_address_1']    = 'Адреса має містити від 3 до 128 символів';
	$_['error_postcode']     = 'Індекс має містити від 2 до 10 символів';
	$_['error_city']         = 'Назва міста має містити від 2 до 128 символів';
	$_['error_country']      = 'Будь ласка, вкажіть країну';
	$_['error_zone']         = 'Будь ласка, вкажіть регіон / область';
	$_['error_custom_field'] = 'Вкажіть %s';
}

// account/order
if ($page === 'order') {
	$_['heading_title']         = 'Історія замовлень';
	$_['text_order']            = 'Замовлення';
	$_['text_order_detail']     = 'Деталі замовлення';
	$_['text_invoice_no']       = '№ рахунку:';
	$_['text_order_id']         = '№ замовлення:';
	$_['text_date_added']       = 'Дата додавання:';
	$_['text_shipping_address'] = 'Адреса доставки';
	$_['text_shipping_method']  = 'Спосіб доставки:';
	$_['text_payment_address']  = 'Платіжна адреса';
	$_['text_payment_method']   = 'Спосіб оплати:';
	$_['text_comment']          = 'Коментар до замовлення';
	$_['text_history']          = 'Історія замовлення';
	$_['text_success']          = 'Товари із замовлення <a href="%s">%s</a> додані <a href="%s">у кошик</a>';
	$_['text_empty']            = 'У вас ще не було покупок';
	$_['text_error']            = 'Такого замовлення не існує';
	$_['column_order_id']       = '№ замовлення';
	$_['column_product']        = 'Кількість товарів';
	$_['column_customer']       = 'Покупець';
	$_['column_name']           = 'Назва товару';
	$_['column_model']          = 'Модель';
	$_['column_quantity']       = 'Кількість';
	$_['column_price']          = 'Ціна';
	$_['column_total']          = 'Сума';
	$_['column_action']         = 'Дія';
	$_['column_date_added']     = 'Дата додавання';
	$_['column_status']         = 'Статус';
	$_['column_comment']        = 'Коментар';
	$_['error_reorder']         = '%s наразі не доступні для замовлення';
}

// account/download
if ($page === 'download') {
	$_['heading_title']     = 'Файли для завантаження';
	$_['text_downloads']    = 'Файли для завантаження';
	$_['text_empty']        = 'Немає доступних для завантаження файлів';
	$_['column_order_id']   = '№ замовлення';
	$_['column_name']       = 'Назва';
	$_['column_size']       = 'Розмір';
	$_['column_date_added'] = 'Дата додавання';
}

// account/transaction
if ($page === 'transaction') {
	$_['heading_title']      = 'Історія фінансових операцій';
	$_['column_date_added']  = 'Дата додавання';
	$_['column_description'] = 'Опис';
	$_['column_amount']      = 'Сума (%s)';
	$_['text_transaction']   = 'Фінансові операції';
	$_['text_total']         = 'Ваш поточний баланс:';
	$_['text_empty']         = 'Не було фінансових операцій';
}

// account/reward
if ($page === 'reward') {
	$_['heading_title']      = 'Бонусні бали';
	$_['column_date_added']  = 'Дата додавання';
	$_['column_description'] = 'Опис';
	$_['column_points']      = 'Бонусні бали';
	$_['text_reward']        = 'Бонусні бали';
	$_['text_total']         = 'Накопичено бонусних балів:';
	$_['text_empty']         = 'У вас немає бонусних балів';
}

// account/return
if ($page === 'return') {
	$_['heading_title']      = 'Повернення товару';
	$_['text_return']        = 'Інформація про повернення';
	$_['text_return_detail'] = 'Детальна інформація про повернення';
	$_['text_description']   = 'Будь ласка, заповніть форму запиту на повернення товару';
	$_['text_order']         = 'Інформація про замовлення';
	$_['text_product']       = 'Інформація про товар та причина повернення';
	$_['text_message']       = '<p>Ви надіслали запит на повернення товару.</p><p>Повідомлення про статус запиту будуть надходити на вашу адресу e-mail.</p>';
	$_['text_return_id']     = '№ запиту на повернення:';
	$_['text_order_id']      = '№ замовлення:';
	$_['text_date_ordered']  = 'Дата замовлення:';
	$_['text_status']        = 'Статус:';
	$_['text_date_added']    = 'Дата додавання:';
	$_['text_comment']       = 'Коментар до повернення';
	$_['text_history']       = 'Історія повернень';
	$_['text_empty']         = 'У вас не було раніше повернення товарів';
	$_['text_agree']         = 'Я погоджуюсь з умовами <a href="%s" class="agree"><b>%s</b></a>';
	$_['text_error']         = 'Запит на повернення не знайдений';
	$_['column_return_id']   = '№ запиту на повернення';
	$_['column_order_id']    = '№ замовлення';
	$_['column_status']      = 'Статус';
	$_['column_date_added']  = 'Дата додавання';
	$_['column_customer']    = 'Покупець';
	$_['column_product']     = 'Назва товару';
	$_['column_model']       = 'Модель';
	$_['column_quantity']    = 'Кількість';
	$_['column_price']       = 'Ціна';
	$_['column_opened']      = 'Відкрито';
	$_['column_comment']     = 'Коментар';
	$_['column_reason']      = 'Причина';
	$_['column_action']      = 'Дія';
	$_['entry_order_id']     = '№ замовлення';
	$_['entry_date_ordered'] = 'Дата замовлення';
	$_['entry_firstname']    = 'Ім’я';
	$_['entry_lastname']     = 'Прізвище';
	$_['entry_email']        = 'E-Mail';
	$_['entry_telephone']    = 'Телефон';
	$_['entry_product']      = 'Назва товару';
	$_['entry_model']        = 'Модель';
	$_['entry_quantity']     = 'Кількість';
	$_['entry_reason']       = 'Причина повернення';
	$_['entry_opened']       = 'Товар розпакований';
	$_['entry_fault_detail'] = 'Опис дефектів';
	$_['error_order_id']     = 'Не вказаний номер замовлення';
	$_['error_firstname']    = 'Ім’я має містити від 1 до 32 символів';
	$_['error_lastname']     = 'Прізвище має містити від 1 до 32 символів';
	$_['error_email']        = 'E-Mail адресу вказано невірно';
	$_['error_telephone']    = 'Номер телефону має містити від 3 до 32 символів';
	$_['error_product']      = 'Назва товару має містити від 3 до 255 символів';
	$_['error_model']        = 'Модель має містити від 3 до 64 символів';
	$_['error_reason']       = 'Необхідно вказати причину повернення товару';
	$_['error_agree']        = 'Ви маєте прочитати та погодитись з умовами %s!';
}

// account/recurring
if ($page === 'recurring') {
	$_['heading_title']                        = 'Регулярні платежі';
	$_['text_recurring']                       = 'Регулярні платежі';
	$_['text_order_recurring_id']              = '№ профілю:';
	$_['text_date_added']                      = 'Дата додавання:';
	$_['text_status']                          = 'Статус:';
	$_['text_payment_method']                  = 'Метод оплати:';
	$_['text_order_id']                        = '№ замовлення:';
	$_['text_product']                         = 'Товар:';
	$_['text_quantity']                        = 'Кількість:';
	$_['text_description']                     = 'Опис';
	$_['text_reference']                       = 'Примітка';
	$_['text_transaction']                     = 'Операції';
	$_['text_empty']                           = 'Не знайдений платіжний профіль';
	$_['text_status_active']                   = 'Включений';
	$_['text_status_inactive']                 = 'Виключений';
	$_['text_status_cancelled']                = 'Скасований';
	$_['text_status_suspended']                = 'Заморожений';
	$_['text_status_expired']                  = 'Закінчився';
	$_['text_status_pending']                  = 'Очікує';
	$_['column_order_recurring_id']            = '№ профілю';
	$_['column_product']                       = 'Товар';
	$_['column_status']                        = 'Статус';
	$_['column_date_added']                    = 'Дата додавання';
	$_['column_type']                          = 'Тип';
	$_['column_amount']                        = 'Сума';
}

// account/affiliate
if ($page === 'affiliate') {
	$_['heading_title']             = 'Партнерська інформація';
	$_['text_affiliate']            = 'Партнерський розділ';
	$_['text_my_affiliate']         = 'Партнерський обліковий запис';
	$_['text_payment']              = 'Платіжна інформація';
	$_['text_cheque']               = 'Чек';
	$_['text_paypal']               = 'PayPal';
	$_['text_bank']                 = 'Банківський платіж';
	$_['text_success']              = 'Ваш обліковий запис оновлений.';
	$_['text_agree']                = 'Я ознайомлений та даю згоду з <a href="%s" class="agree"><b>%s</b></a>';
	$_['entry_company']             = 'Компанія';
	$_['entry_website']             = 'Веб-сайт';
	$_['entry_tax']                 = 'Податковий (ІПН)';
	$_['entry_payment']             = 'Спосіб оплати';
	$_['entry_cheque']              = 'Чек, ім’я отримувача платежу';
	$_['entry_paypal']              = 'PayPal Email';
	$_['entry_bank_name']           = 'Назва банку';
	$_['entry_bank_branch_number']  = 'ABA/BSB номер (номер відділення)';
	$_['entry_bank_swift_code']     = 'SWIFT код';
	$_['entry_bank_account_name']   = 'Назва рахунку';
	$_['entry_bank_account_number'] = 'Номер рахунку';
	$_['error_agree']               = 'Необхідно прийняти %s!';
	$_['error_cheque']              = 'Невірно вказане ім’я отримувача';
	$_['error_paypal']              = 'Недійсна адреса електронної пошти PayPal';
	$_['error_bank_account_name']   = 'Назва рахунку обов’язкова';
	$_['error_bank_account_number'] = 'Номер рахунку обов’язковий';
	$_['error_custom_field']        = '%s - обов’язково';
}

// account/tracking
if ($page === 'tracking') {
	$_['heading_title']   = 'Реферальні посилання';
	$_['text_description'] = 'Для отримання агентської винагороди з покупок, що здійснили приведені вами покупці, необхідно додати в посилання реферальний код. Скористайтесь генератором посилань для сайту %s.';
	$_['entry_code']      = 'Ваш реферальний код';
	$_['entry_generator'] = 'Генератор реферальних посилань';
	$_['entry_link']      = 'Реферальне посилання';
	$_['help_generator']  = 'Введіть назву товару, на який необхідно створити посилання';
}

// account/voucher
if ($page === 'voucher') {
	$_['heading_title']    = 'Подарунковий сертифікат';
	$_['text_voucher']     = 'Подарунковий сертифікат';
	$_['text_description'] = 'Цей подарунковий сертифікат буде відправлений отримувачу на E-Mail після оплати.';
	$_['text_agree']       = 'Я розумію та погоджусь з тим, що подарункові сертифікати не підлягають поверненню';
	$_['text_message']     = '<p>Дякуємо за придбання подарункового сертифікату. Після завершення замовлення отримувачу сертифіката буде надіслано листа з детальною інформацією щодо його використання.</p>';
	$_['text_for']         = '%s Подарунковий сертифікат для %s';
	$_['entry_to_name']    = 'Ім’я отримувача';
	$_['entry_to_email']   = 'E-Mail отримувача';
	$_['entry_from_name']  = 'Ваше ім’я';
	$_['entry_from_email'] = 'Ваш E-Mail';
	$_['entry_theme']      = 'Тема подарункового сертифікату';
	$_['entry_message']    = 'Повідомлення';
	$_['entry_amount']     = 'Разом';
	$_['help_message']     = 'Не обов’язково';
	$_['help_amount']      = 'Має бути від %s до %s';
	$_['error_to_name']    = 'Ім’я отримувача має містити від 1 до 64 символів';
	$_['error_from_name']  = 'Ваше ім’я має містити від 1 до 64 символів';
	$_['error_email']      = 'E-Mail адресу вказано невірно';
	$_['error_theme']      = 'Оберіть тему сертифікату';
	$_['error_amount']     = 'Сума має бути від %s до %s!';
	$_['error_agree']      = 'Ви маєте погодитись з тим, що подарункові сертифікати не підлягають поверненню';
}
