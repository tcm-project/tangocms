/*
 * Zula Framework Module (module_manager) Drag and Drop
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Module_manager
 */
$(document).ready(function(){function a(b,c){b=(b=="select")?"select":"input";$("table#module_manager-loadorder .order "+b).each(function(d){$(this).replaceWith('<input type="text" name="'+$(this).attr("name")+'" value="'+(d+1)+'">')});$("table#module_manager-loadorder tbody tr").each(function(d){$(this).removeClass("odd even");$(this).addClass((d%2==0)?"even":"odd")});$(c).addClass("ondrop")}$("table#module_manager-loadorder").tableDnD({onDrop:a});a("select");$("table#module_manager-loadorder .order").hide()});