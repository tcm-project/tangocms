/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function(c)
{
	// Default configuration
	c.baseHref = $("head > base").attr("href");
	c.docType = "<!DOCTYPE HTML>";
	c.height = 170;
	c.language = "en";
	c.resize_dir = "vertical";
	c.toolbarCanCollapse = false;
	c.toolbar = "average";
	c.toolbar_average = [
							["Bold","Italic","Underline","Strike"],
							["BulletedList","NumberedList","Blockquote"],
							["JustifyLeft","JustifyCenter","JustifyRight","JustifyBlock"],
							["Image","Link","Unlink"],
							["Outdent","Indent"],
							["Undo","Redo"],
							"/",
							["Format","FontSize","TextColor"],
							["PasteText","PasteFromWord","RemoveFormat"],
							["Table","Flash","SpecialChar"],
							["Form", "Checkbox", "Radio", "TextField", "Textarea", "Select", "Button", "ImageButton", "HiddenField"],
							["Source"]
						  ];
	// Load any additional plugins, e.g. "foo,bar,car"
	c.extraPlugins = "tableresize";
	if ( typeof CKEDITOR_UPLOAD_URL !== "undefined" ) {
		c.filebrowserUploadUrl = CKEDITOR_UPLOAD_URL;
	}
};

CKEDITOR.on("instanceReady",
			function(ev) {
				ev.editor.dataProcessor.writer.setRules( "p", {indent: false, breakAfterOpen: false} );
				ev.editor.dataProcessor.writer.selfClosingEnd = ">";
			}
		   );
