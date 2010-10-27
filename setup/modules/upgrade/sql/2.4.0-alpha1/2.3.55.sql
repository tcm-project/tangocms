ALTER TABLE {PREFIX}acl_resources
	CHANGE id id MEDIUMINT( 6 ) NOT NULL AUTO_INCREMENT ,
	CHANGE name name VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

ALTER TABLE {PREFIX}acl_roles CHANGE id id SMALLINT( 4 ) NOT NULL AUTO_INCREMENT ,
	ADD INDEX parent_id ( parent_id ),
	CHANGE name name VARCHAR( 48 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
	CHANGE parent_id parent_id SMALLINT( 4 ) NOT NULL;

ALTER TABLE {PREFIX}groups
	CHANGE id id SMALLINT( 4 ) NOT NULL AUTO_INCREMENT ,
	CHANGE name name VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
	CHANGE role_id role_id SMALLINT( 4 ) NOT NULL;

ALTER TABLE {PREFIX}modules
	ADD INDEX `order` ( `order` ),
	CHANGE `order` `order` SMALLINT( 3 ) NOT NULL DEFAULT '0';

ALTER TABLE {PREFIX}sessions CHANGE uid uid MEDIUMINT( 6 ) NOT NULL ,
	CHANGE session_key session_key CHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
	CHANGE session_id session_id VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

ALTER TABLE {PREFIX}users
	ADD INDEX `group` ( `group` ),
	ADD status ENUM( 'active', 'locked', 'closed' ) NOT NULL DEFAULT 'active' AFTER id,
	CHANGE id id MEDIUMINT( 6 ) NOT NULL AUTO_INCREMENT,
	CHANGE username username VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
	CHANGE password password CHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
	CHANGE reset_code reset_code CHAR( 48 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
	CHANGE activate_code activate_code CHAR( 48 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

	

ALTER TABLE {PREFIX}mod_aliases
	CHANGE id id SMALLINT( 4 ) NOT NULL AUTO_INCREMENT;

ALTER TABLE {PREFIX}mod_articles
	ADD INDEX cat_id ( cat_id ),
	ADD INDEX clean_title ( clean_title ),
	CHANGE id id MEDIUMINT( 6 ) NOT NULL AUTO_INCREMENT ,
	CHANGE cat_id cat_id SMALLINT( 3 ) NOT NULL ,
	CHANGE author author SMALLINT( 32 ) NOT NULL;

ALTER TABLE {PREFIX}mod_article_cats
	ADD INDEX clean_title ( clean_title ),
	CHANGE id id SMALLINT( 3 ) NOT NULL AUTO_INCREMENT ,
	CHANGE parent parent SMALLINT( 3 ) NOT NULL DEFAULT '0';


ALTER TABLE {PREFIX}mod_article_parts
	CHANGE id id TINYINT( 3 ) NOT NULL AUTO_INCREMENT ,
	CHANGE article_id article_id MEDIUMINT( 6 ) NOT NULL ,
	CHANGE `order` `order` TINYINT( 3 ) NOT NULL DEFAULT '10';

ALTER TABLE {PREFIX}mod_comments
	ADD INDEX url ( url ),
	ADD INDEX status ( status ),
	CHANGE id id MEDIUMINT( 6 ) NOT NULL AUTO_INCREMENT ,
	CHANGE user_id user_id MEDIUMINT( 6 ) NOT NULL ;

ALTER TABLE {PREFIX}mod_contact
	CHANGE id id SMALLINT( 4 ) NOT NULL AUTO_INCREMENT;

ALTER TABLE {PREFIX}mod_contact_fields
	ADD INDEX form_id ( form_id ),
	ADD INDEX `order` ( `order` ),
	CHANGE id id SMALLINT( 4 ) NOT NULL AUTO_INCREMENT ,
	CHANGE form_id form_id SMALLINT( 4 ) NOT NULL ,
	CHANGE `order` `order` SMALLINT( 4 ) NOT NULL DEFAULT '2';

ALTER TABLE {PREFIX}mod_media_cats
	ADD INDEX clean_name ( clean_name ),
	CHANGE id id SMALLINT( 4 ) NOT NULL AUTO_INCREMENT;

ALTER TABLE {PREFIX}mod_menu
	ADD INDEX cat_id ( cat_id ),
	ADD INDEX heading_id ( heading_id ),
	ADD INDEX `order` ( `order` ),
	CHANGE id id SMALLINT( 4 ) NOT NULL AUTO_INCREMENT ,
	CHANGE cat_id cat_id SMALLINT( 4 ) NOT NULL ,
	CHANGE heading_id heading_id SMALLINT( 4 ) NOT NULL DEFAULT '0',
	CHANGE `order` `order` SMALLINT( 4 ) NOT NULL DEFAULT '0';

ALTER TABLE {PREFIX}mod_menu_cats
	CHANGE id id SMALLINT( 4 ) NOT NULL AUTO_INCREMENT ;

ALTER TABLE {PREFIX}mod_page
	ADD INDEX clean_title ( clean_title ),
	CHANGE id id SMALLINT( 5 ) NOT NULL AUTO_INCREMENT ,
	CHANGE author author MEDIUMINT( 6 ) NOT NULL ,
	CHANGE `date` `date` INT( 10 ) NOT NULL DEFAULT '0',
	CHANGE parent parent SMALLINT( 5 ) NOT NULL DEFAULT '0',
	CHANGE `order` `order` SMALLINT( 5 ) NOT NULL DEFAULT '0';

ALTER TABLE {PREFIX}mod_poll
	CHANGE id id SMALLINT( 4 ) NOT NULL AUTO_INCREMENT ;

ALTER TABLE {PREFIX}mod_poll_options
	ADD INDEX poll_id ( poll_id ),
	CHANGE id id SMALLINT( 5 ) NOT NULL AUTO_INCREMENT ,
	CHANGE poll_id poll_id SMALLINT( 4 ) NOT NULL ;

ALTER TABLE {PREFIX}mod_poll_votes
	ADD INDEX option_id ( option_id ),
	CHANGE id id MEDIUMINT( 6 ) NOT NULL AUTO_INCREMENT ,
	CHANGE option_id option_id SMALLINT( 5 ) NOT NULL ,
	CHANGE ip ip INT( 10 ) NOT NULL ,
	CHANGE uid uid MEDIUMINT( 6 ) NOT NULL DEFAULT '0';

ALTER TABLE {PREFIX}mod_session
	CHANGE ip ip INT( 10 ) NOT NULL ,
	CHANGE attempts attempts TINYINT( 3 ) NOT NULL;

ALTER TABLE {PREFIX}mod_shareable
	CHANGE id id TINYINT( 3 ) NOT NULL AUTO_INCREMENT ,
	CHANGE `order` `order` TINYINT( 3 ) NOT NULL DEFAULT '0';

ALTER TABLE {PREFIX}mod_tags
	CHANGE id id MEDIUMINT( 6 ) NOT NULL AUTO_INCREMENT;

ALTER TABLE {PREFIX}mod_tags_xref
	CHANGE id id MEDIUMINT( 6 ) NOT NULL AUTO_INCREMENT ,
	CHANGE tag tag MEDIUMINT( 6 ) NOT NULL ;

OPTIMIZE TABLE
	{PREFIX}acl_resources , {PREFIX}acl_roles , {PREFIX}acl_rules , {PREFIX}config , {PREFIX}groups ,
	{PREFIX}layouts , {PREFIX}modules , {PREFIX}mod_aliases , {PREFIX}mod_articles , {PREFIX}mod_article_cats ,
	{PREFIX}mod_article_parts , {PREFIX}mod_comments , {PREFIX}mod_contact , {PREFIX}mod_contact_fields ,
	{PREFIX}mod_media_cats , {PREFIX}mod_media_items , {PREFIX}mod_menu , {PREFIX}mod_menu_cats ,
	{PREFIX}mod_page , {PREFIX}mod_poll , {PREFIX}mod_poll_options , {PREFIX}mod_poll_votes , {PREFIX}mod_session ,
	{PREFIX}mod_shareable , {PREFIX}mod_tags , {PREFIX}mod_tags_xref , {PREFIX}sessions , {PREFIX}users;