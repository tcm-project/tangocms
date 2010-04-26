<?php

/**
 * Zula Framework Theme
 * --- Provides access to themes details, and can load a Layout object to place
 * different controllers output into sectors. Loading of the main dispatchers
 * content is also placed into the 'SC' sector.
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
		 * Toggles if JavaScript files should be aggregated
		 * @var bool
		 */
		protected $jsAggregation = false;

		/**
		 * Toggles if Google's CDN should be used
		 * @var bool
		 */
		protected $googleCdn = false;

		/**
		 * Details from the themes .xml file
		 * @var array
		 */
		protected $details = array();

		/**
		 * Details of sectors for the theme
		 * @var array
		 */
		protected $sectors = null;

		/**
		 * All JS files that have been loaded via Theme::loadJsFile()
		 * @var array
		 */
		protected $loadedJsFiles = array();

		/**
		 * Stores all loaded JS files from Google's CDN
		 * @var array
		 */
		protected $loadedGoogleLibs = array();

		/**
		 * Check if the theme exists
		 *
		 * @param string $name
		 * @return object
		 */
		public function __construct( $name ) {
			if ( !self::exists( $name ) ) {
				throw new Theme_NoExist( 'unable to construct theme "'.$name.'" as it does not exist' );
			}
			$this->details = array('path' => $this->_zula->getDir('themes').'/'.$name);
			// Load all details for the theme
			$dom = new DomDocument;
			$dom->load( $this->details['path'].'/details.xml' );
			foreach( $dom->getElementsByTagName('theme')->item(0)->getElementsByTagName('*') as $item ) {
				$this->details[ $item->nodeName ] = $item->nodeValue;
			}
			// Construct parent view and register cache_purge hook
			parent::__construct( $this->details['path'].'/main_template.html' );
			Hooks::register( 'cache_purge', array('Theme', 'clearJsTmp') );
		}

		/**
		 * Alias to Theme::output()
		 *
		 * @return string
		 */
		public function __toString() {
			return $this->output();
		}

		/**
		 * Hook callback for 'cache_purge' to remove all tmp JavaScript files
		 *
		 * @return int
		 */
		static public function clearJsTmp() {
			return zula_empty_dir( Registry::get('zula')->getDir('tmp').'/js/', 'js' );
		}

		/**
		 * Returns true if a themes main_template.html and details.xml exists
		 *
		 * @param string $themeName
		 * @return bool
		 */
		static public function exists( $themeName ) {
			$themeDir = Registry::get( 'zula' )->getDir( 'themes' ).'/'.$themeName;
			foreach( array($themeDir.'/main_template.html', $themeDir.'/details.xml', $themeDir.'/sectors.xml') as $path ) {
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
		 * Sets of JavaScript aggregation should be enabled or disabled
		 *
		 * @param bool $enable
		 * @return object
		 */
		public function setJsAggregation( $enable=true ) {
			$this->jsAggregation = (bool) $enable;
			return $this;
		}

		/**
		 * Sets if Googles CDN servers should be used for common files such as jQuery
		 *
		 * @param bool $useGoogle
		 * @return object
		 */
		public function setGoogleCdn( $useGoogle=true ) {
			$this->googleCdn = (bool) $useGoogle;
			return $this;
		}

		/**
		 * Gets all of the details from the 'details.xml' file for the current theme.
		 *
		 * @return array
		 */
		public function getAllDetails() {
			return $this->details;
		}

		/**
		 * Gets a single detail by key
		 *
		 * @param string $key
		 * @return mixed
		 */
		public function getDetail( $key ) {
			if ( isset( $this->details[ $key ] ) ) {
				return $this->details[ $key ];
			}
			throw new Theme_DetailNoExist( 'details "'.$key.'" does not exist' );
		}

		/**
		 * Scans the theme directory for all CSS files available
		 *
		 * @return array
		 */
		public function getAllCss() {
			$cssFiles = array();
			foreach( glob( $this->getDetail('path').'/*.css' ) as $file ) {
				$name = pathinfo( $file, PATHINFO_FILENAME );
				$cssFiles[ $name ] = array(
											'path' 	=> $this->getDetail('path').'/'.pathinfo( $file, PATHINFO_BASENAME ),
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
			throw new Theme_CssNoExist( 'CSS "'.$cssName.'" does not exist for' );
		}

		/**
		 * Gets details for every sector in the sector.xml file
		 *
		 * @return array
		 */
		public function getSectors() {
			if ( !is_array( $this->sectors ) ) {
				$cacheKey = 'theme_sectors_'.$this->getDetail('name');
				if ( ($this->sectors = $this->_cache->get($cacheKey)) == false ) {
					// Parse the XML file
					$dom = new DomDocument( '1.0', 'UTF-8' );
					$dom->load( $this->getDetail('path').'/sectors.xml' );
					foreach( $dom->getElementsByTagName( 'sector' ) as $node ) {
						$id = strtoupper( $node->getAttribute('id') );
						$this->sectors[ $id ] = array(
													'id' 			=> $id,
													'description'	=> $node->getElementsByTagName( 'description' )
																			->item(0)
																			->nodeValue,
													);
					}
					$this->_cache->add( $cacheKey, $this->sectors );
				}
			}
			return $this->sectors;
		}

		/**
		 * Returns true if the provided sector exists
		 *
		 * @param string $id
		 * @return bool
		 */
		public function sectorExists( $id ) {
			return array_key_exists( strtoupper($id), $this->getSectors() );
		}

		/**
		 * Gets detail of the provided sector, if it exists
		 *
		 * @param int $id
		 * @return array
		 */
		public function getSectorDetails( $id ) {
			if ( $this->sectorExists( $id ) ) {
				return $this->sectors[ strtoupper($id) ];
			}
			throw new Theme_SectorNoExist( 'sector "'.$id.'" does not exist' );
		}

		/**
		 * Deletes a theme by removing it's directory
		 *
		 * @return bool
		 */
		public function delete() {
			return zula_full_rmdir( $this->getDetail('path') );
		}

		/**
		 * Assigns data to a sector. Note that the sector is not checked to exist
		 * before it assigns.
		 *
		 * @param string $sector
		 * @param string $data
		 * @return bool
		 */
		public function loadIntoSector( $sector, $data ) {
			if ( preg_match( '#^S(?:C|[0-9]+)$#i', $sector ) ) {
				return $this->assignHtml( array($sector => $data), false );
			}
			return false;
		}

		/**
		 * Loads the main dispatchers content which will be assigned to the special
		 * sector 'SC'. Details from the dispatcher object will be used to form the
		 * page title and gather things such as page links.
		 *
		 * @param string $content
		 * @param object $dispatcher
		 * @return bool
		 */
		public function loadDispatcher( $content, Dispatcher $dispatcher ) {
			$pageLinks = $pageId = null;
			$cntrlrTitle = t('Oops!', Locale::_DTD);
			if ( $dispatcher->isDispatched() ) {
				$reqCntrlr = $dispatcher->getReqCntrlr();
				$dispatchData = $dispatcher->getDispatchData();
				// Decide on what title to display
				$cntrlrTitle = $reqCntrlr->getDetail( 'title' );
				if ( isset( $dispatchData['config']['displayTitle'], $dispatchData['customTitle'] ) ) {
					if ( $dispatchData['config']['displayTitle'] === 'custom' && !empty( $dispatchData['config']['customTitle'] ) ) {
						$cntrlrTitle = $dispatchData['config']['customTitle'];
					} else if ( !$dispatchData['config']['displayTitle'] ) {
						$cntrlrTitle = null;
					}
				}
				// Make the page links from the cntrlr
				foreach( $reqCntrlr->getPageLinks() as $title=>$url ) {
					$pageLinks .= sprintf( '<li><a href="%1$s" title="%2$s">%2$s</a></li>',
											(trim($url) ? zula_htmlspecialchars($url) : '#'),
											zula_htmlspecialchars( $title ) );
				}
				$pageLinks = $pageLinks ? '<ul id="pagelinks">'.$pageLinks.'</ul>' : null;
			}
			$pageTitle = str_replace( array('[PAGE]', '[SITE_TITLE]'),
									  array($cntrlrTitle, $this->_config->get('config/title')),
									  $this->_config->get('config/title_format')
									);
			// Assign all needed data to the view
			$this->assign( array(
								'CONTROLLER_TITLE'	=> $cntrlrTitle,
								'PAGE_TITLE'		=> $pageTitle,
								'PAGE_ID'			=> $pageId,
								));
			$this->assignHtml( array(
								'EVENT_FEEDBACK'	=> $this->_event->output(),
								'PAGE_LINKS'		=> $pageLinks,
								));
			return $this->loadIntoSector( 'SC', $content );
		}

		/**
		 * Loads all needed controllers from the layout into the correct sectors.
		 *
		 * @param object $layout
		 * @return int
		 */
		public function loadLayout( Theme_Layout $layout ) {
			$cntrlrCount = 0;
			foreach( $layout->getControllers() as $cntrlr ) {
				if ( $cntrlr['sector'] == 'SC' || !$this->sectorExists( $cntrlr['sector'] ) ) {
					continue;
				}
				$resource = 'layout_controller_'.$cntrlr['id'];
				if ( _ACL_ENABLED && ($this->_acl->resourceExists( $resource ) && !$this->_acl->check( $resource, null, false )) ) {
					continue;
				}
				$cntrlrOutput = false;
				try {
					$module = new Module( $cntrlr['mod'] );
					$ident = $cntrlr['mod'].'::'.$cntrlr['con'].'::'.$cntrlr['sec'];
					$tmpCntrlr = $module->loadController( $cntrlr['con'], $cntrlr['sec'], $cntrlr['config'], $cntrlr['sector'] );
					if ( $tmpCntrlr['output'] !== false ) {
						/**
						 * Wrap the cntrlr in the module_wrap.html file
						 */
						if ( $cntrlr['config']['displayTitle'] === 'custom' && !empty( $cntrlr['config']['customTitle'] ) ) {
							$title = $cntrlr['config']['customTitle'];
						} else {
							$title = isset($tmpCntrlr['title']) ? $tmpCntrlr['title'] : t('Oops!', Locale::_DTD);
						}
						$wrap = new View( $this->getDetail('path').'/module_wrap.html' );
						$wrap->assign( array(
											'ID'			=> $cntrlr['id'],
											'TITLE'			=> $title,
											'DISPLAY_TITLE'	=> !empty( $cntrlr['config']['displayTitle'] ),
											'WRAP_CLASS'	=> $cntrlr['config']['htmlWrapClass'],
											));
						$wrap->assignHtml( array('CONTENT' => $tmpCntrlr['output'])  );
						$this->loadIntoSector( $cntrlr['sector'], $wrap->getOutput() );
						++$cntrlrCount;
					}
				} catch ( Module_NoExist $e ) {
					$this->_log->message( 'sector module "'.(isset($ident) ? $ident : $cntrlr['mod']).'" does not exist',
											Log::L_WARNING );
				} catch ( Module_ControllerNoExist $e ) {
					$this->_log->message( $e->getMessage(), Log::L_WARNING );
				} catch ( Module_UnableToLoad $e ) {
					// Could also be a Module_NoPermission
				}
				if ( !$this->isAssigned( $cntrlr['sector'] ) ) {
					$this->loadIntoSector( $cntrlr['sector'], '' );
				}
			}
			return $cntrlrCount;
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
					$type = $this->jsAggregation && $merge ? 'merging' : 'standalone';
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
				if ( $this->jsAggregation ) {
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
		 * Returns the themes view output as a string. If a layout object has been
		 * loaded, then all required controllers for said layout will be loaded into
		 * the correct sector.
		 *
		 * @return string
		 */
		public function output() {
			if ( !empty( $this->loadedJsFiles ) ) {
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
			if ( $this->jsAggregation ) {
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
						$tmpJsPath = $this->_zula->getDir( 'tmp' ).'/'.$tmpJsFile;
						// Check if the aggregated file exists, if so - see if we need to expire it
						$hasFile = false;
						if ( is_dir( dirname($tmpJsPath) ) ) {
							if ( file_exists( $tmpJsPath ) ) {
								$hasFile = true;
								$lastModified = filemtime( $tmpJsPath );
								foreach( $this->loadedJsFiles[ $type ] as $file ) {
									if ( filemtime( $file ) > $lastModified ) {
										unlink( $tmpJsPath );
										$hasFile = false;
										break;
									}
								}
							}
						} else {
							zula_make_dir( dirname($tmpJsPath) );
						}
						if ( $hasFile === false ) {
							// Create the new aggregation file
							$content = null;
							foreach( $this->loadedJsFiles[ $type ] as $file ) {
								$content .= file_get_contents( $file );
							}
							file_put_contents( $tmpJsPath, $content );
						}
						$this->addHead( 'js', array('src' => $this->_zula->getDir('tmp', true).'/'.$tmpJsFile) );
					}
				}
			}
			if ( !$this->isAssigned( 'HEAD' ) ) {
				$this->assign( array('HEAD' => '') );
			}
			return $this->getOutput();
		}



	}

?>
