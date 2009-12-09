
/*!
 * TangoCMS Media
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Media
 */

$(document).ready(
	function() {
		$("a.media_player").flowplayer(
										{src: zula_dir_js+"/flowplayer/flowplayer.swf", wmode: "transparent"},
										{
											clip: {
													autoPlay: true,
													scaling: "fit"
											},
											plugins: {
														audio: {url: zula_dir_js+"/flowplayer/flowplayer-audio.swf"}
											}
										}
									);
	}
);