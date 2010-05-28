
/*!
 * TangoCMS Media
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Media
 */

$(document).ready(
	function() {
		$("a.mediaPlayer").each( function() {
			if ( $(this).hasClass("audio") ) {
				var playerOpts = {
									plugins: {
										controls: {autoHide: false},
										audio: {url: zula_dir_js+"/flowplayer/flowplayer.audio.swf"}
									},
									playlist: [{url: $(this).attr("href"), provider: "audio"}]
								}
			} else {
				var playerOpts = {
									plugins: {
										pseudo: {
											url: zula_dir_js+"/flowplayer/flowplayer.pseudostreaming.swf",
											queryString: escape("&start=${start}")
										}
									},
									playlist: [{url: $(this).attr("href"), provider: "pseudo"}]
								}
			}
			$(this).flowplayer( {src: zula_dir_js+"/flowplayer/flowplayer.swf", wmode: "transparent"}, playerOpts );
		});
	}
);