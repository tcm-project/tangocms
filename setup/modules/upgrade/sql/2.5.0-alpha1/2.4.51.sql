ALTER TABLE {SQL_PREFIX}users
	CHANGE  `status`  `status` ENUM(  'active',  'locked' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  'active';