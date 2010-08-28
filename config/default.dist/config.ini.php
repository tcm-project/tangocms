;<?php die( 'You can not access this file directly ... You can have a cup of tea though, if you want?' ); ?>

; ---
; Zula Framework configuration ini file.
; 	Provides common configuration details needed for the base of the Zula Framework.
;	Additional config details can be specified here and they will also be added.
;
; @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
; @author Alex Cartwright
; @package Zula
; ---

;---
; SQL Database connection settings
;
; @enable 	bool	If set to true then the bootstrap will attempt to connect to SQL database
; @host 	string	Hostname of your server, eg - localhost, 127.0.0.1, mysql.example.com
; @user		string	Username to be used when connecting
; @pass 	string	Password to be used when connecting
; @database string	The database to select once connected to the server
; @prefix 	string	Database prefix to be used for all tables
; @type 	string	Database type to use (uses PDO drivers)
;---
[sql]
enable = 0
host = 127.0.0.1
user = dbUser
pass = dbPass
database = dbName
type = mysql
prefix = tcm_
port = 3306

;---
; URL Router configuration
; 	Configure how Zula should handle URL routing
;
; @type	string	Router type to use, supported: sef, standard
;---
[url_router]
type = sef

;---
; Control how the Front Controller/Controller lib should react to certian situations
;
; @full_page_errors		bool	Display a full error page when the requested controller does not exist/no permission
; @npc					string	No Permission Controller. The Controller/Module to load if user does not have
;								permission to the requested one
;---
[controller]
full_page_errors = false
npc = session

;---
; ACL - Access Control Levels/Lists
;
; ACL provides a robust system to finely tune permissions to controllers and their sections
; Zula's ACL is by default Deny All which means you must explicitly allow roles access to a request (controller)
;
; @enable	bool
;---
[acl]
enable = 1

;---
; Global themes
;
; Zula has support for global themes, this can be a single file that will be used/displayed for every controller
; Themes have what are called 'sectors'. Controllers 'plug' into these different sectors throughout the theme.
; For example if I plug the controller 'login' into Sector2, on every page (main-controller loaded), the controller
; 'login' will be displayed in Sector2.
;
; @use_global	bool
; @default		string	The default theme to use (just it's name - default 'innocent' )
;
; You can also use different themes for the different site types simply use this format:
;	sitetype_default, EG: admin_default
; would set the theme to use for the site type 'admin'
;---
[theme]
use_global = 1
default = cappuccino
admin_default = innocent

;---
; Debug/Error Reporting and Logging
; Configure how Zula should display and log errors
;
; @php_error_level		int		Set the PHP error reporting level
; @php_display_errors	bool	Set if PHP should display errors
; @zula_detailed_error	bool	Toggle details errors.
; @zula_log_errors 		bool	Should Zula log errors?
; @zula_log_level 		int		Set the Zula internel log level
; 								L_DEBUG     = 1
; 								L_NOTICE 	= 2
; 								L_WARNING   = 4
; 								L_ERROR     = 8
; 								L_FATAL     = 16
; 								L_EVENT     = 32
;								L_STRICT	= 64
;								L_ALL		= 127
;								default: 94
; @zula_log_daily		bool	Set Zula to rotate the log files daily
; @zula_log_ttl			int		Time (in seconds) for log files to live, default 2 weeks (1209600)
;---
[debug]
php_error_level = highest
php_display_errors = 1
zula_detailed_error = 1
zula_log_level = 94
zula_log_daily = 1
zula_log_errors = 1
zula_show_errors = 1
zula_log_ttl = 1209600

;---
; Languages/Locale
; Zula is a multi-lingual framework, you can tweak the settings here
;
; @engine	string	How should translations be made? Supported: failsafe, gettext, gettext_php
; @default 	string	Default locale to use
;---
[locale]
engine = gettext_php
default = en_US.UTF-8

;---
; General Configuration Details
; These are normally overwritten by the database settings, no real need to touch these
;
; @title		string	Title of the website
; @title_format	string	Format the title should appear in. Use [PAGE] for the current page
;						and [SITE_TITLE] for the title
; @slogan		string
; @version		string	Current version of TangoCMS (Ideally don't change this)
;---
[config]
title = "Powered By TangoCMS"
title_format = "[PAGE] | [SITE_TITLE]"
slogan = "Powered by TangoCMS"
version = 2.5.56

;---
; Meta Data
; These are normally overwritten by the database settings, no real need to touch these
;
; @keywords 	string	Keywords that users will search for
; @description	string	Short Description of your website
;---
[meta]
keywords = "TangoCMS, Powered By, Open Source CMS, PHP"
description = "Powered by TangoCMS, an open source PHP CMS"

;---
; Password Hashing
; Sets how passwords should be hashed. Please, please - KEEP the salt safe as if you ever need
; to re-install TangoCMS and don't give it the correct salt, then no users will be able to
; login without resetting their passwords.
;
; @method	string	The hasing method to use
; @salt 	string	Salt to use when hasing the passwords
;---
[hashing]
method = sha256
salt = "ks£Ldf07s_$%==Dk$5;"

;---
; Caching
; Configure Caching options
;
; @type			string	Type of caching to use, supported: disabled, file, apc
; @ttl			int		Time To Live, how long cache should be stored/valid
; @js_aggregate bool	Merge all JavaScript files together
; @google_cdn	bool	Use Google CDN for common JavaScript libraries
;---
[cache]
type = file
ttl = 604800
js_aggregate = 1
google_cdn = 1
