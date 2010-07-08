ALTER TABLE {SQL_PREFIX}mod_media_items
	ADD outstanding TINYINT( 1 ) NOT NULL DEFAULT  '1' AFTER `id`;

UPDATE {SQL_PREFIX}mod_media_items SET outstanding = 0;