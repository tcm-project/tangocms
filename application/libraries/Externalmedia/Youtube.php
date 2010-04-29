<?php

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
				$dom = new DomDocument;
				$dom->loadXml( $youtubeXml );
				$xPath = new DomXPath( $dom );
				// Gather all details
				$group = $xPath->query( '//media:group' )->item(0);
				$this->attributes['title'] = $xPath->query( 'media:title', $group )->item(0)->nodeValue;
				$this->attributes['description'] = $xPath->query( 'media:description', $group )->item(0)->nodeValue;
				$this->attributes['videoUrl'] = $xPath->query( 'media:content/@url[starts-with(., "http")]', $group )
														->item(0)
														->value;
				$this->attributes['thumbUrl'] = $xPath->query( 'media:thumbnail[position()=1]/@url', $group )
														->item(0)
														->value;
				return true;
			} else {
				return false;
			}
		}

	}

?>
