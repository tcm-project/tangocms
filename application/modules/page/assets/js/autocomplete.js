$(document).ready(function(){$("div.jsSearchBox").show();$("#page-name").focus().autocomplete({serviceUrl:zula_dir_base+"page/config/autocomplete",onSelect:function(b,a){window.location=a}})});
