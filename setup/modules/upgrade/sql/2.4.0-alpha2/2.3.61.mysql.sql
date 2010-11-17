ALTER TABLE {PREFIX}users
	CHANGE joined joined_old INT( 11 ) NOT NULL DEFAULT '0',
	ADD joined DATETIME NOT NULL AFTER joined_old ;

UPDATE {PREFIX}users SET joined = FROM_UNIXTIME( joined_old ) ;

ALTER TABLE {PREFIX}users
	DROP joined_old;


ALTER TABLE {PREFIX}mod_articles
	CHANGE `date` `date_old` INT( 11 ) NOT NULL DEFAULT '0',
	ADD `date` DATETIME NOT NULL AFTER `date_old` ;

UPDATE {PREFIX}mod_articles SET `date` = FROM_UNIXTIME( `date_old` ) ;

ALTER TABLE {PREFIX}mod_articles
	DROP `date_old`,
	ADD INDEX `date` ( `date` );

ALTER TABLE {PREFIX}mod_comments
	ADD INDEX `date` ( `date` );


ALTER TABLE {PREFIX}mod_page
	CHANGE `date` `date_old` INT( 11 ) NOT NULL DEFAULT '0',
	ADD `date` DATETIME NOT NULL AFTER `date_old` ;

UPDATE {PREFIX}mod_page SET `date` = FROM_UNIXTIME( `date_old` ) ;

ALTER TABLE {PREFIX}mod_page
	DROP `date_old`,
	ADD INDEX `date` ( `date` );
	

ALTER TABLE {PREFIX}mod_poll
	ADD INDEX `start_date` ( `start_date` );