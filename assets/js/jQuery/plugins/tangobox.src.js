/*!
 * Tangobox, part of the TangoCMS Project
 * --- jQuery plugin based upon FancyBox by Janis Skarnelis, and ideas taken from jQuery
 * lightbox by Leandro Vieira Pinho.
 *
 * Changes we basically do to Fancybox is remove MSIE6 support, default support for certain
 * selectors, improve the markup/css and major code cleanups. It also handles loading images
 * that the URL does not contain the file extension in.
 *
 * @author Alex Cartwright
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL 2
 */

jQuery(document).ready(
	function() {
		// Add support for selectors, so all you need to do is add this JS file.
		jQuery("a[rel=modal]").tangobox();
		jQuery("a[rel=modalImage]").tangobox();
	}
);
 
(function($) {
	$.fn.tangobox = function( settings ) {
		var settings = $.extend(
								{
									imageScale:				true,
									zoomOpacity:			true,
									zoomSpeedIn:			500,
									zoomSpeedOut:			"slow",
									frameWidth:				560,
									frameHeight:			340,
									overlayShow:			true,
									overlayOpacity:			0.7,
									overlayColor:			'#444',
									showCloseButton:		true,
									callbackOnStart:		null,
									callbackOnShow:			null,
									callbackOnClose:		null
								},
								settings
							  );
		var matchedGroup = this; // 'this' refers to the match items passed into the plugin, from the selector.		
		var elem; // Element the user 'clicked' on.
		var itemArray = [], itemIndex = 0, busy = false, imageRegExp = /\.(jpe?g|gif|png)($|\?)?$/i;
		var tbOuter, tbContent; // Store the selectors, saves selecting every time

		/**
		 * Begin the whole process of the Tangobox
		 *
		 * @return bool false
		 */
		function _initialize() {
			if ( !busy ) {
				elem = this;
				if ( $.isFunction( settings.callbackOnStart ) ) {
					settings.callbackOnStart();
				}
				if ( !$("#tbOverlay").length ) {
					var html = '<div id="tbOverlay"></div> \
								<div id="tbWrap"> \
									<div id="tbOuter"> \
										<div id="tbInner"> \
											<div id="tbContent"></div> \
											<div id="tbNav"> \
												<a href="" id="tbNavPrev"></a> \
												<a href="" id="tbNavNext"></a> \
											</div> \
											<div id="tbTitle"></div> \
										</div> \
									</div> \
								</div>';
					$(html).appendTo("body");
				}
				tbOuter = $("#tbOuter");
				tbContent = $("#tbContent");
				// Update the overlay and change item to the first one.
				if ( settings.overlayShow ) {
					$("#tbOverlay").css({
										backgroundColor: 	settings.overlayColor,
										opacity:			settings.overlayOpacity
										}).fadeIn(200);
				} else {
					$("tbOverlay").hide();
				}				
				// Use all elements that were matched in the selector
				var slideShowLength = matchedGroup.length;
				for( var i = 0; i < slideShowLength; i++ ) {
					itemArray[i] = {
									elem: matchedGroup[i],
									href: $(matchedGroup[i]).attr("href"),
									title: $(matchedGroup[i]).attr("title"),
									rel: $(matchedGroup[i]).attr("rel")
									};
					if ( itemArray[i].href == $(elem).attr("href") ) {
						itemIndex = i;
					}
				}				
				tbChangeContent();
			}
			return false;
		};

		/**
		 * Changes the content that is to be displayed in the main content
		 * area. The itemIndex of the item which was shown will be returned.
		 *
		 * @return int
		 */
		function tbChangeContent() {
			$("#tbNavPrev, #tbNavNext, #tbClose, #tbTitle").hide();
			var currentItem = itemArray[ itemIndex ];
			if ( tbOuter.is(":visible") === false ) {
				tbOuter.css("top", "120px").show();
			}
			tbOuter.addClass("inProgress");
			if ( currentItem.href.match(/#/) ) {
				// Display an element within the content
				tbOuter.addClass("typeInline");
				tbSetContent( $(window.location.hash).html(), settings.frameWidth, settings.frameHeight );
			} else if ( elem.rel == "modalImage" || currentItem.href.match(imageRegExp) ) {
				// Display image
				tbOuter.addClass("typeImage");
				if ( tbContent.is(":visible") ) {
					tbContent.fadeOut();
				}
				var imagePreloader = new Image;
				imagePreloader.onload = function() {
											var imgX = imagePreloader.width, imgY = imagePreloader.height;
											var vpX = $(window).width() - 120, vpY = $(window).height() - 120; // Viewport Height/Width
											if ( settings.imageScale && (imgX > vpX || imgY > vpY) ) {
												var ratio = Math.min( Math.min(vpX, imgX) / imgX, Math.min(vpY, imgY) / imgY );
												imgX = Math.round( ratio * imgX );
												imgY = Math.round( ratio * imgY );
											}
											tbSetContent('<img alt="" id="tbImage" src="'+imagePreloader.src+'" height="'+imgY+'" width="'+imgX+'">', imgX, imgY );
										};
				imagePreloader.src = currentItem.href;
			} else {
				// Display 'AJAX' content.
				tbOuter.addClass("typeAjax");
				$.get( currentItem.href, function(data) {
											// Get all query string args to check for width/height
											var args = {}, splitQuery = currentItem.elem.search.replace("?", "").split("&"), tmpVal;
											var splitCount = splitQuery.length;
											for( var i = 0; i < splitCount; i++ ) {
												tmpVal = splitQuery[i].split("=");
												args[ tmpVal[0] ] = tmpVal[1];
											}									
											tbSetContent( '<div id="tbInline">'+data+'</div>', (args.width || settings.frameWidth), (args.height || settings.frameHeight) );
										 }
					 );
			}
			return itemIndex;
		};

		/**
		 * Sets the content that is to be shown.
		 *
		 * @return void
		 */
		function tbSetContent( content, cWidth, cHeight ) {			
			busy = true;
			cWidth = parseInt( cWidth );
			cHeight = parseInt( cHeight );
			// Get viewports height/width to center the content
			var vpY = $(window).height(), vpScrollY = $(window).scrollTop();
			var tbPadding = tbOuter.css("paddingTop").replace(/[^\d\.]/g, "") * 2;
			var cTop = (cHeight + tbPadding) / 2;
			var tbOuterCssTop = (vpY/2 - cTop)+vpScrollY+"px";		
			if ( tbOuter.is(":visible") && cWidth == tbOuter.width() && cHeight == tbOuter.height() ) {
				var dimSettings = {width: cWidth}; // just to keep jQuery happy, it needs some animation.
			} else {					
				var dimSettings = {
									height: cHeight+"px",
									width: cWidth+"px"
								  };
			}			
			if ( tbOuterCssTop !== tbOuter.css("top") ) {
				dimSettings.top = tbOuterCssTop;
				if ( tbOuter.is(":visible") == false && vpScrollY !== 0 && dimSettings.top.replace(/[^\d\.]/g, "") > vpScrollY ) {
					// Bring the initial tbOuter into the viewport, then resize from there.
					tbOuter.css("top", vpScrollY+"px");
				}					
			}
			tbOuter.show();
			tbContent.fadeOut("normal", function() {
											tbContent.empty();
											tbOuter.animate( dimSettings,
															 settings.zoomSpeedIn,
															 function() {
																tbContent.html( $(content) );
																tbContent.fadeIn("normal", tbFinish);
															 }
														);
										}
								);
		};

		/**
		 * Handles finishing up of loading the content (from tbSetContent)
		 *
		 * @return void
		 */
		function tbFinish() {
			// Preload neighbour images
			var preloadImgs = [];
			if ( (itemArray.length - 1) > itemIndex ) {
				preloadImgs[0] = itemArray[ itemIndex+1 ];
			}
			if ( itemIndex > 0 ) {
				preloadImgs[1] = itemArray[ itemIndex-1 ];
			}
			for( var i in preloadImgs ) {
				var imgItem = preloadImgs[i];
				if ( imgItem.rel == "modalImage" || imgItem.href.match(imageRegExp) ) {
					objNext = new Image();
					objNext.src = imgItem.href;
				}
			}
			/**
			 * Setup the navigation (Next/Prev) buttons for images only
			 */
			if ( itemArray[ itemIndex ].rel == "modalImage" || itemArray[ itemIndex ].href.match(imageRegExp) ) {
				if ( itemIndex !== 0 ) {
					$("#tbNavPrev").show().unbind().click( function() {
																--itemIndex;
																tbChangeContent();
																return false;
															}
														  );
				}
				if ( itemIndex !== (itemArray.length-1) ) {
					$("#tbNavNext").show().unbind().click( function() {
																++itemIndex;
																tbChangeContent();
																return false;
															}
														  );
				}
			}
			// Different ways of closing the modal box
			$(document).bind("keyup", tbClose);
			tbContent.bind("click", tbClose);
			$("#tbOuter").removeClass("inProgress");
			if ( $.isFunction( settings.callbackOnShow ) ) {
				settings.callbackOnShow( itemArray[ itemIndex ] );
			}
			busy = false;
		};

		/**
		 * Handles closing of the Tangobox
		 *
		 * @param object e
		 * @return void
		 */
		function tbClose( e ) {
			if ( e.keyCode === 27 || e.button === 0 ) {
				$(document).unbind();
				tbContent.unbind().empty();
				tbOuter.css( {height: "250px", width: "250px", top: "0"} ).hide().removeAttr("class");
				$("#tbOverlay").fadeOut( settings.zoomSpeedOut );
				if ( $.isFunction( settings.callbackOnClose ) ) {
					settings.callbackOnClose( itemArray[ itemIndex ] );
				}
			}
		};

		return this.unbind("click").bind("click", _initialize);
		
	};

})(jQuery);
