DROP TABLE IF EXISTS {PREFIX}mod_shareable;
CREATE TABLE {PREFIX}mod_shareable (
  id tinyint(3) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  url text COLLATE utf8_unicode_ci NOT NULL,
  icon varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  disabled tinyint(1) NOT NULL DEFAULT '0',
  `order` tinyint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=8 ;

INSERT INTO {PREFIX}mod_shareable (id, name, url, icon, disabled, `order`) VALUES
(1, 'Delicious', 'http://delicious.com/post?url={URL}&title={TITLE}', 'delicious', 0, 0),
(2, 'Digg', 'http://digg.com/submit?phase=2&url={URL}&title={TITLE}', 'digg', 0, 0),
(3, 'Facebook', 'http://www.facebook.com/share.php?u={URL}&t={TITLE}', 'facebook', 0, 0),
(4, 'Google', 'http://www.google.com/bookmarks/mark?op=edit&bkmk={URL}&title={TITLE}', 'google', 0, 0),
(5, 'Reddit', 'http://reddit.com/submit?url={URL}&title={TITLE}', 'reddit', 0, 0),
(6, 'Slashdot', 'http://slashdot.org/bookmark.pl?title={TITLE}&url={URL}', 'slashdot', 0, 0),
(7, 'Stumbleupon', 'http://www.stumbleupon.com/submit?url={URL}&title={TITLE}', 'stumbleupon', 0, 0);
