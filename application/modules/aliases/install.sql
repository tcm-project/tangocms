DROP TABLE IF EXISTS {SQL_PREFIX}mod_aliases;
CREATE TABLE {SQL_PREFIX}mod_aliases (
  id smallint(4) NOT NULL AUTO_INCREMENT,
  alias varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  url varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  redirect tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (alias),
  UNIQUE KEY id (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
