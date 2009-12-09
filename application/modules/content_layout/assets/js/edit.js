/*
 * Zula Framework Module (content_layout) AJAX
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2007, 2008, 2009 Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Content_Layout
 */
$(document).ready(function(){function a(){var b=$("#content_layout-display-mode").val();$("div#content_layout-module-config").ajax_throbber();if(b=="default"){$("div#content_layout-module-config").html("<p>There are no configuration values for this display mode</p>");return true}else{if(content_layout_config.length==0){content_layout_config="frontpage_layout=change"}$.post(b,content_layout_config,function(d){var c=d.length<1?"<p>There are no configuration values for this display mode</p>":d;$("div#content_layout-module-config").hide();$("div#content_layout-module-config").html(c).slideDown()})}}a();$("#content_layout-display-mode").change(a);if($("#content_layout_title").val()!="custom"){$("#content_layout_custom").hide()}$("#content_layout_title").change(function(){if($(this).val()=="custom"){$("#content_layout_custom").show().focus()}else{$("#content_layout_custom").hide()}})});