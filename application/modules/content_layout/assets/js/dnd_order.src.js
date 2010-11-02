/*
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Content_layout
 */

$(document).ready( function() {
	/**
	 * Set the order of all the fields sequentially and remove
	 * the select box, changing it with a hidden input box
	 */
	function setOrder( element ) {
		var iteration = 1, sector = null;
		$("table#contentlayout tbody tr").each( function() {
			if ( $(this).hasClass("subheading") ) {
				sector = $(this).find("input.sector:first").val();
				iteration = 1;
			} else {
				// Update style to keep it odd-even
				$(this).removeClass("odd even");
				$(this).addClass( (iteration-1)%2 == 0 ? "even" : "odd");
				/**
				 * Replace/Update the type and order/sector of the inputs
				 */
				var el_order = $(this).find( element+":first");
				el_order.replaceWith('<input class="order-values" type="text" name="'+$(el_order).attr("name")+'" value="'+iteration+'">');

				var el_sector = $(this).find( element+":last");
				el_sector.replaceWith('<input class="order-values" type="text" name="'+$(el_sector).attr("name")+'" value="'+sector+'">');
				iteration++;
			}
		});
	}
	$("table#contentlayout").tableDnD({
										onDrop: function( table, row ) {
													setOrder("input.order-values");
													$(row).addClass("ondrop");
												}
										});
	$("table#contentlayout .order").hide();
	setOrder("select");
});
