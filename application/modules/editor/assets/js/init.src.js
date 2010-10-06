/*!
 * Zula Framework Module (Editor) Specific
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Editor
 */

$(document).ready(
	function() {
		var editorShebangs = new Array(),
			ckeditorInstances = new Array();
		$("textarea.richtext").each(
			function(i) {
				var tmpTextarea = this;
				shebang = /^#\!([A-Z0-9_\-]+)/i.exec( $(this).val() );
				if ( shebang == null ) {
					editorShebangs[i] = tcmEditor.defaultFormat.toLowerCase();
				} else {
					// Remove the shebang from the textarea (user does not need to see it)
					$(this).val( jQuery.trim( $(this).val().replace(/^#\![A-Z0-9_\-]+/i, "") ) );
					editorShebangs[i] = shebang[1].toLowerCase();
				}
				if ( editorShebangs[i] == "html" && typeof CKEDITOR != undefined ) {
					ckeditorInstances[i] = CKEDITOR.replace( tmpTextarea );
				}
				// Prepend the shebang back into the textarea on submit
				$(this).parents("form").submit(
					function(event) {
						var editorType = editorShebangs.shift();
						if ( editorType == "html" && typeof CKEDITOR != undefined ) {
							ckeditorInstances.shift().destroy();
						}
						var editorVal = $(tmpTextarea).val();
						if ( editorType == "html" && /^<p>#\!([A-Z0-9_\-]+)<\/p>/i.test( editorVal ) ) {
							$(tmpTextarea).val( editorVal.replace(/<p>#\!([A-Z0-9_\-]+)<\/p>/i, "#!$1") );
						} else if ( /^#\!([A-Z0-9_\-]+)/i.test( editorVal ) == false ) {
							$(tmpTextarea).val( "#!" + editorType + "\n" + editorVal );
						}
						return true;
					}
				);
			}
		);
	}
);
