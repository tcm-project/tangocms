DROP TABLE IF EXISTS {SQL_PREFIX}mod_media_cats;
CREATE TABLE {SQL_PREFIX}mod_media_cats (
  id smallint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  clean_name varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  description varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  UNIQUE KEY id (id),
  KEY clean_name (clean_name)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT INTO {SQL_PREFIX}mod_media_cats (name, clean_name) VALUES
('General', 'general');

DROP TABLE IF EXISTS {SQL_PREFIX}mod_media_items;
CREATE TABLE {SQL_PREFIX}mod_media_items (
  id mediumint(6) NOT NULL AUTO_INCREMENT,
  cat_id smallint(4) NOT NULL DEFAULT '1',
  `date` datetime NOT NULL,
  `type` enum('image','video','audio','external') COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  clean_name varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  description text COLLATE utf8_unicode_ci NOT NULL,
  filename varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  thumbnail varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  external_service varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  external_id varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  UNIQUE KEY id (id),
  KEY clean_name (clean_name),
  KEY cat_id (cat_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
