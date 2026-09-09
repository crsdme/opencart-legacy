-- ---------------------------------------------------------
-- OpenTail install SQL
-- Legal / info pages referenced by checkout settings.
-- ---------------------------------------------------------

SET sql_mode = '';

INSERT INTO `oc_information` (`information_id`, `bottom`, `sort_order`, `status`) VALUES
(3, 1, 3, 1),
(4, 1, 1, 1),
(5, 1, 4, 1),
(6, 1, 2, 1);

INSERT INTO `oc_information_description` (`information_id`, `language_id`, `title`, `description`, `meta_title`, `meta_description`, `meta_keyword`, `meta_h1`) VALUES
(4, 1, 'Про магазин', '&lt;p&gt;\r\n	Опис магазину&lt;/p&gt;\r\n', 'Про магазин', '', '', ''),
(5, 1, 'Умови оформлення замовлення', '&lt;p&gt;\r\n	Опис умов оформлення замовлення&lt;/p&gt;\r\n', 'Умови оформлення замовлення', '', '', ''),
(3, 1, 'Угода користувача', '&lt;p&gt;\r\n	Текст угоди користувача&lt;/p&gt;\r\n', 'Угода користувача', '', '', ''),
(6, 1, 'Інформація про доставку', '&lt;p&gt;\r\n	Опис умов доставки&lt;/p&gt;\r\n', 'Інформація про доставку', '', '', ''),
(4, 2, 'About Us', '&lt;p&gt;About Us&lt;br&gt;&lt;/p&gt;', '', '', '', ''),
(6, 2, 'Delivery Information', '&lt;p&gt;Delivery Information&lt;br&gt;&lt;/p&gt;', '', '', '', ''),
(5, 2, 'Terms &amp; Conditions', '&lt;p&gt;Terms &amp;amp; Conditions&lt;br&gt;&lt;/p&gt;', '', '', '', ''),
(3, 2, 'Privacy Policy', '&lt;p&gt;Privacy Policy&lt;br&gt;&lt;/p&gt;', '', '', '', ''),
(4, 3, 'О магазине', '&lt;p&gt;\r\n	Описание магазина&lt;/p&gt;\r\n', 'О магазине', '', '', ''),
(5, 3, 'Условия оформления заказа', '&lt;p&gt;\r\n	Описание условий оформления заказа&lt;/p&gt;\r\n', 'Условия оформления заказа', '', '', ''),
(3, 3, 'Пользовательское соглашение', '&lt;p&gt;\r\n	Текст пользовательского соглашения&lt;/p&gt;\r\n', 'Пользовательское соглашение', '', '', ''),
(6, 3, 'Информация о доставке', '&lt;p&gt;\r\n	Описание условий доставки&lt;/p&gt;\r\n', 'Информация о доставке', '', '', '');

INSERT INTO `oc_information_to_store` (`information_id`, `store_id`) VALUES
(3, 0),
(4, 0),
(5, 0),
(6, 0);
