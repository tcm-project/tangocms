<?php

/**
 * Zula Framework Date
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
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
		 * Constructor
		 * Updates some of the date configuration values
		 *
		 * @return object
		 */
		public function __construct() {
			if ( $this->_config->has( 'date/timezone' ) ) {
				$tz = $this->_config->get( 'date/timezone' );
			}
			$this->changeTimezone( empty($tz) ? @date_default_timezone_get() : $tz );
		}

		/**
		 * Changes the default timezone to use. If no argument is provided
		 * it will attempt to get the system default.
		 *
		 * @param string $timezone
		 * @return bool
		 */
		public function changeTimezone( $timezone=null ) {
			if ( !trim( $timezone ) ) {
				$timezone = date_default_timezone_get();
			}
			return @date_default_timezone_set( $timezone );
		}

		/**
		 * Sets the default format to use.
		 *
		 * @param string $format
		 * @return bool
		 */
		public function setFormat( $format='D j M, H:i' ) {
			$this->format = (string) $format;
		}

		/**
		 * Sets if to use relative date or not. Defaults to true if
		 * no argument sent.
		 *
		 * @param bool $relative
		 * @return bool
		 */
		public function useRelative( $relative=true ) {
			$this->useRelative = (bool) $relative;
			return true;
		}

		/**
		 * Sets the cut off point for relative date (ie; how many seconds
		 * back it should continue to use relative date for)
		 *
		 * @param int $cutoff
		 * @return bool
		 */
		public function setRelativeCutoff( $cutoff ) {
			$this->relativeCutoff = abs( $cutoff );
		}

		/**
		 * Ensures that when we convert a string date, to make PHP think we
		 * are giving it a GMT/UTC time, instead of the current TZ. This fixes
		 * a few issues with changing timezones (and helps with Bug #208)
		 *
		 * @param string $format
		 * @return bool|int
		 */
		public function utcStrtotime( $format ) {
			$oldTz = date_default_timezone_get();
			date_default_timezone_set( 'UTC' );
			$stamp = strtotime( $format );
			date_default_timezone_set( $oldTz );
			return $stamp;
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
			if ( !trim( $stamp ) ) {
				$stamp = time();
			} else if ( !ctype_digit( (string) $stamp ) ) {
				// Make sure we convert the string and get PHP to think it represents UTC, not the current TZ.
				$stamp = $this->utcStrtotime( $stamp );
			}
			if ( $this->useRelative && !$overrideRelative ) {
				return $this->relativeDate( $stamp, $format );
			} else {
				if ( !trim( $format ) ) {
					$format = $this->format;
				}
				return date( $format, $stamp );
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
		public function format( $stamp=null, $format=null, $overrideRelative=false ) {
			return $this->format( $stamp, $format, $overrideRelative );
		}

		/**
		 * Calculates how many of the following are between
		 * 2 unix timestamps.
		 *
		 *	Seconds, Minutes, Hours, Days, Weeks
		 *
		 * @param int $stamp1
		 * @param int $stamp2	Defaults to the current unix-timestamp
		 * @return array
		 */
		public function difference( $stamp1, $stamp2=null ) {
			$stamp1 = (int) $stamp1;
			$stamp2 = trim($stamp2) ? (int) $stamp2 : time();
			$timeDiff = abs( $stamp2 - $stamp1 );
			$differences = array();
			// Calculate the differences for all of the parts
			$differences['years']	= (int) floor( $timeDiff / (60*60*24*365) );
			$differences['weeks'] 	= (int) floor( $timeDiff / (60*60*24*7) );
			$differences['days'] 	= (int) floor( $timeDiff / (60*60*24) );
			$differences['hours'] 	= (int) floor( ($timeDiff - ($differences['days']*60*60*24) ) / (60*60) );
			$differences['minutes']	= (int) floor( ($timeDiff - ($differences['days']*60*60*24) - ($differences['hours']*60*60) ) / 60 );
			$differences['seconds']	= $timeDiff % 60;
			$differences['unix_seconds'] = $timeDiff;
			return $differences;
		}

		/**
		 * Takes a UNIX timestamp and will create a relative date compared to
		 * the current timestamp, EG: 34 Seconds Ago, 1 Week Ago, Within 4 Days
		 *
		 * If time is outside of the cutoff, then it will return the format it was meant
		 * to be in before it came to this method.
		 *
		 * @param int $stamp
		 * @param string $format
		 * @return strng
		 */
		public function relativeDate( $stamp, $format='' ) {
			$differences = $this->difference( $stamp );
			if ( $differences['unix_seconds'] > $this->relativeCutoff ) {
				return $this->format( $stamp, $format, true );
			}
			// Configure the formats used for relative date
			$formats = array(
							'past'	=> array(
											'weeks'		=> array( 'singular' => t('1 Week Ago', Locale::_DTD), 	'plural' => t('%d Weeks Ago', Locale::_DTD) ),
											'days'		=> array( 'singular' => t('1 Day Ago', Locale::_DTD),		'plural' => t('%d Days Ago', Locale::_DTD) ),
											'hours'		=> array( 'singular' => t('1 Hour Ago', Locale::_DTD), 	'plural' => t('%d Hours Ago', Locale::_DTD) ),
											'minutes'	=> array( 'singular' => t('1 Minute Ago', Locale::_DTD), 	'plural' => t('%d Minutes Ago', Locale::_DTD) ),
											'seconds'	=> array( 'singular' => t('1 Second Ago', Locale::_DTD), 	'plural' => t('%d Seconds Ago', Locale::_DTD) ),
											),
							'future' => array(
											'weeks'		=> array( 'singular' => t('Within 1 Week', Locale::_DTD), 	'plural' => t('Within %d Weeks', Locale::_DTD) ),
											'days'		=> array( 'singular' => t('Within 1 Day', Locale::_DTD), 	'plural' => t('Within %d Days', Locale::_DTD) ),
											'hours'		=> array( 'singular' => t('Within 1 Hour', Locale::_DTD), 	'plural' => t('Within %d Hours', Locale::_DTD) ),
											'minutes'	=> array( 'singular' => t('Within 1 Minute', Locale::_DTD), 	'plural' => t('Within %d Minutes', Locale::_DTD) ),
											'seconds'	=> array( 'singular' => t('Within 1 Second', Locale::_DTD), 	'plural' => t('Within %d Seconds', Locale::_DTD) ),
											),
							);
			$format = $formats[ ($stamp > time()) ? 'future' : 'past' ];
			foreach( $differences as $key=>$val ) {
				// Get the correct precision needed
				if ( $val != 0 ) {
					$diffPrecision = $key;
					$value = $val;
					break;
				}
			}
			if ( isset( $diffPrecision, $value ) && $value > 0 ) {
				return sprintf( $format[ $diffPrecision ][ ($value>1) ? 'plural' : 'singular' ], $value );
			} else {
				return t('Now', Locale::_DTD);
			}
		}

	}

?>
