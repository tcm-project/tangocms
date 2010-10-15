ALTER TABLE {SQL_PREFIX}mod_polls
	CHANGE duration duration SMALLINT( 2 ) NOT NULL DEFAULT '0' COMMENT 'number of weeks',
	CHANGE status status ENUM( 'active', 'closed' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
	CHANGE start_time start_date DATETIME NOT NULL;

RENAME TABLE {SQL_PREFIX}mod_polls  TO {SQL_PREFIX}mod_poll;