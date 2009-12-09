<?php

/**
 * Zula Framework Validator (CCNum - Credit Card Numbers)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU/LGPL 2.1
 * @package Zula_Validator
 */

	class Validator_ccnum extends Validator_Base {

		/**
		 * Runs the needed checks to see if the value is valid. A non
		 * true value will be returned if it failed.
		 *
		 * @param mixed $value
		 * @return bool|string
		 */
		public function validate( $value ) {
			$value = preg_replace( '#[ \-]#', '', $value );
			if ( trim( $value ) ) {
				// Get length and parity
				$numberLen = strlen( $value );
				$parity = $numberLen % 2;
				// Loop each digit and do the needed calculations
				$total = 0;
				for( $i = 0; $i < $numberLen; $i++ ) {
					$digit = $value[ $i ];
					if ( $i % 2 == $parity ) {
						$digit *= 2;
						if ( $digit > 9 ) {
							$digit -= 9;
						}
					}
					$total += $digit;
				}
				if ( $total % 10 == 0 ) {
					return true;
				}
			}
			return t('%1$s must be a valid Credit Card Number', Locale::_DTD);
		}

	}

?>
