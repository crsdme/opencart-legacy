-- ---------------------------------------------------------
-- OpenTail install SQL
-- Layout names, routes, and the account column on account layouts.
-- ---------------------------------------------------------

SET sql_mode = '';

INSERT INTO `oc_layout` (`layout_id`, `name`) VALUES
(1, 'Головна'),
(2, 'Товар'),
(3, 'Категорія'),
(4, 'За замовчуванням'),
(5, 'Список виробників'),
(6, 'Обліковий запис'),
(7, 'Оформлення замовлення'),
(8, 'Контакти'),
(9, 'Карта сайту'),
(10, 'Партнерська програма'),
(11, 'Інформація (статті)'),
(12, 'Порівняння товарів'),
(13, 'Пошук'),
(14, 'Блог'),
(15, 'Категорії Блогу'),
(16, 'Статті Блогу'),
(17, 'Сторінка виробника'),
(18, '404'),
(19, '410');

INSERT INTO `oc_layout_module` (`layout_module_id`, `layout_id`, `code`, `position`, `sort_order`) VALUES
(69, 10, 'account', 'column_right', 1),
(68, 6, 'account', 'column_right', 1);

INSERT INTO `oc_layout_route` (`layout_route_id`, `layout_id`, `store_id`, `route`) VALUES
(38, 6, 0, 'account/%'),
(17, 10, 0, 'affiliate/%'),
(44, 3, 0, 'product/category'),
(42, 1, 0, 'common/home'),
(20, 2, 0, 'product/product'),
(24, 11, 0, 'information/information'),
(23, 7, 0, 'checkout/%'),
(31, 8, 0, 'information/contact'),
(32, 9, 0, 'information/sitemap'),
(34, 4, 0, ''),
(45, 5, 0, 'product/manufacturer'),
(52, 12, 0, 'product/compare'),
(53, 13, 0, 'product/search'),
(57, 14, 0, 'blog/latest'),
(58, 15, 0, 'blog/category'),
(56, 16, 0, 'blog/article'),
(63, 17, 0, 'product/manufacturer/info'),
(64, 18, 0, 'error/not_found'),
(65, 19, 0, 'error/gone');
