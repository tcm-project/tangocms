DROP TABLE IF EXISTS {SQL_PREFIX}mod_comments;
CREATE TABLE {SQL_PREFIX}mod_comments (
  id mediumint(6) NOT NULL AUTO_INCREMENT,
  user_id mediumint(6) NOT NULL,
  `status` enum('accepted','moderation') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'moderation',
  url varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  website varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  body text COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY id (id),
  KEY url (url),
  KEY `status` (`status`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;