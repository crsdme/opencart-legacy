-- ---------------------------------------------------------
-- OpenTail language pack: Ukrainian (language_id = 2)
-- Applied when "ua" is selected during install.
-- ---------------------------------------------------------

SET sql_mode = '';

INSERT INTO `oc_language` (`language_id`, `name`, `code`, `locale`, `hreflang`, `image`, `directory`, `sort_order`, `status`) VALUES
(2, 'Українська', 'ua', 'uk_UA.UTF-8,uk_UA,uk-ua,uk,ukrainian', 'uk-UA', 'uk-ua.png', 'ua', 2, 1);

INSERT INTO `oc_length_class_description` (`length_class_id`, `language_id`, `title`, `unit`) VALUES
(1, 2, 'Сантиметр', 'см'),
(2, 2, 'Міліметр', 'мм'),
(3, 2, 'Дюйм', 'in');

INSERT INTO `oc_order_status` (`order_status_id`, `language_id`, `name`) VALUES
(1, 2, 'Очікування'),
(2, 2, 'В обробці'),
(3, 2, 'Доставлено'),
(5, 2, 'Завершено'),
(7, 2, 'Скасовано'),
(8, 2, 'Повернення'),
(9, 2, 'Скасування та анулювання'),
(10, 2, 'Помилкове'),
(11, 2, 'Відшкодоване'),
(12, 2, 'Змінене'),
(13, 2, 'Повне повернення'),
(14, 2, 'Не актуальне'),
(15, 2, 'Оброблене'),
(16, 2, 'Анульоване');

INSERT INTO `oc_return_action` (`return_action_id`, `language_id`, `name`) VALUES
(1, 2, 'Відшкодовано'),
(2, 2, 'Повернення коштів'),
(3, 2, 'Відправлена заміна');

INSERT INTO `oc_return_reason` (`return_reason_id`, `language_id`, `name`) VALUES
(1, 2, 'Отримано/доставлено несправним (зламаним)'),
(2, 2, 'Отримано не той (помилковий) товар'),
(3, 2, 'Помилкове замовлення'),
(4, 2, 'Несправний, будь ласка, вкажіть подробиці'),
(5, 2, 'Інше (інша причина), будь ласка, вкажіть/докладіть подробиці');

INSERT INTO `oc_return_status` (`return_status_id`, `language_id`, `name`) VALUES
(1, 2, 'В очікуванні'),
(2, 2, 'Очікування товару'),
(3, 2, 'Виконано');

INSERT INTO `oc_stock_status` (`stock_status_id`, `language_id`, `name`) VALUES
(5, 2, 'Немає в наявності'),
(6, 2, 'Очікується через 2-3 дні'),
(7, 2, 'В наявності'),
(8, 2, 'Під замовлення');

INSERT INTO `oc_weight_class_description` (`weight_class_id`, `language_id`, `title`, `unit`) VALUES
(1, 2, 'Кілограми', 'кг'),
(2, 2, 'Грами', 'г'),
(5, 2, 'Фунти', 'lb'),
(6, 2, 'Унції', 'oz');

INSERT INTO `oc_customer_group_description` (`customer_group_id`, `language_id`, `name`, `description`) VALUES
(1, 2, 'Default', 'Стандартна група покупців. Бонусні бали не нараховуються, знижки не передбачені.');

INSERT INTO `oc_voucher_theme_description` (`voucher_theme_id`, `language_id`, `name`) VALUES
(6, 2, 'Новий Рік'),
(7, 2, 'День народження'),
(8, 2, 'Подарунок');

