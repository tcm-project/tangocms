/*
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Poll
 */

$(document).ready( function() {
	$("#pollAddOption").click( function() {
		var newLi = $("#pollOptionList li:last").clone( true );
		$(newLi).find("input[type=text]").val(""); // Reset the value to nothing
		$(newLi).insertAfter("#pollOptionList li:last");
	});
	$("#pollOptionList li input[type=button]").click(	function() {
		$(this).parent("li").remove();
	});
});
