// $Id: jumpbox.src.js 2768 2009-11-13 18:12:34Z alexc $

/*!
 * Zula Framework Module (Article)
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Article
 */

	$(document).ready(
		function() {
			$('#article-jumpbox').change(
				function() {
					window.location = $(this).val();
				}
			);
		}
	);
