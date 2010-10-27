DROP TABLE IF EXISTS {PREFIX}mod_tags;
CREATE TABLE {PREFIX}mod_tags (
  id mediumint(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;


DROP TABLE IF EXISTS {PREFIX}mod_tags_xref;
CREATE TABLE {PREFIX}mod_tags_xref (
  id mediumint(6) NOT NULL AUTO_INCREMENT,
  url varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  tag mediumint(6) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY url (url,tag)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;