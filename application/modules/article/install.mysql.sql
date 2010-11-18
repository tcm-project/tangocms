DROP TABLE IF EXISTS {PREFIX}mod_articles;
CREATE TABLE {PREFIX}mod_articles (
  id mediumint(6) NOT NULL AUTO_INCREMENT,
  cat_id smallint(3) NOT NULL,
  title varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  identifier varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  author smallint(32) NOT NULL,
  `date` datetime NOT NULL,
  published int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY id (id),
  UNIQUE KEY identifier (identifier),
  INDEX cat_id (cat_id),
  INDEX `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS {PREFIX}mod_article_cats;
CREATE TABLE {PREFIX}mod_article_cats (
  id smallint(3) NOT NULL AUTO_INCREMENT,
  parent smallint(3) NOT NULL DEFAULT '0',
  title varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'unknown',
  identifier varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  description varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY id (id),
  UNIQUE KEY identifier (identifier)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

INSERT INTO {PREFIX}mod_article_cats (id, parent, title, identifier, description) VALUES
(1, 0, 'General', 'general', '');

DROP TABLE IF EXISTS {PREFIX}mod_article_parts;
CREATE TABLE {PREFIX}mod_article_parts (
  id tinyint(3) NOT NULL AUTO_INCREMENT,
  article_id mediumint(6) NOT NULL,
  title varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `order` tinyint(3) NOT NULL DEFAULT '10',
  body text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY id (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
