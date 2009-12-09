/*
 * Zula Framework Module Manager Search
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Module_Manager
 */
$(document).ready(function(){$("div.jsSearchBox").show();$("#module_manager-filter").focus().keyup(function(d){var b=RegExp.escape($("#module_manager-filter").val());$("ol.module_manager-item li").each(function(){var f=jQuery.trim($(this).find("p a.title").text());var e=new RegExp(b,"gi");if(f.match(e)){$(this).show()}else{$(this).hide()}});var a=0;var c;$("div.module_manager-category").each(function(){$(this).show();var e=$(this).find("li:visible");if(e.length>0){c=e.eq(0)}else{$(this).hide()}a+=e.length});if(a==1&&d.keyCode==13){if(d.shiftKey){location.href=c.find("p a.module_manager-item-permission").attr("href")}else{location.href=c.find("p a.title").attr("href")}}if(a>0){$("#module_manager-filter").removeClass("error")}else{if($("#module_manager-filter").hasClass("error")==false){$("#module_manager-filter").addClass("error")}}})});