DROP TABLE IF EXISTS {PREFIX}mod_media_cats;
CREATE TABLE {PREFIX}mod_media_cats (
  id smallint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  identifier varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  description varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY id (id),
  UNIQUE KEY identifier (identifier)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT INTO {PREFIX}mod_media_cats (id, name, identifier, description) VALUES
(1, 'General', 'general', '');

DROP TABLE IF EXISTS {PREFIX}mod_media_items;
CREATE TABLE {PREFIX}mod_media_items (
  id mediumint(6) NOT NULL AUTO_INCREMENT,
  cat_id smallint(4) NOT NULL DEFAULT '1',
  `outstanding` tinyint(1) NOT NULL DEFAULT '1',
  `date` datetime NOT NULL,
  `type` enum('image','video','audio','external') COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  identifier varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  filename varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  thumbnail varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  external_service varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  external_id varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  description text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY id (id),
  UNIQUE KEY identifier (identifier),
  INDEX cat_id (cat_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
