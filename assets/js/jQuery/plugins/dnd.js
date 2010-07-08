jQuery.tableDnD={currentTable:null,dragObject:null,mouseOffset:null,oldY:0,build:function(a){this.each(function(){this.tableDnDConfig=jQuery.extend({onDragStyle:null,onDropStyle:null,onDragClass:"tDnD_whileDrag",onDrop:null,onDragStart:null,scrollAmount:5,serializeRegexp:/[^\-]*$/,serializeParamName:null,dragHandle:null},a||{});jQuery.tableDnD.makeDraggable(this)});jQuery(document).bind("mousemove",jQuery.tableDnD.mousemove).bind("mouseup",jQuery.tableDnD.mouseup);return this},makeDraggable:function(a){var b=
a.tableDnDConfig;a.tableDnDConfig.dragHandle?jQuery("td."+a.tableDnDConfig.dragHandle,a).each(function(){jQuery(this).mousedown(function(c){jQuery.tableDnD.dragObject=this.parentNode;jQuery.tableDnD.currentTable=a;jQuery.tableDnD.mouseOffset=jQuery.tableDnD.getMouseOffset(this,c);b.onDragStart&&b.onDragStart(a,this);return false})}):jQuery("tr",a).each(function(){var c=jQuery(this);c.hasClass("nodrag")||c.mousedown(function(d){if(d.target.tagName=="TD"){jQuery.tableDnD.dragObject=this;jQuery.tableDnD.currentTable=
a;jQuery.tableDnD.mouseOffset=jQuery.tableDnD.getMouseOffset(this,d);b.onDragStart&&b.onDragStart(a,this);return false}}).css("cursor","move")})},updateTables:function(){this.each(function(){this.tableDnDConfig&&jQuery.tableDnD.makeDraggable(this)})},mouseCoords:function(a){if(a.pageX||a.pageY)return{x:a.pageX,y:a.pageY};return{x:a.clientX+document.body.scrollLeft-document.body.clientLeft,y:a.clientY+document.body.scrollTop-document.body.clientTop}},getMouseOffset:function(a,b){b=b||window.event;
var c=this.getPosition(a),d=this.mouseCoords(b);return{x:d.x-c.x,y:d.y-c.y}},getPosition:function(a){var b=0,c=0;if(a.offsetHeight==0)a=a.firstChild;for(;a.offsetParent;){b+=a.offsetLeft;c+=a.offsetTop;a=a.offsetParent}b+=a.offsetLeft;c+=a.offsetTop;return{x:b,y:c}},mousemove:function(a){if(jQuery.tableDnD.dragObject!=null){var b=jQuery(jQuery.tableDnD.dragObject),c=jQuery.tableDnD.currentTable.tableDnDConfig,d=jQuery.tableDnD.mouseCoords(a);a=d.y-jQuery.tableDnD.mouseOffset.y;var e=window.pageYOffset;
if(document.all)if(typeof document.compatMode!="undefined"&&document.compatMode!="BackCompat")e=document.documentElement.scrollTop;else if(typeof document.body!="undefined")e=document.body.scrollTop;if(d.y-e<c.scrollAmount)window.scrollBy(0,-c.scrollAmount);else(window.innerHeight?window.innerHeight:document.documentElement.clientHeight?document.documentElement.clientHeight:document.body.clientHeight)-(d.y-e)<c.scrollAmount&&window.scrollBy(0,c.scrollAmount);if(a!=jQuery.tableDnD.oldY){d=a>jQuery.tableDnD.oldY;
jQuery.tableDnD.oldY=a;c.onDragClass?b.addClass(c.onDragClass):b.css(c.onDragStyle);if(b=jQuery.tableDnD.findDropTargetRow(b,a))if(d&&jQuery.tableDnD.dragObject!=b)jQuery.tableDnD.dragObject.parentNode.insertBefore(jQuery.tableDnD.dragObject,b.nextSibling);else!d&&jQuery.tableDnD.dragObject!=b&&jQuery.tableDnD.dragObject.parentNode.insertBefore(jQuery.tableDnD.dragObject,b)}return false}},findDropTargetRow:function(a,b){for(var c=jQuery.tableDnD.currentTable.rows,d=0;d<c.length;d++){var e=c[d],f=
this.getPosition(e).y,g=parseInt(e.offsetHeight)/2;if(e.offsetHeight==0){f=this.getPosition(e.firstChild).y;g=parseInt(e.firstChild.offsetHeight)/2}if(b>f-g&&b<f+g){if(e==a)return null;c=jQuery.tableDnD.currentTable.tableDnDConfig;return c.onAllowDrop?c.onAllowDrop(a,e)?e:null:jQuery(e).hasClass("nodrop")?null:e}}return null},mouseup:function(){if(jQuery.tableDnD.currentTable&&jQuery.tableDnD.dragObject){var a=jQuery.tableDnD.dragObject,b=jQuery.tableDnD.currentTable.tableDnDConfig;b.onDragClass?
jQuery(a).removeClass(b.onDragClass):jQuery(a).css(b.onDropStyle);jQuery.tableDnD.dragObject=null;b.onDrop&&b.onDrop(jQuery.tableDnD.currentTable,a);jQuery.tableDnD.currentTable=null}},serialize:function(){return jQuery.tableDnD.currentTable?jQuery.tableDnD.serializeTable(jQuery.tableDnD.currentTable):"Error: No Table id set, you need to set an id on your table and every row"},serializeTable:function(a){for(var b="",c=a.id,d=a.rows,e=0;e<d.length;e++){if(b.length>0)b+="&";var f=d[e].id;if(f&&f&&a.tableDnDConfig&&
a.tableDnDConfig.serializeRegexp)f=f.match(a.tableDnDConfig.serializeRegexp)[0];b+=c+"[]="+f}return b},serializeTables:function(){var a="";this.each(function(){a+=jQuery.tableDnD.serializeTable(this)});return a}};jQuery.fn.extend({tableDnD:jQuery.tableDnD.build,tableDnDUpdate:jQuery.tableDnD.updateTables,tableDnDSerialize:jQuery.tableDnD.serializeTables});
