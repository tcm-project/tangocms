/*!
 * Zula Framework Module (Editor) Specific
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Editor
 */

	/** Just In Time replacement/addition of the editor Shebang */
	var editorShebangs = new Array();
	function editorJitShebang( body, editorType ) {
		if ( editorType == 'html' && /^<p>#\!([A-Z0-9_\-]+)<\/p>/i.test( body ) ) {
			return body.replace( /^<p>#\!([A-Z0-9_\-]+)<\/p>/i, '#!$1');
		} else if ( /^#\!([A-Z0-9_\-]+)/i.test( body ) == false ) {
			return "#!" + editorType + "\n" + body;
		}
	}

	$(document).ready(
		function() {
			$('textarea.richTextEditor').each(
				function(i) {
					var tmpTextarea = this;
					shebang = /^#\!([A-Z0-9_\-]+)/i.exec( $(this).val() );
					if ( shebang == null ) {
						editorShebangs[i] = tcmEditor.defaultFormat.toLowerCase();
					} else {
						// Remove the shebang from the textarea (user does not need to see it)
						$(this).val( jQuery.trim( $(this).val().replace( /^#\![A-Z0-9_\-]+/i, '') ) );
						editorShebangs[i] = shebang[1].toLowerCase();
					}
					if ( editorShebangs[i] == 'html' ) {
						// Add in class so that CKEditor knows where to load
						$(this).addClass('ckeditor');
					}
					// Prepend the shebang back into the textarea on submit
					$(this).parents('form').submit(
						function() {
							var editorType = editorShebangs.shift();
							if ( editorType != 'html' ) {
								$(tmpTextarea).val( editorJitShebang( $(tmpTextarea).val(), editorType ) );
							}
							return true;
						}
					);
				}
			);
		}
	);