/*!
 * Zula Framework Module (page)
 * --- Autocomplete/suggest feature
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Page
 */
$(document).ready(
	function() {
		$("div.jsSearchBox").show();
		$("#page-name").autocomplete({
									serviceUrl: zula_dir_base+"index.php?url=page/config/autocomplete",
									onSelect: function(value, data) {
												window.location = data;
											}
									});
		$("#page-name").focus();
	}
);
