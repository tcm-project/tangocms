<?php

/**
 * Zula Framework Date
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Date
 */

	class Date extends Zula_LibraryBase {

		/**
		 * Default format to use
		 * @var string
		 */
		protected $format = 'D j M, H:i';

		/**
		 * Use relative dates
		 * @var bool
		 */
		protected $useRelative = true;

		/**
		 * Relative date cut off value. When should the relative date stop
		 * and display a normal formated date?
		 * @var int
		 */
		protected $relativeCutoff = 1209600;

		/**
		 * UTC timezone object, used as a base
		 * @var object
		 */
		protected $utcTimezone = null;

		/**
		 * The DateTimeZone object for the correct local time
		 * @var object
		 */
		protected $timezone = null;

		/**
		 * Constructor
		 * Updates some of the date configuration values
		 *
		 * @return object
		 */
		public function __construct() {
			if ( $this->_config->has( 'date/timezone' ) ) {
				$tz = $this->_config->get( 'date/timezone' );
			}
			$this->utcTimezone = new DateTimeZone( 'UTC' );
			$this->setTimezone( empty($tz) ? @date_default_timezone_get() : $tz );
		}

		/**
		 * Changes the default timezone to use. If no argument is provided
		 * it will attempt to get the system default.
		 *
		 * @param string $timezone
		 * @return bool
		 */
		public function setTimezone( $timezone=null ) {
			if ( $timezone instanceof DateTimeZone ) {
				$timezone = $timezone->getName();
			} else if ( !trim( $timezone ) ) {
				$timezone = date_default_timezone_get();
			}
			if ( @date_default_timezone_set( $timezone ) ) {
				$this->timezone = new DateTimeZone( $timezone );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Sets the default format to use.
		 *
		 * @param string $format
		 * @return object
		 */
		public function setFormat( $format='D j M, H:i' ) {
			$this->format = (string) $format;
			return $this;
		}

		/**
		 * Sets if to use relative date or not. Defaults to true if
		 * no argument sent.
		 *
		 * @param bool $relative
		 * @return object
		 */
		public function useRelative( $relative=true ) {
			$this->useRelative = (bool) $relative;
			return $this;
		}

		/**
		 * Sets the cut off point for relative date (ie; how many seconds
		 * back it should continue to use relative date for)
		 *
		 * @param int $cutoff
		 * @return object
		 */
		public function setRelativeCutoff( $cutoff ) {
			$this->relativeCutoff = abs( $cutoff );
			return $this;
		}

		/**
		 * Takes any format strtotime() handles, or a unix timestamp and returns
		 * a DateTime object in the correct timezone.
		 *
		 * @param string|int $stamp
		 * @return DateTime
		 */
		public function getDateTime( $stamp ) {
			if ( $stamp instanceof DateTime ) {
				return $stamp;
			}
			if ( ctype_digit( (string) $stamp ) ) {
				$stamp = '@'.$stamp; # Stops DateTime::__construct() throwing an exception
			}
			$date = new DateTime( $stamp, $this->utcTimezone );
			$date->setTimezone( $this->timezone );
			return $date;
		}

		/**
		 * Generates a formated date string from a Unix Timestamp. If
		 * overrideRelative is set to true, then it will not return a
		 * relative date if the setting is normally set to true.
		 *
		 * @param int $stamp
		 * @param string $format
		 * @param bool $overrideRelative
		 * @return string
		 */
		public function format( $stamp=null, $format=null, $overrideRelative=false ) {
			if ( $this->useRelative && !$overrideRelative ) {
				return $this->relativeDate( $stamp, $format );
			} else {
				return $this->getDateTime( $stamp )
							->format( ($format ? $format : $this->format) );
			}
		}

		/**
		 * Alias to Date::format()
		 *
		 * @deprecated Deprecated as of 0.7.71
		 * @see Date::format()
		 * @param int $stamp
		 * @param string $format
		 * @param bool $overrideRelative
		 * @return string
		 */
		public function formatStamp( $stamp=null, $format=null, $overrideRelative=false ) {
			return $this->format( $stamp, $format, $overrideRelative );
		}

		/**
		 * Calculates the differences between 2 dates, which can be instances
		 * of DateTime.
		 *
		 * @param mixed $stamp1
		 * @param mixed $stamp2		Defaults to now
		 * @return array
		 */
		public function difference( $stamp1, $stamp2=null ) {
			$stamp1 = $this->getDateTime( $stamp1 );
			$stamp2 = $this->getDateTime( $stamp2 );
			if ( method_exists( $stamp1, 'diff' ) ) {
				// Use the newer 5.3 DateTime::diff() function
				$diff = (array) $stamp1->diff( $stamp2 );
			} else {
				$timeDiff = abs( $stamp2->format('U') - $stamp1->format('U') );
				$diff = array();
				$diff['y'] = (int) floor( $timeDiff / (60*60*24*365) );
				$diff['m'] = (int) floor( $timeDiff / (60*60*24*7) );
				$diff['d'] = (int) floor( $timeDiff / (60*60*24) );
				$diff['h'] = (int) floor( ($timeDiff - ($diff['d']*60*60*24) ) / (60*60) );
				$diff['i'] = (int) floor( ($timeDiff - ($diff['d']*60*60*24) - ($diff['h']*60*60) ) / 60 );
				$diff['s'] = $timeDiff;
			}
			return $diff;
		}

		/**
		 * Takes a UNIX timestamp and will create a relative date compared to
		 * the current timestamp, EG: 34 Seconds Ago, 1 Week Ago, Within 4 Days
		 *
		 * If time is outside of the cutoff, then it will return the format it was meant
		 * to be in before it came to this method.
		 *
		 * @param mixed $date
		 * @param string $format
		 * @return strng
		 */
		public function relativeDate( $date, $format=null ) {
			$date = $this->getDateTime( $date );
			$diff = $this->difference( $date );
			if ( $diff['s'] > $this->relativeCutoff ) {
				return $this->format( $date, $format, true );
			}
			// Configure the formats used for relative date
			if ( $date > new DateTime ) {
				$format = array(
								'w'	=> array( 'singular' => t('within 1 week', Locale::_DTD), 'plural' => t('within %d weeks', Locale::_DTD) ),
								'd'	=> array( 'singular' => t('within 1 day', Locale::_DTD), 'plural' => t('within %d days', Locale::_DTD) ),
								'h'	=> array( 'singular' => t('within 1 hour', Locale::_DTD), 'plural' => t('within %d hours', Locale::_DTD) ),
								'i'	=> array( 'singular' => t('within 1 minute', Locale::_DTD), 'plural' => t('within %d minutes', Locale::_DTD) ),
								's'	=> array( 'singular' => t('within 1 second', Locale::_DTD), 'plural' => t('within %d seconds', Locale::_DTD) ),
								);
			} else {
				$format = array(
								'w'	=> array( 'singular' => t('1 week ago', Locale::_DTD), 'plural' => t('%d weeks ago', Locale::_DTD) ),
								'd'	=> array( 'singular' => t('1 day ago', Locale::_DTD), 'plural' => t('%d days ago', Locale::_DTD) ),
								'h'	=> array( 'singular' => t('1 hour ago', Locale::_DTD), 'plural' => t('%d hours ago', Locale::_DTD) ),
								'i'	=> array( 'singular' => t('1 minute ago', Locale::_DTD), 'plural' => t('%d minutes ago', Locale::_DTD) ),
								's'	=> array( 'singular' => t('1 second ago', Locale::_DTD), 'plural' => t('%d seconds ago', Locale::_DTD) ),
								);
			}
			// Get the largest precision that has a value
			foreach( $diff as $key=>$val ) {
				if ( $val != 0 ) {
					$precision = $key;
					$value = $val;
					break;
				}
			}
			if ( isset( $precision, $value ) && $value > 0 ) {
				return sprintf( $format[ $precision ][ ($value>1 ? 'plural' : 'singular') ], $value );
			} else {
				return t('now', Locale::_DTD);
			}
		}

	}

?>
