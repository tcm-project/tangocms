DROP TABLE IF EXISTS {SQL_PREFIX}mod_articles;
CREATE TABLE {SQL_PREFIX}mod_articles (
  id mediumint(6) NOT NULL AUTO_INCREMENT,
  cat_id smallint(3) NOT NULL,
  author smallint(32) NOT NULL,
  `date` datetime NOT NULL,
  published int(1) NOT NULL DEFAULT '0',
  title varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  clean_title varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY id (id),
  KEY cat_id (cat_id),
  KEY clean_title (clean_title),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;


DROP TABLE IF EXISTS {SQL_PREFIX}mod_article_cats;
CREATE TABLE {SQL_PREFIX}mod_article_cats (
  id smallint(3) NOT NULL AUTO_INCREMENT,
  title varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'unknown',
  clean_title varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  parent smallint(3) NOT NULL DEFAULT '0',
  description varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  UNIQUE KEY id (id),
  KEY clean_title (clean_title)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

INSERT INTO {SQL_PREFIX}mod_article_cats (id, title, parent, description, clean_title) VALUES
(1, 'General', 0, 'Articles relating to anything and everything.', 'general');

DROP TABLE IF EXISTS {SQL_PREFIX}mod_article_parts;
CREATE TABLE {SQL_PREFIX}mod_article_parts (
  id tinyint(3) NOT NULL AUTO_INCREMENT,
  article_id mediumint(6) NOT NULL,
  title varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `order` tinyint(3) NOT NULL DEFAULT '10',
  body text COLLATE utf8_unicode_ci NOT NULL, 
  UNIQUE KEY id (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;