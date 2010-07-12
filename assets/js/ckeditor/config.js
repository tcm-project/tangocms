/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function(c)
{
	// Load any additional plugins, e.g. "foo,bar,car"
	c.extraPlugins = "";
	// Default configuration for TangoCMS
	c.baseHref = $("head > base").attr("href");
	c.docType = "<!DOCTYPE HTML>";
	c.height = 170;
	c.language = "en";
	c.resize_dir = "vertical";
	c.toolbarCanCollapse = false;
	c.toolbar = "tangocms";
	c.toolbar_tangocms = [
							["Bold","Italic","Underline","Strike","-","BulletedList","NumberedList","Blockquote","-",
							 "JustifyLeft","JustifyCenter","JustifyRight","JustifyBlock","-","Image","Link","Unlink",
							 "Undo","Redo"
							],
							"/",
							["Format","FontSize","TextColor","Table","-","PasteText","RemoveFormat",
							 "-","Flash","SpecialChar","-","Outdent","Indent","-","Source"
							]
						  ];
	// None of these exist in our ckeditor.js, so don"t enable the, :)
	c.removePlugins = "about,div,elementspath,find,forms,horizontalrule,maximize,newpage,pagebreak,pastefromword, \
					   popup,preview,print,save,scayt,showblocks,smiley,stylescombo,tab,templates,wsc";
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
