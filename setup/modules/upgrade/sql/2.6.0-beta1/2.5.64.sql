ALTER TABLE  {SQL_PREFIX}mod_poll ADD end_date DATETIME NOT NULL AFTER start_date;

UPDATE {SQL_PREFIX}mod_poll SET end_date = DATE_ADD(start_date, INTERVAL duration WEEK);

ALTER TABLE {SQL_PREFIX}mod_poll DROP duration;