-- ---------------------------------------------------------
-- OpenTail install SQL
-- Demo banners, module instances, layout placements, demo extensions.
-- ---------------------------------------------------------

SET sql_mode = '';

INSERT INTO `oc_banner` (`banner_id`, `name`, `status`) VALUES
(1, 'Home Page Banner', 1);

INSERT INTO `oc_banner_image` (`banner_image_id`, `banner_id`, `language_id`, `title`, `link`, `image`, `mobile_image`, `sort_order`) VALUES
(1, 1, 1, 'Legacy', '', 'catalog/demo/banners/banner-demo-desktop-1.png', 'catalog/demo/banners/banner-demo-mobile-1.png', 0),
(2, 1, 1, 'Legacy', '', 'catalog/demo/banners/banner-demo-desktop-2.png', 'catalog/demo/banners/banner-demo-mobile-2.png', 0),
(3, 1, 2, 'Legacy', '', 'catalog/demo/banners/banner-demo-desktop-1.png', 'catalog/demo/banners/banner-demo-mobile-1.png', 0),
(4, 1, 2, 'Legacy', '', 'catalog/demo/banners/banner-demo-desktop-2.png', 'catalog/demo/banners/banner-demo-mobile-2.png', 0),
(5, 1, 3, 'Legacy', '', 'catalog/demo/banners/banner-demo-desktop-1.png', 'catalog/demo/banners/banner-demo-mobile-1.png', 0),
(6, 1, 3, 'Legacy', '', 'catalog/demo/banners/banner-demo-desktop-2.png', 'catalog/demo/banners/banner-demo-mobile-2.png', 0);

INSERT INTO `oc_extension` (`extension_id`, `type`, `code`) VALUES
(53, 'module', 'carousel'),
(54, 'module', 'blog_latest'),
(55, 'module', 'latest'),
(56, 'module', 'special');

INSERT INTO `oc_module` (`module_id`, `name`, `code`, `setting`) VALUES
(1, 'Home Page Banner', 'carousel', '{"name":"Home Page Banner","banner_id":"1","width":"2160","height":"730","slides":"1","use_autoplay":"1","use_controls":"0","use_dots":"1","use_loop":"1","width_mobile":"420","height_mobile":"420","slides_mobile":"1","use_autoplay_mobile":"1","use_controls_mobile":"0","use_dots_mobile":"1","use_loop_mobile":"1","gap":"8","breakpoint":"768","autoplay_delay":"3","status":"1"}'),
(2, 'New Products', 'latest', '{"name":"New Products","module_description":{"1":{"title":"\\u041d\\u043e\\u0432\\u0438\\u043d\\u043a\\u0438"},"2":{"title":"New Products"},"3":{"title":"\\u041d\\u043e\\u0432\\u0438\\u043d\\u043a\\u0438"}},"limit":"12","use_autoplay":"1","use_controls":"1","use_loop":"1","gap":"16","use_autoplay_mobile":"1","use_controls_mobile":"0","use_loop_mobile":"1","gap_mobile":"8","breakpoint":"1024","autoplay_delay":"3","status":"1"}'),
(3, 'Last Articles', 'blog_latest', '{"name":"Last Articles","module_description":{"1":{"title":"\\u0411\\u043b\\u043e\\u0433"},"2":{"title":"Blog"},"3":{"title":"\\u0411\\u043b\\u043e\\u0433"}},"limit":"8","use_autoplay":"1","use_controls":"1","use_loop":"1","gap":"16","use_autoplay_mobile":"1","use_controls_mobile":"1","use_loop_mobile":"1","gap_mobile":"8","breakpoint":"1024","autoplay_delay":"3","status":"1"}'),
(4, 'Discount Products', 'special', '{"name":"Discount Products","module_description":{"1":{"title":"\\u0410\\u043a\\u0446\\u0456\\u044f"},"2":{"title":"Discounts"},"3":{"title":"\\u0410\\u043a\\u0446\\u0438\\u044f"}},"limit":"12","use_autoplay":"1","use_controls":"1","use_loop":"1","gap":"16","use_autoplay_mobile":"1","use_controls_mobile":"0","use_loop_mobile":"1","gap_mobile":"8","breakpoint":"1024","autoplay_delay":"3","status":"1"}');

INSERT INTO `oc_layout_module` (`layout_module_id`, `layout_id`, `code`, `position`, `sort_order`) VALUES
(1, 1, 'carousel.1', 'content_top', 0),
(2, 1, 'latest.2', 'content_top', 1),
(3, 1, 'special.4', 'content_top', 2),
(4, 1, 'blog_latest.3', 'content_top', 3);
