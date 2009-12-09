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
$(document).ready(function(){$("div.jsSearchBox").show();var a=zula_dir_base+"article/config/autocomplete";var b=$("#article-filter").val();if(b!==undefined&&b!=0){a+="/catId/"+b}$("#article-title").focus().autocomplete({serviceUrl:a,onSelect:function(d,c){window.location=c}})});