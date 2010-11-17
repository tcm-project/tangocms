ALTER TABLE {PREFIX}users
	ADD INDEX `password` ( `password` );

ALTER TABLE {PREFIX}acl_rules
	CHANGE id id MEDIUMINT( 6 ) NOT NULL AUTO_INCREMENT ,
	CHANGE role_id role_id SMALLINT( 4 ) NOT NULL ,
	CHANGE resource_id resource_id MEDIUMINT( 6 ) NOT NULL;

ALTER TABLE {PREFIX}mod_page
	ADD INDEX `title` ( `title` );