INSERT INTO `oc_information_description` (`information_id`, `language_id`, `title`, `description`, `meta_title`, `meta_description`, `meta_keyword`, `meta_h1`) VALUES
(4, 2, 'Про магазин', '&lt;p&gt;\r\n	Опис магазину&lt;/p&gt;\r\n', 'Про магазин', '', '', ''),
(5, 2, 'Умови оформлення замовлення', '&lt;p&gt;\r\n	Опис умов оформлення замовлення&lt;/p&gt;\r\n', 'Умови оформлення замовлення', '', '', ''),
(3, 2, 'Угода користувача', '&lt;p&gt;\r\n	Текст угоди користувача&lt;/p&gt;\r\n', 'Угода користувача', '', '', ''),
(6, 2, 'Інформація про доставку', '&lt;p&gt;\r\n	Опис умов доставки&lt;/p&gt;\r\n', 'Інформація про доставку', '', '', '');

INSERT INTO `oc_seo_url` (`seo_url_id`, `store_id`, `language_id`, `query`, `keyword`) VALUES
(1201, 0, 2, 'common/home', 'ua'),
(1202, 0, 2, 'account/voucher', 'sertyfikaty'),
(1203, 0, 2, 'account/wishlist', 'spysok-bazhan'),
(1204, 0, 2, 'account/account', 'osobystyj-kabinet'),
(1205, 0, 2, 'checkout/cart', 'koshyk'),
(1206, 0, 2, 'checkout/checkout', 'oformlennya'),
(1207, 0, 2, 'account/login', 'vkhid'),
(1208, 0, 2, 'account/logout', 'vykhid'),
(1209, 0, 2, 'account/order', 'istoriya-zamovlen'),
(1210, 0, 2, 'account/newsletter', 'rozsylka'),
(1211, 0, 2, 'product/special', 'aktsii'),
(1212, 0, 2, 'affiliate/account', 'partnerska-prohrama'),
(1213, 0, 2, 'checkout/voucher', 'podarunkovi-sertyfikaty'),
(1214, 0, 2, 'product/manufacturer', 'vyrobnyky'),
(1215, 0, 2, 'information/contact', 'kontakty'),
(1216, 0, 2, 'account/return/insert', 'zapyt-na-povernennya'),
(1217, 0, 2, 'information/sitemap', 'karta-sajtu'),
(1218, 0, 2, 'account/forgotten', 'zabuly-parol'),
(1219, 0, 2, 'account/download', 'zavantazhennya'),
(1220, 0, 2, 'account/return', 'povernennya'),
(1221, 0, 2, 'account/transaction', 'transaktsii'),
(1222, 0, 2, 'account/register', 'reiestratsiia'),
(1223, 0, 2, 'product/compare', 'porivnyannya'),
(1224, 0, 2, 'product/search', 'poshuk'),
(1225, 0, 2, 'account/edit', 'redahuvaty-oblikovyy-zapys'),
(1226, 0, 2, 'account/password', 'zminyty-parol'),
(1227, 0, 2, 'account/address', 'adresna-knyha'),
(1228, 0, 2, 'account/reward', 'bonusni-baly'),
(1229, 0, 2, 'affiliate/edit', 'redahuvaty-partnerskyj'),
(1230, 0, 2, 'affiliate/password', 'partnerskyj-parol'),
(1231, 0, 2, 'affiliate/payment', 'partnerski-vyplaty'),
(1232, 0, 2, 'affiliate/tracking', 'partnerske-vidstezhennia'),
(1233, 0, 2, 'affiliate/transaction', 'partnerski-transaktsii'),
(1234, 0, 2, 'affiliate/logout', 'partnerskyj-vykhid'),
(1235, 0, 2, 'affiliate/forgotten', 'partnerskyj-zabuly-parol'),
(1236, 0, 2, 'affiliate/register', 'partnerska-reiestratsiia'),
(1237, 0, 2, 'affiliate/login', 'partnerskyj-vkhid'),
(1238, 0, 2, 'account/return/add', 'dodaty-povernennya'),
(1239, 0, 2, 'information_id=4', 'pro-nas'),
(1240, 0, 2, 'information_id=6', 'dostavka'),
(1241, 0, 2, 'information_id=3', 'konfidentsijnist'),
(1242, 0, 2, 'information_id=5', 'umovy');
