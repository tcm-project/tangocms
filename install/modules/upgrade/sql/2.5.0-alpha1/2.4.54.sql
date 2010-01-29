ALTER TABLE  {SQL_PREFIX}groups
	ADD  `status` ENUM("active", "locked") NOT NULL DEFAULT 'active' AFTER `name`;
