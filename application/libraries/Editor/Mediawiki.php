<?php

/**
 * Zula Framework Editor Parser
 * --- Parser for MediaWiki Wiki syntax
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Editor
 */

	class Editor_mediawiki extends Editor_base {

		/**
		 * All the different regexs for matching the
		 * MediaWiki formatting syntax.
		 * @var array
		 */
		protected $regexes = array(
									# Pre Parse formatting regex.
									'pre-parse'	=> array(
														'signature'	=> '#~{3,5}#',
														),
									# Global regexs that are done on the entire text.
									'global'	=> array(
														'externalLink'	=> '#(?<!\[)\[([^][]+)\](?!\])#',
														'internalLink'	=> '#\[\[(?!Image:)([^\|\]]+)(.*?)?\]\]#',
														'images'		=> '#\[\[Image:(.*?)(\|(.*?))?(\|(.*?))?\]\]#',

														'hozRule'		=> '#^----$#',
														'sections'		=> '#^(={1,6})(.*?)(={1,6})$#',
														),
									# Regexs which are done using line-by-line parsing.
									'lbl-parse'	=> array(
														'preformat'			=> '#^\s{1}(.*?)$#',
														'lists'				=> '#^([\*\#]+)(.*)$#',
														'definitionLists'	=> '#^([\;\:])(.*)$#',
														),
									);

		/**
		 * All stored tokens that have been replaced/extracted
		 * @var array
		 */
		protected $tokens = array( 'nowiki' => array(), 'content' => array() );

		/**
		 * Array of all the exploded lines
		 * @var array
		 */
		protected $lines = array();

		/**
		 * The maximum level the headings can get to
		 * eg, if this is set at 2 - all <h1> levels will
		 * be changed to <h2>
		 * @var integer
		 */
		protected $headingLevelCap = 2;

		/**
		 * Constructor Function
		 * Sets the text to be parsed
		 *
		 * @param string $text
		 */
		public function __construct( $text ) {
			$this->text = trim( $text );
		}

		/**
		 * Set the text to be used that will be parsed
		 *
		 * @page string $text
		 * @return bool
		 */
		public function setText( $text ) {
			$this->text = (string) $text;
			return true;
		}

		/**
		 * Sets the cap limit for the section headings (<hX>)
		 * The limit set here will mean all headings above it will
		 * be moved down to the limit set
		 *
		 * @param int $limit
		 * @return bool
		 */
		public function setHeadingCapLimit( $limit ) {
			$limit = (int) $limit;
			if ( $limit < 1 || $limit > 6 ) {
				trigger_error( 'Editor_MediaWiki::set_heading_cap_limit() Heading cap limit must between 1 and 6. Value given was "'.$limit.'"', E_USER_NOTICE );
				return false;
			}
			$this->headingLevelCap = $limit;
			return true;
		}

		/**
		 * Extracts the text inbetween all of the <nowiki> tags
		 * and stores it in an array for later use.
		 *
		 * @param array $matches
		 * @return string
		 */
		protected function extractNowiki( $matches ) {
			$this->tokens['nowiki'][] = $matches[1];
			return '@!no-wiki!@';
		}

		/**
		 * Returns the correct string that needs to be inserted
		 * back into the text for all tokens (<nowiki> and <me-token)
		 *
		 * @param array $matches
		 * @return string
		 */
		protected function restoreTokens( $matches ) {
			if ( strpos( $matches[0], '@!mw-token-' ) === 0 ) {
				if ( isset( $this->tokens['content'][ $matches[1] ] ) ) {
					$val = $this->tokens['content'][ $matches[1] ];
					unset( $this->tokens['content'][ $matches[1] ] );
				}
				return isset($val) ? $val : $matches[0];
			} else {
				return array_shift( $this->tokens['nowiki'] );
			}
		}

		/**
		 * Returns the correct string that needs to be inserted
		 * back into the text with the nowiki tags
		 *
		 * @param array $matches
		 * @return string
		 */
		protected function insertPreparseNowiki( $matches ) {
			return '<nowiki>'.array_shift( $this->tokens['nowiki'] ).'</nowiki>';
		}

		/**
		 * Adds a new token to the class property, and returns a unique
		 * token identifier
		 *
		 * @param string $content
		 * @return string
		 */
		protected function addToken( $content ) {
			$key = uniqid();
			$this->tokens['content'][ $key ] = $content;
			return '@!mw-token-'.$key.'!@';
		}

		/**
		 * The main method that will take the text and split it up from every new line.
		 * Once it has done that it will then send it off to parse each line, after
		 * every line has been parsed it will return the final output.
		 *
		 * All Nonwiki text is removed and stored, then placed back in at the last moment
		 *
		 * @param bool $break
		 * @return string
		 */
		public function parse( $break=false ) {
			// Reset some vars and break the text
			$this->tokens['nowiki'] = array();
			$inside_p = false;
			$text = $this->breakText( $break );
			/**
			 * Tokenise all <nowiki> entries, then run all 'global' regular
			 * expressions on the entire text first
			 */
			$text = preg_replace_callback( '#<nowiki>(.*?)</nowiki>#s', array($this, 'extractNowiki'), $text );
			$text = htmlspecialchars( $text, ENT_COMPAT, 'UTF-8' );
			foreach( $this->regexes['global'] as $type=>$regex ) {
				$text = preg_replace_callback( $regex.'im', array( $this, 'globalParse'.$type ), $text );
			}
			/**
			 * Begin the Line By Line (LBL) parsing of the document
			 */
			$this->lines = array_merge( array(0 => ''), explode("\n", $text) );
			$parsedLines = array();
			while( ($line = next($this->lines)) !== false ) {
				if ( $line != '@!no-wiki!@' ) {
					$line = trim( $this->parseLine( $line ) );
					if ( trim( $line ) && strpos( $line, '<' ) !== 0 ) {
						$line = $this->doQuotes( $line );
						if ( $inside_p === true ) {
							$newLine = '<br>'.$line;
						} else {
							$inside_p = true;
							$line = '<p>'.$line;
						}
					} else {
						if ( $inside_p === true ) {
							$line = '</p>'.$line;
							$inside_p = false;
						}
						$line = $this->doQuotes( $line );
					}
				}
				$parsedLines[] = $line;
			}
			if ( $inside_p === true ) {
				$parsedLines[] = '</p>';
			}
			// Restore all tokens
			$text = str_replace( '&lt;br&gt;', '<br>', implode("\n", $parsedLines) );
			$text = preg_replace_callback( '#@!mw-token-(.*?)!@#', array($this, 'restoreTokens'), $text );
			return trim( preg_replace_callback('#@!no-wiki!@#', array($this, 'restoreTokens'), $text) );
		}

		/**
		 * Pre-Parser for WikiSyntax.
		 *
		 * Replaces certain things, such as ~~~~ and Date/Time tags only as these
		 * need to be in the body text that will get stored in the DB. Other wise,
		 * the date will always show as 1 Second Ago etc
		 *
		 * @param string $text
		 * @return string
		 */
		public function preParse() {
			$this->lines = array( 0 => '' );
			$this->tokens['nowiki'] = array();
			// Remove nowiki
			$text = preg_replace_callback( '#<nowiki>(.*?)</nowiki>#si', array($this, 'extractNowiki'), $this->text );
			foreach( $this->regexes['pre-parse'] as $type=>$regex ) {
				$text = preg_replace_callback( $regex.'i', array( $this, 'preParse'.ucfirst($type) ), $text );
			}
			$text = $this->preParseTags( $text );
			$text = preg_replace_callback( '#@!no-wiki!@#i', array( $this, 'insertPreparseNowiki' ), $text );
			return $text;
		}

		/**
		 * Parses a line at a time, sending the different elements off
		 * to the correct handle function.
		 *
		 * @param string $line
		 * @param array $skip	Array of regex titles to skip
		 * @return string
		 */
		protected function parseLine( $line, $skip=array() ) {
			if ( !is_array( $skip ) ) {
				$skip = array( $skip );
			}
			foreach( $this->regexes['lbl-parse'] as $type=>$regex ) {
				if ( !in_array( $type, $skip ) ) {
					$line = preg_replace_callback( $regex.'i', array($this, 'lblParse'.$type), $line );
				}
			}
			return $line;
		}

		/**
		 * Requests a new line and will return that new line if and only if
		 * the regex type/title matches. If it does it will also shift on the
		 * internal array pointer
		 *
		 * @param string $regexTitle
		 * @return string|bool
		 */
		protected function requestNextLine( $regexTitle ) {
			if ( isset( $this->regexes['lbl-parse'][ $regexTitle ] ) ) {
				$nextLine = next( $this->lines );
				if ( $nextLine === false ) {
					return false;
				} else if ( preg_match_all( $this->regexes['lbl-parse'][ $regexTitle ].'i', $nextLine, $matches ) ) {
					return $this->parseLine( $nextLine, $regexTitle );
				} else {
					// Move pointer back one, it didn't match needed regex
					prev( $this->lines );
				}
			}
			return false;
		}

		/**
		 * Changes a format of the matches array into a single array
		 * instead of multidimensional
		 *
		 * @param array $matches
		 * @return array
		 */
		protected function cleanMatches( $matches ) {
			if ( is_array( $matches ) ) {
				return array( $matches[0][0], $matches[1][0], $matches[2][0] );
			} else {
				return array();
			}
		}

		/**
		 * Handle for sections/headings (<hx>)
		 *
		 * @param array $matches
		 * @return string
		 */
		protected function globalParseSections( $matches ) {
			$content = $matches[2];
			# Count how many equal signs we have on the left and right
			$lsideCount = zula_strlen( $matches[1] );
			$rsideCount = zula_strlen( $matches[3] );
			if ( $lsideCount > $rsideCount ) {
				/**
				 * We've got more equal signs on the left than the right, move
				 * those extra ones to the content
				 */
				$content = str_repeat( '=', ($lsideCount-$rsideCount) ).$content;
				$lsideCount = $rsideCount;
			} else if ( $rsideCount > $lsideCount ) {
				# Same again but reveresed
				$content .= str_repeat( '=', ($rsideCount-$lsideCount) );
				$rsideCount = $lsideCount;
			}
			$level = $rsideCount;
			if ( $level < $this->headingLevelCap ) {
				# Level is above the cap, bring it down to the cap level
				$level = $this->headingLevelCap;
			}
			# Return in correct format
			$format = '<h%1$d>%2$s</h%1$d>';
			return sprintf( $format, $level, $content );
		}

		/**
		 * Handle horizontal rule
		 *
		 * @param array $matches
		 * @return string
		 */
		protected function globalParseHozRule( $matches ) {
			return '<hr>';
		}

		/**
		 * Handle for any internal link
		 *
		 * @param array $matches
		 * @return string
		 */
		protected function globalParseInternalLink( $matches ) {
			$matches[2] = ltrim($matches[2], '|');
			$title = empty($matches[2]) ? $matches[1] : $matches[2];
			// If URL is a valid scheme, or starts with a / - don't prepend the base dir
			$url = $matches[1];
			if ( preg_match( '#^(wikipedia|delicious|freenode|irc):#', $url, $interMatch ) ) {
				// Link provided is an 'Interwiki' link, to link to another wikis page.
				switch( $interMatch[0] ) {
					case 'wikipedia:':
						$url = 'http://wikipedia.org/wiki/'.str_replace( ' ', '_', substr($url, 10) );
						break;
					case 'delicious:':
						$url = 'http://del.icio.us/tag/'.substr($url, 10);
						break;
					case 'freenode:':
						$url = 'irc://irc.freenode.net/'.substr($url, 9);
						break;
					case 'irc:':
						$url = 'irc://irc.freenode.net/'.substr($url, 4);
						break;
				}
			} else if ( !preg_match( '#^[A-Z][A-Z0-9+.\-]+://|^/#i', $url ) ) {
				$url = _BASE_DIR.$url;
			}
			return $this->addToken( '<a href="'.$url.'">'.$title.'</a>' );
		}

		/**
		 * Handle for any external link
		 *
		 * @param array $matches
		 * @return string
		 */
		protected function globalParseExternalLink( $matches ) {
			$parts = explode( ' ', $matches[1], 2 );
			$title = empty($parts[1]) ? $parts[0] : $parts[1];
			return $this->addToken( '<a href="'.$parts[0].'" class="external">'.$title.'</a>' );
		}

		/**
		 * Handle for all images
		 *
		 * @param array $matches
		 * @return string
		 */
		protected function globalParseImages( $matches ) {
			$imgDetails = array(
								'src'	=> '#',
								'alt'	=> '',
								'frame'	=> false,
								);
			$imgDetails['src'] = $matches[1];
			if ( isset( $matches[3] ) ) {
				if ( $matches[3] == 'frame' ) {
					$imgDetails['frame'] = true;
				} else if ( !empty( $matches[3] ) ) {
					$imgDetails['alt'] = $matches[3];
				}
				if ( isset( $matches[5] ) ) {
					$imgDetails['alt'] = $matches[5];
				}
			}
			if ( $imgDetails['frame'] === true ) {
				$format = '<div class="%3$s"><img src="%1$s" alt="%2$s"><p>%2$s</p></div>';
			} else {
				$format = '<img src="%1$s" alt="%2$s">';
			}
			return $this->addToken( sprintf( $format, $imgDetails['src'], $imgDetails['alt'], 'wiki_image_frame' ) );
		}

		/**
		 * Handle for preformated/code text
		 *
		 * @param array $matches
		 * @return string
		 */
		protected function lblParsePreformat( $matches ) {
			$code = $matches[1];
			while( ($line = $this->requestNextLine( 'preformat' )) !== false ) {
				/**
				 * Get more new lines that are needed to be preformated
				 * and merge them into the same <code> block
				 */
				preg_match_all( $this->regexes['lbl-parse']['preformat'].'i', $line, $matches );
				$code .= "\n".$matches[1][0];
			}
			return trim($code) ? '<pre>'.$code.'</pre>' : '';
		}

		/**
		 * Handle unordered and ordered lists
		 *
		 * @param array $matches
		 * @return string
		 */
		protected function lblParseLists( $matches ) {
			$content = trim( $matches[2] );
			$listType = $this->resolveListType( $matches[1] );
			if ( $listType === false ) {
				return $matches[0];
			}
			$curLevel = zula_strlen( $matches[1] );
			$format = array(
							'new_list' 	=> '<%s><li>%s',
							'new_item' 	=> '</li><li>%s',
							'end_list' 	=> '</li></%s>',
							);
			$list = sprintf( $format['new_list'], $listType, $content ); # Create initial first list
			$openTags = array(); # Keeps track of all open-tags made by the extra lines
			while( ($newLine = $this->requestNextLine( 'lists' )) !== false ) {
				/**
				 * Keep requesting more and more lines until we get to the end
				 * of the list. We must match again to get needed data.
				 */
				preg_match_all( $this->regexes['lbl-parse']['lists'], $newLine, $tmpMatches );
				$tmp = $this->cleanMatches( $tmpMatches );
				$item = array(
							'matches'	=> $tmp,
							'content'	=> trim( $tmp[2] ),
							'list_type'	=> $this->resolveListType( $tmp[1] ),
							'level'		=> zula_strlen( $tmp[1] ),
							);
				// Now create the correct item on the correct level
				if ( $item['level'] == $curLevel ) {
					$list .= sprintf( $format['new_item'], $item['content'] );
				} else if ( $item['level'] == $curLevel+1 ) {
					$openTags[] = $item['list_type'];
					$list .= sprintf( $format['new_list'], $item['list_type'], $item['content'] );
					$curLevel++;
				} else if ( $item['level'] < $curLevel ) {
					for( $i = $curLevel; $i > $item['level']; $i-- && $curLevel-- ) {
						$list .= sprintf( $format['end_list'], $item['list_type'] );
						array_pop( $openTags ); # Take one off the open tags, since we just closed it.
					}
					$list .= sprintf( $format['new_item'], $item['content'] );
				}
			}
			foreach( $openTags as $tag ) {
				// Close each open tag
				$list .= sprintf( $format['end_list'], $tag );
			}
			$list .= sprintf( $format['end_list'], $listType );
			return $list;
		}

		/**
		 * Gets the correct list type to use
		 *
		 * @param string $type
		 * @return string || bool false
		 */
		protected function resolveListType( $type ) {
			if ( zula_substr( $type, 0, 1 ) == '*' ) {
				return 'ul';
			} else if ( zula_substr( $type, 0, 1 ) == '#' ) {
				return 'ol';
			} else {
				return false;
			}
		}

		/**
		 * Handle for definition lists
		 *
		 * @param array $matches
		 * @return string
		 */
		protected function lblParseDefinitionLists( $matches ) {
			if ( $matches[1] == ':' ) {
				// If the first list starts with a : then it's not a defintion list!
				return $matches[0];
			}
			$formats = array(
							'title'		=> '<dt>%s</dt>',
							'definition'=> '<dd>%s</dd>',
							);
			$list = '<dl>';
			$content = explode( ':', trim($matches[2]) );
			$list .= sprintf( $formats['title'], $content[0] );
			if ( count( $content ) > 1 ) {
				array_shift( $content );
				foreach( $content as $definition ) {
					$list .= sprintf( $formats['definition'], trim($definition) );
				}
			}
			while( ($line = $this->requestNextLine( 'definition_lists' )) !== false ) {
				preg_match_all( $this->regexes['lbl-parse']['definition_lists'], $line, $tmpMatch );
				$tmpMatch = $this->cleanMatches( $tmpMatch );
				if ( $tmpMatch[1] == ':' ) {
					// New definition element
					$list .= sprintf( $formats['definition'], trim($tmpMatch[2]) );
				} else if ( $tmpMatch[1] == ';' ) {
					$tmpContent = explode( ':', trim($tmpMatch[2]) );
					$list .= sprintf( $formats['title'], $tmpContent[0] );
					if ( count( $tmpContent ) > 1 ) {
						array_shift( $tmpContent );
						foreach( $tmpContent as $tmpDefinition ) {
							$list .= sprintf( $formats['definition'], trim($tmpDefinition) );
						}
					}
				}
			}
			return $list.'</dl>';
		}

		/**
		 * Pre parser for signature.
		 * 3 tildes (~) gives the username
		 * 4 ~ gives username plus date/time
		 * 5 ~ gives date/time
		 *
		 * @apram array $matches
		 * @return string
		 */
		protected function preParseSignature( $matches ) {
			$date = Registry::get( 'date' )->format( null, null, true );
			$userDetails = Registry::get( 'session' )->getCurrentUserInfo();
			switch( zula_strlen( $matches[0] ) ) {
				case 5:
					return '-- '.$date;
				case 4:
					return '-- '.$userDetails['username'].', '.$date;
				case 3:
				default:
					return '-- '.$userDetails['username'];
			}
		}

		/**
		 * Pre parser for some preset tags
		 *
		 * @param string $text
		 * @return string
		 */
		protected function preParseTags( $text ) {
			$tags = array(
						'{{CURRENTTIME}}'		=> date( 'H:i' ),
						'{{CURRENTYEAR}}'		=> date( 'Y' ),
						'{{CURRENTDAYNAME}}'	=> date( 'l' ),
						'{{CURRENTDAY}}'		=> date( 'd' ),
						'{{CURRENTMONTHNAME}}'	=> date( 'F' ),
						'{{CURRENTMONTH}}'		=> date( 'm' ),
						'{{CURRENTDOW}}'		=> date( 'w' ),
						'{{CURRENTWEEK}}'		=> date( 'W' ),
						);
			return str_replace( array_keys( $tags ), array_values( $tags ), $text );
		}

		/**
		 * Helper function for emphasis
		 *
		 * @param string $text
		 * @return string
		 * @author MediaWiki Project
		 */
		public function doQuotes( $text ) {
			$arr = preg_split( "/(''+)/", $text, -1, PREG_SPLIT_DELIM_CAPTURE );
			if ( count( $arr ) == 1 )
				return $text;
			else
			{
				# First, do some preliminary work. This may shift some apostrophes from
				# being mark-up to being text. It also counts the number of occurrences
				# of bold and italics mark-ups.
				$i = 0;
				$numbold = 0;
				$numitalics = 0;
				foreach ( $arr as $r )
				{
					if ( ( $i % 2 ) == 1 )
					{
						# If there are ever four apostrophes, assume the first is supposed to
						# be text, and the remaining three constitute mark-up for bold text.
						if ( zula_strlen( $arr[$i] ) == 4 )
						{
							$arr[$i-1] .= "'";
							$arr[$i] = "'''";
						}
						# If there are more than 5 apostrophes in a row, assume they're all
						# text except for the last 5.
						else if ( zula_strlen( $arr[$i] ) > 5 )
						{
							$arr[$i-1] .= str_repeat( "'", zula_strlen( $arr[$i] ) - 5 );
							$arr[$i] = "'''''";
						}
						# Count the number of occurrences of bold and italics mark-ups.
						# We are not counting sequences of five apostrophes.
						if ( zula_strlen( $arr[$i] ) == 2 )      { $numitalics++;             }
						else if ( zula_strlen( $arr[$i] ) == 3 ) { $numbold++;                }
						else if ( zula_strlen( $arr[$i] ) == 5 ) { $numitalics++; $numbold++; }
					}
					$i++;
				}

				# If there is an odd number of both bold and italics, it is likely
				# that one of the bold ones was meant to be an apostrophe followed
				# by italics. Which one we cannot know for certain, but it is more
				# likely to be one that has a single-letter word before it.
				if ( ( $numbold % 2 == 1 ) && ( $numitalics % 2 == 1 ) )
				{
					$i = 0;
					$firstsingleletterword = -1;
					$firstmultiletterword = -1;
					$firstspace = -1;
					foreach ( $arr as $r )
					{
						if ( ( $i % 2 == 1 ) and ( zula_strlen( $r ) == 3 ) )
						{
							$x1 = zula_substr ($arr[$i-1], -1);
							$x2 = zula_substr ($arr[$i-1], -2, 1);
							if ($x1 == ' ') {
								if ($firstspace == -1) $firstspace = $i;
							} else if ($x2 == ' ') {
								if ($firstsingleletterword == -1) $firstsingleletterword = $i;
							} else {
								if ($firstmultiletterword == -1) $firstmultiletterword = $i;
							}
						}
						$i++;
					}

					# If there is a single-letter word, use it!
					if ($firstsingleletterword > -1)
					{
						$arr [ $firstsingleletterword ] = "''";
						$arr [ $firstsingleletterword-1 ] .= "'";
					}
					# If not, but there's a multi-letter word, use that one.
					else if ($firstmultiletterword > -1)
					{
						$arr [ $firstmultiletterword ] = "''";
						$arr [ $firstmultiletterword-1 ] .= "'";
					}
					# ... otherwise use the first one that has neither.
					# (notice that it is possible for all three to be -1 if, for example,
					# there is only one pentuple-apostrophe in the line)
					else if ($firstspace > -1)
					{
						$arr [ $firstspace ] = "''";
						$arr [ $firstspace-1 ] .= "'";
					}
				}

				# Now let's actually convert our apostrophic mush to HTML!
				$output = '';
				$buffer = '';
				$state = '';
				$i = 0;
				foreach ($arr as $r)
				{
					if (($i % 2) == 0)
					{
						if ($state == 'both')
							$buffer .= $r;
						else
							$output .= $r;
					}
					else
					{
						if (zula_strlen ($r) == 2)
						{
							if ($state == 'i')
							{ $output .= '</em>'; $state = ''; }
							else if ($state == 'bi')
							{ $output .= '</em>'; $state = 'b'; }
							else if ($state == 'ib')
							{ $output .= '</strong></em><strong>'; $state = 'b'; }
							else if ($state == 'both')
							{ $output .= '<strong><em>'.$buffer.'</em>'; $state = 'b'; }
							else # $state can be 'b' or ''
							{ $output .= '<em>'; $state .= 'i'; }
						}
						else if (zula_strlen ($r) == 3)
						{
							if ($state == 'b')
							{ $output .= '</strong>'; $state = ''; }
							else if ($state == 'bi')
							{ $output .= '</em></strong><em>'; $state = 'i'; }
							else if ($state == 'ib')
							{ $output .= '</strong>'; $state = 'i'; }
							else if ($state == 'both')
							{ $output .= '<em><strong>'.$buffer.'</strong>'; $state = 'i'; }
							else # $state can be 'i' or ''
							{ $output .= '<strong>'; $state .= 'b'; }
						}
						else if (zula_strlen ($r) == 5)
						{
							if ($state == 'b')
							{ $output .= '</strong><em>'; $state = 'i'; }
							else if ($state == 'i')
							{ $output .= '</em><strong>'; $state = 'b'; }
							else if ($state == 'bi')
							{ $output .= '</em></strong>'; $state = ''; }
							else if ($state == 'ib')
							{ $output .= '</strong></em>'; $state = ''; }
							else if ($state == 'both')
							{ $output .= '<em><strong>'.$buffer.'</strong></em>'; $state = ''; }
							else # ($state == '')
							{ $buffer = ''; $state = 'both'; }
						}
					}
					$i++;
				}
				# Now close all remaining tags.  Notice that the order is important.
				if ($state == 'b' || $state == 'ib')
					$output .= '</strong>';
				if ($state == 'i' || $state == 'bi' || $state == 'ib')
					$output .= '</em>';
				if ($state == 'bi')
					$output .= '</strong>';
				# There might be lonely ''''', so make sure we have a buffer
				if ($state == 'both' && $buffer)
					$output .= '<strong><em>'.$buffer.'</em></strong>';
				return $output;
			}
		}
	}

?>
