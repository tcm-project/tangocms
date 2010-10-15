UPDATE {SQL_PREFIX}mod_media_items
SET
	thumbnail = CONCAT_WS('/', SUBSTRING_INDEX(filename, '.', 1), thumbnail),
	filename = CONCAT_WS('/', SUBSTRING_INDEX(filename, '.', 1), filename)
WHERE INSTR(filename, '/') = 0;