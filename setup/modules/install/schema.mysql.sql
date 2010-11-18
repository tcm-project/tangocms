SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

DROP TABLE IF EXISTS {PREFIX}acl_resources;
CREATE TABLE {PREFIX}acl_resources (
  id mediumint(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS {PREFIX}acl_roles;
CREATE TABLE {PREFIX}acl_roles (
  id smallint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(48) COLLATE utf8_unicode_ci NOT NULL,
  parent_id smallint(4) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY `name` (`name`),
  KEY parent_id (parent_id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=6 ;

INSERT INTO {PREFIX}acl_roles (id, name, parent_id) VALUES
(1, 'group_guest', 0),
(2, 'group_member', 1),
(3, 'group_admin', 2),
(4, 'group_root', 0);

DROP TABLE IF EXISTS {PREFIX}acl_rules;
CREATE TABLE {PREFIX}acl_rules (
  id mediumint(6) NOT NULL AUTO_INCREMENT,
  role_id smallint(4) NOT NULL,
  resource_id mediumint(6) NOT NULL,
  access tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS {PREFIX}config;
CREATE TABLE {PREFIX}config (
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO {PREFIX}config (name, value) VALUES
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
('theme/admin_default', 'purity'),
('theme/main_default', 'carbon');

DROP TABLE IF EXISTS {PREFIX}groups;
CREATE TABLE {PREFIX}groups (
  id smallint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('active','locked') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  role_id smallint(4) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=6 ;

INSERT INTO {PREFIX}groups (id, name, role_id) VALUES
(1, 'root', 4),
(2, 'admin', 3),
(3, 'guest', 1),
(4, 'member', 2);

DROP TABLE IF EXISTS {PREFIX}layouts;
CREATE TABLE {PREFIX}layouts (
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  regex varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`name`),
  KEY regex (regex)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO {PREFIX}layouts (`name`, regex) VALUES
('main-fullwidth-edit', '^page/config/(edit|add)');

DROP TABLE IF EXISTS {PREFIX}modules;
CREATE TABLE {PREFIX}modules (
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `order` smallint(3) NOT NULL DEFAULT '0',
  disabled tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`),
  KEY `order` (`order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS {PREFIX}sessions;
CREATE TABLE {PREFIX}sessions (
  uid mediumint(6) NOT NULL,
  session_key char(64) COLLATE utf8_unicode_ci NOT NULL,
  session_id varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY session_key (session_key)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS {PREFIX}users;
CREATE TABLE {PREFIX}users (
  id mediumint(6) NOT NULL AUTO_INCREMENT,
  `status` enum('active','locked') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  `group` int(4) NOT NULL DEFAULT '0',
  username varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `password` char(64) COLLATE utf8_unicode_ci NOT NULL,
  email varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  hide_email tinyint(1) NOT NULL DEFAULT '1',
  joined datetime NOT NULL,
  first_name varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  last_name varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  last_login int(11) NOT NULL DEFAULT '0',
  last_pw_change datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY username (username),
  INDEX `password` (`password`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT INTO {PREFIX}users (username, `password`, email, `group`, joined) VALUES
('guest', 'guest', '', 3, UTC_TIMESTAMP() ),
('rootUser', 'rootPass', 'rootEmail', 1, UTC_TIMESTAMP() );

DROP TABLE IF EXISTS {PREFIX}users_meta;
CREATE TABLE {PREFIX}users_meta (
  uid mediumint(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (uid,`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=8 ;
