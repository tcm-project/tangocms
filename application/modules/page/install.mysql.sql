DROP TABLE IF EXISTS {PREFIX}mod_page;
CREATE TABLE {PREFIX}mod_page (
  id smallint(5) NOT NULL AUTO_INCREMENT,
  parent smallint(5) NOT NULL DEFAULT '0',
  title varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  identifier varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  author mediumint(6) NOT NULL,
  `date` datetime NOT NULL,
  `order` smallint(5) NOT NULL DEFAULT '0',
  body text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY id (id),
  UNIQUE KEY identifier (identifier),
  INDEX title (title),
  INDEX `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT INTO {PREFIX}mod_page (id, parent, title, identifier, author, `date`, `order`, body) VALUES
(1, 0, 'Welcome!', 'welcome!', 2, UTC_TIMESTAMP(), 0, '#!html\n<p>Welcome to your new <a title="Opensource PHP CMS" href="http://tangocms.org">TangoCMS</a> powered website! The installation was a success and you can now <a href="session">login</a> and manage your website through the <a href="admin">Admin Control Panel</a>.</p>\n<p>This page can be edited by <a href="admin/page/config">managing your pages</a> or changed to display something else by adjusting your <a href="admin/content_layout">content layout</a>.</p>\n<p>If you need help with anything related to <a title="Opensource PHP CMS" href="http://tangocms.org/">TangoCMS</a>, feel free to join our <a href="http://tangocms.org/community">community</a> to ask any question you wish and we''ll help you out in anyway we can!</p>');
