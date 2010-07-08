$(document).ready(function(){$("div.jsSearchBox").show();$("#users-username").focus().autocomplete({serviceUrl:zula_dir_base+"users/config/autocomplete",onSelect:function(b,a){window.location=a}})});
