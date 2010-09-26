DROP TABLE IF EXISTS {SQL_PREFIX}mod_menu;
CREATE TABLE {SQL_PREFIX}mod_menu (
  id smallint(4) NOT NULL AUTO_INCREMENT,
  cat_id smallint(4) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  attr_title varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  url varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  heading_id smallint(4) NOT NULL DEFAULT '0',
  `order` smallint(4) NOT NULL DEFAULT '0',
  UNIQUE KEY id (id),
  KEY cat_id (cat_id),
  KEY heading_id (heading_id),
  KEY `order` (`order`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=15 ;

INSERT INTO {SQL_PREFIX}mod_menu (id, cat_id, heading_id, url, name, attr_title, `order`) VALUES
(1, 1, 0, '/', 'View website', '', 1),
(2, 1, 0, 'admin', 'Modules', '', 2),
(3, 1, 0, 'admin/settings', 'Settings', '', 3),
(4, 1, 0, 'admin/theme', 'Theme & style', '', 4),
(5, 1, 0, 'admin/content_layout', 'Content layout', '', 5),
(6, 2, 0, 'admin/menu/config', 'Manage menu', '', 1),
(7, 2, 0, 'admin/article/config', 'Manage articles', '', 2),
(8, 2, 0, 'admin/page/config/add', 'Add page', '', 3),
(9, 3, 0, '/', 'Home', '', 1),
(10, 3, 0, 'article', 'Articles', '', 2),
(11, 3, 0, 'media', 'Media', '', 3),
(12, 3, 0, 'users', 'Users', '', 4),
(13, 3, 0, 'contact', 'Contact', '', 5),
(14, 3, 0, 'admin', 'AdminCP', '', 6);

DROP TABLE IF EXISTS {SQL_PREFIX}mod_menu_cats;
CREATE TABLE {SQL_PREFIX}mod_menu_cats (
  id smallint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  UNIQUE KEY id (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;

INSERT INTO {SQL_PREFIX}mod_menu_cats (id, name) VALUES
(1, 'AdminCP'),
(2, 'Quick links'),
(3, 'Main');