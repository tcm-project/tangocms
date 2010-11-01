/*
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Contact
 */

$(document).ready( function() {
	/**
	 * Set the order of all the fields sequentially and remove
	 * the select box, changing it with a hidden input box
	 */
	function setOrder( element, row ) {
		element = (element == "select") ? "select" : "input";
		$("table#contact-form-fields .order "+element ).each( function(i) {
			$(this).replaceWith('<input type="text" name="'+$(this).attr("name")+'" value="'+(i+1)+'">');
		});
		// Update the odd/even class of the rows
		$("table#contact-form-fields tbody tr").each( function(i) {
			$(this).removeClass("odd even");
			$(this).addClass( (i % 2 == 0) ? "even" : "odd");
		});
		$(row).addClass("ondrop");
	}
	$("table#contact-form-fields").tableDnD({onDrop: setOrder});
	$("table#contact-form-fields .order").hide();
	setOrder("select");
});
