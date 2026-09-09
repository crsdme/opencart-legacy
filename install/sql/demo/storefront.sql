-- ---------------------------------------------------------
-- OpenTail install SQL
-- Demo banners, module instances, layout placements, demo extensions.
-- ---------------------------------------------------------

SET sql_mode = '';

INSERT INTO `oc_banner` (`banner_id`, `name`, `status`) VALUES
(6, 'HP Products', 1),
(7, 'Home Page Slideshow', 1),
(8, 'Manufacturers', 1);

INSERT INTO `oc_banner_image` (`banner_image_id`, `banner_id`, `language_id`, `title`, `link`, `image`, `sort_order`) VALUES
(100, 7, 1, 'MacBookAir', '', 'catalog/demo/banners/MacBookAir.jpg', 0),
(103, 6, 1, 'HP Banner', 'index.php?route=product/manufacturer/info&amp;manufacturer_id=7', 'catalog/demo/compaq_presario.jpg', 0),
(113, 8, 1, 'Disney', '', 'catalog/demo/manufacturer/disney.png', 0),
(112, 8, 1, 'Dell', '', 'catalog/demo/manufacturer/dell.png', 0),
(111, 8, 1, 'Harley Davidson', '', 'catalog/demo/manufacturer/harley.png', 0),
(110, 8, 1, 'Canon', '', 'catalog/demo/manufacturer/canon.png', 0),
(109, 8, 1, 'Burger King', '', 'catalog/demo/manufacturer/burgerking.png', 0),
(108, 8, 1, 'Coca Cola', '', 'catalog/demo/manufacturer/cocacola.png', 0),
(107, 8, 1, 'Sony', '', 'catalog/demo/manufacturer/sony.png', 0),
(99, 7, 1, 'iPhone 6', 'index.php?route=product/product&amp;path=57&amp;product_id=49', 'catalog/demo/banners/iPhone6.jpg', 0),
(106, 8, 1, 'RedBull', '', 'catalog/demo/manufacturer/redbull.png', 0),
(105, 8, 1, 'NFL', '', 'catalog/demo/manufacturer/nfl.png', 0),
(101, 7, 2, 'iPhone 6', 'index.php?route=product/product&amp;path=57&amp;product_id=49', 'catalog/demo/banners/iPhone6.jpg', 0),
(102, 7, 2, 'MacBookAir', '', 'catalog/demo/banners/MacBookAir.jpg', 0),
(104, 6, 2, 'HP Banner', 'index.php?route=product/manufacturer/info&amp;manufacturer_id=7', 'catalog/demo/compaq_presario.jpg', 0),
(114, 8, 1, 'Starbucks', '', 'catalog/demo/manufacturer/starbucks.png', 0),
(115, 8, 1, 'Nintendo', '', 'catalog/demo/manufacturer/nintendo.png', 0),
(116, 8, 2, 'NFL', '', 'catalog/demo/manufacturer/nfl.png', 0),
(117, 8, 2, 'RedBull', '', 'catalog/demo/manufacturer/redbull.png', 0),
(118, 8, 2, 'Sony', '', 'catalog/demo/manufacturer/sony.png', 0),
(119, 8, 2, 'Coca Cola', '', 'catalog/demo/manufacturer/cocacola.png', 0),
(120, 8, 2, 'Burger King', '', 'catalog/demo/manufacturer/burgerking.png', 0),
(121, 8, 2, 'Canon', '', 'catalog/demo/manufacturer/canon.png', 0),
(122, 8, 2, 'Harley Davidson', '', 'catalog/demo/manufacturer/harley.png', 0),
(123, 8, 2, 'Dell', '', 'catalog/demo/manufacturer/dell.png', 0),
(124, 8, 2, 'Disney', '', 'catalog/demo/manufacturer/disney.png', 0),
(125, 8, 2, 'Starbucks', '', 'catalog/demo/manufacturer/starbucks.png', 0),
(126, 8, 2, 'Nintendo', '', 'catalog/demo/manufacturer/nintendo.png', 0);

INSERT INTO `oc_extension` (`extension_id`, `type`, `code`) VALUES
(7, 'module', 'carousel'),
(18, 'module', 'featured'),
(43, 'module', 'blog_latest'),
(44, 'module', 'blog_featured');

INSERT INTO `oc_layout_module` (`layout_module_id`, `layout_id`, `code`, `position`, `sort_order`) VALUES
(2, 4, '0', 'content_top', 0),
(3, 4, '0', 'content_top', 1),
(20, 5, '0', 'column_left', 2),
(67, 1, 'carousel.29', 'content_top', 3),
(66, 1, 'carousel.27', 'content_top', 1),
(65, 1, 'featured.28', 'content_top', 2),
(75, 14, 'blog_featured.33', 'column_left', 1),
(76, 14, 'blog_latest.32', 'content_bottom', 0),
(78, 15, 'blog_latest.32', 'column_left', 1),
(79, 15, 'blog_featured.33', 'content_bottom', 0),
(81, 16, 'blog_featured.33', 'column_left', 1);

INSERT INTO `oc_module` (`module_id`, `name`, `code`, `setting`) VALUES
(29, 'Home Page', 'carousel', '{"name":"Home Page","banner_id":"8","width":"130","height":"100","width_mobile":"130","height_mobile":"100","slides":"5","slides_mobile":"5","use_autoplay":"1","use_controls":"0","use_dots":"0","use_loop":"1","status":"1"}'),
(28, 'Home Page', 'featured', '{"name":"Home Page","product":["43","40","42","30"],"limit":"4","width":"200","height":"200","status":"1"}'),
(27, 'Home Page', 'carousel', '{"name":"Home Page","banner_id":"7","width":"1140","height":"380","width_mobile":"375","height_mobile":"210","slides":"1","slides_mobile":"1","use_autoplay":"1","use_controls":"1","use_dots":"1","use_loop":"1","status":"1"}'),
(32, 'Останні статті', 'blog_latest', '{"name":"\\u041f\\u043e\\u0441\\u043b\\u0435\\u0434\\u043d\\u0438\\u0435 \\u0441\\u0442\\u0430\\u0442\\u044c\\u0438","limit":"4","width":"200","height":"200","status":"1"}'),
(33, 'Рекомендовані статті', 'blog_featured', '{"name":"\\u0420\\u0435\\u043a\\u043e\\u043c\\u0435\\u043d\\u0434\\u0443\\u0435\\u043c\\u044b\\u0435 \\u0441\\u0442\\u0430\\u0442\\u044c\\u0438","article_name":"","article":["120","123","125","124"],"limit":"4","width":"200","height":"200","status":"1"}');
