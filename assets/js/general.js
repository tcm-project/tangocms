RegExp.escape=function(a){if(!arguments.callee.sRE)arguments.callee.sRE=new RegExp("(\\/|\\.|\\*|\\+|\\?|\\||\\^|\\$|\\(|\\)|\\[|\\]|\\{|\\}|\\\\)","g");return a.replace(arguments.callee.sRE,"\\$1")};jQuery.fn.ajax_throbber=function(){this.html('<span class="ajax_throbber"><img src="'+zula_dir_icon+'/misc/throbber.gif"></span>')};
function acl_toggle(a){var b=$("."+a+":checkbox");b.size()!=b.filter(":checked").size()?$("."+a+":checkbox").each(function(){$(this).attr("checked","checked")}):$("."+a+":checkbox").each(function(){$(this).removeAttr("checked")})};
