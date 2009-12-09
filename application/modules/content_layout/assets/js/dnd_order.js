/*
 * Zula Framework Module (content_layout) Drag and Drop
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Content_layout
 */
$(document).ready(function(){function a(c){var d=1;var b=null;$("table#content_layout tbody tr").each(function(){if($(this).hasClass("subheading")){b=$(this).find("input.sector:first").val();d=1}else{$(this).removeClass("odd even");$(this).addClass((d-1)%2==0?"even":"odd");var e=$(this).find(c+":first");e.replaceWith('<input class="order-values" type="text" name="'+$(e).attr("name")+'" value="'+d+'">');var f=$(this).find(c+":last");f.replaceWith('<input class="order-values" type="text" name="'+$(f).attr("name")+'" value="'+b+'">');d++}})}$("table#content_layout").tableDnD({onDrop:function(b,c){a("input.order-values");$(c).addClass("ondrop")}});a("select");$("table#content_layout .order").hide()});