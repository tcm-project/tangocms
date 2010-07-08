<?php

/**
 * Zula Framework Image (Malipulation)
 * --- Provides a base library with common methods for malipulation
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Image
 */

	abstract class Image_base extends Zula_LibraryBase {

		/**
		 * Image resource that will be used
		 * @var resource
		 */
		protected $resource = null;

		/**
		 * Details of the image we are working with
		 * @var array
		 */
		protected $details = array();

		/**
		 * Destroy image resource
		 *
		 * @return void
		 */
		public function __destruct() {
			if ( is_resource( $this->resource ) ) {
				imagedestroy( $this->resource );
			}
		}

		/**
		 * Provides easy access to details of the image
		 *
		 * @param string $name
		 * @return mixed
		 */
		public function __get( $name ) {
			return isset($this->details[ $name ]) ? $this->details[ $name ] : parent::__get( $name );
		}

		/**
		 * Sets certain details of the image
		 *
		 * @param string $name
		 * @param mixed $val
		 * @return mixed
		 */
		public function __set( $name, $val ) {
			if ( isset( $this->details[ $name ] ) ) {
				$this->details[ $name ] = $val;
			}
		}

		/**
		 * Converts hex such as #ff0000 to decimal RGB values
		 *
		 * @param string $hexCode
		 * @return array|bool
		 */
		protected function hex2rgb( $hexCode ) {
			$hexCode = ltrim( $hexCode, '#' );
			if ( preg_match( '#^([a-f0-9]{3}|[a-f0-9]{6})$#i', $hexCode ) ) {
				$rgb = array();
				foreach( str_split( $hexCode, strlen($hexCode)/3 ) as $hex ) {
					$rgb[] = hexdec( strlen($hex) == 1 ? $hex.$hex : $hex );
				}
				return $rgb;
			} else {
				return false;
			}
		}

		/**
		 * Makes a color identifier but instead of using RGB
		 * for the input it can use hex instead, such as #ff0000
		 *
		 * @param string $hexCode
		 * @param resource $image
		 * @return color identifier|bool
		 */
		protected function hexColorAllocate( $hexCode, $image=null ) {
			if ( !is_resource( $image ) ) {
				$image = $this->resource;
			}
			$rgbHex = $this->hex2rgb( $hexCode );
			return imagecolorallocate( $image, $rgbHex[0], $rgbHex[1], $rgbHex[2] );
		}

		/**
		 * Handles transparency for GIF and PNG images
		 *
		 * @param resource $image
		 * @param int $x1
		 * @param int $y1
		 * @param int $x2
		 * @param int $y2
		 * @return resource
		 */
		protected function handleTransparency( $image, $x1, $y1, $x2, $y2 ) {
			switch ( $this->mime ) {
				case 'image/png':
					imagealphablending( $image, false );
					imagefilledrectangle( $image, $x1, $y1, $x2, $y2,
										  imagecolorallocatealpha( $image, 0, 0, 0, 127 )
										);
					imagealphablending( $image, true );
					break;

				case 'image/gif':
					$transparencyIndex = imagecolortransparent( $this->resource );
					if ( $transparencyIndex >= 0 ) {
						$transparentColor = imagecolorsforindex( $this->resource, $transparencyIndex);
						$transparencyIndex = imagecolorallocate( $image, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue'] );
						imagefilledrectangle( $image, $x1, $y1, $x2, $y2, $transparencyIndex );
						imagecolortransparent( $image, $transparencyIndex );
						$numberColors = imagecolorstotal( $this->resource );
						imagetruecolortopalette( $image, true, $numberColors );
					}
					break;
			}
			return $image;
		}

		/**
		 * Creates a scaled thumbnail version of the image
		 *
		 * This is very similar to Image_base::resize(), however that method will do an exact
		 * resize to the dimensions you gave it. This is not always the wanted behaviour
		 * for a thumbnail as that can lead to distorted, small images.
		 *
		 * This method will instead create an image of the dimensions given, and then scale
		 * the base image to fit inside of that thumbnail "box", resulting in a nicely formatted
		 * thhumbnail image where every thumbnail will be of the same dimensions.
		 *
		 * @param int $width
		 * @param int $height
		 * @param string $bgColor	A background color to use, if any. Hex
		 * @return object
		 */
		public function thumbnail( $width=80, $height=80, $bgColor=null ) {
			if ( $this->height > $this->width ) {
				$newHeight = $height;
				$newWidth = ($height/$this->height) * $this->width;
				// Padding we need to fit in central in box
				$paddingWidth = ($width-$newWidth)/2;
				$paddingHeight = 0;
			} else {
				$newHeight = ($width/$this->width) * $this->height;
				$newWidth = $width;
				// Padding needed to fit central
				$paddingWidth = 0;
				$paddingHeight = ($height-$newHeight)/2;
			}
			$thumbnailImage = imagecreatetruecolor( $width, $height );
			if ( $thumbnailImage === false ) {
				throw new Image_Exception( 'failed to create true color' );
			}
			if ( $bgColor != null ) {
				imagefill( $thumbnailImage, 0, 0, $this->hexColorAllocate($bgColor, $thumbnailImage) );
			}
			$thumbnailImage = $this->handleTransparency( $thumbnailImage, 0, 0, $width-1, $height-1 );
			// Attempt to copy the the original image into the new thumbnail image
			$resampled = imagecopyresampled( $thumbnailImage, $this->resource, $paddingWidth, $paddingHeight+1, 0, 0,
											 $newWidth, $newHeight, $this->width, $this->height );
			if ( $resampled === false ) {
				throw new Image_Exception( 'thumbnail image could not be created. Failed to copy resampled image' );
			} else {
				$this->height = $newHeight;
				$this->width = $newWidth;
				$this->resource = $thumbnailImage;
				return $this;
			}
		}

		/**
		 * Resizes an image to specific sizes, or it will scale
		 * it if only 1 paramater is given
		 *
		 * @param int $width
		 * @param int $height
		 * @param bool $scaleUp	Should the image be scaled up if it's smaller than the set dimensions?
		 * @return object
		 */
		public function resize( $width=null, $height=null, $scaleUp=true ) {
			if ( empty( $width ) && empty( $height ) ) {
				return $this;
			} else if ( empty( $width ) ) {
				// We have no width, scale it based on the height
				if ( $scaleUp === false && $this->height < $height ) {
					return $this;
				}
				$newHeight = $height;
				$newWidth = ($height/$this->height) * $this->width;
			} else if ( empty( $height ) ) {
				// No height given, scale based on width
				if ( $scaleUp === false && $this->width < $width ) {
					return $this;
				}
				$newHeight = ($width/$this->width) * $this->height;
				$newWidth = $width;
			} else {
				if ( $scaleUp === false && $this->height < $height && $this->width < $width ) {
					return $this;
				}
				// Exact resize needed
				$newHeight = $height;
				$newWidth = $width;
			}
			$newImage = imagecreatetruecolor( $newWidth, $newHeight );
			if ( $newImage === false ) {
				throw new Image_Exception( 'failed to create true color' );
			} else {
				$newImage = $this->handleTransparency( $newImage, 0, 0, $newWidth-1, $newHeight-1 );
				// Copy the original into the new size image
				$resampled = imagecopyresampled( $newImage, $this->resource, 0, 0, 0, 0, $newWidth, $newHeight, $this->width, $this->height );
				if ( $resampled === false ) {
					throw new Image_Exception( 'image could not be resized. Failed to copy resampled image' );
				} else {
					$this->height = $newHeight;
					$this->width = $newWidth;
					$this->resource = $newImage;
					return $this;
				}
			}
		}

		/**
		 * Adds a border of a specified size and color around the image
		 *
		 * @param int $borderWidth
		 * @param string $color
		 * @return object
		 */
		public function addBorder( $borderWidth=10, $color='#000' ) {
			$borderWidth = abs( $borderWidth );
			if ( $borderWidth < 1 ) {
				return $this;
			}
			// New image with height/width plus 2 * border
			$newHeight = $this->height + 2 * $borderWidth;
			$newWidth = $this->width + 2 * $borderWidth;
			$newImage = imagecreatetruecolor( $newWidth, $newHeight );
			if ( $newImage === false ) {
				throw new Image_Exception( 'failed to create true color' );
			} else {
				$color = $this->hexColorAllocate( $color, $newImage );
				imagefill( $newImage, 0, 0, $color );
				$newImage = $this->handleTransparency( $newImage, $borderWidth, $borderWidth,
													   $newWidth-$borderWidth-1, $newHeight-$borderWidth-1 );
				// Copy original into a the new bigger image
				$resampled = imagecopyresampled( $newImage, $this->resource, $borderWidth, $borderWidth,
												 0, 0, $this->width, $this->height, $this->width, $this->height );
				if ( $resampled === false ) {
					throw new Image_Exception( 'image could not add border. Failed to copy resampled image' );
				} else {
					$this->height = $newHeight;
					$this->width = $newWidth;
					$this->resource = $newImage;
					return $this;
				}
			}
		}

		/**
		 * Copies the provided image into the current open image at set locations
		 * which is mainly used for watermarks
		 *
		 * @param string $file
		 * @param string $position
		 * @return object
		 */
		public function watermark( $file, $position='bl' ) {
			$wmImage = new Image( $file );
			if ( !in_array( $position, array('t', 'tr', 'r', 'br', 'b', 'bl', 'l', 'tl') ) ) {
				trigger_error( 'Image_Base::watermark() invalid value for argument 2, reverting to "bl"', E_USER_NOTICE );
				$position = 'bl';
			}
			// Work out position
			$posX = $posY = 0;
			if ( substr( $position, -1, 1 ) == 'r' ) {
				$posX = $this->width - $wmImage->width;
			} else if ( $position == 't' || $position == 'b' ) {
				$posX = ($this->width - $wmImage->width) / 2;
			}
			if ( $position[0] == 'b' ) {
				$posY = $this->height - $wmImage->height;
			} else if ( $position == 'l' || $position == 'r' ) {
				$posY = ($this->height - $wmImage->height) / 2;
			}
			imagecopymerge( $this->resource, $wmImage->getResource(),
							$posX, $posY, 0, 0,
							$wmImage->width, $wmImage->height, 100 );
			$wmImage->destroy();
			return $this;
		}

	}

?>
