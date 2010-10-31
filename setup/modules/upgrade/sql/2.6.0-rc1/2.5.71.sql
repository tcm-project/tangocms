ALTER TABLE {PREFIX}mod_articles
	CHANGE clean_title identifier VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
	DROP INDEX clean_title,
	ADD UNIQUE identifier ( identifier );

ALTER TABLE {PREFIX}mod_article_cats
	CHANGE clean_title identifier VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
	DROP INDEX clean_title,
	ADD UNIQUE identifier ( identifier );


ALTER TABLE {PREFIX}mod_contact
	CHANGE clean_name identifier VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
	ADD UNIQUE identifier ( identifier );


ALTER TABLE {PREFIX}mod_media_cats
	CHANGE clean_title identifier VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
	DROP INDEX clean_title,
	ADD UNIQUE identifier ( identifier );

ALTER TABLE {PREFIX}mod_media_items
	CHANGE clean_title identifier VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
	DROP INDEX clean_title,
	ADD UNIQUE identifier ( identifier );


ALTER TABLE {PREFIX}mod_page
	CHANGE clean_title identifier VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
	DROP INDEX clean_title,
	ADD UNIQUE identifier ( identifier );