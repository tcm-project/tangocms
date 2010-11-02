/*
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Content_Layout
 */

$(document).ready( function () {
	function content_layout_dm_update() {
		var layout_url = $("#contentlayoutDM").val();
		// Display AJAX throbber
		$("div#contentlayoutConfig").ajax_throbber();
		if ( layout_url == "default" ) {
			$("div#contentlayoutConfig").html("<p>There are no configuration values for this display mode</p>");
			return true;
		} else {
			if ( content_layout_config.length == 0 ) {
				content_layout_config = "frontpage_layout=change";
			}
			$.post(
				layout_url,
				content_layout_config,
				function( data ) {
					var content = data.length < 1 ? "<p>There are no configuration values for this display mode</p>" : data;
					$("div#contentlayoutConfig").hide();
					$("div#contentlayoutConfig").html( content ).slideDown();
				}
			);
		}
	}
	content_layout_dm_update();
	$("#contentlayoutDM").change( content_layout_dm_update );
	/**
	 * Toggles the custom title box depending on drop down value
	 */
	if ( $("#contentlayoutTitle").val() != "custom" ) {
		$("#contentlayoutCustomTitle").hide();
	}
	$("#contentlayoutTitle").change( function() {
		if ( $(this).val() == "custom" ) {
			$("#contentlayoutCustomTitle").show().focus();
		} else {
			$("#contentlayoutCustomTitle").hide();
		}
	});
});
