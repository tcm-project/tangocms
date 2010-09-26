DROP TABLE IF EXISTS {SQL_PREFIX}users_meta;
CREATE TABLE {SQL_PREFIX}users_meta (
  uid mediumint(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY uid (uid, `name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT INTO {SQL_PREFIX}users_meta (uid, `name`, `value`)
	SELECT id AS uid, "theme" AS `name`, theme AS `value` FROM {SQL_PREFIX}users WHERE theme != "";

INSERT INTO {SQL_PREFIX}users_meta (uid, `name`, `value`)
	SELECT id AS uid, "activate_code" AS `name`, activate_code AS `value` FROM {SQL_PREFIX}users WHERE activate_code != "";

INSERT INTO {SQL_PREFIX}users_meta (uid, `name`, `value`)
	SELECT id AS uid, "reset_code" AS `name`, reset_code AS `value` FROM {SQL_PREFIX}users WHERE reset_code != "";

ALTER TABLE {SQL_PREFIX}users
  DROP theme,
  DROP activate_code,
  DROP reset_code;