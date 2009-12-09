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
			$('textarea.editor_body').each(
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
						// Add in class so that TinyMCE knows where to load
						$(this).addClass('tinymce');
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

	// Check if TinyMCE is to be loaded
	if ( typeof tinyMCE !== 'undefined' ) {
		tinyMCE.init({
					mode: 'specific_textareas',
					editor_selector: 'tinymce',
					width: '100%',

					setup: function(ed) {
								ed.onSubmit.add(
									function(ed, event) {
										ed.setContent( editorJitShebang(
																		ed.getContent().replace( /<br><\/p>/i, '</p>'),
																		'html'
																		)
													  );
									}
								)
						   },

					document_base_url: zula_dir_base,
					convert_urls: false,
					relative_urls: false,

					cleanup_on_startup: true,
					doctype: '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
					element_format: 'html',
					extended_valid_elements: 'code,pre',

					fix_list_elements: true,
					fix_table_elements: true,
					fix_nesting: true,
					remove_trailing_nbsp: true,

					plugins: tcmEditor.tinymcePlugins,
					pagebreak_separator: '<!--break-->',
					inlinepopups_skin: 'tcmPop',

					// Advanced theme setup
					theme: 'advanced',
					skin: 'tcm',
					button_tile_map: true,
					theme_advanced_toolbar_align: 'left',
					theme_advanced_toolbar_location: 'top',
					theme_advanced_statusbar_location: 'bottom',
					theme_advanced_resizing: true,
					theme_advanced_resize_horizontal: false,
					theme_advanced_buttons1: 'bold,italic,underline,strikethrough,|,bullist,numlist,blockquote,|,justifyleft,justifycenter,justifyright,|,image,link,unlink,|,pagebreak,undo,redo',
					theme_advanced_buttons2: 'formatselect,fontsizeselect,forecolor,justifyfull,table,|,pastetext,pasteword,removeformat,|,media,charmap,|,outdent,indent,|,code,help',
					theme_advanced_buttons3: ''
					});
	}
