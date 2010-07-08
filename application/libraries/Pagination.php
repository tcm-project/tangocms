<?php

/**
 * Zula Framework Pagination
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Pagination
 */

	class Pagination extends Zula_LibraryBase {

		/**
		 * Request path to build off, if empty use current raw request path
		 * @var string
		 */
		protected $requestPath = null;

		/**
		 * Toggles if the URL argument should be appended to the other queries
		 * @var bool
		 */
		protected $appendQuery = true;

		/**
		 * URL argument to use when generating the links
		 * @var strng
		 */
		protected $urlArgument = 'page';

		/**
		 * Total amount of items
		 * @var int
		 */
		protected $totalItems = 0;

		/**
		 * Number of items that will be displayed per page
		 * @var int
		 */
		protected $perPage = 10;

		/**
		 * Number of links to show before and after the current page
		 * @var int
		 */
		protected $numLinks = 2;

		/**
		 * Formats used with sprintf when creating the links
		 * @var array
		 */
		protected $formats = array(
									'overall'	=> '<ul class="pagination">%1$s</ul>',
									'first'		=> '<li class="first"><a href="%1$s">&laquo; %2$s</a></li>',
									'previous'	=> '<li class="previous"><a href="%1$s">&laquo; %2$s</a></li>',
									'current'	=> '<li class="current">%1$s</li>',
									'digit'		=> '<li class="digit"><a href="%1$s">%2$s</a></li>',
									'next'		=> '<li class="next"><a href="%1$s">%2$s &raquo;</a></li>',
									'last'		=> '<li class="last"><a href="%1$s">%2$s &raquo;</a></li>',
									);

		/**
		 * Constructor
		 * Sets the paramters that will be needed later
		 *
		 * @param int $totalItems
		 * @param int $perPage
		 * @param string $urlArgument
		 * @return object
		 */
		public function __construct( $totalItems, $perPage=10, $urlArgument='page' ) {
			$this->totalItems = abs( $totalItems );
			$this->perPage = abs( $perPage );
			$this->urlArgument = trim($urlArgument) ? $urlArgument : 'page';
		}

		/**
		 * Sets the request path pagination should use when building links.
		 * By default, it will use the current raw request path with URL arguments
		 *
		 * @param string $path
		 * @return object
		 */
		public function setRequestPath( $path ) {
			$this->requestPath = $path;
			return $this;
		}

		/**
		 * Sets if the URL argument value should be appended to other URL
		 * arguments, or to construct a new URL with 1 URL argument
		 *
		 * @param bool $append
		 * @return object
		 */
		public function appendQuery( $append=true ) {
			$this->appendQuery = (bool) $append;
			return $this;
		}

		/**
		 * Builds the pagination links with all of the correct configuration
		 * paramaters set.
		 *
		 * @return string
		 */
		public function build() {
			if ( $this->totalItems == 0 || $this->perPage == 0 ) {
			   return '';
			}
			// Calculate how many pages there will be of the data
			$numPages = ceil( $this->totalItems / $this->perPage );
			if ( $numPages <= 1 ) {
				return '';
			}
			try {
				$curPage = abs( $this->_input->get($this->urlArgument) );
				if ( $curPage > $this->totalItems ) {
					// There are more pages than items, set current page to last one
					$curPage = $numPages - 1;
				}
			} catch ( Input_KeyNoExist $e ) {
				$curPage = 1;
			}
			/**
			 * Build all of the markup needed for the provided data and calculations above
			 */
			$pagination = array();
			// Build next and previus
			if ( $curPage > $this->numLinks ) {
				$pagination[] = sprintf( $this->formats['first'], $this->makeUrl(), t('First', I18n::_DTD) );
			}
			if ( $curPage - $this->numLinks >= 0 ) {
				$pagination[] = sprintf( $this->formats['previous'], $this->makeUrl( $curPage-1 ), t('Previous', I18n::_DTD) );
			}
			// Calculate the start and end numbers for the digit links
			$digits = array(
							'start'	=> (($curPage - $this->numLinks) > 0) ? $curPage - ($this->numLinks - 1) : 1,
							'end'	=> (($curPage + $this->numLinks) < $numPages) ? $curPage + $this->numLinks : $numPages,
							);
			for( $i = $digits['start']-1; $i <= $digits['end']; $i++ ) {
				if ( $i > 0 && $curPage == $i ) {
					// The digit is the current page
					$pagination[] = sprintf( $this->formats['current'], $i );
				} else if ( $i > 0 ) {
					$page = $i == 0 ? '' : $i;
					$pagination[] = sprintf( $this->formats['digit'], $this->makeUrl( $page ), $i, t('Page', I18n::_DTD) );
				}
			}
			// Build next and last
			if ( $curPage < $numPages ) {
				$pagination[] = sprintf( $this->formats['next'], $this->makeUrl( $curPage+1 ), t('Next', I18n::_DTD) );
			}
			if ( $curPage + $this->numLinks < $numPages ) {
				$pagination[] = sprintf( $this->formats['last'], $this->makeUrl( $numPages ), t('Last', I18n::_DTD) );
			}
			return sprintf( $this->formats['overall'], implode( ' ', $pagination ) );
		}

		/**
		 * Makes a correct URL needed for all of the pagination links
		 *
		 * @param int $page
		 * @return string
		 */
		protected function makeUrl( $page=1 ) {
			$page = abs( $page );
			if ( $this->requestPath ) {
				$url = new Router_Url( $this->requestPath );
			} else {
				// Use the current parsed URL as a base
				$url = new Router_Url( $this->_router->getRawRequestPath() );
				if ( $this->appendQuery ) {
					$url->queryArgs( $this->_router->getParsedUrl()->getAllQueryArgs() );
				}
			}
			if ( $page > 1 ) {
				$url->queryArgs( array($this->urlArgument => $page) );
			} else {
				$url->removeQueryArgs( $this->urlArgument );
			}
			return $url->make();
		}

	}

?>
