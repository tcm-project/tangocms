SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

DROP TABLE IF EXISTS {SQL_PREFIX}acl_resources;
CREATE TABLE {SQL_PREFIX}acl_resources (
  id mediumint(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`name`),
  UNIQUE KEY id (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS {SQL_PREFIX}acl_roles;
CREATE TABLE {SQL_PREFIX}acl_roles (
  id smallint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(48) COLLATE utf8_unicode_ci NOT NULL,
  parent_id smallint(4) NOT NULL,
  PRIMARY KEY (`name`),
  UNIQUE KEY id (id),
  KEY parent_id (parent_id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=6 ;

INSERT INTO {SQL_PREFIX}acl_roles (id, name, parent_id) VALUES
(1, 'group_guest', 0),
(2, 'group_member', 1),
(3, 'group_admin', 2),
(4, 'group_root', 0);

DROP TABLE IF EXISTS {SQL_PREFIX}acl_rules;
CREATE TABLE {SQL_PREFIX}acl_rules (
  id mediumint(6) NOT NULL AUTO_INCREMENT,
  role_id smallint(4) NOT NULL,
  resource_id mediumint(6) NOT NULL,
  access tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY id (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS {SQL_PREFIX}config;
CREATE TABLE {SQL_PREFIX}config (
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO {SQL_PREFIX}config (name, value) VALUES
('antispam/backend', 'captcha'),
('antispam/recaptcha/public', ''),
('antispam/recaptcha/private', ''),
('config/slogan', 'Powered by TangoCMS'),
('config/title_format', '[PAGE] | [SITE_TITLE]'),
('config/title', 'websiteTitle'),
('date/format', 'D j M, H:i'),
('date/use_relative', 'true'),
('date/timezone', ''),
('editor/default', 'Html'),
('editor/parse_php', '0'),
('mail/incoming', 'mailIncoming'),
('mail/outgoing', 'mailOutgoing'),
('mail/signature', 'Regards,'),
('mail/type', 'mail'),
('mail/smtp_host', 'localhost'),
('mail/smtp_password', ''),
('mail/smtp_port', '25'),
('mail/smtp_username', ''),
('mail/smtp_encryption', 'false'),
('mail/subject_prefix', 'true'),
('meta/description', ''),
('meta/keywords', ''),
('sql/host', 'zula-framework'),
('sql/pass', 'zula-framework'),
('sql/user', 'zula-framework'),
('theme/allow_user_override', '0'),
('theme/admin_default', 'innocent'),
('theme/main_default', 'carbon');

DROP TABLE IF EXISTS {SQL_PREFIX}groups;
CREATE TABLE {SQL_PREFIX}groups (
  id smallint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('active','locked') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  role_id smallint(4) NOT NULL,
  UNIQUE KEY id (id),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=6 ;

INSERT INTO {SQL_PREFIX}groups (id, name, role_id) VALUES
(1, 'root', 4),
(2, 'admin', 3),
(3, 'guest', 1),
(4, 'member', 2);

DROP TABLE IF EXISTS {SQL_PREFIX}layouts;
CREATE TABLE {SQL_PREFIX}layouts (
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  regex varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY `name` (`name`),
  KEY regex (regex)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO {SQL_PREFIX}layouts (`name`, regex) VALUES
('main-fullwidth-edit', '^page/config/(edit|add)');

DROP TABLE IF EXISTS {SQL_PREFIX}modules;
CREATE TABLE {SQL_PREFIX}modules (
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `order` smallint(3) NOT NULL DEFAULT '0',
  disabled tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `name` (`name`),
  KEY `order` (`order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS {SQL_PREFIX}sessions;
CREATE TABLE {SQL_PREFIX}sessions (
  uid mediumint(6) NOT NULL,
  session_key char(64) COLLATE utf8_unicode_ci NOT NULL,
  session_id varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY session_key (session_key)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS {SQL_PREFIX}users;
CREATE TABLE {SQL_PREFIX}users (
  id mediumint(6) NOT NULL AUTO_INCREMENT,
  `status` enum('active','locked') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  username varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `password` char(64) COLLATE utf8_unicode_ci NOT NULL,
  email varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `group` int(4) NOT NULL DEFAULT '0',
  joined datetime NOT NULL,
  hide_email tinyint(1) NOT NULL DEFAULT '1',
  first_name varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  last_name varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  theme varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  activate_code char(48) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  reset_code char(48) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  last_login int(11) NOT NULL DEFAULT '0',
  last_pw_change datetime NOT NULL,
  UNIQUE KEY id (id),
  UNIQUE KEY username (username),
  INDEX `password` (`password`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT INTO {SQL_PREFIX}users (username, `password`, email, `group`, joined) VALUES
('guest', 'guest', '', 3, UTC_TIMESTAMP() ),
('rootUser', 'rootPass', 'rootEmail', 1, UTC_TIMESTAMP() );
