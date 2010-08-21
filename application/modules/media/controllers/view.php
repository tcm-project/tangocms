<?php

/**
 * Zula Framework Module (media)
 * --- Displays a single media item
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Media
 */

	class Media_controller_view extends Zula_ControllerBase {

		/**
		 * Magic call function to allow for shorter URLs of /media/view/clean-title.
		 *
		 * This is also the method that shall get the media item images, passing
		 * through PHP.
		 *
		 * @param string $name
		 * @param array $args
		 * @return mixed
		 */
		public function __call( $name, $args ) {
			$this->setTitle( t('View media item') );
			// Which format to display the media item as (mostly for Image types)
			try {
				$format = $this->_input->get( 'f' );
				if ( in_array( $format, array('large', 'medium', 'thumb', 'stream') ) ) {
					$this->_session->storePrevious( false );
				} else {
					$format = null;
				}
			} catch ( Input_KeyNoExist $e ) {
				$format = null;
			}
			try {
				/**
				 * Gather details for the media item, and check user has permission
				 * to the parent category.
				 */
				$item = $this->_model()->getItem( substr($name, 0, -7), false );
				$this->setTitle( $item['name'] );
				$category = $this->_model()->getCategory( $item['cat_id'] );
				$resource = 'media-cat_view_'.$category['id'];
				if ( !$this->_acl->resourceExists( $resource ) || !$this->_acl->check( $resource ) ) {
					throw new Module_NoPermission;
				}
				if ( $format == null ) {
					return $this->buildView( $item, $category );
				} else {
					$this->displayFormat( $item, $format );
					return false;
				}
			} catch ( Media_ItemNoExist $e ) {
				throw new Module_ControllerNoExist;
			} catch ( Media_CategoryNoExist $e ) {
				$this->_log->message( 'media item parent category '.$item['cat_id'].' does not exist', Log::L_WARNING );
				throw new Module_ControllerNoExist;
			}
		}

		/**
		 * Builds up the view for displaying a media item
		 *
		 * @param array $item
		 * @param array $category
		 * @return string
		 */
		protected function buildView( array $item, array $category ) {
			$modResource = 'media-cat_moderate_'.$category['id'];
			if ( $this->_acl->resourceExists( $modResource ) && $this->_acl->check( $modResource ) ) {
				// Add in the moderation page links for this media item
				$delUrl = $this->_router->makeUrl( 'media', 'manage', 'delete', null, array('id' => $item['id']) )
										->queryArgs( array('zct' => $this->_input->createToken()) );
				$this->setPageLinks( array(
										t('Edit item')	=> $this->_router->makeUrl( 'media', 'manage', 'edit', null, array('id' => $item['id']) ),
										t('Delete item')=> $delUrl,
										));
			}
			// Build up the view and add in any JavaScript files needed
			if ( $item['type'] == 'video' || $item['type'] == 'audio' ) {
				$this->_theme->addJsFile( 'flowplayer/flowplayer.js' );
				$this->addAsset( 'js/player.js' );
				// Calculate the width/height of the player
				try {
					$contentWidth = $this->_theme->getDetail( 'contentWidth' );
				} catch ( Theme_DetailNoExist $e ) {
					$contentWidth = 500;
				}
				if ( $contentWidth <= 240 ) {
					$playerWidth = 240;
				} else if ( $contentWidth <= 320 ) {
					$playerWidth = 320;
				} else if ( $contentWidth <= 480 ) {
					$playerWidth = 480;
				} else if ( $contentWidth <= 560 ) {
					$playerWidth = 560;
				} else if ( $contentWidth <= 640 ) {
					$playerWidth = 640;
				} else if ( $contentWidth <= 720 ) {
					$playerWidth = 720;
				} else {
					$playerWidth = 960;
				}
			}
			$view = $this->loadView( 'view/view.html' );
			$view->assign( array(
								'ITEM'		=> $item,
								'CATEGORY'	=> $category,
								'PLAYER'	=> array(
													'WIDTH'		=> isset($playerWidth) ? $playerWidth : null,
													'HEIGHT'	=> isset($playerWidth) ? $playerWidth / 16*9 : null,
													),
								));
			// Check if lightbox effect needs to be used
			if ( $item['type'] == 'image' && $this->_config->get( 'media/use_lightbox' ) ) {
				$this->_theme->addJsFile( 'jquery.tangobox' );
				$this->_theme->addCssFile( 'jquery.tangobox.css' );
				$view->assign( array('LIGHTBOX' => true) );
			} else {
				$view->assign( array('LIGHTBOX' => false) );
			}
			return $view->getOutput();
		}

		/**
		 * Gets the media item file in the correct format and passes it through PHP
		 *
		 * @param array $item
		 * @param string $format
		 * @return bool
		 */
		protected function displayFormat( array $item, $format ) {
			if ( $format == 'thumb' ) {
				$file = $item['path_fs'].'/'.$item['thumbnail'];
			} else if ( $item['type'] == 'image' ) {
				/**
				 * Get either full or medium sized image, however no large than
				 * the specified max width.
				 */
				$imgPath = $item['path_fs'].'/'.$item['filename'];
				if ( is_file( $imgPath ) ) {
					list( $imgWidth ) = getimagesize( $imgPath );
					$maxWidth = $this->_config->get( 'media/max_image_width' );
					if ( $format == 'medium' ) {
						// Display the medium size image no wider than the themes content
						try {
							$contentWidth = $this->_theme->getDetail( 'contentWidth' );
						} catch ( Theme_DetailNoExist $e ) {
							$contentWidth = 500;
						}
						if ( $contentWidth < $maxWidth ) {
							$maxWidth = $contentWidth;
						}
					}
					// Resize and add watermark if needed
					$wmPath = $this->_zula->getDir( 'uploads' ).'/media/wm.png';
					if ( $imgWidth <= $maxWidth && !is_file( $wmPath ) ) {
						$file = $imgPath;
					} else {
						$file = $this->_zula->getDir( 'tmp' )."/media/max{$maxWidth}-".pathinfo($item['filename'], PATHINFO_BASENAME);
						if ( !is_file( $file ) ) {
							$image = new Image( $imgPath );
							$image->resize( $maxWidth, null, false );
							if ( is_file( $wmPath ) ) {
								$image->watermark( $wmPath, $this->_config->get('media/wm_position') );
							}
							$image->save( $file );
						}
					}
				}
			} else if ( $format == 'stream' && $item['type'] == 'audio' || $item['type'] == 'video' ) {
				$file = $item['path_fs'].'/'.$item['filename'];
			}
			if ( isset( $file ) && is_file( $file ) ) {
				zula_readfile( $file );
				return false;
			} else if ( $format == 'thumb' ) {
				zula_readfile( zula_get_icon('misc/missing_'.$item['type'], null, false) );
				return false;
			} else if ( $item['type'] == 'image' ) {
				// Display default icon
				zula_readfile( zula_get_icon('misc/no_file', null, false) );
				return false;
			} else {
				throw new Module_ControllerNoExist;
			}
		}

	}

?>
