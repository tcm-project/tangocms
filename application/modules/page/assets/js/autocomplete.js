/*
 * Zula Framework Module (page)
 * --- Autocomplete/suggest feature
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Page
 */
$(document).ready(function(){$("div.jsSearchBox").show();$("#page-name").focus().autocomplete({serviceUrl:zula_dir_base+"page/config/autocomplete",onSelect:function(b,a){window.location=a}})});