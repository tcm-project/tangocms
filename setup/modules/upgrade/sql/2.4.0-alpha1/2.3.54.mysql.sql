DROP TABLE IF EXISTS {PREFIX}sessions;
CREATE TABLE {PREFIX}sessions (
  uid int(11) NOT NULL,
  session_key varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  session_id varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY session_key (session_key)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE {PREFIX}users DROP session_id;