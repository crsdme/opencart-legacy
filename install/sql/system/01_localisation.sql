-- ---------------------------------------------------------
-- OpenTail install SQL
-- Statuses, units, tax, shipping couriers.
-- ---------------------------------------------------------

SET sql_mode = '';

INSERT INTO `oc_length_class` (`length_class_id`, `value`) VALUES
(1, '1.00000000'),
(2, '10.00000000'),
(3, '0.39370000');

INSERT INTO `oc_length_class_description` (`length_class_id`, `language_id`, `title`, `unit`) VALUES
(1, 1, 'Сантиметр', 'см'),
(1, 2, 'Centimeter', 'cm'),
(1, 3, 'Сантиметр', 'см'),
(2, 1, 'Міліметр', 'мм'),
(2, 2, 'Millimeter', 'mm'),
(2, 3, 'Миллиметр', 'мм'),
(3, 1, 'Дюйм', 'in'),
(3, 2, 'Inch', 'in'),
(3, 3, 'Дюйм', 'in');

INSERT INTO `oc_shipping_courier` (`shipping_courier_id`, `shipping_courier_code`, `shipping_courier_name`) VALUES
  (1, 'dhl', 'DHL'),
  (2, 'fedex', 'Fedex'),
  (3, 'ups', 'UPS'),
  (4, 'royal-mail', 'Royal Mail'),
  (5, 'usps', 'United States Postal Service'),
  (6, 'auspost', 'Australia Post');

INSERT INTO `oc_order_status` (`order_status_id`, `language_id`, `name`) VALUES
(2, 1, 'В обробці'),
(3, 1, 'Доставлено'),
(7, 1, 'Скасовано'),
(5, 1, 'Завершено'),
(8, 1, 'Повернення'),
(9, 1, 'Скасування та анулювання'),
(10, 1, 'Помилкове'),
(11, 1, 'Відшкодоване'),
(12, 1, 'Змінене'),
(13, 1, 'Повне повернення'),
(1, 1, 'Очікування'),
(15, 1, 'Оброблене'),
(14, 1, 'Не актуальне'),
(2, 2, 'Processing'),
(8, 2, 'Denied'),
(11, 2, 'Refunded'),
(3, 2, 'Shipped'),
(10, 2, 'Failed'),
(1, 2, 'Pending'),
(9, 2, 'Canceled Reversal'),
(7, 2, 'Canceled'),
(12, 2, 'Reversed'),
(13, 2, 'Chargeback'),
(5, 2, 'Complete'),
(14, 2, 'Expired'),
(16, 1, 'Анульоване'),
(16, 2, 'Voided'),
(15, 2, 'Processed'),
(2, 3, 'В обработке'),
(3, 3, 'Доставлено'),
(7, 3, 'Отменено'),
(5, 3, 'Завершено'),
(8, 3, 'Возврат'),
(9, 3, 'Отмена и аннулирование'),
(10, 3, 'Ошибочный'),
(11, 3, 'Возмещено'),
(12, 3, 'Изменено'),
(13, 3, 'Полный возврат'),
(1, 3, 'Ожидание'),
(15, 3, 'Обработано'),
(14, 3, 'Неактуально'),
(16, 3, 'Аннулировано');

INSERT INTO `oc_return_action` (`return_action_id`, `language_id`, `name`) VALUES
(1, 1, 'Відшкодовано'),
(2, 1, 'Повернення коштів'),
(3, 1, 'Відправлена заміна'),
(1, 2, 'Refunded'),
(3, 2, 'Replacement Sent'),
(2, 2, 'Credit Issued'),
(1, 3, 'Возмещено'),
(2, 3, 'Возврат средств'),
(3, 3, 'Отправлена замена');

