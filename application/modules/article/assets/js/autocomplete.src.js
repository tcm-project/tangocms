/*
 * Zula Framework Module
 * Autocomplete/suggest feature
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @author Robert Clipsham
 * @copyright Copyright (C) 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Page
 */

$(document).ready( function() {
	$("div.jsSearchBox").show();
	// Find out which URL to use, only search in category if we have specified category.
	var ajaxUrl = zula_dir_base+"index.php?url=article/config/autocomplete";
	var cid = $("#article-filter").val();
	if ( cid !== undefined && cid != 0 ) {
		ajaxUrl += '/catId/'+cid;
	}
	$("#article-title").autocomplete({
									serviceUrl: ajaxUrl,
									onSelect: function(value, data) {
												window.location = data;
											}
									});
	$("#article-title").focus();
});
