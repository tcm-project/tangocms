/*
 * Zula Framework General JS
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package Zula
 */
RegExp.escape=function(b){if(!arguments.callee.sRE){var a=["/",".","*","+","?","|","^","$","(",")","[","]","{","}","\\"];arguments.callee.sRE=new RegExp("(\\"+a.join("|\\")+")","g")}return b.replace(arguments.callee.sRE,"\\$1")};jQuery.fn.ajax_throbber=function(){this.html('<span class="ajax_throbber"><img src="'+zula_dir_icon+'/misc/throbber.gif"></span>')};function acl_toggle(a){var b=$("."+a+":checkbox");if(b.size()!=b.filter(":checked").size()){$("."+a+":checkbox").each(function(){$(this).attr("checked","checked")})}else{$("."+a+":checkbox").each(function(){$(this).removeAttr("checked")})}};