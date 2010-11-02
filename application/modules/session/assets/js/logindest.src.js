/*
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Session
 */

$(document).ready( function() {
	if ( $("#sessionLoginDestination").val() != "custom" ) {
		$("#sessionLoginDestinationUrl").hide();
	}
	$("#sessionLoginDestination").change( function() {
		if ( $(this).val() == "custom" ) {
			$("#sessionLoginDestinationUrl").show().focus();
		} else {
			$("#sessionLoginDestinationUrl").hide();
		}
	});
});
