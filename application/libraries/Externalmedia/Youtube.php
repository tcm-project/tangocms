<?php
// $Id: Youtube.php 2768 2009-11-13 18:12:34Z alexc $

/**
 * Zula Framework External Media (YouTube)
 *
 * @author James Stephenson
 * @copyright Copyright (C) 2007, 2008, 2009 James Stephenson
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Externalmedia
 */

	class Externalmedia_YouTube extends Externalmedia_Driver {

		/**
		 * Defines the YouTube API URL
		 * @var string
		 */
		protected $apiUrl = 'http://gdata.youtube.com/feeds/videos';

		/**
		 * Fetches the necessary attribute information for
		 * the defined media item.
		 *
		 * @return bool
		 */
		protected function fetchAttributes() {
			if ( ini_get( 'allow_url_fopen' ) ) {
				$youtubeXml = @file_get_contents( $this->apiUrl.'/'.$this->mediaId );
				if ( isset( $http_response_header ) && strpos( $http_response_header[0], '200' ) === false ) {
					throw new ExternalMediaDriver_InvalidID( 'YouTube ID "'.$this->mediaId.'" does not exist' );
				}
				$xml = simplexml_load_string( $youtubeXml );
				if ( $xml === false ) {
					return false;
				}
				$mediaAttributes = $xml->children( 'http://search.yahoo.com/mrss/' )->group;
				$this->attributes['title'] = $mediaAttributes->title;
				$this->attributes['description'] = $mediaAttributes->description;
				foreach( $mediaAttributes->thumbnail as $thumbnail ) {
					$thumbnailUrl = $thumbnail->attributes()->url;
					$thumbnailPath = pathinfo( $thumbnailUrl );
					if ( !array_key_exists( 'filename', $thumbnailPath ) ) {
						$thumbnailPath['filename'] = substr( $thumbnailPath['basename'],
															 0,
															 strlen($thumbnailPath['basename']) - (strlen($thumbnailPath['extension']) + 1)
														   );
					}
					if ( $thumbnailPath['filename'] === '0' ) {
						$this->attributes['thumbnail_url'] = $thumbnailUrl;
					}
				}
				$this->attributes['video_url'] = $mediaAttributes->content->attributes()->url;
				return true;
			} else {
				return false;
			}
		}

	}

?>
