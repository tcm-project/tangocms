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
 * @package TangoCMS_Users
 */

$(document).ready( function() {
	$("div.jsSearchBox").show();
	$("#usersFilter").autocomplete({
									serviceUrl: zula_dir_base+"index.php?url=users/config/autocomplete",
									onSelect: function(value, data) {
												window.location = data;
											}
									});
	$("#usersFilter").focus();
});