INSERT INTO `oc_return_reason` (`return_reason_id`, `language_id`, `name`) VALUES
(1, 1, 'Отримано/доставлено несправним (зламаним)'),
(1, 2, 'Dead On Arrival'),
(1, 3, 'Получено/доставлено неисправным (сломанным)'),
(2, 1, 'Отримано не той (помилковий) товар'),
(2, 2, 'Received Wrong Item'),
(2, 3, 'Получен не тот товар'),
(3, 1, 'Помилкове замовлення'),
(3, 2, 'Order Error'),
(3, 3, 'Ошибочный заказ'),
(4, 1, 'Несправний, будь ласка, вкажіть подробиці'),
(4, 2, 'Faulty, please supply details'),
(4, 3, 'Неисправен, укажите подробности'),
(5, 1, 'Інше (інша причина), будь ласка, вкажіть/докладіть подробиці'),
(5, 2, 'Other, please supply details'),
(5, 3, 'Другое, укажите подробности');

INSERT INTO `oc_return_status` (`return_status_id`, `language_id`, `name`) VALUES
(1, 1, 'В очікуванні'),
(3, 1, 'Виконано'),
(2, 1, 'Очікування товару'),
(1, 2, 'Pending'),
(2, 2, 'Awaiting Products'),
(3, 2, 'Complete'),
(1, 3, 'В ожидании'),
(2, 3, 'Ожидание товара'),
(3, 3, 'Выполнено');

INSERT INTO `oc_stock_status` (`stock_status_id`, `language_id`, `name`) VALUES
(7, 1, 'В наявності'),
(8, 1, 'Під замовлення'),
(5, 1, 'Немає в наявності'),
(6, 1, 'Очікується через 2-3 дні'),
(7, 2, 'In Stock'),
(8, 2, 'Pre-Order'),
(5, 2, 'Out Of Stock'),
(6, 2, '2-3 Days'),
(7, 3, 'В наличии'),
(8, 3, 'Под заказ'),
(5, 3, 'Нет в наличии'),
(6, 3, 'Ожидается через 2-3 дня');

INSERT INTO `oc_tax_class` (`tax_class_id`, `title`, `description`, `date_added`, `date_modified`) VALUES
(9, 'Taxable Goods', 'Taxed goods', '2009-01-06 23:21:53', '2011-09-23 14:07:50'),
(10, 'Downloadable Products', 'Downloadable', '2011-09-21 22:19:39', '2011-09-22 10:27:36');

INSERT INTO `oc_tax_rate` (`tax_rate_id`, `geo_zone_id`, `name`, `rate`, `type`, `date_added`, `date_modified`) VALUES
(86, 3, 'VAT (20%)', '20.0000', 'P', '2011-03-09 21:17:10', '2011-09-22 22:24:29'),
(87, 3, 'Eco Tax (-2.00)', '2.0000', 'F', '2011-09-21 21:49:23', '2011-09-23 00:40:19');

INSERT INTO `oc_tax_rate_to_customer_group` (`tax_rate_id`, `customer_group_id`) VALUES
(86, 1),
(87, 1);

INSERT INTO `oc_tax_rule` (`tax_rule_id`, `tax_class_id`, `tax_rate_id`, `based`, `priority`) VALUES
(121, 10, 86, 'payment', 1),
(120, 10, 87, 'store', 0),
(128, 9, 86, 'shipping', 1),
(127, 9, 87, 'shipping', 2);

INSERT INTO `oc_weight_class` (`weight_class_id`, `value`) VALUES
(1, '1.00000000'),
(2, '1000.00000000'),
(5, '2.20460000'),
(6, '35.27400000');

INSERT INTO `oc_weight_class_description` (`weight_class_id`, `language_id`, `title`, `unit`) VALUES
(1, 1, 'Кілограми', 'кг'),
(1, 2, 'Kilogram', 'kg'),
(1, 3, 'Килограммы', 'кг'),
(2, 1, 'Грами', 'г'),
(2, 2, 'Gram', 'g'),
(2, 3, 'Граммы', 'г'),
(5, 1, 'Фунти', 'lb'),
(5, 2, 'Pound', 'lb'),
(5, 3, 'Фунты', 'lb'),
(6, 1, 'Унції', 'oz'),
(6, 2, 'Ounce', 'oz'),
(6, 3, 'Унции', 'oz');
