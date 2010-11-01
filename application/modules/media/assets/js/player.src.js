/*
 * Zula Framework Module
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009, 2010 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Media
 */

$(document).ready(
	function() {
		var fpConfig = {
						canvas: {backgroundColor: "#0C1013", backgroundGradient: "low", border: "1px solid #000", borderRadius: "10"},
						clip: {autoPlay: true, scaling: "fit"}
						}
		$("a.mediaPlayer").each( function() {
			if ( $(this).hasClass("audio") ) {
				fpConfig = $.extend(fpConfig,
									{
										plugins: {
											controls: {autoHide: false}
										},
										clip: {type: "audio"}
									});
			}
			$(this).flowplayer( {src: zula_dir_js+"/flowplayer/flowplayer-3.2.3.swf", wmode: "transparent"}, fpConfig );
		});
	}
);