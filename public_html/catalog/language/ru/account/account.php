<?php

$page = !empty($this->data['_account_page']) ? $this->data['_account_page'] : 'account';

// Shared
$_['text_account']        = 'Личный кабинет';
$_['text_none']           = ' --- Нет --- ';
$_['text_confirm']        = 'Вы уверены?';
$_['text_no_results']     = 'Нет данных';
$_['text_select']         = ' --- Выберите --- ';
$_['button_view']         = 'Просмотр';
$_['button_edit']         = 'Изменить';
$_['button_delete']       = 'Удалить';
$_['button_new_address']  = 'Новый адрес';
$_['button_download']     = 'Скачать';
$_['button_reorder']      = 'Повторить заказ';
$_['button_return']       = 'Возврат';

// account/account
$_['heading_title']       = 'Личный кабинет';
$_['text_my_account']     = 'Личный кабинет';
$_['text_my_orders']      = 'Заказы';
$_['text_my_affiliate']   = 'Партнерская программа';
$_['text_my_newsletter']  = 'Подписка на рассылку';
$_['text_edit']           = 'Учетная запись';
$_['text_password']       = 'Изменить пароль';
$_['text_address']        = 'Адреса доставки';
$_['text_credit_card']    = 'Управление кредитными картами';
$_['text_wishlist']       = 'Закладки';
$_['text_order']          = 'История заказов';
$_['text_download']       = 'Файлы для скачивания';
$_['text_reward']         = 'Бонусные баллы';
$_['text_return']         = 'Возвраты';
$_['text_transaction']    = 'История платежей';
$_['text_newsletter']     = 'Подписаться или отказаться от рассылки';
$_['text_recurring']      = 'Регулярные платежи';
$_['text_affiliate_add']  = 'Регистрация партнерского аккаунта';
$_['text_affiliate_edit'] = 'Партнерская информация';
$_['text_tracking']       = 'Код отслеживания';
$_['text_logout']         = 'Выход';

// account/password
if ($page === 'password') {
	$_['heading_title']  = 'Изменить пароль';
	$_['text_password']  = 'Пароль';
	$_['text_success']   = 'Ваш пароль успешно изменен';
	$_['entry_password'] = 'Новый пароль';
	$_['entry_confirm']  = 'Подтвердите пароль';
	$_['error_password'] = 'Пароль должен содержать от 4 до 20 символов';
	$_['error_confirm']  = 'Пароли не совпадают';
}

// account/forgotten
if ($page === 'forgotten') {
	$_['heading_title']   = 'Забыли пароль?';
	$_['text_forgotten']  = 'Забыли пароль?';
	$_['text_your_email'] = 'Ваш E-Mail';
	$_['text_email']      = 'Введите адрес электронной почты вашей учетной записи. Нажмите кнопку Продолжить, чтобы получить пароль на e-mail.';
	$_['text_success']    = 'Новый пароль был отправлен на ваш E-Mail';
	$_['entry_email']     = 'E-Mail';
	$_['error_email']     = 'E-Mail адрес не найден, проверьте правильность ввода';
	$_['error_approved']  = 'Ваша учетная запись требует одобрения администрацией, прежде чем вы сможете войти.';
}

// account/reset
if ($page === 'reset') {
	$_['heading_title']  = 'Сброс пароля';
	$_['text_password']  = 'Введите новый пароль';
	$_['text_success']   = 'Пароль успешно изменен';
	$_['entry_password'] = 'Пароль';
	$_['entry_confirm']  = 'Подтвердите пароль';
	$_['error_password'] = 'Пароль должен содержать от 4 до 20 символов';
	$_['error_confirm']  = 'Пароли не совпадают';
	$_['error_code']     = 'Код сброса пароля недействителен или уже был использован';
}

// account/newsletter
if ($page === 'newsletter') {
	$_['heading_title']    = 'Подписка на новости';
	$_['text_newsletter']  = 'Рассылка';
	$_['text_success']     = 'Ваша подписка успешно обновлена';
	$_['entry_newsletter'] = 'Подписаться';
}

// account/edit
if ($page === 'edit') {
	$_['heading_title']      = 'Учетная запись';
	$_['text_edit']          = 'Редактировать информацию';
	$_['text_your_details']  = 'Ваши данные';
	$_['text_success']       = 'Ваша учетная запись обновлена';
	$_['entry_firstname']    = 'Имя';
	$_['entry_lastname']     = 'Фамилия';
	$_['entry_email']        = 'E-Mail';
	$_['entry_telephone']    = 'Телефон';
	$_['entry_fax']          = 'Факс';
	$_['error_exists']       = 'Такой E-Mail уже зарегистрирован';
	$_['error_firstname']    = 'Имя должно содержать от 1 до 32 символов';
	$_['error_lastname']     = 'Фамилия должна содержать от 1 до 32 символов';
	$_['error_email']        = 'E-Mail введен неверно';
	$_['error_telephone']    = 'Телефон должен содержать от 3 до 32 символов';
	$_['error_custom_field'] = 'Укажите %s';
}

