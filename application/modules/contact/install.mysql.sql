DROP TABLE IF EXISTS {PREFIX}mod_contact;
CREATE TABLE {PREFIX}mod_contact (
  id smallint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  identifier varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  email varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  body text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY id (id),
  UNIQUE KEY identifier (identifier)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

INSERT INTO {PREFIX}mod_contact (id, `name`, identifier, email, body) VALUES
(1, 'Contact us', 'contact-us', 'tangocms@example.com', '');

DROP TABLE IF EXISTS {PREFIX}mod_contact_fields;
CREATE TABLE {PREFIX}mod_contact_fields (
  id smallint(4) NOT NULL AUTO_INCREMENT,
  form_id smallint(4) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `options` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  required int(1) NOT NULL DEFAULT '1',
  `order` smallint(4) NOT NULL DEFAULT '2',
  PRIMARY KEY id (id),
  INDEX form_id (form_id),
  INDEX `order` (`order`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

INSERT INTO {PREFIX}mod_contact_fields (id, form_id, name, type, options, required, `order`) VALUES
(1, 1, 'Your name', 'textbox', '', 1, 2),
(2, 1, 'Your message', 'textarea', '', 1, 2);
