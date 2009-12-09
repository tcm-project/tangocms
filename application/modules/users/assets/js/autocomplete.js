/*
 * Zula Framework Module (Users)
 * --- Autocomplete/suggest feature
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Users
 */
$(document).ready(function(){$("div.jsSearchBox").show();$("#users-username").focus().autocomplete({serviceUrl:zula_dir_base+"users/config/autocomplete",onSelect:function(b,a){window.location=a}})});