// account/address
if ($page === 'address') {
	$_['heading_title']      = 'Адреса';
	$_['text_address_book']  = 'Список адресов доставки';
	$_['text_address_add']   = 'Добавить адрес';
	$_['text_address_edit']  = 'Изменить адрес';
	$_['text_add']           = 'Адрес добавлен';
	$_['text_edit']          = 'Адрес изменен';
	$_['text_delete']        = 'Адрес удален';
	$_['text_empty']         = 'В вашей учетной записи нет адресов';
	$_['entry_firstname']    = 'Имя';
	$_['entry_lastname']     = 'Фамилия';
	$_['entry_company']      = 'Компания';
	$_['entry_address_1']    = 'Адрес 1';
	$_['entry_address_2']    = 'Адрес 2';
	$_['entry_postcode']     = 'Индекс';
	$_['entry_city']         = 'Город';
	$_['entry_country']      = 'Страна';
	$_['entry_zone']         = 'Регион / Область';
	$_['entry_default']      = 'Основной адрес';
	$_['error_delete']       = 'У вас должен быть хотя бы один адрес';
	$_['error_default']      = 'Вы не можете удалить основной адрес';
	$_['error_firstname']    = 'Имя должно содержать от 1 до 32 символов';
	$_['error_lastname']     = 'Фамилия должна содержать от 1 до 32 символов';
	$_['error_vat']          = 'Неверный VAT номер';
	$_['error_address_1']    = 'Адрес должен содержать от 3 до 128 символов';
	$_['error_postcode']     = 'Индекс должен содержать от 2 до 10 символов';
	$_['error_city']         = 'Название города должно содержать от 2 до 128 символов';
	$_['error_country']      = 'Пожалуйста, укажите страну';
	$_['error_zone']         = 'Пожалуйста, укажите регион / область';
	$_['error_custom_field'] = 'Укажите %s';
}

// account/order
if ($page === 'order') {
	$_['heading_title']         = 'История заказов';
	$_['text_order']            = 'Заказ';
	$_['text_order_detail']     = 'Детали заказа';
	$_['text_invoice_no']       = '№ счета:';
	$_['text_order_id']         = '№ заказа:';
	$_['text_date_added']       = 'Дата добавления:';
	$_['text_shipping_address'] = 'Адрес доставки';
	$_['text_shipping_method']  = 'Способ доставки:';
	$_['text_payment_address']  = 'Платежный адрес';
	$_['text_payment_method']   = 'Способ оплаты:';
	$_['text_comment']          = 'Комментарий к заказу';
	$_['text_history']          = 'История заказа';
	$_['text_success']          = 'Товары из заказа <a href="%s">%s</a> добавлены <a href="%s">в корзину</a>';
	$_['text_empty']            = 'Вы еще не совершали покупок';
	$_['text_error']            = 'Заказ не найден';
	$_['column_order_id']       = '№ заказа';
	$_['column_product']        = 'Количество товаров';
	$_['column_customer']       = 'Покупатель';
	$_['column_name']           = 'Название товара';
	$_['column_model']          = 'Модель';
	$_['column_quantity']       = 'Количество';
	$_['column_price']          = 'Цена';
	$_['column_total']          = 'Итого';
	$_['column_action']         = 'Действие';
	$_['column_date_added']     = 'Дата добавления';
	$_['column_status']         = 'Статус';
	$_['column_comment']        = 'Комментарий';
	$_['error_reorder']         = '%s в данный момент недоступны для заказа';
}

// account/download
if ($page === 'download') {
	$_['heading_title']     = 'Файлы для скачивания';
	$_['text_downloads']    = 'Файлы для скачивания';
	$_['text_empty']        = 'Нет доступных файлов для скачивания';
	$_['column_order_id']   = '№ заказа';
	$_['column_name']       = 'Название';
	$_['column_size']       = 'Размер';
	$_['column_date_added'] = 'Дата добавления';
}

// account/transaction
if ($page === 'transaction') {
	$_['heading_title']      = 'История финансовых операций';
	$_['column_date_added']  = 'Дата добавления';
	$_['column_description'] = 'Описание';
	$_['column_amount']      = 'Сумма (%s)';
	$_['text_transaction']   = 'Финансовые операции';
	$_['text_total']         = 'Ваш текущий баланс:';
	$_['text_empty']         = 'Не было финансовых операций';
}

