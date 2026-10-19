-- ---------------------------------------------------------
-- OpenTail install SQL
-- Legal / info pages (English descriptions; extra langs in packs).
-- ---------------------------------------------------------

SET sql_mode = '';

INSERT INTO `oc_information` (`information_id`, `bottom`, `sort_order`, `status`) VALUES
(3, 1, 3, 1),
(4, 1, 1, 1),
(5, 1, 4, 1),
(6, 1, 2, 1);

INSERT INTO `oc_information_description` (`information_id`, `language_id`, `title`, `description`, `meta_title`, `meta_description`, `meta_keyword`, `meta_h1`) VALUES
(4, 1, 'About Us', '&lt;p&gt;About Us&lt;br&gt;&lt;/p&gt;', 'About Us', '', '', ''),
(6, 1, 'Delivery Information', '&lt;p&gt;Delivery Information&lt;br&gt;&lt;/p&gt;', 'Delivery Information', '', '', ''),
(5, 1, 'Terms &amp; Conditions', '&lt;p&gt;Terms &amp;amp; Conditions&lt;br&gt;&lt;/p&gt;', 'Terms &amp; Conditions', '', '', ''),
(3, 1, 'Privacy Policy', '&lt;p&gt;Privacy Policy&lt;br&gt;&lt;/p&gt;', 'Privacy Policy', '', '', '');

INSERT INTO `oc_information_to_store` (`information_id`, `store_id`) VALUES
(3, 0),
(4, 0),
(5, 0),
(6, 0);
