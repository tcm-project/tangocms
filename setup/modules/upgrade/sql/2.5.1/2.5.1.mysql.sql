ALTER TABLE {PREFIX}users CHANGE  last_pw_change last_pw_change DATETIME NOT NULL;
ALTER TABLE {PREFIX}mod_session CHANGE blocked blocked DATETIME NOT NULL;

UPDATE {PREFIX}users SET
	joined = CONVERT_TZ(joined, @@global.time_zone, '+00:00'),
	last_pw_change = CONVERT_TZ(last_pw_change, @@global.time_zone, '+00:00');

UPDATE {PREFIX}mod_article SET `date` = CONVERT_TZ(`date`, @@global.time_zone, '+00:00');
UPDATE {PREFIX}mod_comments SET `date` = CONVERT_TZ(`date`, @@global.time_zone, '+00:00');
UPDATE {PREFIX}mod_media_items SET `date` = CONVERT_TZ(`date`, @@global.time_zone, '+00:00');
UPDATE {PREFIX}mod_page SET `date` = CONVERT_TZ(`date`, @@global.time_zone, '+00:00');
UPDATE {PREFIX}mod_poll SET `start_date` = CONVERT_TZ(`start_date`, @@global.time_zone, '+00:00');