// account/reward
if ($page === 'reward') {
	$_['heading_title']      = 'Бонусные баллы';
	$_['column_date_added']  = 'Дата добавления';
	$_['column_description'] = 'Описание';
	$_['column_points']      = 'Бонусные баллы';
	$_['text_reward']        = 'Бонусные баллы';
	$_['text_total']         = 'Накоплено бонусных баллов:';
	$_['text_empty']         = 'У вас нет бонусных баллов';
}

// account/return
if ($page === 'return') {
	$_['heading_title']      = 'Возврат товара';
	$_['text_return']        = 'Информация о возврате';
	$_['text_return_detail'] = 'Подробная информация о возврате';
	$_['text_description']   = 'Пожалуйста, заполните форму запроса на возврат товара';
	$_['text_order']         = 'Информация о заказе';
	$_['text_product']       = 'Информация о товаре и причина возврата';
	$_['text_message']       = '<p>Вы отправили запрос на возврат товара.</p><p>Уведомления о статусе запроса будут приходить на ваш e-mail.</p>';
	$_['text_return_id']     = '№ запроса на возврат:';
	$_['text_order_id']      = '№ заказа:';
	$_['text_date_ordered']  = 'Дата заказа:';
	$_['text_status']        = 'Статус:';
	$_['text_date_added']    = 'Дата добавления:';
	$_['text_comment']       = 'Комментарий к возврату';
	$_['text_history']       = 'История возвратов';
	$_['text_empty']         = 'У вас не было возвратов товаров';
	$_['text_agree']         = 'Я согласен с условиями <a href="%s" class="agree"><b>%s</b></a>';
	$_['text_error']         = 'Запрос на возврат не найден';
	$_['column_return_id']   = '№ запроса на возврат';
	$_['column_order_id']    = '№ заказа';
	$_['column_status']      = 'Статус';
	$_['column_date_added']  = 'Дата добавления';
	$_['column_customer']    = 'Покупатель';
	$_['column_product']     = 'Название товара';
	$_['column_model']       = 'Модель';
	$_['column_quantity']    = 'Количество';
	$_['column_price']       = 'Цена';
	$_['column_opened']      = 'Открыто';
	$_['column_comment']     = 'Комментарий';
	$_['column_reason']      = 'Причина';
	$_['column_action']      = 'Действие';
	$_['entry_order_id']     = '№ заказа';
	$_['entry_date_ordered'] = 'Дата заказа';
	$_['entry_firstname']    = 'Имя';
	$_['entry_lastname']     = 'Фамилия';
	$_['entry_email']        = 'E-Mail';
	$_['entry_telephone']    = 'Телефон';
	$_['entry_product']      = 'Название товара';
	$_['entry_model']        = 'Модель';
	$_['entry_quantity']     = 'Количество';
	$_['entry_reason']       = 'Причина возврата';
	$_['entry_opened']       = 'Товар распакован';
	$_['entry_fault_detail'] = 'Описание дефектов';
	$_['error_order_id']     = 'Не указан номер заказа';
	$_['error_firstname']    = 'Имя должно содержать от 1 до 32 символов';
	$_['error_lastname']     = 'Фамилия должна содержать от 1 до 32 символов';
	$_['error_email']        = 'E-Mail введен неверно';
	$_['error_telephone']    = 'Телефон должен содержать от 3 до 32 символов';
	$_['error_product']      = 'Название товара должно содержать от 3 до 255 символов';
	$_['error_model']        = 'Модель должна содержать от 3 до 64 символов';
	$_['error_reason']       = 'Необходимо указать причину возврата товара';
	$_['error_agree']        = 'Вы должны прочитать и согласиться с условиями %s!';
}

// account/recurring
if ($page === 'recurring') {
	$_['heading_title']             = 'Регулярные платежи';
	$_['text_recurring']            = 'Регулярные платежи';
	$_['text_order_recurring_id']   = '№ профиля:';
	$_['text_date_added']           = 'Дата добавления:';
	$_['text_status']               = 'Статус:';
	$_['text_payment_method']       = 'Способ оплаты:';
	$_['text_order_id']             = '№ заказа:';
	$_['text_product']              = 'Товар:';
	$_['text_quantity']             = 'Количество:';
	$_['text_description']          = 'Описание';
	$_['text_reference']            = 'Примечание';
	$_['text_transaction']          = 'Операции';
	$_['text_empty']                = 'Платежный профиль не найден';
	$_['text_status_active']        = 'Включен';
	$_['text_status_inactive']      = 'Выключен';
	$_['text_status_cancelled']     = 'Отменен';
	$_['text_status_suspended']     = 'Заморожен';
	$_['text_status_expired']       = 'Истек';
	$_['text_status_pending']       = 'Ожидает';
	$_['column_order_recurring_id'] = '№ профиля';
	$_['column_product']            = 'Товар';
	$_['column_status']             = 'Статус';
	$_['column_date_added']         = 'Дата добавления';
	$_['column_type']               = 'Тип';
	$_['column_amount']             = 'Сумма';
}

