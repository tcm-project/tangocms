/*
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Module_manager
 */

$(document).ready( function() {
	/**
	 * Set the order of all the fields sequentially and remove
	 * the select box, changing it with a hidden input box
	 */
	function setOrder( element, row ) {
		element = (element == "select") ? "select" : "input";
		$("table#module_manager-loadorder .order "+element ).each(
			function(i) {
				$(this).replaceWith('<input type="text" name="'+$(this).attr("name")+'" value="'+(i+1)+'">');
				}
		);
		// Update the odd/even class of the rows
		$("table#module_manager-loadorder tbody tr").each( function(i) {
			$(this).removeClass("odd even");
			$(this).addClass( (i % 2 == 0) ? "even" : "odd");
		});
		$(row).addClass("ondrop");
	}
	$("table#module_manager-loadorder").tableDnD({onDrop: setOrder});
	$("table#module_manager-loadorder .order").hide();
	setOrder("select");
});
