// $Id: dnd_order.src.js 2768 2009-11-13 18:12:34Z alexc $

/*!
 * Zula Framework Module (content_layout) Drag and Drop
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Content_layout
 */

 	/**
 	 * Drag and Drop init for the correct table
 	 */
	$(document).ready(
		function() {
			/**
				* Set the order of all the fields sequentially and remove
				* the select box, changing it with a hidden input box
				*/
			function set_order( element ) {
				var iteration = 1;
				var sector = null;
				$('table#content_layout tbody tr').each(
					function() {
						if ( $(this).hasClass('subheading') ) {
							sector = $(this).find('input.sector:first').val();
							iteration = 1;
						} else {
							// Update style to keep it odd-even
							$(this).removeClass('odd even');
							$(this).addClass( (iteration-1)%2 == 0 ? 'even' : 'odd');
							/**
								* Replace/Update the type and order/sector of the inputs
								*/
							var el_order = $(this).find( element+':first');
							el_order.replaceWith('<input class="order-values" type="text" name="'+$(el_order).attr('name')+'" value="'+iteration+'">');

							var el_sector = $(this).find( element+':last');
							el_sector.replaceWith('<input class="order-values" type="text" name="'+$(el_sector).attr('name')+'" value="'+sector+'">');
							iteration++;
						}
					}
				);
			}
			$('table#content_layout').tableDnD({
													onDrop: function( table, row ) {
																set_order('input.order-values');
																$(row).addClass('ondrop');
															}
												 });
			set_order('select');
			$('table#content_layout .order').hide();
		}
	);