// account/affiliate
if ($page === 'affiliate') {
	$_['heading_title']             = 'Партнерская информация';
	$_['text_affiliate']            = 'Партнерский раздел';
	$_['text_my_affiliate']         = 'Партнерский аккаунт';
	$_['text_payment']              = 'Платежная информация';
	$_['text_cheque']               = 'Чек';
	$_['text_paypal']               = 'PayPal';
	$_['text_bank']                 = 'Банковский перевод';
	$_['text_success']              = 'Ваша учетная запись обновлена.';
	$_['text_agree']                = 'Я ознакомлен и согласен с <a href="%s" class="agree"><b>%s</b></a>';
	$_['entry_company']             = 'Компания';
	$_['entry_website']             = 'Веб-сайт';
	$_['entry_tax']                 = 'ИНН';
	$_['entry_payment']             = 'Способ оплаты';
	$_['entry_cheque']              = 'Чек, имя получателя';
	$_['entry_paypal']              = 'PayPal Email';
	$_['entry_bank_name']           = 'Название банка';
	$_['entry_bank_branch_number']  = 'ABA/BSB номер (номер отделения)';
	$_['entry_bank_swift_code']     = 'SWIFT код';
	$_['entry_bank_account_name']   = 'Имя счета';
	$_['entry_bank_account_number'] = 'Номер счета';
	$_['error_agree']               = 'Необходимо принять %s!';
	$_['error_cheque']              = 'Неверно указано имя получателя';
	$_['error_paypal']              = 'Недействительный адрес электронной почты PayPal';
	$_['error_bank_account_name']   = 'Имя счета обязательно';
	$_['error_bank_account_number'] = 'Номер счета обязателен';
	$_['error_custom_field']        = '%s обязательно';
}

// account/tracking
if ($page === 'tracking') {
	$_['heading_title']    = 'Реферальные ссылки';
	$_['text_description'] = 'Чтобы получать вознаграждение с покупок приведенных вами покупателей, добавьте в ссылку реферальный код. Воспользуйтесь генератором ссылок для сайта %s.';
	$_['entry_code']       = 'Ваш реферальный код';
	$_['entry_generator']  = 'Генератор реферальных ссылок';
	$_['entry_link']       = 'Реферальная ссылка';
	$_['help_generator']   = 'Введите название товара, на который нужно создать ссылку';
}

// account/voucher
if ($page === 'voucher') {
	$_['heading_title']    = 'Подарочный сертификат';
	$_['text_voucher']     = 'Подарочный сертификат';
	$_['text_description'] = 'Этот подарочный сертификат будет отправлен получателю на E-Mail после оплаты.';
	$_['text_agree']       = 'Я понимаю и согласен с тем, что подарочные сертификаты возврату не подлежат';
	$_['text_message']     = '<p>Спасибо за покупку подарочного сертификата. После завершения заказа получателю будет отправлено письмо с информацией о его использовании.</p>';
	$_['text_for']         = '%s Подарочный сертификат для %s';
	$_['entry_to_name']    = 'Имя получателя';
	$_['entry_to_email']   = 'E-Mail получателя';
	$_['entry_from_name']  = 'Ваше имя';
	$_['entry_from_email'] = 'Ваш E-Mail';
	$_['entry_theme']      = 'Тема подарочного сертификата';
	$_['entry_message']    = 'Сообщение';
	$_['entry_amount']     = 'Сумма';
	$_['help_message']     = 'Не обязательно';
	$_['help_amount']      = 'Сумма должна быть от %s до %s';
	$_['error_to_name']    = 'Имя получателя должно содержать от 1 до 64 символов';
	$_['error_from_name']  = 'Ваше имя должно содержать от 1 до 64 символов';
	$_['error_email']      = 'E-Mail введен неверно';
	$_['error_theme']      = 'Выберите тему сертификата';
	$_['error_amount']     = 'Сумма должна быть от %s до %s!';
	$_['error_agree']      = 'Вы должны согласиться с тем, что подарочные сертификаты возврату не подлежат';
}
