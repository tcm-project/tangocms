/*
 * Zula Framework Module (contact) Drag and Drop
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Contact
 */
$(document).ready(function(){function a(b,c){b=(b=="select")?"select":"input";$("table#contact-form-fields .order "+b).each(function(d){$(this).replaceWith('<input type="text" name="'+$(this).attr("name")+'" value="'+(d+1)+'">')});$("table#contact-form-fields tbody tr").each(function(d){$(this).removeClass("odd even");$(this).addClass((d%2==0)?"even":"odd")});$(c).addClass("ondrop")}$("table#contact-form-fields").tableDnD({onDrop:a});a("select");$("table#contact-form-fields .order").hide()});