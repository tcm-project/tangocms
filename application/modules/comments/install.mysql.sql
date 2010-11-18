DROP TABLE IF EXISTS {PREFIX}mod_comments;
CREATE TABLE {PREFIX}mod_comments (
  id mediumint(6) NOT NULL AUTO_INCREMENT,
  user_id mediumint(6) NOT NULL,
  url varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('accepted','moderation') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'moderation',
  `date` datetime NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  website varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  body text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY id (id),
  INDEX url (url),
  INDEX `status` (`status`),
  INDEX `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
