<?php

/**
 * Zula Framework RSS
 * --- Allows for modules to interact with RSS Feeds
 *
 * @author Robert Clipsham
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009 Robert Clipsham
 * @licence http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Rss
 */

	class Rss extends Zula_LibraryBase implements Iterator {

		/**
		 * Directory to where RSS files are stored
		 * @var string
		 */
		protected $rssDir = '';

		/**
		 * Feed Name
		 * @var string
		 */
		protected $name = '';

		/**
		 * XML Document
		 * @var DOMDocument
		 */
		protected $feed;

		/**
		 * All items within the RSS feed
		 * @var array
		 */
		protected $items = array();

		/**
		 * Holds if the RSS feed is a remote file
		 * @var bool
		 */
		protected $isRemote = false;

		/**
		 * Constructor
		 *  - Create or load an RSS Feed
		 *
		 * @param string $name - name of the feed
		 * @return object
		 */
		public function __construct( $name ) {
			$this->rssDir = $this->_zula->getDir( 'tmp' ).'/rss';
			if ( !is_dir( $this->rssDir ) ) {
				zula_make_dir( $this->rssDir );
			}
			$this->name = $name;
			$this->isRemote = (bool) strpos( $name, '://' );
			// Create the main DomDocument
			$this->feed = new DOMDocument( '1.0', 'utf-8' );
			$this->feed->preserveWhiteSpace = false;
			$this->feed->formatOutput = true;
			if ( $this->isRemote == false ) {
				if ( file_exists( $this->rssDir.'/'.$this->name.'.xml' ) ) {
					$this->feed->load( $this->rssDir.'/'.$this->name.'.xml' );
					$this->getItems();
				} else {
					$this->feed->appendChild( $this->feed->createElement('rss') )->setAttribute( 'version', '2.0' );
				}
			} else {
				$this->feed->load( $this->name );
				$this->getItems();
			}
		}

		/**
		 * Check if the given feed exists
		 *
		 * @param string $feed
		 * @return bool
		 */
		static public function feedExists( $feed ) {
			return file_exists( Registry::get( 'zula' )->getDir( 'tmp' ).'/rss/'.$feed.'.xml' );
		}

		/**
		 * Get feed location
		 *  - Return the location that RSS Feeds are saved to
		 *
		 * @return string
		 */
		public function getFeedLocation() {
			return $this->rssDir;
		}

		/**
		 * SPL Iterator methods, to provide an easy way to iterate
		 * over all RSS items within the feed.
		 */
		public function rewind() {
			reset( $this->items );
		}

		public function current() {
			return current( $this->items );
		}

		public function key() {
			return key( $this->items );
		}

		public function next() {
			return next( $this->items );
		}

		public function valid() {
			return $this->current() !== false;
		}

		/**
		 * Generates an array of all the items within the RSS feed
		 *
		 * @return array|bool
		 */
		protected function getItems() {
        		$channel = $this->feed->getElementsByTagName( 'channel' );
			if ( $channel->length == 0 ) {
        		    	return false;
			}
			$items = $channel->item(0)->getElementsByTagName( 'item' );
			if ( $items->length == 0 ) {
				return false;
			} else {
				// Loop through all items in the feed
				for( $i = 0; $i < $items->length; $i++ ) {
			        	$node = $items->item( $i );
				        $item = array();
					// Loop through each of the elements in the item
					$elements = $node->getElementsByTagName( '*' );
					for( $l = 0; $l < $elements->length; $l++ ) {
		        		    	$element = $elements->item( $l );
						$item[ $element->tagName ] = $element->nodeValue;
					}
					$this->items[ $node->getAttribute( 'xml:id' ) ] = $item;
				}
				return $this->items;
			}
		}

		/**
		 * Saves the RSS feed back to a file;
		 *
		 * @return bool
		 */
		public function save() {
			if ( $this->isRemote ) {
        		    	throw new Rss_RemoteFeed;
			} else if ( !is_dir( $this->rssDir ) ) {
				zula_make_dir( $this->rssDir );
			}
			if ($this->feed->save( $this->rssDir.'/'.$this->name.'.xml' ) ) {
				Hooks::notifyAll( 'rss_feed_save', $this->name );
	        	   	$this->_log->message( 'Saved RSS feed "'.$this->name.'"', Log::L_DEBUG );
				return true;
			}
			$this->_log->message( 'Unable to save RSS feed "'.$this->name.'"', Log::L_WARNING );
			return false;
		}

		/**
		 * Sets genreal information about the RSS feed.
		 *
		 * @param string $title 	- Title of the feed
		 * @param string $desc 		- A description of the feed
		 * @param string $link 		- A link to module that's relevant to the feed made
		 *							  with Router_(sef|standard)::make_full_url()
		 * @param array $details 	- An array of other feed details, see
		 *							  http://cyber.law.harvard.edu/rss/rss.html#optionalChannelElements]
		 * @return bool
		 */
		public function setFeedInfo( $title, $desc, $link, array $details=array() ) {
			if ( $this->isRemote ) {
		            	throw new Rss_RemoteFeed;
			}
			// Merge 2 arrays together to get all of the needed elements to be added/edited
			$newNodes = array(
								'title'			=> $title,
								'description'	=> $desc,
								'link'			=> $link,
								);
			$neededElements = array_merge( $newNodes, $details );
			$channels = $this->feed->getElementsByTagName( 'channel' );
			if ( $channels->length == 0 ) {
				// Create the main 'channel' node and add RSS generator on.
            			$channel = $this->feed->getElementsByTagName( 'rss' )->item(0)->appendChild( new DOMElement( 'channel' ) );
            			$channel->appendChild( new DOMElement( 'generator', _PROJECT_NAME.' '._PROJECT_VERSION.' (Zula)' ) );
            		} else {
            			$channel = $channels->item(0);
           		}
            		foreach( $neededElements as $key=>$val ) {
				// Replace any <![CDATA[ or ]]> with an encoded version so it doesn't cause issues.
        		    	$val = str_replace( array( '<![CDATA[', ']]>' ), array( '&lt;![CDATA[', ']]&gt;' ), $val );
				// Create a CDATA Section or Text Node from the item.
				$node = zula_needs_cdata($val) ? $this->feed->createCDATASection( $val ) : $this->feed->createTextNode( $val );
        		    	$element = $channel->getElementsByTagName( $key );
        		    	if ( $element->length > 0 ) {
            				// Update existing
            				$element = $element->item(0);
            				$element->nodeValue = '';
            				$element->appendChild( $node );
            			} else {
            				// Add new one
            				$channel->appendChild( new DOMElement( $key ) )->appendChild( $node );
            			}
            		}
			Hooks::notifyAll( 'rss_set_info', $this->name, $title, $desc, $link, $details );
			return true;
		}

		/**
		 * Get feed info
		 *  - Get information about a feed
		 *
		 * @param string $element - The element to get
		 * @return mixed
		 */
		public function getFeedInfo( $element='' ) {
			if ( !$this->hasFeedInfo() ) {
            			throw new Rss_NoFeedInfo;
			}
			$channel = $this->feed->getElementsByTagName( 'channel' )->item(0);
			// Return all elements an their value
			if ( empty( $element ) ) {
            			$info = array();
				foreach( $channel->getElementsByTagName( '*' ) as $element ) {
                			$info[ $element->tagName ] = $element->nodeValue;
				}
				return $info;
			}
			// Return the specified elements contents
			$el = $channel->getElementsByTagName( $element );
			if ( $el->length == 0 ) {
            			throw new Rss_NoSuchElement;
			}
			return $el->item(0)->nodeValue;
		}

		/**
		 * Has feed info
		 * - Check if the feed has info set yet
		 *
		 * @return bool
		 */
		public function hasFeedInfo() {
        		$channels = $this->feed->getElementsByTagName( 'channel' );
        		return (bool) $channels->length;
		}

		/**
		 * Get Item IDs
		 *  - Get the ID's of all items in the feed
		 *
		 * @return array
		 */
		public function getItemIds() {
        		$channel = $this->feed->getElementsByTagName( 'channel' );
			if ( $channel->length == 0 ) {
            			return array();
			}
			$items = $channel->item(0)->getElementsByTagName( 'item' );
			if ( $items->length == 0 ) {
				return array();
			} else {
				$ids = array();
				// Loop through all items in the feed
				for( $i = 0; $i < $items->length; $i++ ) {
		        		$node = $items->item( $i );
                			$ids[] = $node->getAttribute( 'xml:id' );
				}
				return $ids;
			}
		}

		/**
		 * Prepares a description by cutting it down to a max length, removing
		 * possible shebang (from editor) and other small jazzy things
		 *
		 * @param string $str
		 * @return string
		 */
		protected function prepareDescription( $str ) {
			$editor = new Editor( $str );
			$editor->preParse();
			return $editor->parse();
		}

		/**
	 	 * Add
	 	 * - Add an item to a feed
	 	 *
		 * @param string $id - Unique id for the item
		 * @param string $title - Title for the item
		 * @param string $link - A link to the item made with Router->make_full_url()
		 * @param string $desc - The main content. This should just be a clip it.
		 * @param array $other - An array of other details in the style 'tag_name' => 'content'
		 *						 or 'tag_name' => array( 'attribute' => 'attr_content',
		 * 												 'content'   => 'content' )
	 	 */
   		public function add( $id, $title, $link, $desc, array $other=array() ) {
			if ( $this->isRemote ) {
            			throw new Rss_RemoteFeed;
			}
			$channels = $this->feed->getElementsByTagName( 'channel' );
			if ( $channels->length == 0 ) {
            			throw new Rss_NoFeedInfo;
			}
            		$channel = $channels->item(0);
			/**
			 * Check that there are not more items than the set value, if
			 * so, delete the last one to make room for this new one.
			 */
			$list = $channel->getElementsbyTagName( 'item' );
			$itemsPerFeed = $this->_config->get( 'rss/items_per_feed' );
			if ( $itemsPerFeed &&  $list->length >= $itemsPerFeed ) {
				$itemId = $this->feed->getElementById( $list->item( $list->length-1 )->getAttribute( 'xml:id' ) ); # ID of element to remove
				$channel->removeChild( $itemId );
			}
			/**
			 * Add the new item in to the top of the document with the
			 * correct ID and details provided
			 */
			if ( $list->length > 0 ) {
				$item = $channel->insertBefore( new DOMElement( 'item' ), $list->item(0) );
			} else {
				$item = $channel->appendChild( new DOMElement( 'item' ) );
			}
			// Update $this->items
			$other = array_merge( $other, array( 'title' 		=> $title,
							     'link'		=> $link,
							     'guid'		=> $link,
							     'description'	=> $this->prepareDescription( $desc ),
							     'pubDate'		=> date( 'r' ),
							   )
					);
			foreach( $other as $name=>$value ) {
				if ( is_array( $value ) ) {
					$value = $value[ 'content' ];
				}
				$this->items[ $id ][ $name ] = $value;
			}
			// Set id for item
			$item->setAttribute( 'xml:id', $id );
			// Append other values
			foreach( $other as $key=>$value ) {
				if ( is_array( $value ) ) {
					if ( zula_needs_cdata( $value['content'] ) ) {
						// Replace any <![CDATA[ or ]]> with an encoded version so it doesn't cause issues.
            					$val = str_replace( array( '<![CDATA[', ']]>' ), array( '&lt;![CDATA[', ']]&gt;' ), $value );
						$value['content'] = $this->feed->createCDATASection( $value['content'] );
					} else {
						$value['content'] = $this->feed->createTextNode( $value['content'] );
					}
					$element = $item->appendChild( new DOMElement( $key ) )->appendChild( $value['content'] );
					// Unset content so it isn't added as an attribute too
					unset( $value['content'] );
					// Set attributes for the element
					foreach( $value as $attr=>$val ) {
						$element->setAttribute( $attr, $val );
					}
				} else {
					// No attributes need to be set
					// Replace any <![CDATA[ or ]]> with an encoded version so it doesn't cause issues.
		            		$val = str_replace( array( '<![CDATA[', ']]>' ), array( '&lt;![CDATA[', ']]&gt;' ), $value );
					$value = zula_needs_cdata( $value ) ? $this->feed->createCDATASection( $value ) : $this->feed->createTextNode( $value );
					$item->appendChild( new DOMElement( $key ) )->appendChild( $value );
				}
			}
			Hooks::notifyAll( 'rss_item_add', $this->name, $id, $title, $link, $desc, $other );
		}

		/**
		 * Edit
		 *  - Edit an RSS Item
		 *
		 * @param string $id
		 * @param string $title
		 * @param string $link
		 * @param string $desc
		 * @param array $other
		 * @see Rss->add() above
		 * @return bool
		 */
		public function edit( $id, $title, $link, $desc=null, array $other=array() ) {
			if ( $this->isRemote ) {
            			throw new Rss_RemoteFeed;
			}
        		$channels = $this->feed->getElementsByTagName( 'channel' );
			if ( $channels->length == 0 ) {
           			throw new Rss_NoFeedInfo;
			}
			// Get the item that we'll be editing
			$item = $this->feed->getElementById( $id );
			if ( is_null( $item ) ) {
				$this->_log->message( 'RSS could not edit item "'.$id.'" as it does not exist', Log::L_INFO );
				throw new Rss_ItemNoExist( 'unable to edit item "'.$id.'" as it does not exist' );
			}
			// Save repeating code, get the foreach loop to handle them
			$other['title'] = $title;
			$other['link']  = $link;
			$other['guid']  = $link;
			$other['description'] = is_null($desc) ? null : $this->prepareDescription( $desc );
			$other = array_filter( $other );
			// Update $this->items
			foreach( $other as $name=>$value ) {
            			if ( is_array( $value ) ) {
                			$value = $value[ 'content' ];
				}
				$this->items[ $id ][ $name ] = $value;
			}
			// Loop over all elements and update them
			foreach( $other as $key=>$value ) {
				$tmpNode = $item->getElementsByTagName( $key )->item(0);
				// Remove the content of the node. Causes issues if you append straight off
				$tmpNode->nodeValue = '';
				if ( is_array( $value ) ) {
		                   	if ( zula_needs_cdata( $value['content'] ) ) {
						// Replace any <![CDATA[ or ]]> with an encoded version so it doesn't cause issues.
            					$val = str_replace( array( '<![CDATA[', ']]>' ), array( '&lt;![CDATA[', ']]&gt;' ), $value );
                			       	$child = $this->feed->createCDATASection( $value['content'] );
					} else {
			                       	$child = $this->feed->createTextNode( $value['content'] );
					}
					$element = $tmpNode->appendChild( $child );
					// Unset the content so it isn't added again later
					unset( $value['content'] );
					// Set attributes for the element
					foreach( $value as $attr=>$value ) {
                       				$element->setAttribute( $attr, $val );
					}
				} else if ( zula_needs_cdata( $value ) ) {
					// Replace any <![CDATA[ or ]]> with an encoded version so it doesn't cause issues.
            				$val = str_replace( array( '<![CDATA[', ']]>' ), array( '&lt;![CDATA[', ']]&gt;' ), $value );
            			       	$tmpNode->appendChild( $this->feed->createCDATASection( $value ) );
				} else {
                   			$tmpNode->appendChild( $this->feed->createTextNode( $value ) );
				}
			}
			Hooks::notifyAll( 'rss_item_edit', $this->name, $id, $title, $link, $desc, $other );
			return true;
		}

		/**
		 * Delete
		 *  - Delete the given item from a feed
		 *    if no item is given, the feed is deleted
		 *
		 * @param string $id
		 * @return bool
		 */
		public function delete( $id=null ) {
			if ( $this->isRemote ) {
            			throw new Rss_RemoteFeed;
			}
			if ( is_null( $id ) ) {
				if ( zula_is_deletable( $this->rssDir . $this->name ) ) {
					// Call hooks
					Hooks::notifyAll( 'rss_feed_delete', $this->rssDir, $this->name );
					return unlink( $this->rssDir . $this->name );
				}
				$this->_log->message( 'Unable to delete RSS Feed "'. $this->name .'".', LOG::L_WARNING );
				return false;
			}
			$channels = $this->feed->getElementsByTagName( 'channel' );
			if ( $channels->length == 0 ) {
           			throw new Rss_NoFeedInfo;
			}
           		$channel = $channels->item(0);
			// Find element to delete
	           	$node = $this->feed->getElementById( $id );
			if ( is_null( $node ) ) {
        		       	return false;
			}
			$channel->removeChild( $node );
			unset( $this->items[ $id ] );
			Hooks::notifyAll( 'rss_item_delete', $this->name, $id );
			return true;
		}

		/**
		 * Add Namespace
		 *  - Add a custom namespace to the feed
		 *
		 * @param string $type
		 * @param string $name
		 * @param string $url
		 */
		public function addNamespace( $type, $name=null, $url=null ) {
        		$root = $this->feed->getElementsByTagName( 'rss' )->item(0);
			switch ( $type ) {
				case 'media':
					$url = 'http://search.yahoo.com/mrss/';
					break;
				case 'dc':
					$url = 'http://purl.org/dc/elements/1.1/';
					break;
				case 'itunes':
					$url = 'http://www.itunes.com/dtds/podcast-1.0.dtd';
					break;
				case 'content':
					$url = 'http://purl.org/rss/1.0/modules/content/';
					break;
				case 'wdf':
					$url = 'http://wellformedweb.org/CommentAPI/';
					break;
				case 'creativeCommons':
					$url = 'http://backend.userland.com/creativeCommonsRssModule';
					break;
				case 'atom':
					$url = 'http://www.w3.org/2005/Atom';
					break;
				case 'taxo':
					$url = 'http://purl.org/rss/1.0/modules/taxonomy/';
					break;
				case 'syn':
					$url = 'http://purl.org/rss/1.0/modules/syndication/';
					break;
				case 'rdf':
					$url = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
					break;
				case 'custom':
                	$type = $name;
					break;
				default:
					throw new Rss_NoSuchNameSpace;
					break;
			}
			$root->setAttribute( 'xmlns:'.$type, $url );
		}

	}
?>
