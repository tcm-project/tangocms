
/*!
 * Zula Framework General JS
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package Zula
 */

 	RegExp.escape = function( text ) {
						if (!arguments.callee.sRE) {
							var specials = [
						  		'/', '.', '*', '+', '?', '|', '^', '$',
						  		'(', ')', '[', ']', '{', '}', '\\'
							];
							arguments.callee.sRE = new RegExp('(\\' + specials.join('|\\') + ')', 'g');
					  	}
					  	return text.replace(arguments.callee.sRE, '\\$1');
					}

	/**
	 * jQuery plugin to add in or remove the AJAX throbber
	 *
	 * [@param bool $enable]
	 * [@param string $size]
	 * @return bool
	 */
	jQuery.fn.ajax_throbber = function() {
								this.html('<span class="ajax_throbber"><img src="'+zula_dir_icon+'/misc/throbber.gif"></span>');
							};

 	/**
 	 * Toggle function for the ACL form that is displayed,
 	 * will toggle all of the Role/Resource checkboxes for a given
 	 * resource or Role
 	 *
 	 * @param string $class
 	 * @return void
 	 */
	function acl_toggle( name ) {
		var check_boxes = $('.'+name+':checkbox');
		if ( check_boxes.size() != check_boxes.filter(':checked').size() ) {
			// Check boxes need to be checked
			$('.'+name+':checkbox').each(
				function() {
					$(this).attr('checked', 'checked');
				}
			);
		} else {
			$('.'+name+':checkbox').each(
				function() {
					$(this).removeAttr('checked');
				}
			);
		}
	}
