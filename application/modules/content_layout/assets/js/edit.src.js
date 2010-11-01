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

	$(document).ready(
		function () {
			function content_layout_dm_update() {
				var layout_url = $('#content_layout-display-mode').val();
				// Display AJAX throbber
				$('div#content_layout-module-config').ajax_throbber();
				if ( layout_url == 'default' ) {
					$('div#content_layout-module-config').html('<p>There are no configuration values for this display mode</p>');
					return true;
				} else {
					if ( content_layout_config.length == 0 ) {
						content_layout_config = 'frontpage_layout=change';
					}
					$.post(
						layout_url,
						content_layout_config,
						function( data ) {
							var content = data.length < 1 ? '<p>There are no configuration values for this display mode</p>' : data;
							$('div#content_layout-module-config').hide();
							$('div#content_layout-module-config').html( content ).slideDown();
						}
					);
				}
			}
			content_layout_dm_update();
			$('#content_layout-display-mode').change( content_layout_dm_update );
			/**
			 * Toggles the custom title box depending on drop down value
			 */
			if ( $('#content_layout_title').val() != 'custom' ) {
				$('#content_layout_custom').hide();
			}
			$('#content_layout_title').change(
				function() {
					if ( $(this).val() == 'custom' ) {
						$('#content_layout_custom').show().focus();
					} else {
						$('#content_layout_custom').hide();
					}
				}
			);
		}
	);
