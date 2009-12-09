<?php

/**
 * Zula Framework Module (Rss)
 * --- Hooks file for listning to possible events
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Robert Clipsham
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Robert Clipsham
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Rss
 */

	class Rss_hooks extends Zula_HookBase {

		/**
		 * Constructor
		 * Calls the parent constructor to register the methods
		 *
		 * @return object
		 */
		public function __construct() {
			parent::__construct( $this );
		}

		/**
		 * Add an item to the global RSS feed
		 *
		 * @param string $feed
		 * @param string $id
		 * @param string $title
		 * @param string $link
		 * @param string $description
		 * @param array $other
		 * @return bool
		 */
		public function hookRssItemAdd( $feed, $id, $title, $link, $description, array $other=array() ) {
        	if ( $feed != 'global' ) {
				$rss = new Rss( 'global' );
				if ( !$rss->hasFeedInfo() ) {
					$st = $this->_config->get( 'config/title' );
					$rss->setFeedInfo( sprintf( t('%s - Global RSS Feed', _PROJECT_ID.'-rss' ), $st ),
										 sprintf( t('Global RSS Feed for %s', _PROJECT_ID.'-rss'), $st ),
										 $this->_router->makeFullUrl( '', '', '', 'main' )
										);
				}
				if ( !in_array( $id, $rss->getItemIds() ) ) {
					$rss->add( $id, $title, $link, $description, $other );
					if ( $rss->save() ) {
						return true;
					}
				}
				return false;
			}
			return false;
		}

		/**
		 * Edit an item in the global RSS feed
		 *
		 * @param string $feed
		 * @param string $id
		 * @param string $title
		 * @param string $link
		 * @param string $description
		 * @param array $other
		 * @return bool
		 */
		public function hookRssItemEdit( $feed, $id, $title, $link, $description, array $other=array() ) {
			if ( $feed != 'global' ) {
				$rss = new Rss( 'global' );
				$rss->edit( $id, $title, $link, $description, $other );
				if ( $rss->save() ) {
            		return true;
				}
				return false;
			}
			return false;
		}

		/**
		 * Delete an item from the global RSS feed
		 *
		 * @param string $feed
		 * @param string $id
		 * @return bool
		 */
		public function hookRssItemDelete( $feed, $id ) {
			if ( $feed != 'global' ) {
				$rss = new Rss( 'global' );
				$rss->delete( $id );
				if ( $rss->save() ) {
            		return true;
				}
				return false;
			}
			return false;
		}

		/**
		 * Provides a way for modules to easily add to an RSS feed
		 * by using this module, and the RSS lib.
		 *
		 * @param array $feeds
		 * @param array $item
		 * @return bool
		 */
		public function hookRssmodAdd( array $feeds, array $item ) {
			foreach( $feeds as $feedName=>$fDetails ) {
				$rss = new Rss( $feedName );
				$rss->setFeedInfo( $fDetails['title'], $fDetails['desc'], $fDetails['url'] );
				$rss->add( $item['id'], $item['title'], $item['url'], $item['desc'] );
				$rss->save();
			}
			return true;
		}

		/**
		 * Provides a way for modules to easily add to an RSS feed
		 * by using this module, and the RSS lib.
		 *
		 * @param array $feeds
		 * @param array $item
		 * @return bool
		 */
		public function hookRssmodEdit( array $feeds, array $item ) {
			try {
				foreach( $feeds as $feedName=>$fDetails ) {
					$rss = new Rss( is_array($fDetails) ? $feedName : $fDetails );
					if ( is_array( $fDetails ) ) {
						$rss->setFeedInfo( $fDetails['title'], $fDetails['desc'], $fDetails['url'] );
					}
					$rss->edit( $item['id'], $item['title'], $item['url'], $item['desc'] );
					$rss->save();
				}
				return true;
			} catch ( Exception $e ) {
				$this->_log->message( 'rss unable to edit item "'.$e->getMessage().'", attempting to add', Log::L_DEBUG );
				return $this->hookRssmodAdd( $feeds, $item );
			}
		}

		/**
		 * Allows modules to delete an item from an RSS feed
		 *
		 * @param array $feeds
		 * @param string $itemId
		 * @return bool
		 */
		public function hookRssmodDelete( $feeds, $itemId ) {
			if ( !is_array( $feeds ) ) {
				$feeds = array( $feeds );
			}
			foreach( $feeds as $feed ) {
				if ( Rss::feedExists( $feed ) ) {
					try {
						$rss = new Rss( $feed );
						$rss->delete( $itemId );
						$rss->save();
					} catch ( Exception $e ) {
						$this->_log->message( $e->getMessage(), Log::L_WARNING );
					}
				}
			}
			return true;
		}

		/**
		 * Add in the right RSS feed to the HTML head
		 *
		 * @param array $rData
		 * @return array
		 */
		public function hookCntrlrPreDispatch( $rData ) {
        	if ( Registry::has( 'theme' ) ) {
				if ( $rData['module'] != 'rss' && $rData['controller'] != 'feed' ) {
					// Get the default feed
					try {
						$defFeed = array();
                		$defFeed[] = $this->_config->get( 'rss/default_feed' );
						if ( !Rss::feedExists( $defFeed[0] ) ) {
                        	unset( $defFeed[0] );
                    	}
					} catch ( Config_KeyNoExist $e ) {
                		$this->_log->message( 'RSS config key "rss/default_feed" does not exist, unable to add default feed to head.', Log::L_WARNING );
					}
					// Find all the RSS feeds for the current page
					$feeds = Hooks::notifyAll( 'rss_insert_head', $rData['module'], $rData['controller'], $rData['section'] );
					if ( is_array( $feeds ) ) {
						foreach( array_filter( array_merge($defFeed, $feeds) ) as $feed ) {
							// Add all found feeds to head
							$rss = new Rss( $feed );
	                		if ( $rss->hasFeedInfo() ) {
								$details = array(
													'rel'	=> 'alternate',
													'type'	=> 'application/rss+xml',
													'href'	=> $this->_router->makeFullUrl( 'rss', 'feed', $feed ),
													'title'	=> $rss->getFeedInfo( 'title' )
												);
	                    		$this->_theme->addHead( 'link', $details );
							} else {
	                        	$this->_log->message( 'Feed "'.$feed.'" does not have feed info set.', Log::L_WARNING );
							}
						}
					}
        		}
			}
			return $rData;
		}

	}

?>
