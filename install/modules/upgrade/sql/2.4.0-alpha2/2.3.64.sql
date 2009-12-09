ALTER TABLE {SQL_PREFIX}mod_media_items
	DROP key_id,
	CHANGE id id MEDIUMINT( 6 ) NOT NULL AUTO_INCREMENT ,
	CHANGE cat_id cat_id SMALLINT( 4 ) NOT NULL DEFAULT '1',
	CHANGE category type ENUM( 'image', 'video', 'audio', 'external' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
	CHANGE thumbname thumbnail VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
	ADD INDEX `clean_name` ( `clean_name` ),
	ADD INDEX `cat_id` ( `cat_id` );

ALTER TABLE {SQL_PREFIX}mod_media_items
	CHANGE `date` `date_old` INT( 11 ) NOT NULL DEFAULT '0',
	ADD `date` DATETIME NOT NULL AFTER `date_old` ;

UPDATE {SQL_PREFIX}mod_media_items SET `date` = FROM_UNIXTIME( `date_old` ) ;

ALTER TABLE {SQL_PREFIX}mod_media_items
	DROP `date_old`,
	ADD INDEX `date` ( `date` );