-- ---------------------------------------------------------
-- OpenTail language pack: Russian (language_id = 3)
-- Applied when "ru" is selected during install.
-- ---------------------------------------------------------

SET sql_mode = '';

INSERT INTO `oc_language` (`language_id`, `name`, `code`, `locale`, `hreflang`, `image`, `directory`, `sort_order`, `status`) VALUES
(3, 'Русский', 'ru', 'ru_UA.UTF-8,ru_UA,ru-ua,ru,russian', 'ru-UA', 'ru.png', 'ru', 3, 1);

INSERT INTO `oc_length_class_description` (`length_class_id`, `language_id`, `title`, `unit`) VALUES
(1, 3, 'Сантиметр', 'см'),
(2, 3, 'Миллиметр', 'мм'),
(3, 3, 'Дюйм', 'in');

INSERT INTO `oc_order_status` (`order_status_id`, `language_id`, `name`) VALUES
(1, 3, 'Ожидание'),
(2, 3, 'В обработке'),
(3, 3, 'Доставлено'),
(5, 3, 'Завершено'),
(7, 3, 'Отменено'),
(8, 3, 'Возврат'),
(9, 3, 'Отмена и аннулирование'),
(10, 3, 'Ошибочный'),
(11, 3, 'Возмещено'),
(12, 3, 'Изменено'),
(13, 3, 'Полный возврат'),
(14, 3, 'Неактуально'),
(15, 3, 'Обработано'),
(16, 3, 'Аннулировано');

INSERT INTO `oc_return_action` (`return_action_id`, `language_id`, `name`) VALUES
(1, 3, 'Возмещено'),
(2, 3, 'Возврат средств'),
(3, 3, 'Отправлена замена');

INSERT INTO `oc_return_reason` (`return_reason_id`, `language_id`, `name`) VALUES
(1, 3, 'Получено/доставлено неисправным (сломанным)'),
(2, 3, 'Получен не тот товар'),
(3, 3, 'Ошибочный заказ'),
(4, 3, 'Неисправен, укажите подробности'),
(5, 3, 'Другое, укажите подробности');

INSERT INTO `oc_return_status` (`return_status_id`, `language_id`, `name`) VALUES
(1, 3, 'В ожидании'),
(2, 3, 'Ожидание товара'),
(3, 3, 'Выполнено');

INSERT INTO `oc_stock_status` (`stock_status_id`, `language_id`, `name`) VALUES
(5, 3, 'Нет в наличии'),
(6, 3, 'Ожидается через 2-3 дня'),
(7, 3, 'В наличии'),
(8, 3, 'Под заказ');

INSERT INTO `oc_weight_class_description` (`weight_class_id`, `language_id`, `title`, `unit`) VALUES
(1, 3, 'Килограммы', 'кг'),
(2, 3, 'Граммы', 'г'),
(5, 3, 'Фунты', 'lb'),
(6, 3, 'Унции', 'oz');

INSERT INTO `oc_customer_group_description` (`customer_group_id`, `language_id`, `name`, `description`) VALUES
(1, 3, 'Default', 'Стандартная группа покупателей. Бонусные баллы не начисляются, скидки не предусмотрены.');

INSERT INTO `oc_voucher_theme_description` (`voucher_theme_id`, `language_id`, `name`) VALUES
(6, 3, 'Новый год'),
(7, 3, 'День рождения'),
(8, 3, 'Подарок');

INSERT INTO `oc_information_description` (`information_id`, `language_id`, `title`, `description`, `meta_title`, `meta_description`, `meta_keyword`, `meta_h1`) VALUES
(4, 3, 'О магазине', '&lt;p&gt;\r\n	Описание магазина&lt;/p&gt;\r\n', 'О магазине', '', '', ''),
(5, 3, 'Условия оформления заказа', '&lt;p&gt;\r\n	Описание условий оформления заказа&lt;/p&gt;\r\n', 'Условия оформления заказа', '', '', ''),
(3, 3, 'Пользовательское соглашение', '&lt;p&gt;\r\n	Текст пользовательского соглашения&lt;/p&gt;\r\n', 'Пользовательское соглашение', '', '', ''),
(6, 3, 'Информация о доставке', '&lt;p&gt;\r\n	Описание условий доставки&lt;/p&gt;\r\n', 'Информация о доставке', '', '', '');

INSERT INTO `oc_seo_url` (`seo_url_id`, `store_id`, `language_id`, `query`, `keyword`) VALUES
(1300, 0, 3, 'common/home', 'ru'),
(1301, 0, 3, 'account/voucher', 'sertifikaty'),
(1302, 0, 3, 'account/wishlist', 'izbrannoe'),
(1303, 0, 3, 'account/account', 'lichnyj-kabinet'),
(1304, 0, 3, 'checkout/cart', 'korzina'),
(1305, 0, 3, 'checkout/checkout', 'oformlenie'),
(1306, 0, 3, 'account/login', 'vhod'),
(1307, 0, 3, 'account/logout', 'vyhod'),
(1308, 0, 3, 'account/order', 'istoriya-zakazov'),
(1309, 0, 3, 'account/newsletter', 'rassylka'),
(1310, 0, 3, 'product/special', 'akcii'),
(1311, 0, 3, 'affiliate/account', 'partnerskaya-programma'),
(1312, 0, 3, 'checkout/voucher', 'podarochnye-sertifikaty'),
(1313, 0, 3, 'product/manufacturer', 'proizvoditeli'),
(1314, 0, 3, 'information/contact', 'kontakt'),
(1315, 0, 3, 'account/return/insert', 'zapros-na-vozvrat'),
(1316, 0, 3, 'information/sitemap', 'karta-sajta'),
(1317, 0, 3, 'account/forgotten', 'zabyl-parol'),
(1318, 0, 3, 'account/download', 'zagruzki'),
(1319, 0, 3, 'account/return', 'vozvraty'),
(1320, 0, 3, 'account/transaction', 'tranzakcii'),
(1321, 0, 3, 'account/register', 'registraciya'),
(1322, 0, 3, 'product/compare', 'sravnenie'),
(1323, 0, 3, 'product/search', 'poisk'),
(1324, 0, 3, 'account/edit', 'redaktirovat-akkaunt'),
(1325, 0, 3, 'account/password', 'smenit-parol'),
(1326, 0, 3, 'account/address', 'adresnaya-kniga'),
(1327, 0, 3, 'account/reward', 'bonusnye-bally'),
(1328, 0, 3, 'affiliate/edit', 'redaktirovat-partnera'),
(1329, 0, 3, 'affiliate/password', 'partnerskij-parol'),
(1330, 0, 3, 'affiliate/payment', 'partnerskie-vyplaty'),
(1331, 0, 3, 'affiliate/tracking', 'partnerskoe-otslezhivanie'),
(1332, 0, 3, 'affiliate/transaction', 'partnerskie-tranzakcii'),
(1333, 0, 3, 'affiliate/logout', 'partnerskij-vyhod'),
(1334, 0, 3, 'affiliate/forgotten', 'partnerskij-zabyl-parol'),
(1335, 0, 3, 'affiliate/register', 'partnerskaya-registraciya'),
(1336, 0, 3, 'affiliate/login', 'partnerskij-vhod'),
(1337, 0, 3, 'account/return/add', 'dobavit-vozvrat'),
(1338, 0, 3, 'information_id=4', 'o-nas'),
(1339, 0, 3, 'information_id=6', 'dostavka-i-oplata'),
(1340, 0, 3, 'information_id=3', 'konfidencialnost'),
(1341, 0, 3, 'information_id=5', 'usloviya');
