if (typeof require != 'undefined') {
	require("layout.js");
	require("animation.js");
}
if (typeof theme != 'undefined')
	theme.css("popup_window.css");

/**
 * @constructor
 * @param {string} title title of the window 
 * @param {string} icon path of the icon, or null
 * @param {string|HTMLElement} content content of the window: either an html element, or a string containing the html
 */
function popup_window(title,icon,content,hide_close_button) {
	theme.css("popup_window.css");
	var t = this;
	t.icon = icon;
	t.title = title;
	t.content = content;
	/** Callback which will be called when the popup window is closed 
	 * @member {function} popup_window#onclose 
	 */
	t.onclose = null;
	/** Indicate if the content should be kept (only hidden) when the window is closed
	 * @member {boolean} popup_window#keep_content_on_close
	 */
	t.keep_content_on_close = false;
	t.buttons = [];
	
	/** Set (change) the content of the popup window
	 * @method popup_window#setContent
	 * @param {string|HTMLElement} content content of the window: either an html element, or a string containing the html
	 */
	t.setContent = function(content) { 
		t.content = content;
		if (t.content_container) {
			t.content_container.removeAllChildren();
			if (typeof content == 'string')
				t.content_container.innerHTML = content;
			else
				t.content_container.appendChild(content);
			layout.changed(t.content);
		}
	};
	
	/** Set (change) the content of the popup window to be an IFRAME.
	 * @param {String} url url to load in the frame
	 * @param {Function} onload if specified, it is called when the frame is loaded
	 * @returns {Element} the IFRAME element
	 */
	t.setContentFrame = function(url, onload, post_data) {
		if (!t.content_container)
			t.content_container = document.createElement("DIV");
		else
			t.content_container.removeAllChildren();

		t.content = document.createElement("DIV");
		t.content.style.textAlign = "center";
		t.content.style.padding = "10px";
		t.content.innerHTML = "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Loading...";
		t.content_container.appendChild(t.content);
		var frame = document.createElement("IFRAME");
		t.content._frame_loading = frame;
		frame.style.border = "0px";
		frame.style.width = "0px";
		frame.style.height = "0px";
		frame.style.visibility = "hidden";
		frame._no_loading = true;
		t.content_container.appendChild(frame);
		frame.onload = function() {
			if (!t) return;
			frame._no_loading = false;
			if (t.content == frame) {
				// this is a new onload, probably to follow a link inside the frame
			} else {
				t.content_container.removeChild(t.content);
				t.content = frame;
				t.content.style.visibility = "visible";
				if (t.content._post_data) {
					postData(t.content._post_url, t.content._post_data, getIFrameWindow(t.content));
					t.content._post_data;
				}
				// fix the frame size in the mid-time
				t.content.style.width = "200px";
				t.content.style.height = "100px";
				waitFrameContentReady(frame, function(win) {
					return win.layout && win._page_ready;
				}, function() {
					if (!t.popup) return; // already closed
					//t.content.style.width = "";
					//t.content.style.height = "";
					t._setSizeType(t._size_type);
					if (onload) onload(frame);
				});
			}
		};
		if (!post_data) {
			frame.src = url;
		} else {
			frame._post_url = url;
			frame._post_data = post_data;
		}
		layout.changed(t.content_container);
		return frame;
	};
	
	/** Add a button at the bottom of the popup.
	 * @method popup_window#addButton
	 * @param {string} html html to put inside the button 
	 * @param {string} id id of the button, that can be used to refer it later on
	 * @param {function} onclick onclick event handler
	 */
	t.addButton = function(html, id, onclick, onclick_param) {
		var b = (t.popup ? t.popup.ownerDocument : document).createElement("BUTTON");
		if (typeof html == 'string')
			b.innerHTML = html;
		else
			b.appendChild(html);
		b.id = id;
		if (onclick_param)
			b.onclick = function() { onclick(onclick_param); };
		else
			b.onclick = onclick;
		t.buttons.push(b);
		if (t.footer) {
			t.footer.appendChild(b);
			t.footer.style.display = "";
			if (!t.footer.parentNode)
				t.popup.appendChild(t.footer);
			layout.changed(t.footer);
			layout.changed(t.content);
			layout.changed(t.popup);
		}
	};
	t.removeButtons = function() {
		t.buttons = [];
		if (t.footer) {
			t.footer.removeAllChildren();
			t.footer.display = "none";
			if (t.footer.parentNode)
				t.popup.removeChild(t.footer);
			layout.changed(t.popup);
		}
	};
	t.addFooter = function(html) {
		t.footer.appendChild(html);
		t.footer.style.display = "";
		if (!t.footer.parentNode)
			t.popup.appendChild(t.footer);
		layout.changed(t.footer);
		layout.changed(t.content);
		layout.changed(t.popup);
	};
	t.addIconTextButton = function(icon, text, id, onclick, onclick_param) {
		var span = document.createElement("SPAN");
		if (icon) {
			var img = document.createElement("IMG");
			img.src = icon;
			img.style.verticalAlign = "bottom";
			img.style.marginRight = "3px";
			span.appendChild(img);
		}
		span.appendChild(document.createTextNode(text));
		t.addButton(span, id, onclick, onclick_param);
	};
	/** Disable the given button.
	 * @method popup_window#disableButton
	 * @param {string} id of the button to disable
	 */
	t.disableButton = function(id) {
		for (var i = 0; i < t.buttons.length; ++i)
			if (t.buttons[i].id == id) {
				if (t.isFrozen())
					t.buttons[i].unfrozen_status = 'disabled';
				else
					t.buttons[i].disabled = 'disabled';
			}
	};
	
	/** Return true if the given button is disabled
	 * @method popup_window#getIsDisabled
	 * @param {string} id of the button
	 * @return {boolean}
	 */
	t.getIsDisabled = function(id) {
		for (var i = 0; i < t.buttons.length; ++i){
			if (t.buttons[i].id == id)
				return t.isFrozen() ? t.buttons[i].unfrozen_status : t.buttons[i].disabled;
		}
	};
	/** Enable the given button.
	 * @method popup_window#enableButton
	 * @param {string} id if of the button to enable
	 */
	t.enableButton = function(id) {
		for (var i = 0; i < t.buttons.length; ++i)
			if (t.buttons[i].id == id) {
				if (t.isFrozen())
					t.buttons[i].unfrozen_status = '';
				else
					t.buttons[i].disabled = '';
			}
	};
	/** Simulate a button pressed
	 * @param {string} id the button id
	 */
	t.pressButton = function(id) {
		for (var i = 0; i < t.buttons.length; ++i)
			if (t.buttons[i].id == id) {
				if (t.buttons[i].disabled == "disabled") return;
				t.buttons[i].onclick();
				break;
			}
	};
	t.addOkButton = function(onok) {
		t.addIconTextButton(theme.icons_16.ok, "Ok", 'ok', onok);
		t.onEnter(onok);
	};
	t.addFinishButton = function(onfinish) {
		t.addIconTextButton(theme.icons_16.ok, "Finish", 'finish', onfinish);
	};
	t.addCancelButton = function(oncancel) {
		t.addIconTextButton(theme.icons_16.cancel, "Cancel", 'cancel', function() { if (oncancel && !oncancel()) return; t.close(); });
		t.onEscape(function() { if (oncancel && !oncancel()) return; t.close(); });
	};
	t.addCloseButton = function(onclose) {
		t.addIconTextButton(theme.icons_16.cancel, "Close", 'close', function() { if (onclose) onclose(); t.close(); });
		t.onEscape(function() { if (onclose) onclose(); t.close(); });
	};
	t.addSaveButton = function(onsave) {
		t.addIconTextButton(theme.icons_16.save, "Save", 'save', function() { if (onsave) onsave(); });
	};
	t.addFrameSaveButton = function(onsave) {
		t.addSaveButton(onsave);
		t.disableButton('save');
		var check_frame = function() {
			var win = getIFrameWindow(t.content);
			if (!win || !win.pnapplication || !win._page_ready) {
				setTimeout(check_frame, 25);
				return;
			}
			if (!win.pnapplication.hasDataUnsaved()) t.disableButton('save'); else t.enableButton('save');
			win.pnapplication.ondatatosave.add_listener(function() { t.enableButton('save'); });
			win.pnapplication.onalldatasaved.add_listener(function() { t.disableButton('save'); });
		};
		check_frame();
	};
	t.addCreateButton = function(onclick) {
		t.addIconTextButton(theme.icons_16.ok, "Create", 'create', function() { onclick(); });
		t.onEnter(onclick);
	};
	t.addNextButton = function(onnext) {
		var span = document.createElement("SPAN");
		span.appendChild(document.createTextNode("Next"));
		var img = document.createElement("IMG");
		img.src = theme.icons_16.forward;
		img.style.verticalAlign = "bottom";
		img.style.marginLeft = "3px";
		span.appendChild(img);
		t.addButton(span, "next", onnext);
	};
	/** Add 2 buttons to the window: Ok and Cancel. When Cancel is pressed, the window is closed.
	 * @method popup_window#addOkCancelButtons
	 * @param {function} onok handler to be called when the Ok button is pressed. 
	 * @param {function} (optional) oncancel handler to be called when the Cancel button is pressed. 
	 */
	t.addOkCancelButtons = function(onok, oncancel) {
		t.addOkButton(onok);
		t.addCancelButton(oncancel);
	};
	t.addFinishCancelButtons = function(onfinish, oncancel) {
		t.addFinishButton(onfinish);
		t.addCancelButton(oncancel);
	};
	/** Add 2 buttons to the window: Yes and No. When No is pressed, the window is closed.
	 * @method popup_window#addYesNoButtons
	 * @param {function} onyes handler to be called when the Yes button is pressed. 
	 */
	t.addYesNoButtons = function(onyes,onno) {
		t.addIconTextButton(theme.icons_16.yes, "Yes", 'yes', onyes);
		t.addIconTextButton(theme.icons_16.no, "No", 'no', function() { if (!onno || onno()) t.close(); });
		t.onEscape(function() { if (!onno || onno()) t.close(); });
	};

	t._onenter_listeners = [];
	t._onEnterListener = function(ev) {
		if (!t || !t.popup) return;
		if (ev.target.nodeName == "TEXTAREA") return;
		var e = getCompatibleKeyEvent(ev);
		if (e.isEnter)
			for (var i = 0; i < t._onenter_listeners.length; ++i)
				t._onenter_listeners[i]();
	};
	t._onEnterListenerRegistrations = [];
	t.onEnter = function(onenter) {
		t._onenter_listeners.push(onenter);
		if (t._onenter_listeners.length == 1) {
			listenEvent(window,'keyup',t._onEnterListener);
			t._onEnterListenerRegistrations.push(window);
		}
		var frame = t.content.nodeName == "IFRAME" ? t.content : typeof t.content._frame_loading != 'undefined' ? t.content._frame_loading : null;
		if (frame) {
			var win = getIFrameWindow(frame);
			if (win) {
				listenEvent(win,'keyup',t._onEnterListener);
				t._onEnterListenerRegistrations.push(win);
			}
			listenEvent(frame,'load',function(){
				var win = getIFrameWindow(frame);
				if (win) {
					listenEvent(win,'keyup',t._onEnterListener);
					t._onEnterListenerRegistrations.push(win);
				}
			});
		};
	};
	t._onescape_listeners = [];
	t._onEscapeListener = function(ev) {
		if (!t || !t.popup) return;
		if (ev.target.nodeName == "TEXTAREA") return;
		var e = getCompatibleKeyEvent(ev);
		if (e.isEscape)
			for (var i = 0; i < t._onescape_listeners.length; ++i)
				t._onescape_listeners[i]();
	};
	t._onEscapeListenerRegistrations = [];
	t.onEscape = function(onescape) {
		t._onescape_listeners.push(onescape);
		if (t._onescape_listeners.length == 1) {
			listenEvent(window,'keyup',t._onEscapeListener);
			t._onEscapeListenerRegistrations.push(window);
		}
		var frame = t.content.nodeName == "IFRAME" ? t.content : typeof t.content._frame_loading != 'undefined' ? t.content._frame_loading : null;
		if (frame) {
			var win = getIFrameWindow(frame);
			if (win) {
				listenEvent(win,'keyup',t._onEscapeListener);
				t._onEscapeListenerRegistrations.push(win);
			}
			listenEvent(frame,'load',function(){
				var win = getIFrameWindow(frame);
				if (win) {
					listenEvent(win,'keyup',t._onEscapeListener);
					t._onEscapeListenerRegistrations.push(win);
				}
			});
		};
	};
	
	t.isShown = function() {
		return t != null && t.popup != null;
	};
	
	t._onwindow_closed_listener = function(){
		if (!t || !t.popup) return;
		pnapplication.onclose.remove_listener(t._onwindow_closed_listener);
		t.close();
	};
	
	t.showPercent = function(width, height) {
		var win;
		if (!t.popup)
			win = t._buildPopup();
		else
			win = getWindowFromElement(t.popup);
		t.popup.style.width = width+"%";
		t.popup.style.height = height+"%";
		t._setSizeType("fixed");
		
		if (typeof win.animation != 'undefined') {
			if (t.anim) win.animation.stop(t.anim);
			t.anim = win.animation.fadeIn(t.popup, 200);
		}
		pnapplication.onclose.add_listener(t._onwindow_closed_listener);
	};
	
	/** Display the popup window
	 * @method popup_window#show
	 */
	t.show = function(){
		var win = t._buildPopup();
		t._setSizeType("fit");

		var move_handler = function(ev) {
			var win = getWindowFromDocument(t.popup.ownerDocument);
			if (!t.popup) {
				// popup closed!
				unlistenEvent(t.popup_container,'mousemove',move_handler);
				unlistenEvent(win,'mouseup',up_handler);
				//unlistenEvent(win,'mouseout',up_handler);
				return;
			}
			if (!ev) ev = win.event;
			var diff_x = ev.clientX - t._move_x;
			var diff_y = ev.clientY - t._move_y;
			if (diff_x == 0 && diff_y == 0) return;
			t._move_x = ev.clientX;
			t._move_y = ev.clientY;
			var x = parseInt(t.popup.style.left);
			x += diff_x;
			t.popup.style.left = x+"px";
			var y = parseInt(t.popup.style.top);
			y += diff_y;
			t.popup.style.top = y+"px";
		};
		var up_handler = null; // only to remove the warning
		up_handler = function(ev) {
			var win = getWindowFromDocument(t.popup.ownerDocument);
			unlistenEvent(t.popup_container,'mousemove',move_handler);
			unlistenEvent(win,'mouseup',up_handler);
			//unlistenEvent(win,'mouseout',up_handler);
		};
		t.header.onmousedown = function(ev) {
			if (!t.popup) return; // it is disappearing
			if (t.popup.style.position != "relative") {
				t.popup.style.position = "relative";
				t.popup.style.top = "0px";
				t.popup.style.left = "0px";
			}
			var win = getWindowFromDocument(t.popup.ownerDocument);
			if (!ev) ev = win.event;
			t._move_x = ev.clientX;
			t._move_y = ev.clientY;
			listenEvent(t.popup_container,'mousemove',move_handler);
			listenEvent(win,'mouseup',up_handler);
			//listenEvent(win,'mouseout',up_handler);
			return false;
		};
		
		if (typeof win.animation != 'undefined') {
			if (t.anim) win.animation.stop(t.anim);
			t.anim = win.animation.fadeIn(t.popup, 200);
		}
		pnapplication.onclose.add_listener(t._onwindow_closed_listener);
	};
	
	t._size_type = "fit";
	t._setSizeType = function(type) {
		t._size_type = type;
		if (!t.popup) return;
		if (t.content.nodeName == "IFRAME") {
			layout.unlistenElementSizeChanged(t.content_container, t._layout_content);
			switch (t._size_type) {
			case "fit":
				t.content_container.style.overflow = "auto";
				t.content_container.style.display = "";
				layout.autoResizeIFrame(t.content, function() {
					t._layout_content();
				});
				// do not change frame size when the frame is moving to another page
				listenEvent(t.content, 'unload', function() {
					layout.stopResizingIFrame(t.content);
					var w = getIFrameWindow(t.content);
					if (w) w._popup_frame_unloading = true;
					waitFrameContentReady(t.content, function(win) {
						return !win._popup_frame_unloading && win._page_ready_step;
					}, function() {
						layout.autoResizeIFrame(t.content, function() {
							t._layout_content();
						});
					});
				});
				break;
			case "fixed":
				layout.stopResizingIFrame(t.content);
				t.content_container.style.overflow = "";
				t.content_container.style.display = "flex";
				t.content_container.style.flexDirection = "column";
				t.content.style.flex = "1 1 auto";
				t.content.style.width = "100%";
				t.content.style.height = "100%";
				break;
			};
		} else {
			switch (t._size_type) {
			case "fit":
				t.content_container.style.overflow = "auto";
				layout.listenElementSizeChanged(t.content_container, t._layout_content);
				break;
			case "fixed":
				layout.unlistenElementSizeChanged(t.content_container, t._layout_content);
				// handle bug of Chrome when content wants to take 100%
				t.content_container.style.position = "relative";
				t.content.style.position = "absolute";
				break;
			}
		}
	};
	t._layout_content = function() {
		if (!t || !t.popup) return;
		if (t.content_container.style.overflow == "") return;
		t.content_container.style.minWidth = "";
		t.content_container.style.minHeight = "";
		t.content_container.style.overflow = "";
		if (t.content_container.scrollHeight > t.content_container.clientHeight) {
			// vertical scroll bar needed
			if (t.content_container.scrollWidth > t.content_container.clientWidth) {
				// horizontal scroll bar needed
				t.content_container.style.overflow = "auto";
				return;
			}
			if (t.content_container.scrollWidth+window.top.browser_scroll_bar_size < t.popup_container.clientWidth) {
				t.content_container.style.minWidth = (t.content_container.scrollWidth+window.top.browser_scroll_bar_size)+"px";
			}
		} else if (t.content_container.scrollWidth > t.content_container.clientWidth) {
			// horizontal scroll bar needed
			if (t.content_container.scrollHeight+window.top.browser_scroll_bar_size < t.popup_container.clientHeight) {
				t.content_container.style.minHeight = (t.content_container.scrollHeight+window.top.browser_scroll_bar_size)+"px";
			}
		}
		t.content_container.style.overflow = "auto";
	};
	
	t._buildPopup = function() {
		var parent_popup = get_popup_window_from_frame(window);
		var win,doc;
		if (!parent_popup || !parent_popup.popup) {
			win = window;
			doc = document;
			t.locker = lock_screen(function() {
				t.blink();
			});
		} else {
			doc = parent_popup.popup.ownerDocument;
			win = getWindowFromDocument(doc);
			parent_popup.freeze();
		}
		
		t.popup_container = doc.createElement("DIV");
		t.popup_container.className = "popup_window_layer";
		t.popup_container.style.position = "fixed";
		t.popup_container.style.top = "0px";
		t.popup_container.style.left = "0px";
		t.popup_container.win = win;
		t.popup_container.listener = function() {
			t.popup_container.style.width = win.getWindowWidth()+"px";
			t.popup_container.style.height = win.getWindowHeight()+"px";
		};
		listenEvent(win, 'resize', t.popup_container.listener);
		doc.body.appendChild(t.popup_container);
		t.popup_container.style.display = "flex";
		t.popup_container.style.flexDirection = "column";
		t.popup_container.style.justifyContent = "center";
		t.popup_container.style.alignItems = "center";
		t.popup_container.listener();

		t.popup = doc.createElement("DIV");
		t.popup.style.flex = "none";
		t.popup.style.display = "flex";
		t.popup.style.flexDirection = "column";
		t.popup.className = "popup_window";
		t.popup_container.appendChild(t.popup);
		t.popup.data = t;
		
		t.header = doc.createElement("DIV");
		t.header.style.flex = "none";
		t.header.style.display = "flex";
		t.header.style.flexDirection = "row";
		t.popup.appendChild(t.header);
		t.header.className = "popup_window_header";

		if (t.icon) {
			t.icon_container = doc.createElement("DIV");
			t.icon_container.style.flex = "none";
			t.icon_container.style.display = "flex";
			t.icon_container.style.flexDirection = "column";
			t.icon_container.style.justifyContent = "center";
			t.icon_container.style.paddingLeft = "2px";
			t.icon_img = doc.createElement("IMG");
			t.icon_img.src = t.icon;
			t.icon_img.style.flex = "none";
			t.icon_container.appendChild(t.icon_img);
			t.header.appendChild(t.icon_container);
		}
		t.title_container = doc.createElement("DIV");
		t.title_container.className = "popup_window_title";
		t.title_container.style.flex = "1 1 auto";
		t.title_container.style.display = "flex";
		t.title_container.style.flexDirection = "column";
		t.title_container.style.justifyContent = "center";
		t.title_container.appendChild(document.createTextNode(t.title));
		t.header.appendChild(t.title_container);
		if (!hide_close_button) {
			t.close_button = doc.createElement("BUTTON");
			t.close_button.className = "flat icon";
			t.close_button.innerHTML = "<img src='"+theme.icons_16.close+"'/>";
			t.close_button.style.flex = "none";
			t.header.appendChild(t.close_button);
			t.close_button.onclick = function() { t.close(); };
		}
		
		if (!t.content_container)
			t.content_container = doc.createElement("DIV");
		t.content_container.style.flex = "1 1 auto";
		t.content_container.style.overflow = "auto";
		t.popup.appendChild(t.content_container);
		
		t.footer = doc.createElement("DIV");
		t.footer.className = "popup_window_buttons";
		t.footer.style.flex = "none";
		
		if (t.buttons.length > 0) {
			t.popup.appendChild(t.footer);
			for (var i = 0; i < t.buttons.length; ++i)
				t.footer.appendChild(t.buttons[i]);
		} else
			t.footer.style.display = "none";
		
		if (typeof t.content == 'string') t.content_container.innerHTML = t.content;
		else {
			t.content_container.appendChild(t.content);
			t.content.style.visibility = 'visible';
		}
		if (t.content.nodeName == "IFRAME" && t.content._post_data) {
			postData(t.content._post_url, t.content._post_data, getIFrameWindow(t.content));
			t.content._post_data = null;
		}

		return win;
	};
		
	t.blink = function() {
		t.popup.className = "popup_window blink";
		setTimeout(function() { if (t.popup) t.popup.className = "popup_window"; },100);
		setTimeout(function() { if (t.popup) t.popup.className = "popup_window blink"; },200);
		setTimeout(function() { if (t.popup) t.popup.className = "popup_window"; },300);
		setTimeout(function() { if (t.popup) t.popup.className = "popup_window blink"; },400);
		setTimeout(function() { if (t.popup) t.popup.className = "popup_window"; },500);
	};
	
	t.disableClose = function() {
		t.close_button.disabled = "disabled";
	};
	t.enableClose = function() {
		t.close_button.disabled = "";
	};
	
	t.freeze = function(freeze_content) {
		if (t.freezer) {
			t.freezer.usage_counter++;
			if (freeze_content)
				t.set_freeze_content(freeze_content);
			return;
		}
		t.freezer = t.popup.ownerDocument.createElement("DIV");
		t.freezer.usage_counter = 1;
		t.freezer.style.position = "absolute";
		t.freezer.style.top = t.content_container.scrollTop+"px";
		t.freezer.style.left = t.content_container.scrollLeft+"px";
		t.freezer.style.width = "100%";
		t.freezer.style.height = "100%";
		t.freezer.style.backgroundColor = "rgba(128,128,128,0.5)";
		t.content_container.onmousewheel = function(ev) {
			stopEventPropagation(ev);
			return false;
		};
		if (freeze_content)
			set_lock_screen_content(t.freezer, freeze_content);
		t.content_container.style.position = "relative";
		t.content_container.appendChild(t.freezer);
		for (var i = 0; i < t.buttons.length; ++i) {
			t.buttons[i].unfrozen_status = t.buttons[i].disabled;
			t.buttons[i].disabled = 'disabled';
		}
		t.disableClose();
	};
	t.freeze_progress = function(message, total, onready) {
		theme.css("progress_bar.css"); // to make it available, even if we are starting a lot of AJAX requests
		require("progress_bar.js", function() {
			var div = document.createElement("DIV");
			div.style.textAlign = "center";
			var span = document.createElement("SPAN");
			span.style.marginBottom = "2px";
			span.innerHTML = message;
			div.appendChild(span);
			div.appendChild(document.createElement("BR"));
			var pb = new progress_bar(200, 17);
			pb.element.style.display = "inline-block";
			div.appendChild(pb.element);
			pb.setTotal(total);
			t.freeze(div);
			onready(span, pb);
		});
	};
	t.freeze_progress_sub = function(message, total, onready) {
		theme.css("progress_bar.css"); // to make it available, even if we are starting a lot of AJAX requests
		require("progress_bar.js", function() {
			var div = document.createElement("DIV");
			div.style.textAlign = "center";
			var span = document.createElement("SPAN");
			span.style.marginBottom = "2px";
			span.innerHTML = message;
			div.appendChild(span);
			div.appendChild(document.createElement("BR"));
			var pb = new progress_bar(200, 17);
			pb.element.style.display = "inline-block";
			div.appendChild(pb.element);
			pb.setTotal(total);
			var sub = document.createElement("DIV");
			div.appendChild(sub);
			t.freeze(div);
			onready(span, pb, sub);
		});
	};
	t.set_freeze_content = function(content) {
		if (!t.freezer) {
			//window.console.warn("set freeze content on popup not frozen: "+content);
			return;
		}
		set_lock_screen_content(t.freezer, content);
	};
	t.unfreeze = function() {
		if (!t.freezer) return;
		if (--t.freezer.usage_counter > 0) return;
		t.content_container.removeChild(t.freezer);
		t.content_container.onmousewheel = null;
		t.freezer = null;
		for (var i = 0; i < t.buttons.length; ++i)
			t.buttons[i].disabled = t.buttons[i].unfrozen_status;
		t.freeze_button_status = null;
		t.enableClose();
	};
	t.isFrozen = function() {
		return t.freezer != null;
	};
	
	/** Close this popup window
	 * @method popup_window#close
	 * @param keep_content_hidden
	 */
	t.close = function(keep_content_hidden) {
		if (!t.popup) return;
		unlistenEvent(t.popup_container.win, 'resize', t.popup_container.listener);
		if (t.content.nodeName == "IFRAME") {
			var w = getIFrameWindow(t.content);
			if (w && w.pnapplication && w.pnapplication.hasDataUnsaved()) {
				if (!confirm("This popup contains data which have not been saved. Are your sure you want to close it (your modifications will be lost) ?")) return;
				w.pnapplication.cancelDataUnsaved();
			}
		}
		if (t.locker)
			unlock_screen(t.locker);
		else {
			var parent_popup = get_popup_window_from_frame(window);
			if(parent_popup && parent_popup.popup) parent_popup.unfreeze();
		}
		var popup = t.popup_container;
		if (t.onclose) t.onclose();
		layout.unlistenElementSizeChanged(t.content_container, t._layout_content);
		t.popup_container = null;
		t.popup.data = null;
		t.popup = null;
		var do_close = function() {
			if (t && (keep_content_hidden || t.keep_content_on_close)) {
				t.content.parentNode.removeChild(t.content);
				t.content.style.position = 'absolute';
				t.content.style.visibility = 'hidden';
				t.content.style.top = '-10000px';
				t.content.ownerDocument.body.appendChild(t.content);
			}
			popup.removeAllChildren();
			if (popup.parentNode)
				popup.parentNode.removeChild(popup);
			popup.data = null;
			if (t) {
				t.content = null;
				t.content_container = null;
				t.header = null;
				t.footer = null;
				if (!keep_content_hidden && !t.keep_content_on_close)
					t.cleanup();
			}
		};
		if (t.content.nodeName == "IFRAME") t.content._no_loading = true;
		var win = getWindowFromElement(popup);
		if (typeof win.animation != 'undefined') {
			if (t.anim) win.animation.stop(t.anim);
			win.animation.fadeOut(popup, 200, do_close);
		} else
			do_close();
	};
	t.hide = function() { t.close(); };
	
	t.hideTitleBar = function() {
		t.header.style.display = "none";
	};
	
	t.cleanup = function() {
		if (!t) return;
		window.to_cleanup.remove(this);
		if (t.content_container)
			layout.unlistenElementSizeChanged(t.content_container, t._layout_content);
		t._onenter_listeners = null;
		for (var i = 0; i < t._onEnterListenerRegistrations.length; ++i)
			unlistenEvent(t._onEnterListenerRegistrations[i],'keyup',t._onEnterListener);
		t._onEnterListener = null;
		t._onescape_listeners = null;
		for (var i = 0; i < t._onEscapeListenerRegistrations.length; ++i)
			unlistenEvent(t._onEscapeListenerRegistrations[i],'keyup',t._onEscapeListener);
		if (t.popup) t.close();
		t._onEscapeListener = null;
		t.content = null;
		t.content_container = null;
		t.header = null;
		t.footer = null;
		t.buttons = null;
		t.cleanup = null;
		t.locker = null;
		t.popup_container = null;
		t.freezer = null;
		t.icon_container = null;
		t.icon_img = null;
		t.title_container = null;
		t.close_button = null;
		if (t.popup) t.popup.data = null;
		t.popup = null;
		t = null;
	};
	window.to_cleanup.push(this);
}

/**
 * Try to get the popup window containing the given element.
 * @param e the element contained in a popup
 * @returns {popup_window} the popup window containing the given element
 */
function get_popup_window_from_element(e) {
	while (e.parentNode != null && e.parentNode != e && e.parentNode != document.body && e.parentNode.className != 'popup_window') e = e.parentNode;
	if (e.parentNode != null && e.parentNode.className == 'popup_window')
		return e.parentNode.data;
	return null;
}
function get_popup_window_from_frame(win) {
	if (!win) return null;
	if (win.frameElement) {
		if (win.parent.get_popup_window_from_element) {
			var pop = win.parent.get_popup_window_from_element(win.frameElement);
			if (pop != null) return pop;
		}
		return get_popup_window_from_frame(win.parent);
	}
	return null;
}
