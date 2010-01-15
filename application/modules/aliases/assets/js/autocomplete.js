$(document).ready(function(){$("div.jsSearchBox").show();$("#aliases-alias").focus().autocomplete({serviceUrl:zula_dir_base+"aliases/index/autocomplete",onSelect:function(b,a){window.location=a}})});
