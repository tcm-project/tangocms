(function(e){function l(a,b,c){b="("+c.replace(m,"\\$1")+")";return a.replace(new RegExp(b,"gi"),"<strong>$1</strong>")}function j(a,b){this.el=e(a);this.el.attr("autocomplete","off");this.suggestions=[];this.data=[];this.badQueries=[];this.selectedIndex=-1;this.currentValue=this.el.val();this.intervalId=0;this.cachedResponse=[];this.onChangeInterval=null;this.ignoreValueChange=false;this.serviceUrl=b.serviceUrl;this.isLocal=false;this.options={autoSubmit:false,minChars:1,maxHeight:300,deferRequestBy:0,
width:0,highlight:true,params:{},fnFormatResult:l,delimiter:null,zIndex:9999};this.initialize();this.setOptions(b)}var m=new RegExp("(\\/|\\.|\\*|\\+|\\?|\\||\\(|\\)|\\[|\\]|\\{|\\}|\\\\)","g");e.fn.autocomplete=function(a){return new j(this.get(0),a)};j.prototype={killerFn:null,initialize:function(){var a,b,c;a=this;b=(new Date).getTime();c="Autocomplete_"+b;this.killerFn=function(f){if(e(f.target).parents(".autocomplete").size()===0){a.killSuggestions();a.disableKillerFn()}};if(!this.options.width)this.options.width=
this.el.width();this.mainContainerId="AutocompleteContainter_"+b;e('<div id="'+this.mainContainerId+'" style="position:absolute;z-index:9999;"><div class="autocomplete-w1"><div class="autocomplete" id="'+c+'" style="display:none; width:300px;"></div></div></div>').appendTo("body");this.container=e("#"+c);this.fixPosition();window.opera?this.el.keypress(function(f){a.onKeyPress(f)}):this.el.keydown(function(f){a.onKeyPress(f)});this.el.keyup(function(f){a.onKeyUp(f)});this.el.blur(function(){a.enableKillerFn()});
this.el.focus(function(){a.fixPosition()})},setOptions:function(a){var b=this.options;e.extend(b,a);if(b.lookup){this.isLocal=true;if(e.isArray(b.lookup))b.lookup={suggestions:b.lookup,data:[]}}e("#"+this.mainContainerId).css({zIndex:b.zIndex});this.container.css({maxHeight:b.maxHeight+"px",width:b.width})},clearCache:function(){this.cachedResponse=[];this.badQueries=[]},disable:function(){this.disabled=true},enable:function(){this.disabled=false},fixPosition:function(){var a=this.el.offset();e("#"+
this.mainContainerId).css({top:a.top+this.el.innerHeight()+"px",left:a.left+"px"})},enableKillerFn:function(){var a=this;e(document).bind("click",a.killerFn)},disableKillerFn:function(){var a=this;e(document).unbind("click",a.killerFn)},killSuggestions:function(){var a=this;this.stopKillSuggestions();this.intervalId=window.setInterval(function(){a.hide();a.stopKillSuggestions()},300)},stopKillSuggestions:function(){window.clearInterval(this.intervalId)},onKeyPress:function(a){if(!(this.disabled||
!this.enabled)){switch(a.keyCode){case 27:this.el.val(this.currentValue);this.hide();break;case 9:case 13:if(this.selectedIndex===-1){this.hide();return}this.select(this.selectedIndex);if(a.keyCode===9)return;break;case 38:this.moveUp();break;case 40:this.moveDown();break;default:return}a.stopImmediatePropagation();a.preventDefault()}},onKeyUp:function(a){if(!this.disabled){switch(a.keyCode){case 38:case 40:return}clearInterval(this.onChangeInterval);if(this.currentValue!==this.el.val())if(this.options.deferRequestBy>
0){var b=this;this.onChangeInterval=setInterval(function(){b.onValueChange()},this.options.deferRequestBy)}else this.onValueChange()}},onValueChange:function(){clearInterval(this.onChangeInterval);this.currentValue=this.el.val();var a=this.getQuery(this.currentValue);this.selectedIndex=-1;if(this.ignoreValueChange)this.ignoreValueChange=false;else a===""||a.length<this.options.minChars?this.hide():this.getSuggestions(a)},getQuery:function(a){var b;b=this.options.delimiter;if(!b)return e.trim(a);a=
a.split(b);return e.trim(a[a.length-1])},getSuggestionsLocal:function(a){var b,c,f,g,d;c=this.options.lookup;f=c.suggestions.length;b={suggestions:[],data:[]};a=a.toLowerCase();for(d=0;d<f;d++){g=c.suggestions[d];if(g.toLowerCase().indexOf(a)===0){b.suggestions.push(g);b.data.push(c.data[d])}}return b},getSuggestions:function(a){var b,c;if((b=this.isLocal?this.getSuggestionsLocal(a):this.cachedResponse[a])&&e.isArray(b.suggestions)){this.suggestions=b.suggestions;this.data=b.data;this.suggest()}else if(!this.isBadQuery(a)){c=
this;c.options.params.query=a;e.get(this.serviceUrl,c.options.params,function(f){c.processResponse(f)},"text")}},isBadQuery:function(a){for(var b=this.badQueries.length;b--;)if(a.indexOf(this.badQueries[b])===0)return true;return false},hide:function(){this.enabled=false;this.selectedIndex=-1;this.container.hide()},suggest:function(){if(this.suggestions.length===0)this.hide();else{var a,b,c,f,g,d,h,k;a=this;b=this.suggestions.length;f=this.options.fnFormatResult;g=this.getQuery(this.currentValue);
h=function(i){return function(){a.activate(i)}};k=function(i){return function(){a.select(i)}};this.container.hide().empty();for(d=0;d<b;d++){c=this.suggestions[d];c=e((a.selectedIndex===d?'<div class="selected"':"<div")+' title="'+c+'">'+f(c,this.data[d],g)+"</div>");c.mouseover(h(d));c.click(k(d));this.container.append(c)}this.enabled=true;this.container.show()}},processResponse:function(a){var b;try{b=eval("("+a+")")}catch(c){return}if(!e.isArray(b.data))b.data=[];this.cachedResponse[b.query]=b;
b.suggestions.length===0&&this.badQueries.push(b.query);if(b.query===this.getQuery(this.currentValue)){this.suggestions=b.suggestions;this.data=b.data;this.suggest()}},activate:function(a){var b,c;b=this.container.children();this.selectedIndex!==-1&&b.length>this.selectedIndex&&e(b.get(this.selectedIndex)).attr("class","");this.selectedIndex=a;if(this.selectedIndex!==-1&&b.length>this.selectedIndex){c=b.get(this.selectedIndex);e(c).attr("class","selected")}return c},deactivate:function(a,b){a.className=
"";if(this.selectedIndex===b)this.selectedIndex=-1},select:function(a){var b;if(b=this.suggestions[a]){this.el.val(b);if(this.options.autoSubmit){b=this.el.parents("form");b.length>0&&b.get(0).submit()}this.ignoreValueChange=true;this.hide();this.onSelect(a)}},moveUp:function(){if(this.selectedIndex!==-1)if(this.selectedIndex===0){this.container.children().get(0).className="";this.selectedIndex=-1;this.el.val(this.currentValue)}else this.adjustScroll(this.selectedIndex-1)},moveDown:function(){this.selectedIndex!==
this.suggestions.length-1&&this.adjustScroll(this.selectedIndex+1)},adjustScroll:function(a){var b,c;a=this.activate(a).offsetTop;b=this.container.scrollTop();c=b+this.options.maxHeight-25;if(a<b)this.container.scrollTop(a);else a>c&&this.container.scrollTop(a-this.options.maxHeight+25)},onSelect:function(a){var b,c,f;b=this;c=b.options.onSelect;f=b.suggestions[a];a=b.data[a];b.el.val(function(g){var d,h;d=b.options.delimiter;if(!d)return g;h=b.currentValue;d=h.split(d);if(d.length===1)return g;return h.substr(0,
h.length-d[d.length-1].length)+g}(f));e.isFunction(c)&&c(f,a)}}})(jQuery);
