DROP TABLE IF EXISTS {PREFIX}mod_page;
CREATE TABLE {PREFIX}mod_page (
  id smallint(5) NOT NULL AUTO_INCREMENT,
  author mediumint(6) NOT NULL,
  `date` datetime NOT NULL,
  title varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  clean_title varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  parent smallint(5) NOT NULL DEFAULT '0',
  `order` smallint(5) NOT NULL DEFAULT '0',
  body text COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY id (id),
  KEY clean_title (clean_title),
  KEY title (title),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT INTO {PREFIX}mod_page (author, date, title, clean_title, parent, `order`, body) VALUES
(2, UTC_TIMESTAMP(), 'Welcome!', 'welcome!', 0, 0, '#!html\n<p>Welcome to your new <a title="Opensource PHP CMS" href="http://tangocms.org">TangoCMS</a> powered website! The installation was a success and you can now <a href="session">login</a> and manage your website through the <a href="admin">Admin Control Panel</a>.</p>\n<p>This page can be edited by <a href="admin/page/config">managing your pages</a> or changed to display something else by adjusting your <a href="admin/content_layout">content layout</a>.</p>\n<p>If you need help with anything related to <a title="Opensource PHP CMS" href="http://tangocms.org/">TangoCMS</a>, feel free to join our <a href="http://tangocms.org/community">community</a> to ask any question you wish and we''ll help you out in anyway we can!</p>');