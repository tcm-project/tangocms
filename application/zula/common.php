<?php

/**
 * Zula Framework Common Functions
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @author: Evangelos Foutras
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula
 */

	/**
	 * Unicode: Returns the number of characters in a string
	 *
	 * @param string $string
	 * @return integer
	 */
	function zula_strlen( $string ) {
		return UNICODE_MBSTRING === true ? mb_strlen( $string ) : utf8_strlen( $string );
	}

	/**
	 * Unicode: Returns the position of the first occurrence of needle in haystack
	 *
	 * @param string haystack
	 * @param string needle
	 * @param int offset
	 * @return integer or false on failure
	 */
	function zula_strpos( $haystack, $needle, $offset=null ) {
		if ( UNICODE_MBSTRING === true ) {
			return ( $offset === null ) ? mb_strpos( $haystack, $needle ) : mb_strpos( $haystack, $needle, $offset );
		} else {
			return ( $offset === null ) ? utf8_strpos( $haystack, $needle ) : utf8_strpos( $haystack, $needle, $offset );
		}
	}

	/**
	 * Unicode: Returns the position of the last occurrence of needle in haystack
	 *
	 * @param string haystack
	 * @param string needle
	 * @param int offset
	 * @return integer or false on failure
	 */
	function zula_strrpos( $haystack, $needle, $offset=null ) {
		if ( UNICODE_MBSTRING === true ) {
			return ( $offset === null ) ? mb_strrpos( $haystack, $needle ) : mb_strrpos( $haystack, $needle, $offset );
		} else {
			return ( $offset === null ) ? utf8_strrpos( $haystack, $needle ) : utf8_strrpos( $haystack, $needle, $offset );
		}
	}

	/**
	 * Unicode: Returns part of a string. Follows the same behaviour as PHP's substr function
	 *
	 * @param string $string
	 * @param int $start
	 * @param int $length
	 * @return string
	 */
	function zula_substr( $string, $start, $length=null ) {
		if ( UNICODE_MBSTRING === true ) {
			return ( $length === null ) ? mb_substr( $string, $start ) : mb_substr( $string, $start, $length ) ;
		} else {
			return utf8_substr( $string, $start, $length );
		}
	}

	/**
	 * Unicode: Make a UTF8 string lowercase
	 *
	 * @param string $string
	 * @return string
	 */
	function zula_strtolower( $string ) {
		return UNICODE_MBSTRING === true ? mb_strtolower( $string ) : utf8_strtolower( $string );
	}

	/**
	 * Unicode: Make a UTF8 string uppercase
	 *
	 * @param string $string
	 * @return string
	 */
	function zula_strtoupper( $string ) {
		return UNICODE_MBSTRING === true ? mb_strtoupper( $string ) : utf8_strtoupper( $string );
	}

	/**
	 * Unicode: Capitalize the first letter of a UTF8 string
	 *
	 * @param string $string
	 * @return string
	 */
	function zula_ucfirst( $string ) {
		$length = zula_strlen( $string );
		if ( $length === 0 ) {
			return '';
		} else if ( $length === 1 ) {
			return zula_strtoupper( $string );
		} else {
			return zula_strtoupper( zula_substr( $string, 0, 1 ) ).zula_substr( $string, 1 );
		}
	}

	/**
	 * Translates a string in the current domain, or the domain
	 * provided as the second argument.
	 *
	 * @param string $string
	 * @param string $textDomain
	 * @return string
	 */
	function t( $string, $textDomain=null ) {
		if ( Registry::has( 'i18n' ) ) {
			return Registry::get( 'i18n' )->t( $string, $textDomain );
		} else {
			trigger_error( 't() no i18n engine has currently been loaded', E_USER_WARNING );
			return $string;
		}
	}

	/**
	 * Plural version of t()
	 *
	 * @param string $string1
	 * @param string $string2
	 * @param int $n
	 * @param string $textDomain
	 * @return string
	 */
	function nt( $string1, $string2, $n, $textDomain=null ) {
		if ( Registry::has( 'i18n' ) ) {
			return Registry::get( 'i18n' )->nt( $string1, $string2, $n, $textDomain );
		} else {
			trigger_error( 'nt() no i18n engine has currently been loaded', E_USER_WARNING );
			return $string1;
		}
	}

	/**
	 * Produces HTML output for a fatal error message, and kill script
	 *
	 * @param string $title
	 * @param string $body
	 * @return void
	 */
	function zula_fatal_error( $title, $body ) {
		$format = <<<ERR
<!DOCTYPE HTML>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>%1\$s</title>
	<style>
		body {
			background-color: #FBFBFB;
			font-family: "Lucida Grande", Arial, Verdana, sans-serif;
			margin: 10px;
		}
		h1 { font-size: 1.4em; }
		p { font-size: 0.9em; }
		hr {
			border: none;
			background-color: #C0C0C0;
			color: #C0C0C0;
			height: 1px;
		}
	</style>
</head>
<body>%2\$s</body>
</html>
ERR;
		if ( !headers_sent() ) {
			header( 'HTTP/1.1 503 Service Unavailable', true, 503 );
			header( 'Content-Type: text/html; charset=utf-8' );
		}
		printf( $format, $title, $body );
		die;
	}

	/**
	 * Debug print function to aid with debugging of values
	 * It can either use print_r() or var_dump() and can also
	 * halt execution of the script
	 *
	 * @param mixed $var
	 * @param bool $die 		Should the script be killed once printed?
	 * @param bool $varDump	Should var_dump be used?
	 * @return void
	 */
	function zula_debug_print( $var, $die=false, $useVarDump=true ) {
		echo '<pre>';
		$useVarDump ? var_dump($var) : print_r($var);
		echo '</pre>';
		if ( $die ) die;
	}

	/**
	 * Adds the correct header to zula_redirect to a specific URL
	 *
	 * @param string $url
	 * @param int $httpStatus
	 * @return bool
	 */
	function zula_redirect( $url, $httpStatus=303 ) {
		$zulaMode = Registry::get( 'zula' )->getMode();
		if ( $zulaMode == 'normal' ) {
			if ( $url instanceof Router_Url ) {
				$url = $url->makeFull('&');
			}
			header( 'Location: '.$url, true, $httpStatus );
			return true;
		} else if ( $zulaMode == 'ajax' ) {
			Registry::get( 'log' )->message( 'unable to redirect whilst in an AJAX request', Log::L_WARNING );
		} else if ( $zulaMode == 'cli' ) {
			Registry::get( 'event' )->error( 'unable to redirect in CLI mode' );
		}
		return false;
	}

	/**
	 * Collction of checks to see if the server environemnt supports
	 * certain features.
	 *
	 * @param string $feature
	 * @return bool
	 */
	function zula_supports( $feature ) {
		static $checks = array(
							'zipExtraction'	=> null,
							'tarExtraction'	=> null,
							);
		if ( !array_key_exists( $feature, $checks ) ) {
			trigger_error( 'zula_supports() unknown feature', E_USER_NOTICE );
			return false;
		} else if ( $checks[ $feature ] === null ) {
			switch( $feature ) {
				case 'zipExtraction':
					$checks[ $feature ] = extension_loaded( 'zip' );
					break;
				case 'tarExtraction':
					if (
						strtoupper( substr(PHP_OS, 0, 3) ) !== 'WIN' &&
						function_exists('shell_exec') && shell_exec('tar --version &> /dev/null; echo -n $?') === '0'
					) {
						$checks[ $feature ] = true;
					} else {
						$checks[ $feature ] = false;
					}
					break;
			}
		}
		return $checks[ $feature ];
	}

	/**
	 * Takes a numeric value and returns the correct English word
	 * such as 'odd' or 'even'. If set to return a numeric value
	 * then it will return 0 or 1 for odd and even respectively.
	 *
	 * @param int $value
	 * @param bool $numeric
	 * @return string|int
	 */
	function zula_odd_even( $value, $numeric=false ) {
		if ( $value % 2 == 0 ) {
			return $numeric ? 0 : 'even';
		} else {
			return $numeric ? 1 : 'odd';
		}
	}

	/**
	 * Generates a salt to be used with hash algorithms
	 * or anything else that has a min length of 8 and a
	 * max length of 32
	 *
	 * @return string
	 */
	function zula_make_salt() {
		$saltLen = rand( 8, 32 );
		$salt = '';
		for( $i = 0; $i < $saltLen; $i++ ) {
			$salt .= chr( rand(35, 126) );
		}
		$invalid = array(
						'{'	=> '£',
						'}'	=> '*',
						'|' => '%',
						'&'	=> '^',
						'~'	=> 4,
						'!'	=> ':',
						'['	=> '<',
						'('	=> 9,
						')'	=> 'z',
						);
		return str_replace( array_keys($invalid), array_values($invalid), $salt );
	}

	/**
	 * Hashes a string with the selected algorithm using
	 * the HMAC method of salting.
	 *
	 * The reason for the $algo param being blank is that
	 * if not provided, it will use the algo in the main
	 * Zula configuration, or will default to SHA512 if
	 * that does not exist.
	 *
	 * @param string $phrase
	 * @param string $salt
	 * @param string $algo
	 * @return string
	 */
	function zula_hash( $phrase, $salt=null, $algo=null ) {
		if ( is_null( $algo ) ) {
			$algo = Registry::get( 'config' )->get( 'hashing/method' );
		}
		if ( is_null( $salt ) ) {
			$salt = Registry::get( 'config' )->get( 'hashing/salt' );
		}
		return hash_hmac( $algo, $phrase, $salt );
	}

	/**
	 * zula_nls2p (New lineS to (2) Paragraph turns 2 or more line breaks
	 * into a paragraph.
	 *
	 * @param string $str
	 * @return string
	 */
	function zula_nls2p( $str ) {
		$str = preg_replace( '#([\r\n]\s*?[\r\n])+#', '</p><p>', $str );
		return str_replace( array('<p></p>', '<br />'), array('', '<br>'), '<p>'.nl2br($str).'</p>' );
	}

	/**
	 * Very similar to array_pop() but will can pop more than 1 element of
	 * the end of an aray
	 *
	 * @param array $array
	 * @param int $amount
	 * @return array
	 */
	function zula_array_multi_pop( array $array, $amount=1 ) {
		for( $i=0; $i < $amount; $i++ ) {
			if ( !is_array( $array ) ) {
				return $array;
			}
			array_pop( $array );
		}
		return $array;
	}

	/**
	 * Creates a random, hopefully unique 'key' to be used for
	 * say a reset code or anything else where you need a unique key.
	 *
	 * @param int $length
	 * @return string
	 */
	function zula_create_key( $length=48 ) {
		$length = (int) $length;
		if ( empty( $length ) ) {
			$length = 48;
		}
		$resetCode = trim( chunk_split( uniqid( rand(), true ), 2, '-' ), '-' );
		while( strlen( $resetCode ) != $length ) {
			if ( strlen( $resetCode ) > $length ) {
				$resetCode = substr( $resetCode, 0, -1 );
			} else {
				$resetCode .= '0';
			}
		}
		return substr($resetCode, -1) == '-' ? substr($resetCode, 0, -1).'0' : $resetCode;
	}

	/**
	 * Takes the default implode() and makes it more advanced by
	 * allowing you to set a prefix and suffix.
	 *
	 * @param array $array
	 * @param string $prefixGlue
	 * @param string $suffixGlue
	 * @return string|bool
	 */
	function zula_implode_adv( array $array, $prefixGlue, $suffixGlue=NULL ) {
		if ( empty( $suffixGlue ) ) {
			return implode( $prefixGlue, $array );
		}
		foreach( $array as $key=>$val ) {
			$array[ $key ] = $prefixGlue.$val.$suffixGlue;
		}
		return implode( '', $array );
	}

	/**
	 * Converts a bool to the correct worded version
	 *
	 * @param bool $bool
	 * @return string
	 */
	function zula_bool2str( $bool ) {
		return (bool) $bool ? 'true' : 'false';
	}

	/**
	 * Converts a string of a bool, ie 'true' to the
	 * correct bool value
	 *
	 * @param string $string
	 * @return string
	 */
	function zula_str2bool( $string ) {
		return $string === 'true' ? true : false;
	}

	/**
	 * Takes a string and creates a short zula_snippet/summary from it
	 *
	 * @param string $summary
	 * @param int $charLimit
	 * @param bool $ellipsis	Should ellipsis (...) be added to the end?
	 * @return string
	 */
	function zula_snippet( $summary, $charLimit=400, $ellipsis=false ) {
		$summary = str_replace( array("\n", "\r"), ' ', $summary );
		$charLimit = abs( $charLimit );
		if ( !$charLimit || zula_strlen( $summary ) < $charLimit ) {
			return $summary;
		} else {
			$str = trim( zula_substr( $summary, 0, $charLimit ) );
			return $str.($ellipsis ? '&#8230;' : '');
		}
	}

	/**
	 * Common way of cleaning a string for things like
	 * Article title and names for URLS etc etc
	 *
	 * @param string $string
	 * @return string
	 */
	function zula_clean( $string ) {
		static $transliteration = null;
		if ( !is_array( $transliteration ) ) {
			$transFile = Registry::get( 'zula' )->getDir( 'zula' ).'/transliteration.ini';
			if ( is_file( $transFile ) && is_readable( $transFile ) ) {
				$transliteration = parse_ini_file( $transFile );
			}
		}
		$string = str_replace( array_keys($transliteration), array_values($transliteration), $string );
		$clean = zula_strtolower( preg_replace( '#[^A-Z0-9_\-.!]#i', '-', $string) );
		return trim( preg_replace( '#-{2,}#', '-', $clean ),'- ' );
	}

	/**
	 * Finds the mime type of a file using either the FileInfo
	 * extension, the 'file' or deprecated mime_content_type
	 *
	 * Will return false if an error occured.
	 *
	 * @param string $file
	 * @return string|bool
	 */
	function zula_get_file_mime( $file ) {
		if ( !is_file( $file ) || !is_readable( $file ) ) {
			trigger_error( 'zula_get_file_mime() file "'.$file.'" does not exist or is not readable', E_USER_WARNING );
			return false;
		}
		$libLog = Registry::get( 'log' );
		$file = realpath( $file );
		if ( extension_loaded( 'fileinfo' ) ) {
			try {
				$finfo = finfo_open( FILEINFO_MIME, Registry::get( 'config' )->get( 'mime/magic_file' ) );
			} catch ( Exception $e ) {
				$finfo = finfo_open( FILEINFO_MIME );
			}
			if ( $finfo === false ) {
				$libLog->message( 'zula_get_file_mime() unable to create FileInfo resource for file "'.$file.'"', Log::L_WARNING );
			} else {
			 	$mime = finfo_file( $finfo, $file, FILEINFO_MIME );
			 	finfo_close( $finfo );
				if ( preg_match( '#^([A-Z0-9+.\-]+/[A-Z0-9+.\-]+);?#i', $mime, $matches ) ) {
					return $matches[1];
				}
				$libLog->message( 'zula_get_file_mime() failed to get mime via FileInfo', Log::L_WARNING );
			}
		}
		// Attempt to get mime type via the 'file' command (will not work on Windows)
		$mime = null;
		if ( function_exists( 'exec' ) ) {
			$mime = exec( 'file -bi '.escapeshellarg($file) );
		}
		if ( preg_match( '#^([A-Z0-9+.\-]+/[A-Z0-9+.\-]+);?#i', $mime, $matches ) ) {
			return $matches[1];
		} else if ( function_exists( 'mime_content_type' ) ) {
			$libLog->message( 'zula_get_file_mime() reverting to "mime_content_type", please install "FileInfo" extension', Log::L_INFO );
			if ( ($mime = mime_content_type($file)) !== false ) {
				return $mime;
			}
		}
		$libLog->message( 'zula_get_file_mime() no method to get mime type, please install "FileInfo" extension', Log::L_WARNING );
		return false;
	}

	/**
	 * Similar to the origianl rmdir() function zula_except this
	 * one will remove all files and other directories inside
	 * of it instead of just failing.
	 *
	 * @param string $dir
	 * @return bool
	 */
	function zula_full_rmdir( $dir ) {
		if ( !zula_is_writable( $dir ) || !zula_is_writable( pathinfo( $dir, PATHINFO_DIRNAME ) ) ) {
			return false;
		}
		// Create new instance of the directory class
		$d = dir( $dir );
		while( false !== ( $entry = $d->read() ) ) {
			if ( $entry == '.' || $entry == '..' ) {
				continue;
			}
			$entry = $dir.'/'.$entry;
			if ( is_dir( $entry ) ) {
				if ( !zula_full_rmdir( $entry ) ) {
					return false;
				}
				continue;
			}
			if ( !@unlink( $entry ) ) {
				$d->close();
				return false;
			}
		}
		$d->close();
		rmdir( $dir );
		return true;
	}

	/**
	 * Converts a texual number word, such as "Five" to 5
	 * Though, only up to 10 for now :P For use in the installer
	 *
	 * @param string $word
	 * @return integer
	 */
	function zula_text2int( $word ) {
		$texual = array(
						'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten'
						);
		$intKey = array_search( strtolower( $word ), $texual );
		if ( $intKey === false ) {
			return false;
		} else {
			return $intKey+1;
		}
	}

	/**
	 * Converts an array into string format to be used in
	 * javascript. Format: {"foo":bar, "car":bra}
	 *
	 * @param array $array
	 * @return string|bool
	 */
	 function zula_array_js_string( array $array ) {
	 	$tmp = array();
	 	foreach( $array as $key=>$val ) {
	 		$tmp[] = $key.'='.$val;
	 	}
	 	return trim( implode( '&', $tmp ), '&' );
	 }

	/**
	 * Wrapper around PHPs is_writable to ensure it works on Windows
	 * operating systems (PHP bugs, and Bug #201 for us).
	 *
	 * @param string $file
	 * @return bool
	 */
	function zula_is_writable( $file ) {
		if ( strtoupper( substr(PHP_OS, 0, 3) ) === 'WIN' ) {
			if ( file_exists( $file ) ) {
				if ( is_dir( $file ) ) {
					// See if we can write inside of the directory
					$path = realpath( $file );
					$tmpFile = @tempnam( $path, 'zwc' );
					if ( $tmpFile !== false && is_file( $tmpFile ) ) {
						unlink( $tmpFile );
						return strpos( $tmpFile, $path ) === 0;
					}
				} else {
					$fHandle = @fopen( $file, 'r+' );
					if ( is_resource( $fHandle ) ) {
						fclose( $fHandle );
						return true;
					}
				}
			}
			return false;
		} else {
			return is_writable( $file );
		}
	}

	/**
	 * Checks if a file can be deleted
	 *
	 * @param string $file
	 * @return bool
	 */
	function zula_is_deletable( $file ) {
		return zula_is_writable( dirname( $file ) );
	}

	/**
	 * Replacement function for memory_get_usage if it
	 * is not avaliable. Thanks to e dot a dot schultz at gmail dot com
	 *
	 * @return integer
	 */
	if ( !function_exists( 'memory_get_usage' ) ) {
		function memory_get_usage() {
			$output = array();
	  		if ( substr( PHP_OS, 0, 3 ) == 'WIN' ) {
	  			/**
	  			 * Tested on WinXP SP2, Should work for Win2003.
	  			 * _Doesn't_ work for Win2000
	  			 */
	  			 exec( 'tasklist /FI "PID eq '. getmypid().'" /FO LIST', $output );
	  			 return preg_replace( '/[\D]/', '', $output[5] ) * 1024;
	  		} else {
	  			/**
	  			 * Assume the OS is UNIX
	  			 */
	  			$pid = getmypid();
	            exec( "ps -eo%mem,rss,pid | grep $pid", $output );
	            $output = explode( "  ", $output[0] );
	            // rss is given in 1024 byte units
	            return $output[1] * 1024;
	        }
	  	}
	}

	/**
	 * Takes an underscore-delimited string
	 * and returns it in camel case.
	 *
	 * @param string $string
	 * @return string
	 */
	function zula_camelise( $string ) {
	 	$fragments = array();
	 	foreach( explode( '_', $string ) as $fragment ) {
			$fragments[] = ucfirst( $fragment );
		}
		return implode( '', $fragments );
	}

	/**
	 * Merges 2 arrays recursively, similar to array_merge_recursive
	 * however it will replace the values if there are any conflicts
	 * instead of creating an array of the valuess
	 *
	 * @param array $array1
	 * @param array $array2
	 * @return array
	 */
	function zula_merge_recursive( $array1, $array2 ) {
		if ( !is_array( $array1 ) && !is_array( $array2 ) ) {
			return $array2;
	    }
	    if ( empty( $array1 ) ) {
	    	return $array2;
	    } else if ( empty( $array2 ) ) {
	    	return $array1;
	    }
	    foreach( $array2 as $key=>$val ) {
	    	if ( isset( $array1[ $key ] ) ) {
	    		$array1[ $key ] = zula_merge_recursive( $array1[ $key ], $val );
	    	} else {
	    		$array1[ $key ] = $val;
	    	}
	    }
	    return $array1;
	}

	/**
	 * Saves typing in code with some defaults setup already for
	 * commonly used HSC
	 *
	 * @param string $string
	 * @return string
	 */
	function zula_htmlspecialchars( $string ) {
		return htmlspecialchars( htmlspecialchars_decode($string, ENT_QUOTES), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Splits up a configuration value in the following format:
	 * 		Title[Value];Title2[Value2]
	 * into an associative array, if no value is provided then
	 * the title will be the value as well
	 *
	 * @param string $config
	 * @return mixed
	 */
	function zula_split_config( $config ) {
		if ( !is_string( $config ) ) {
			return $config;
		} else {
			$splitConf = explode( ';', trim($config, '; ') );
			$conf = array();
			foreach( $splitConf as $val ) {
				preg_match_all( '#(.+?)\[(.+?)\]#', $val, $matches );
				if ( empty( $matches[0] ) ) {
					$conf[ $val ] = $val;
				} else {
					$conf[ $matches[1][0] ] = $matches[2][0];
				}
			}
			return $conf;
		}
	}

	/**
	 * Converts a PHP short-hand byte value, ie '6m' or '3g'
	 * to the correct byte interger value
	 *
	 * @param mixed $val
	 * @return int
	 */
	function zula_byte_value( $val ) {
		$val = strtolower( trim( $val ) );
		switch ( substr( $val, -1 ) ) {
			case 'p':
				$val *= 1024;
			case 't':
				$val *= 1024;
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return abs( $val );
	}

	/**
	 * Converts a byte interger value to a human readable
	 * version such as 4MiB, 16.84GiB etc
	 *
	 * @param int $val
	 * @param int $precision
	 * @return string
	 */
	function zula_human_readable( $val, $precision=2 ) {
		$val = abs( $val );
		if ( $val >= pow(1024, 5) ) {
			$suffix = 'PiB';
			$val /= pow(1024, 5);
		} else if ( $val >= pow(1024, 4) ) {
			$suffix = 'TiB';
			$val /= pow(1024, 4);
		} else if ( $val >= pow(1024, 3) ) {
			$suffix = 'GiB';
			$val /= pow(1024, 3);
		} else if ( $val >= pow(1024, 2) ) {
			$suffix = 'MiB';
			$val /= pow(1024, 2);
		} else if ( $val >= 1024 ) {
			$suffix = 'KiB';
			$val /= 1024;
		} else {
			$suffix = 'B';
		}
		return number_format($val, $precision).$suffix;
	}

	/**
	 * Makes a directory with 0755 permissions and
	 * checks if it exists before hand
	 *
	 * @return bool
	 */
	function zula_make_dir( $dir ) {
		return file_exists($dir) ? true : @mkdir($dir, 0755, true);
	}

	/**
	 * Makes a directory name that is unique in the specified directory, by
	 * default this directory will be created.
	 *
	 * bool false will be returned if the directory can not be created
	 *
	 * @param string $dir
	 * @param bool $create
	 * @return string|bool
	 */
	function zula_make_unique_dir( $dir, $create=true ) {
		$chars = '1234567890ABCDEFGHIJKLMNOPQRSUTVWXYZabcdefghijklmnopqrstuvwxyz';
		do {
			$dirname = '';
			for( $i=0; $i <= 9; $i++ ) {
				$dirname .= substr( $chars, rand(0, 62), 1 );
			}
			$path = $dir.'/'.$dirname;
		} while ( is_dir( $path ) );
		if ( $create ) {
			return zula_make_dir( $path ) ? $dirname : false;
		}
		return $dirname;
	}

	/**
	 * Makes a file name that is unique in the specified directory, by
	 * default this file will be created.
	 *
	 * @param string $dir
	 * @param string $extension
	 * @param bool $create
	 * @return string
	 */
	function zula_make_unique_file( $dir, $extension=null, $create=true ) {
		$chars = '1234567890ABCDEFGHIJKLMNOPQRSUTVWXYZabcdefghijklmnopqrstuvwxyz';
		do {
			$basename = '';
			for( $i=0; $i <= 9; $i++ ) {
				$basename .= substr( $chars, rand(0, 62), 1 );
			}
			if ( $extension ) {
				$basename .= '.'.$extension;
			}
			$path = $dir.'/'.$basename;
		} while ( file_exists( $path ) || is_dir( $path ) );
		if ( $create ) {
			touch( $path );
		}
		return $basename;
	}

	/**
	 * Changes the case of an arrays keys recursively
	 *
	 * @oaram array $arr
	 * @param int $case
	 * @return bool
	 */
	function zula_array_key_case( array &$arr, $case=CASE_LOWER ) {
		$arr = array_change_key_case( $arr, $case );
		foreach( $arr as &$val ) {
			if ( is_array( $val ) ) {
				zula_array_key_case( $val, $case );
			}
		}
		return true;
	}

	/**
	 * Returns an icons path, if none is found then it will
	 * use a default icon.
	 *
	 * @param string $icon
	 * @param string $module	Check this modules directory if global fails
	 * @param bool $forHtml
	 * @return string
	 */
	function zula_get_icon( $icon='default.png', $module=null, $forHtml=true ) {
		$zula = Registry::get( 'zula' );
		$locations = array(
							'theme'		=> $zula->getDir( 'themes' ).'/'._THEME_NAME.'/icons',
							'module'	=> $zula->getDir( 'modules' ).'/'.$module.'/assets/icons'
						  );
		$icon = preg_replace( '#[^A-Z0-9_.\-/]#i', '', trim($icon, '.\/ ') );
		/**
		 * Loop through the different possible directories the file may be in
		 */
		$useType = null;
		foreach( $locations as $type=>$dir ) {
			if ( !is_dir( $dir ) || !is_readable( $dir ) ) {
				continue;
			} else if ( is_file( $dir.'/'.$icon ) ) {
				$useType = $type;
				break;
			} else {
				/**
				 * Search for the correct icon and build the path. If more than
				 * one possible icon is found, then it will return in preference
				 * of 'png', 'gif', 'svg', and then 'jpg/jpeg'
				 */
				$iconPinfo = pathinfo( $icon );
				$file = false;
				$fileType = false;
				$allIcon = glob( $dir.'/'.$icon.'.*' );
				if ( $allIcon !== false ) {
					foreach( $allIcon as $match ) {
						$tmpFile = $iconPinfo['dirname'].'/'.pathinfo( $match, PATHINFO_BASENAME );
						switch( strtolower( pathinfo($match, PATHINFO_EXTENSION) ) ) {
							case 'png':
								$file = $tmpFile;
								break 2;

							case 'gif':
								$fileType = 'gif';
								$file = $tmpFile;
								break;

							case 'svg':
								if ( $fileType != 'gif' ) {
									$fileType = 'svg';
									$file = $tmpFile;
								}
								break;

							case 'jpg':
							case 'jpeg':
								if ( $fileType != 'gif' && $fileType != 'svg' ) {
									$fileType = 'jpg';
									$file = $tmpFile;
								}
						}
					}
					if ( $file ) {
						// We have a file/icon to use
						$useType = $type;
						$icon = $file;
						break;
					}
				}
			}
		}
		/**
		 * Build path to the icon now, if it is for a module then make it a
		 * virtual asset to ensure we can always get at it.
		 */
		if ( $useType === null ) {
			$icon = 'default.png';
			$useType = 'theme';
		}
		if ( $useType == 'module' ) {
			return Registry::get( 'router' )->makeUrl( 'assets/v/'.$module ).'/icons/'.$icon;
		} else {
			return $zula->getDir( 'themes', $forHtml ).'/'._THEME_NAME.'/icons/'.$icon;
		}
	}

	/**
	 * Normalizes a value, in such that strings that look like
	 * bools will be convereted to a bool, ie - 'true' -> true
	 *
	 * Strings that look like ints will be cast to int, same
	 * goes for floats.
	 *
	 * @param mixed $values
	 * @return bool
	 */
	function zula_normalize( &$value ) {
		if ( is_array( $value ) ) {
			$wasArray = true;
		} else {
			$value = array( $value );
			$wasArray = false;
		}
		foreach( $value as $key=>$val ) {
			if ( is_array( $val ) ) {
				zula_normalize( $val );
			} else if ( is_string( $val ) ) {
				$val = trim( $val );
				if ( $val === 'true' ) {
					$val = true;
				} else if ( $val === 'false' ) {
					$val = false;
				} else if ( ctype_digit( $val ) ) {
					$val = $val[0] === '0' ? $val : (int) $val;
				} else if ( is_numeric( $val ) ) {
					$val = (float) $val;
				}
			}
			$value[ $key ] = $val;
		}
		if ( $wasArray === false ) {
			$value = $value[0];
		}
		return true;
	}

	/**
	 * Flattens an array based upon a key. This is useful for a multidimensional
	 * array that recursively contains, for example - children items, in a menu.
	 *
	 * It will flatten it into a simply 2 dimension array.
	 *
	 * @param array $array
	 * @param string $key
	 * @return array
	 */
	function zula_array_flatten( array $array, $flattenKey ) {
		$newArray = array();
		foreach( $array as $val ) {
			if ( empty( $val[ $flattenKey ] ) ) {
				unset( $val[ $flattenKey ] );
				$newArray[] = $val;
			} else {
				$flattenValue = $val[ $flattenKey ];
				unset( $val[ $flattenKey ] );
				// Attach the value (without the children)
				$newArray[] = $val;
				foreach( $flattenValue as $tmpVal ) {
					if ( !empty( $tmpVal[ $flattenKey ] ) ) {
						$children = $tmpVal[ $flattenKey ];
						unset( $tmpVal[ $flattenKey ] );
						$newArray[] = $tmpVal;
						$newArray = array_merge( $newArray, zula_array_flatten( $children, $flattenKey ) );
					} else {
						unset( $tmpVal[ $flattenKey ] );
						$newArray[] = $tmpVal;
					}
				}
			}
		}
		return $newArray;
	}

	/**
	 * Check if the given string needs CDATA
	 *
	 * @param string $string
	 */
	function zula_needs_cdata( $string ) {
		return (bool) preg_match( '#(<|>|&|"|\')#', $string );
	}

	/**
	 * Takes a version string and decides on what type it
	 * is. Ie, Stable or Unstable.
	 *
	 * If the 'maintenance' or 'build' version (the 3rd
	 * part in 2.1.4) is over 50, then it is classed as an
	 * unstable release.
	 *
	 * @param string $version
	 * @return string
	 */
	function zula_version_type( $version ) {
		if ( stripos( $version, 'beta' ) !== false || stripos( $version, 'alpha' ) !== false ) {
			return 'unstable';
		} else {
			$split = explode( '.', $version );
			return (ctype_digit( $split[2] ) && $split[2] >= 50) ? 'unstable' : 'stable';
		}
	}

	/**
	 * Map a development version onto the previous milestone
	 * Eg. 2.5.63 => 2.6.0-alpha1
	 *
	 * Stable versions remain the same, .5x map to 'latest'
	 *
	 * @param string $version
	 * @return string
	 */
	function zula_version_map( $version ) {
		if ( zula_version_type( $version ) == 'unstable' ) {
			if ( strpos( $version, '-' ) !== false ) {
				return $version;
			} else {
				list( $major, $minor, $rev ) = explode( '.', $version );
				if ( $rev >= 90 ) {
					return sprintf( '%s.%d.0-rc1', $major, $minor + 1 );
				} else if ( $rev >= 80 ) {
					return sprintf( '%s.%d.0-beta1', $major, $minor + 1 );
				} else if ( $rev >= 70 ) {
					return sprintf( '%s.%d.0-alpha2', $major, $minor + 1 );
				} else if ( $rev >= 60 ) {
					return sprintf( '%s.%d.0-alpha1', $major, $minor + 1 );
				}
				return 'latest';
			}
		} else {
			return $version;
		}
	}

	/**
	 * Recursive version of PHPs array_map
	 *
	 * @param mixed $callback
	 * @param array $arr
	 * @return array
	 */
	function zula_array_map_recursive( $callback, array $arr ) {
		foreach( $arr as $key=>$val ) {
			if ( is_array( $val ) ) {
				$arr[ $key ] = zula_array_map_recursive( $callback, $val );
			} else {
				$arr[ $key ] = call_user_func( $callback, $val );
			}
		}
		return $arr;
	}

	/**
	 * Returns the English ordinal suffix for an integer
	 *
	 * @param int|string $int
	 * @return string
	 */
	function zula_ordinal( $int ) {
		$int = abs( $int );
		if ( $int < 1 ) {
			return '';
		} else if ( substr( $int, -2, 2 ) >= 11 && substr( $int, -2, 2 ) <= 13 ) {
			return 'th';
		}
		switch( substr( $int, -1, 1 ) ) {
			case 1:
				return 'st';
			case 2:
				return 'nd';
			case 3:
				return 'rd';
			default:
				return 'th';
		}
	}

	/**
	 * Removes all files of a certain extension from a directory
	 * not recursively. No directories will be removed.
	 *
	 * @param string $dir
	 * @param string|bool $extension
	 * @param int $maxAge
	 * @return int|bool
	 */
	function zula_empty_dir( $dir, $extension=true, $maxAge=0 ) {
		try {
			$delCount = 0;
			$maxAge = time() - abs( $maxAge );
			foreach( new DirectoryIterator( $dir ) as $file ) {
				if (
					!$file->isDot() && $file->getMTime() < $maxAge
					&& ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) == $extension)
					&& unlink( $file->getPathName() )
				) {
					++$delCount;
				}
			}
			return $delCount;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Checks if a provided URL has a scheme, or has a scheme
	 * that matches the provided types.
	 *
	 * @param string $url
	 * @param string|array $scheme
	 * @return bool
	 */
	function zula_url_has_scheme( $url, $scheme=null ) {
		return (bool) preg_match( '#^'.($scheme ? $scheme : '[A-Z][A-Z0-9+.\-]+').'://#i', $url );
	}

	/**
	 * Adds on the scheme/protocol to a provided URL if it
	 * does not already have one. Defaults to adding http.
	 *
	 * @param string $url
	 * @param string $scheme
	 * @return string
	 */
	function zula_url_add_scheme( $url, $scheme='http' ) {
		return zula_url_has_scheme($url) ? $url : $scheme.'://'.$url;
	}

	/**
	 * Helper function to PHP 'readfile' to set all the needed
	 * headers to output a file via PHP. If no mimetype is
	 * provided then it will get it its self
	 *
	 * @param string $file
	 * @param string $mime
	 * @return int|bool
	 */
	function zula_readfile( $file, $mime=null ) {
		if ( !is_readable( $file ) ) {
			return false;
		} else if ( $mime == false ) {
			$mime = zula_get_file_mime( $file );
		}
		try {
			$ttl = Registry::get( 'config' )->get( 'cache/ttl' );
		} catch ( Exception $e ) {
			$ttl = 604800;
		}
		$modified = filemtime( $file );
		$eTag = '"'.hash('md5', $modified).'"';
		if ( zula_http_header_get('If-None-Match') == $eTag ) {
			header( 'HTTP/1.1 304 Not Modified' );
		}
		// Set all needed headers
		header( 'Content-Type: '.$mime );
		header( 'Content-Length: '.filesize( $file ) );
		header( 'Cache-Control: max-age='.$ttl.', public' );
		header( 'Pragma: ' );
		header( 'Expires: '.date(DATE_RFC1123, time()+$ttl) );
		header( 'ETag: '.$eTag );
		header( 'Last-Modified: '.date(DATE_RFC1123, $modified) );
		if ( strpos( zula_http_header_get('Range'), 'bytes' ) !== false || strpos( $mime, 'video/' ) === 0 ) {
			header( 'Accept-Ranges: bytes' );
		}
		if ( ini_get( 'output_handler' ) && strpos( $mime, 'image/' ) === 0 ) {
			// We don't want to compress images, it causes all sorts of issues
			ob_get_clean();
			header( 'Content-Encoding: ' );
		}
		return readfile( $file );
	}

	/**
	 * Wrapper for ip2long which adds support for IPv6 addresses and will
	 * be returned as an int. For IPv4 addresses formatted as IPv6,
	 * e.g. ::ffff:192.168.0.1 - they will be run through normal ip2long.
	 *
	 * @param string $ip
	 * @return int|bool
	 */
	function zula_ip2long( $ip ) {
		if ( strpos( $ip, ':' ) === false ) {
			return ip2long( $ip );
		} else if ( strpos( $ip, '.' ) !== false ) {
			return ip2long( substr($ip, strrpos($ip, ':')+1) );
		} else {
			if ( strpos( $ip, '::' ) !== false ) {
				// Expand zero abbreviations
				$ip = str_replace( '::', str_repeat(':0000', 8-substr_count($ip, ':')).':', $ip );
			}
			if ( $ip[0] == ':' ) {
				$ip = '0'.$ip;
			}
			$rIp = '';
			foreach( explode( ':', $ip ) as $v ) {
				$rIp .= str_pad( $v, 4, 0, STR_PAD_LEFT );
			}
			return base_convert( $rIp, 16, 10 );
		}
	}

	/**
	 * Gets all HTTP Request headers for the current request
	 *
	 * @return array
	 */
	function zula_http_headers() {
		static $headers = array();
		if ( empty( $headers ) ) {
			foreach( $_SERVER as $key=>$val ) {
				if ( strpos( $key, 'HTTP_' ) === 0 ) {
					$name = str_replace( '_', ' ', substr($key, 5) );
					$name = ucwords( strtolower($name) );
					$headers[ str_replace(' ', '-', $name) ] = $val;
				}
			}
		}
		return $headers;
	}

	/**
	 * Gets a single HTTP Request header by name
	 *
	 * @param string $header
	 * @return string|bool
	 */
	function zula_http_header_get( $header ) {
		$header = str_replace( '-', ' ', $header );
		$header = str_replace( ' ', '-', ucwords( strtolower($header) ) );
		$requestHeaders = zula_http_headers();
		if ( isset( $requestHeaders[ $header ] ) ) {
			return $requestHeaders[ $header ];
		} else {
			return false;
		}
	}

	/**
	 * Gets the IP address of a domain name
	 *
	 * @param string $domain
	 * @return string|bool
	 */
	function zula_get_domain_ip( $domain ) {
		$domain = parse_url( $domain, PHP_URL_HOST );
		$ip = gethostbyname( $domain );
		return $ip === $domain ? false : $ip;
	}

?>
