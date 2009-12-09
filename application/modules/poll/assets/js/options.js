/*
 * TangoCMS Poll
 *
 * @patches submit all patches to patches@tangocms.org
 *
 * @author Alex Cartwright
 * @copyright Copyright (C) 2009, Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 * @package TangoCMS_Poll
 */
$(document).ready(function(){$("#poll-add-option").click(function(){var a=$("#poll-option-list li:last").clone(true);$(a).find("input[type=text]").val("");$(a).insertAfter("#poll-option-list li:last")});$("#poll-option-list li input[type=button]").click(function(){$(this).parent("li").remove()})});