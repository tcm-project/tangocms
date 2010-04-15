<?php

/**
 * Zula Framework Theme
 * --- Takes all of the sectors for a theme, and poplates it with module/controller
 * output into the various sectors.
 *
 * Also static methods to get general information about themes.
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Theme
 */

	class Theme extends View {

		/**
		 * Version of jQuery to load (used only when use of Google CDN is allowed)
		 */
		const _JQUERY_VERSION = 1.4;

		/**
		 * Name of the current theme
		 * @var string
		 */
		protected $themeName = null;

		/**
		 * Details from the themes .xml file
		 * @var array
		 */
		protected $themeDetails = array();

		/**
		 * Theme/Content Layout object
		 * @var object
		 */
		protected $layout = null;

		/**
		 * All JS files that have been loaded via Theme::loadJsFile()
		 * @var array
		 */
		protected $loadedJsFiles = array();

		/**
		 * Toggles if JavaScript files should be aggregated
		 * @var bool
		 */
		protected $aggregateJs = false;

		/**
		 * Toggles if Google's CDN should be used
		 * @var bool
		 */
		protected $googleCdn = false;

		/**
		 * Stores all loaded JS files from Google's CDN
		 * @var array
		 */
		protected $loadedGoogleLibs = array();

		/**
		 * Create instance of a theme and gather all details for it.
		 *
		 * @param string $themeName
		 * @return object
		 */
		public function __construct( $themeName ) {
			if ( !self::exists( $themeName ) ) {
				throw new Theme_NoExist( 'unable to construct theme "'.$themeName.'" as it does not exist' );
			}
			Hooks::register( 'cache_purge', array($this, 'clearJsDir') );
			// Populate details array and construct parent view
			$this->themeName = $themeName;
			$this->getAllDetails();
			parent::__construct( $this->_zula->getDir( 'themes' ).'/'.$themeName.'/main_template.html' );
			if ( $this->_config->has( 'cache/js_aggregate' ) ) {
				$this->aggregateJs = (bool) $this->_config->get( 'cache/js_aggregate' );
			}
			if ( $this->_config->has( 'cache/google_cdn' ) ) {
				$this->googleCdn = (bool) $this->_config->get( 'cache/google_cdn' );
			}
		}

		/**
		 * Checks if a themes main_template.html and details.xml
		 * file exists. If so, then it exists.
		 *
		 * @param string $themeName
		 * @return bool
		 */
		static public function exists( $themeName ) {
			$themeDir = Registry::get( 'zula' )->getDir( 'themes' ).'/'.$themeName;
			foreach( array($themeDir.'/main_template.html', $themeDir.'/details.xml') as $path ) {
				if ( !file_exists( $path ) || !is_readable( $path ) ) {
					return false;
				}
			}
			return true;
		}

		/**
		 * Scans themes directory to get every theme available
		 *
		 * @return array
		 */
		static public function getAll() {
			$themes = array();
			foreach( new DirectoryIterator( Registry::get('zula')->getDir('themes') ) as $file ) {
				if ( !$file->isDot() && $file->isDir() && self::exists( $file->getFilename() ) ) {
					$themes[] = $file->getFileName();
				}
			}
			return $themes;
		}

		/**
		 * Gets the theme that should be used for a specified site
		 * type. If none is specified, it uses the current.
		 *
		 * @param string $siteType
		 * @return string
		 */
		static public function getSiteTypeTheme( $siteType=null ) {
			if ( !trim( $siteType ) ) {
				$siteType = Registry::get( 'router' )->getSiteType();
			}
			$config = Registry::get( 'config' );
			try {
				return $config->get( 'theme/'.$siteType.'_default' );
			} catch ( Config_KeyNoExist $e ) {
				return $config->get( 'theme/default' );
			}
		}

		/**
		 * Hook callback for 'cache_purge' to remove all JS files
		 * from the tmp directory
		 *
		 * @return int
		 */
		public function clearJsDir() {
			return zula_empty_dir( $this->_zula->getDir( 'tmp' ).'/js/', 'js', $this->_cache->get( 'cache/ttl' ) );
		}

		/**
		 * Gets all of the details from the 'details.xml' file for
		 * the current theme.
		 *
		 * @return array
		 */
		public function getAllDetails() {
			if ( empty( $this->themeDetails ) ) {
				$detailsDom = new DomDocument;
				$detailsDom->load( $this->_zula->getDir( 'themes' ).'/'.$this->themeName.'/details.xml' );
				$xpath = new DomXpath( $detailsDom );
				foreach( $xpath->query( '/themes/theme/*' ) as $node ) {
					if ( $node->nodeType == XML_ELEMENT_NODE && !$xpath->query( '*', $node )->length ) {
						$this->themeDetails[ $node->nodeName ] = $node->nodeValue;
					}
				}
			}
			return $this->themeDetails;
		}

		/**
		 * Gets a single detail by key
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function getDetail( $key ) {
			if ( isset( $this->themeDetails[ $key ] ) ) {
				return $this->themeDetails[ $key ];
			}
			throw new Theme_DetailNoExist( 'details "'.$key.'" does not exist' );
		}

		/**
		 * Access the layout object for the theme
		 *
		 * @return object
		 */
		public function layout() {
			if ( !($this->layout instanceof Theme_Layout) ) {
				$this->layout = new Theme_Layout( null, $this->getDetail( 'name' ) );
			}
			return $this->layout;
		}

		/**
		 * Deletes a theme by removing it's directory, then if any site-types
		 * were using that theme, it will update the setting for a new theme
		 *
		 * It will *not* delete the last remaining theme
		 *
		 * @return bool
		 */
		public function delete() {
			foreach( self::getAll() as $theme ) {
				if ( $theme != $this->getDetail( 'name' ) ) {
					$replacementTheme = $theme;
					break;
				}
			}
			if ( !isset( $replacementTheme ) ) {
				throw new Theme_UnableToDelete( 'unable to delete last remaning theme', 1 );
			}
			// Attempt to remove the directory
			$themeDir = $this->_zula->getDir( 'themes' ).'/'.$this->getDetail( 'name' );
			if ( !zula_full_rmdir( $themeDir ) ) {
				throw new Theme_UnableToDelete( 'Theme directory "'.$themeDir.'" could not be (fully) removed. Please check permissions', 2 );
			}
			foreach( $this->_router->getSiteTypes() as $siteType ) {
				if ( self::getSiteTypeTheme( $siteType ) == $this->getDetail( 'name' ) ) {
					$this->_config_sql->update( 'theme/'.$siteType.'_default', $replacementTheme );
				}
			}
			return true;
		}

		/**
		 * Scans the theme directory for all CSS files available
		 *
		 * @return array
		 */
		public function getAllCss() {
			$dir = $this->_zula->getDir( 'themes' ).'/'.$this->getDetail( 'name' );
			$cssFiles = array();
			foreach( glob( $dir.'/*.css' ) as $file ) {
				$name = pathinfo( $file, PATHINFO_FILENAME );
				$cssFiles[ $name ] = array(
											'path' 	=> $dir.'/'.pathinfo( $file, PATHINFO_BASENAME ),
											'name'	=> $name,
											);
			}
			return $cssFiles;
		}


		/**
		 * Gets the path to a single CSS file by its name (no .css extension)
		 *
		 * @param string $cssName
		 * @return string
		 */
		public function getCssFile( $cssName ) {
			$cssFiles = $this->getAllCss();
			if ( isset( $cssFiles[ $cssName ] ) ) {
				return $cssFiles[ $cssName ];
			}
			throw new Theme_CssNoExist( 'CSS "'.$cssName.'" does not exist for theme "'.$this->getDetail( 'name' ).'"' );
		}

		/**
		 * Adds a new HTML tag into the 'head' of the HTML theme view
		 * file. Extra Head elements are added through the {HEAD} tag.
		 *
		 * @param string $type
		 * @param array $attrs		Attributes for the tag
		 * @param string $content
		 * @return bool
		 */
		public function addHead( $type, $attrs=array(), $content=null, $prepend=false ) {
			if ( $type == 'js' || $type == 'javascript' ) {
				$attrs['type'] = 'text/javascript';
				$type = 'script';
			} else if ( $type == 'css' ) {
				$attrs['type'] = 'text/css';
				$attrs['rel'] = 'StyleSheet';
				if ( !isset( $attrs['media'] ) ) {
					$attrs['media'] = 'screen';
				}
				$type = 'link';
			}
			// Build all of the attributes required
			$attrStr = '';
			foreach( (array) $attrs as $attr=>$val ) {
				$attrStr .= $attr.'="'.zula_htmlspecialchars($val).'" ';
			}
			$attrStr = rtrim( $attrStr );
			switch( $type ) {
				case 'script':
					$str = '<script '.$attrStr.'>'.$content.'</script>';
					break;

				case 'style':
					$str = '<style type="text/css" '.$attrStr.'>'.$content.'</style>';
					break;

				case 'meta':
				case 'link':
					$str = sprintf( '<%1$s %2$s>', $type, $attrStr );
					break;

				default:
					trigger_error( 'Theme::addHead() provided type "'.$type.'" is unknown', E_USER_NOTICE );
					return false;
			}
			return $this->assignHtml( array('HEAD' => $str."\r\n"), false, $prepend );
		}

		/**
		 * Wrapper for Theme::addHead() to easily add a CSS file from
		 * CSS assets directory.
		 *
		 * @param string|array $file
		 * @return int
		 */
		public function addCssFile( $file ) {
			$numAdded = 0;
			$path = $this->_zula->getDir( 'assets', true ).'/css/';
			foreach( (array) $file as $css ) {
				if ( $this->addHead('css', array('href' => $path.'/'.$css)) ) {
					++$numAdded;
				}
			}
			return $numAdded;
		}

		/**
		 * Wrapper for Theme::addHead() which makes it easier to add JS file(s) into
		 * the themes 'head'.
		 *
		 * If enabled, all JS files will be merged into one file. This will reduce HTTP
		 * requests and filesize client has to download.
		 *
		 * First argument can be any of the Google AJAX Libaries API name, if enabled it
		 * shall get it from Google's CDN, if not - find a local copy. If is a Google lib
		 * then the second argument ($merge) becomes the version string to load.
		 *
		 * The first argument can also be in the format of 'jquery.plugin-name' to load a
		 * local jQuery plugin. Same as passing 'jQuery/plugins/plugin-name.js'.
		 *
		 * @param string|array $file
		 * @param bool $merge			Toggle merging of JS files (if enabled)
		 * @param string $module		JS file is a virtual asset for specified module
		 * @return int|bool				Number of JS files added
		 */
		public function addJsFile( $file, $merge=true, $module=null ) {
			$numberAdded = 0;
			foreach( (array) $file as $jsFile ) {
				$isLocal = substr( $jsFile, -3 ) == '.js';
				if ( !$isLocal && strpos( $jsFile, 'jquery.' ) === 0 ) {
					$jsFile = 'jQuery/plugins/'.substr($jsFile, 7).'.js';
					$isLocal = true;
				}
				if ( empty( $this->loadedJsFiles ) && $isLocal && $jsFile != 'jQuery/jquery.js' ) {
					// Load the jQuery library before any other local file.
					$numberAdded += $this->addGoogleLib( 'jquery', self::_JQUERY_VERSION );
				} else if ( !$isLocal ) {
					$numberAdded += $this->addGoogleLib( $jsFile, $merge ); # Merge arg becomes version arg remember!
					continue;
				}
				// Check if the file should be merged. Now, libs and jQuery should not be merged together
				if ( $jsFile == 'jQuery/jquery.js' || $jsFile == 'jQueryUI/jqueryui.js' || strpos( $jsFile, 'libs/' ) === 0 ) {
					$type = 'system';
				} else {
					$type = $this->aggregateJs && $merge ? 'merging' : 'standalone';
				}
				// Get the right path for the JS file and store it correctly
				$jsFileDir = $module == null ? $this->_zula->getDir( 'js' ) : Module::getDirectory().'/'.$module.'/assets';
				$jsPath = $jsFileDir.'/'.$jsFile;
				if ( $this->_zula->getState() == 'development' ) {
					// Attempt to use source file (.src.js) instead.
					if ( file_exists( preg_replace('#(?<!\.src)\.js$#', '.src.js', $jsPath) ) ) {
						// Update module asset as well
						$jsFile = preg_replace( '#(?<!\.src)\.js$#', '.src.js', $jsFile );
						$jsPath = $jsFileDir.'/'.$jsFile;
					}
				}
				if ( $type != 'merging' ) {
					// Generate correct path for those files which wont get merged.
					if ( $module == null ) {
						$jsPath = $this->_zula->getDir( 'js', true ).'/'.$jsFile;
					} else {
						$jsPath = $this->_router->makeUrl( 'assets/v/'.$module ).'/'.$jsFile;
					}
				}
				if ( $this->aggregateJs ) {
					if ( !isset($this->loadedJsFiles[$type]) || !in_array($jsPath, $this->loadedJsFiles[ $type ]) ) {
						$this->loadedJsFiles[ $type ][] = $jsPath;
						++$numberAdded;
					}
				} else {
					if ( !in_array($jsPath, $this->loadedJsFiles) && $this->addHead('js', array('src' => $jsPath)) ) {
						$this->loadedJsFiles[] = $jsPath;
						++$numberAdded;
					}
				}
			}
			return $numberAdded;
		}

		/**
		 * Attempts to add a JavaScript file from the Google AJAX API libraries
		 * if it exists. If disabled, attempt to load a local copy. 'jquery' will
		 * automatically be loaded if 'jqueryui' is loaded first.
		 *
		 * @param string $library
		 * @param string $version
		 * @return int|bool
		 */
		public function addGoogleLib( $library, $version ) {
			$numberAdded = 0;
			if ( $library == 'jqueryui' && !in_array( 'jquery', $this->loadedGoogleLibs ) ) {
				$numberAdded += $this->addGoogleLib( 'jquery', self::_JQUERY_VERSION );
			}
			// All available libraries, and their filename
			$availableLibs = array(
								'jquery'		=> 'jquery.min.js',
								'jqueryui'		=> 'jquery-ui.min.js',
								'prototype'		=> 'prototype.js',
								'scriptaculous'	=> 'scriptaculous.js',
								'mootools'		=> 'mootools-yui-compressed.js',
								'dojo'			=> 'dojo.xd.js',
								'swfobject'		=> 'swfobject.js',
								'yui'			=> 'build/yuiloader/yuiloader-min.js',
								'ext-core'		=> 'ext-core.js',
								'chrome-frame'	=> 'CFInstall.min.js',
								);
			if ( !isset( $availableLibs[ $library ] ) ) {
				return false;
			} else if ( in_array( $library, $this->loadedGoogleLibs ) ) {
				return 0; # Library already loaded.
			} else if ( $this->googleCdn ) {
				$url = 'http://ajax.googleapis.com/ajax/libs/%1$s/%2$s/%3$s';
				$this->addHead( 'js', array(
											'src' => sprintf( $url, $library, $version, $availableLibs[ $library ] ),
											));
				$this->loadedGoogleLibs[] = $library;
				return ++$numberAdded;
			} else {
				// Attempt to load a local copy
				switch( $library ) {
					case 'jquery':
						$library = 'jQuery/jquery.js';
						break;
					case 'jqueryui':
						$library = 'jQueryUI/jqueryui.js';
						break;
					default:
						$library = 'libs/'.$library.'.js';
				}
				return $this->addJsFile( $library );
			}
		}

		/**
		 * Loads something, usually a controllers output in a sector
		 * tag within the current theme.
		 *
		 * @param string $sector
		 * @param string $data
		 * @return bool
		 */
		public function loadIntoSector( $sector, $data ) {
			if ( strtoupper( $sector ) == 'SC' || $this->layout()->sectorExists( $sector ) ) {
				return $this->assignHtml( array($sector => $data), false );
			}
			throw new Theme_SectorNoExist( 'unable to load data into sector "'.$sector.'" as it does not exist' );
		}

		/**
		 * Gets all sectors for the current theme, and loads all of the attached
		 * modules/controllers into them. Each attached controller has its own
		 * ACL resource which is checked first.
		 *
		 * @return bool
		 */
		public function loadSectorControllers() {
			foreach( $this->layout()->asArray() as $sector ) {
				if ( $sector['id'] == 'SC' ) {
					continue; # SC content is not handled by this method
				}
				/**
				 * Attempt to load every needed module/controller, and check if user has permission
				 * to the controller. If no ACL resource exists, it's assumed user has permission!
				 */
				foreach( $sector['controllers'] as $cntrlr ) {
					$aclResource = 'layout_controller_'.$cntrlr['id'];
					if ( _ACL_ENABLED && ($this->_acl->resourceExists( $aclResource ) && !$this->_acl->check( $aclResource, null, false )) ) {
						continue;
					}
					$cntrlrOutput = false;
					try {
						$module = new Module( $cntrlr['mod'] );
						try {
							$ident = $cntrlr['mod'].'::'.$cntrlr['con'].'::'.$cntrlr['sec'];
							$tmpCntrlr = $module->loadController( $cntrlr['con'], $cntrlr['sec'], $cntrlr['config'], $sector['id'] );
							$cntrlrOutput = $tmpCntrlr['output'];
							// Check cntrlr returned something usable
							if ( $cntrlrOutput === false ) {
								continue;
							} else if ( !trim( $cntrlrOutput ) ) {
								$cntrlrOutput = '<p>'.t('Controller loaded but appears to display no content', Locale::_DTD).'</p>';
							}
						} catch ( Module_ControllerNoExist $e ) {
							$this->_log->message( $e->getMessage(), Log::L_WARNING );
							$cntrlrOutput .= '<p>'.sprintf( t('requested controller "%s" does not exist', Locale::_DTD), $ident ).'</p>';
						} catch ( Module_AjaxOnly $e ) {
							$this->_log->message( 'controller "'.$ident.'" must be loaded in an AJAX request only', Log::L_WARNING );
							continue;
						} catch ( Module_UnableToLoad $e ) {
							// Could also be a Module_NoPermission
							continue;
						}
					} catch ( Module_NoExist $e ) {
						$this->_log->message( 'sector module "'.(isset($ident) ? $ident : $cntrlr['mod']).'" does not exist', Log::L_WARNING );
						continue;
					}
					// Wrap the controller in the module_wrap.html file, and load into sector.
					if ( $cntrlr['config']['displayTitle'] === 'custom' && !empty( $cntrlr['config']['customTitle'] ) ) {
						$title = $cntrlr['config']['customTitle'];
					} else {
						$title = isset($tmpCntrlr['title']) ? $tmpCntrlr['title'] : t('Oops!', Locale::_DTD);
					}
					if ( empty( $cntrlr['config']['htmlWrapClass'] ) ) {
						$htmlWrapClass = null;
					} else {
						$htmlWrapClass = $cntrlr['config']['htmlWrapClass'];
					}
					// Build up the final wrap view.
					$view = new View( $this->_zula->getDir( 'themes' ).'/'.$this->getDetail( 'name' ).'/module_wrap.html' );
					$view->assign( array(
										'ID'			=> $cntrlr['id'],
										'TITLE'			=> $title,
										'DISPLAY_TITLE'	=> !empty( $cntrlr['config']['displayTitle'] ),
										'WRAP_CLASS'	=> $htmlWrapClass,
										));
					$view->assignHtml( array('CONTENT' => $cntrlrOutput) );
					$this->loadIntoSector( $sector['id'], $view->getOutput() );
				}
				if ( !$this->isAssigned( $sector['id'] ) ) {
					$this->loadIntoSector( $sector['id'], '' );
				}
			}
			return true;
		}

		/**
		 * Sets controller title, page links, errors etc and returns the
		 * final output of the theme.
		 *
		 * If aggregation is on, all loaded JS files will be merged into
		 * one to reduce HTTP requests. Files that are older than the main
		 * cache TTL will be removed first.
		 *
		 * @return string
		 */
		public function output() {
			if ( !empty( $this->loadedJsFiles ) ) {
				zula_empty_dir( $this->_zula->getDir( 'tmp' ).'/js/', 'js', $this->_config->get( 'cache/ttl' ) );
				// Setup some vars to be used (as JS can not get at the Zula Framework)
				$this->addHead( 'js',
								array(),
								'var zula_dir_base = "'._BASE_DIR.'";
								 var zula_dir_assets = "'.$this->_zula->getDir( 'assets', true ).'";
								 var zula_dir_js = "'.$this->_zula->getDir( 'js', true ).'";
								 var zula_dir_cur_theme = "'.$this->_zula->getDir( 'themes', true ).'/'.$this->getDetail('name').'";
								 var zula_dir_icon = zula_dir_cur_theme+"/icons";',
								true
							   );
			}
			if ( $this->aggregateJs ) {
				// Add in all needed JavaScript files, those under 'merging' will be aggregated.
				foreach( array('system', 'merging', 'standalone') as $type ) {
					if ( empty( $this->loadedJsFiles[ $type ] ) ) {
						continue; # No JS files of this type.
					} else if ( $type == 'system' || $type == 'standalone' ) {
						foreach( $this->loadedJsFiles[$type] as $file ) {
							$this->addHead( 'js', array('src' => $file) );
						}
					} else {
						/**
						 * Merge all 'merging' JS files into 1, help reduce HTTP requests
						 */
						$tmpJsFile = 'js/'.zula_hash( implode('', $this->loadedJsFiles[$type]), null, 'md5' ).'.js';
						$jsFilePath = array(
											'real'	=> $this->_zula->getDir( 'tmp' ).'/'.$tmpJsFile,
											'html'	=> $this->_zula->getDir( 'tmp', true ).'/'.$tmpJsFile,
											);
						// Check if the aggregated file exists, if so - see if we need to expire it
						$hasFile = false;
						if ( is_dir( dirname($jsFilePath['real']) ) ) {
							if ( file_exists( $jsFilePath['real'] ) ) {
								$hasFile = true;
								$lastModified = filemtime( $jsFilePath['real'] );
								foreach( $this->loadedJsFiles[ $type ] as $file ) {
									if ( filemtime( $file ) > $lastModified ) {
										unlink( $jsFilePath['real'] );
										$hasFile = false;
										break;
									}
								}
							}
						} else {
							zula_make_dir( dirname($jsFilePath['real']) );
						}
						if ( $hasFile === false ) {
							// Create the new aggregation file
							$content = null;
							foreach( $this->loadedJsFiles[ $type ] as $file ) {
								$content .= file_get_contents( $file );
							}
							file_put_contents( $jsFilePath['real'], $content );
						}
						$this->addHead( 'js', array('src' => $jsFilePath['html']) );
					}
				}
			}
			$details = array(
							'page_links'	=> '',
							'cntrlr_title'	=> t('Oops!', Locale::_DTD),
							'module'		=> 'unknown',
							'controller'	=> 'unknown',
							'section'		=> 'unknown',
							);
			// Gather data from the requested controller (if it exists)
			if ( $this->_dispatcher->isDispatched() ) {
				$reqCntrlr = $this->_dispatcher->getReqCntrl();
				$dispatchData = $this->_dispatcher->getDispatchData();
				$details = array(
								'page_links'	=> $this->makePageLinks( $reqCntrlr->getPageLinks() ),
								'module'		=> $dispatchData['module'],
								'controller'	=> $dispatchData['controller'],
								'section'		=> $dispatchData['section'],
								);
				// Decide what title to display for the requested controller
				if ( $this->_router->getParsedUrl()->module == null ) {
					if ( $dispatchData['config']['displayTitle'] === 'custom' && !empty( $dispatchData['config']['customTitle'] ) ) {
						$details['cntrlr_title'] = $dispatchData['config']['customTitle'];
					} else if ( $dispatchData['config']['displayTitle'] ) {
						$details['cntrlr_title'] = $reqCntrlr->getDetail( 'title' );
					} else {
						$details['cntrlr_title'] = null;
					}
				} else {
					$details['cntrlr_title'] = $reqCntrlr->getDetail( 'title' );
				}
			}
			// Unqiue page ID used normally on the 'body' element
			#$pageId = $this->_dispatcher->atFrontpage() ? 'frontpage' : $details['module'].'_'.$details['controller'].'_'.$details['section'];
			$this->assign( array(
								'CONTROLLER_TITLE'	=> $details['cntrlr_title'],
								'PAGE_TITLE'		=> $this->makePageTitle( $details['cntrlr_title'] ),
								'PAGE_ID'			=> 'frontpage',
								));
			$this->assignHtml( array(
									'EVENT_FEEDBACK'	=> $this->_event->output(),
									'PAGE_LINKS'		=> $details['page_links'],
									));
			if ( !$this->isAssigned( 'HEAD' ) ) {
				$this->assign( array('HEAD' => '') );
			}
			return $this->getOutput();
		}

		/**
		 * Creates the page title in the correct format
		 *
		 * @param string $page
		 * @return string
		 */
		public function makePageTitle( $page ) {
			try {
				$format = $this->_config->get( 'config/title_format' );
			} catch ( Config_KeyNoExist $e ) {
				$format = '[PAGE] | [SITE_TITLE]';
			}
			// Create tokens what what to replace them with
			$tokens = array(
							'[PAGE]'		=> $page,
							'[SITE_TITLE]' 	=> $this->_config->get( 'config/title' ),
							);
			return str_replace( array_keys($tokens), array_values($tokens), $format );
		}

		/**
		 * Takes an array and creates a simple unsorted-lists that
		 * are used as page links and formats them correctly
		 *
		 * @param array $links
		 * @return string
		 */
		public function makePageLinks( array $links ) {
			if ( empty( $links ) ) {
				return '';
			} else {
				$linkItems = array();
				foreach( $links as $title=>$url ) {
					$url = !trim( $url ) ? '#' : zula_htmlspecialchars( $url );
					$title = zula_htmlspecialchars( $title );
					// Build link and list item
					$linkItems[] = '<li><a href="'.$url.'" title="'.$title.'">'.$title.'</a></li>';
				}
				return '<ul id="pagelinks">'.implode( '', $linkItems ).'</ul>';
			}
		}

	}

?>
