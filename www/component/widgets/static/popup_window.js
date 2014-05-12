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
			while (t.content_container.childNodes.length > 0) t.content_container.removeChild(t.content_container.childNodes[0]);
			if (typeof content == 'string')
				t.content_container.innerHTML = content;
			else
				t.content_container.appendChild(content);
			t.resize();
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
			while (t.content_container.childNodes.length > 0) t.content_container.removeChild(t.content_container.childNodes[0]);
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
			frame._no_loading = false;
			if (t.content == frame) {
				// this is a new onload, probably to follow a link inside the frame
			} else {
				t.content_container.removeChild(t.content);
				t.content = frame;
				frame.style.visibility = "visible";
				//t.table.style.width = "80%";
				t.content.style.width = "100%";
				t.content.style.height = "100%";
				if (t.content._post_data) {
					postData(t.content._post_url, t.content._post_data, getIFrameWindow(t.content));
					t.content._post_data;
				}
				t.resize();
			}
			var check_ready = function() {
				if (!t.table) return; // popup has been already closed
				var win = getIFrameWindow(t.content);
				if (!win || !win.layout || !win._page_ready) {
					setTimeout(check_ready, 10);
					return;
				}
				var b = win.document.body;
				win.layout.cancelResizeEvent();
				win.layout.addHandler(b, t.resize);
				for (var i = 0; i < b.childNodes.length; ++i) getIFrameWindow(t.content).layout.addHandler(b.childNodes[i], t.resize);
				if (onload) onload(t.content);
			};
			check_ready();
		};
		if (!post_data) {
			frame.src = url;
		} else {
			frame._post_url = url;
			frame._post_data = post_data;
		}
		t.resize();
		return frame;
	};
	
	/** Add a button at the bottom of the popup.
	 * @method popup_window#addButton
	 * @param {string} html html to put inside the button 
	 * @param {string} id id of the button, that can be used to refer it later on
	 * @param {function} onclick onclick event handler
	 */
	t.addButton = function(html, id, onclick) {
		var b = document.createElement("BUTTON");
		if (typeof html == 'string')
			b.innerHTML = html;
		else
			b.appendChild(html);
		b.id = id;
		b.onclick = onclick;
		t.buttons.push(b);
		if (t.table) {
			if (!t.buttons_tr) {
				t.buttons_tr = t.table.ownerDocument.createElement("TR");
				t.buttons_tr.className = 'popup_window_buttons';
				t.table.appendChild(t.buttons_tr);
				t.buttons_td = t.table.ownerDocument.createElement("TD"); t.buttons_tr.appendChild(t.buttons_td);
				t.buttons_td.colSpan = 2;
			}
			t.buttons_td.appendChild(b);
			t.resize();
		}
	};
	t.removeButtons = function() {
		if (!t.buttons_tr) return;
		t.table.removeChild(t.buttons_tr);
		t.buttons_tr = null;
		t.buttons_td = null;
	};
	t.addFooter = function(html) {
		if (!t.buttons_tr) {
			t.buttons_tr = t.table.ownerDocument.createElement("TR");
			t.buttons_tr.className = 'popup_window_buttons';
			t.table.appendChild(t.buttons_tr);
			t.buttons_td = t.table.ownerDocument.createElement("TD"); t.buttons_tr.appendChild(t.buttons_td);
			t.buttons_td.colSpan = 2;
		}
		t.buttons_td.appendChild(html);
		t.resize();
	};
	t.addIconTextButton = function(icon, text, id, onclick) {
		var span = document.createElement("SPAN");
		if (icon) {
			var img = document.createElement("IMG");
			img.src = icon;
			img.style.verticalAlign = "bottom";
			img.style.marginRight = "3px";
			span.appendChild(img);
		}
		span.appendChild(document.createTextNode(text));
		t.addButton(span, id, onclick);
	};
	/** Disable the given button.
	 * @method popup_window#disableButton
	 * @param {string} id of the button to disable
	 */
	t.disableButton = function(id) {
		for (var i = 0; i < t.buttons.length; ++i)
			if (t.buttons[i].id == id)
				t.buttons[i].disabled = 'disabled';
	};
	
	/** Return true if the given button is disabled
	 * @method popup_window#getIsDisabled
	 * @param {string} id of the button
	 * @return {boolean}
	 */
	t.getIsDisabled = function(id) {
		for (var i = 0; i < t.buttons.length; ++i){
			if (t.buttons[i].id == id)
				return t.buttons[i].disabled;
		}
	};
	/** Enable the given button.
	 * @method popup_window#enableButton
	 * @param {string} id if of the button to enable
	 */
	t.enableButton = function(id) {
		for (var i = 0; i < t.buttons.length; ++i)
			if (t.buttons[i].id == id)
				t.buttons[i].disabled = '';
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
		t.addIconTextButton(theme.icons_16.cancel, "Cancel", 'cancel', function() { if (oncancel) oncancel(); t.close(); });
		t.onEscape(function() { if (oncancel) oncancel(); t.close(); });
	};
	t.addCloseButton = function(onclose) {
		t.addIconTextButton(theme.icons_16.cancel, "Close", 'close', function() { if (onclose) onclose(); t.close(); });
		t.onEscape(function() { if (onclose) onclose(); t.close(); });
	};
	t.addSaveButton = function(onsave) {
		t.addIconTextButton(theme.icons_16.save, "Save", 'save', function() { if (onsave) onsave(); });
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
	t.addYesNoButtons = function(onyes) {
		t.addIconTextButton(theme.icons_16.yes, "Yes", 'yes', onyes);
		t.addIconTextButton(theme.icons_16.no, "No", 'no', function() { t.close(); });
		t.onEscape(function() { t.close(); });
	};
	
	t.removeAllButtons = function() {
		if (t.buttons_td) while (t.buttons_td.childNodes.length > 0) t.buttons_td.removeChild(t.buttons_td.childNodes[0]);
		t.buttons = [];
	};
	
	t.onEnter = function(onenter) {
		var listener = function(ev) {
			if (!t.table) return;
			var e = getCompatibleKeyEvent(ev);
			if (e.isEnter) onenter();
		};
		listenEvent(window,'keyup',listener);
		var frame = t.content.nodeName == "IFRAME" ? t.content : typeof t.content._frame_loading != 'undefined' ? t.content._frame_loading : null;
		if (frame) {
			var win = getIFrameWindow(frame);
			if (win) listenEvent(win,'keyup',listener);
			listenEvent(frame,'load',function(){
				var win = getIFrameWindow(frame);
				if (win) listenEvent(win,'keyup',listener);
			});
		};
	};
	t.onEscape = function(onescape) {
		var listener = function(ev) {
			if (!t.table) return;
			var e = getCompatibleKeyEvent(ev);
			if (e.isEscape) onescape();
		};
		listenEvent(window,'keyup',listener);
		var frame = t.content.nodeName == "IFRAME" ? t.content : typeof t.content._frame_loading != 'undefined' ? t.content._frame_loading : null;
		if (frame) {
			var win = getIFrameWindow(frame);
			if (win) listenEvent(win,'keyup',listener);
			listenEvent(frame,'load',function(){
				var win = getIFrameWindow(frame);
				if (win) listenEvent(win,'keyup',listener);
			});
		};
	};
	
	t.isShown = function() {
		return t.table != null;
	};
	
	t.showPercent = function(width, height) {
		t.resize = function() {
			if (!t.table) return;
			var win = getWindowFromElement(t.table);
			var ww = win.getWindowWidth();
			var wh = win.getWindowHeight();
			t.table.style.left = Math.floor(ww*(100-width)/200)+"px";
			t.table.style.top = Math.floor(wh*(100-height)/200)+"px";
			t.table.style.width = Math.floor(ww*width/100)+"px";
			t.table.style.height = Math.floor(wh*height/100)+"px";
			var h = 0;
			if (t.header) h += win.getHeight(t.header);
			if (t.buttons_tr) h += win.getHeight(t.buttons_tr);
			t.content_container.style.height = Math.floor(wh*height/100-h)+"px";
			if (t.table._ww != ww || t.table._wh != wh) {
				t.table._ww = ww;
				t.table._wh = wh;
				layout.invalidate(t.content);
			}
		};
		var win;
		if (t.table == null)
			win = t._buildTable();
		else
			win = getWindowFromElement(t.table);
		t.resize();
		win.listenEvent(win, "resize", function() {
			t.resize();
		});
	};
	
	/** Display the popup window
	 * @method popup_window#show
	 */
	t.show = function(){
		t._buildTable();
		var move_handler = function(ev) {
			var win = getWindowFromDocument(t.table.ownerDocument);
			if (!t.table) {
				// popup closed!
				unlistenEvent(win,'mousemove',move_handler);
				unlistenEvent(win,'mouseup',up_handler);
				unlistenEvent(win,'mouseout',up_handler);
				return;
			}
			if (!ev) ev = win.event;
			var diff_x = ev.clientX - t._move_x;
			var diff_y = ev.clientY - t._move_y;
			if (diff_x == 0 && diff_y == 0) return;
			t._move_x = ev.clientX;
			t._move_y = ev.clientY;
			var x = win.absoluteLeft(t.table);
			x += diff_x;
			if (x < 5) x = 5;
			if (x + t.table.offsetWidth > win.getWindowWidth()-10) x = win.getWindowWidth()-5-t.table.offsetWidth;
			var y = win.absoluteTop(t.table);
			y += diff_y;
			if (y < 5) y = 5;
			if (y + t.table.offsetHeight > win.getWindowHeight()-10) y = win.getWindowHeight()-5-t.table.offsetHeight;
			t.table.style.top = y+"px";
			t.table.style.left = x+"px";
		};
		var up_handler = null; // only to remove the warning
		up_handler = function(ev) {
			var win = getWindowFromDocument(t.table.ownerDocument);
			unlistenEvent(win,'mousemove',move_handler);
			unlistenEvent(win,'mouseup',up_handler);
			unlistenEvent(win,'mouseout',up_handler);
		};
		t.header.onmousedown = function(ev) {
			var win = getWindowFromDocument(t.table.ownerDocument);
			if (!ev) ev = win.event;
			t._move_x = ev.clientX;
			t._move_y = ev.clientY;
			listenEvent(win,'mousemove',move_handler);
			listenEvent(win,'mouseup',up_handler);
			listenEvent(win,'mouseout',up_handler);
			return false;
		};
		t.resize();
		layout.invalidate(t.content_container);
		if (typeof animation != 'undefined') {
			if (t.anim) animation.stop(t.anim);
			t.anim = animation.fadeIn(t.table, 200);
		}
		pnapplication.onclose.add_listener(function(){
			if (!t.table) return;
			t.close();
		});
	};
	
	t._buildTable = function() {
		var parent_popup = get_popup_window_from_frame(window);
		var win,doc;
		if (!parent_popup) {
			win = window;
			doc = document;
			t.locker = lock_screen(function() {
				t.blink();
			});
		} else {
			doc = parent_popup.table.ownerDocument;
			win = getWindowFromDocument(doc);
			parent_popup.freeze();
		}

		t.table = doc.createElement("TABLE");
		t.table.className = 'popup_window';
		t.table.data = t;
		t.header = doc.createElement("TR"); t.table.appendChild(t.header);
		t.header.className = "popup_window_title";
		
		var td = doc.createElement("TD"); t.header.appendChild(td);
		td.innerHTML = (t.icon ? "<img src='"+t.icon+"' style='vertical-align:bottom'/> " : "")+t.title;
		if (hide_close_button)
			td.colSpan = 2;
		else {
			td = doc.createElement("TD"); t.header.appendChild(td);
			td.onclick = function() { t.close(); };
			t.close_button_td = td;
			td.style.backgroundImage = "url(\""+theme.icons_16.close+"\")";
			td.style.backgroundPosition = "center";
			td.style.backgroundRepeat = "no-repeat";
		}
		var tr = doc.createElement("TR"); t.table.appendChild(tr);
		var td = doc.createElement("TD"); tr.appendChild(td);
		td.colSpan = 2;
		if (!t.content_container)
			t.content_container = doc.createElement("DIV");
		t.content_container.style.width = "100%";
		t.content_container.style.height = "100%";
		td.appendChild(t.content_container);
		td.style.padding = "0px";
		td.style.margin = "0px";
		if (t.buttons.length > 0) {
			t.buttons_tr = doc.createElement("TR");
			t.buttons_tr.className = 'popup_window_buttons';
			t.table.appendChild(t.buttons_tr);
			t.buttons_td = doc.createElement("TD"); t.buttons_tr.appendChild(t.buttons_td);
			t.buttons_td.colSpan = 2;
			for (var i = 0; i < t.buttons.length; ++i)
				t.buttons_td.appendChild(t.buttons[i]);
		}
		doc.body.appendChild(t.table);
		if (typeof t.content == 'string') t.content_container.innerHTML = t.content;
		else {
			t.content_container.appendChild(t.content);
			t.content.style.visibility = 'visible';
		}
		if (t.content.nodeName == "IFRAME" && t.content._post_data) {
			postData(t.content._post_url, t.content._post_data, getIFrameWindow(t.content));
			t.content._post_data = null;
		}
		win.layout.addHandler(t.table, t.resize);
		return win;
	};
	
	t._computeFrameWidth = function(body) {
		var win = getIFrameWindow(t.content);
		var max = 0;
		for (var i = 0; i < body.childNodes.length; ++i) {
			var e = body.childNodes[i];
			var w = null;
			if (e.nodeType != 1) continue;
			if (e.nodeName == "SCRIPT") continue;
			if (e.style && e.style.position && (e.style.position == "absolute" || e.style.position == "fixed")) continue;
			if (e.nodeName == "FORM")
				w = win.absoluteLeft(e) + t._computeFrameWidth(e);
			else {
				e._display = e.style && e.style.display ? e.style.display : "";
				e._whiteSpace = e.style && e.style.whiteSpace ? e.style.whiteSpace : "";
				e._width = e.style && e.style.width ? e.style.width : "";
				e.style.display = 'inline-block';
				e.style.whiteSpace = 'nowrap';
				if (e._width.indexOf('%') == -1)
					e.style.width = "";
			}
			if (w == null) w = win.absoluteLeft(e)+(win.getWidth ? win.getWidth(e) : getWidth(e));
			if (w > max) max = w;
			if (e.nodeName != "FORM") {
				e.style.display = e._display;
				e.style.whiteSpace = e._whiteSpace;
				e.style.width = e._width;
			}
		}
		return max;
	};
	t._computeFrameHeight = function(body) {
		var win = getIFrameWindow(t.content);
		var max = 0;
		for (var i = 0; i < body.childNodes.length; ++i) {
			var e = body.childNodes[i];
			var h = null;
			if (e.nodeType != 1) continue;
			if (e.nodeName == "SCRIPT") continue;
			if (e.style && e.style.position && (e.style.position == "absolute" || e.style.position == "fixed")) continue;
			if (e.nodeName == "FORM")
				h = win.absoluteTop(e) + t._computeFrameHeight(e);
			else {
				e._display = e.style && e.style.display ? e.style.display : "";
				e._whiteSpace = e.style && e.style.whiteSpace ? e.style.whiteSpace : "";
				e._height = e.style && e.style.height ? e.style.height : "";
				e.style.display = 'inline-block';
				e.style.whiteSpace = 'nowrap';
				if (e._height.indexOf('%') == -1)
					e.style.height = "";
			}
			if (h == null) h = win.absoluteTop(e)+(win.getHeight ? win.getHeight(e) : getHeight(e));
			if (h > max) max = h;
			if (e.nodeName != "FORM") {
				e.style.display = e._display;
				e.style.whiteSpace = e._whiteSpace;
				e.style.height = e._height;
			}
		}
		return max;
	};
		
	/** Resize the window according to its content: this is normally automatically called. 
	 */
	t.resize = function() {
		if (!t.table) return;
		if (t.in_resize) return;
		t.in_resize = true;
		var x, y;
		var win = getWindowFromDocument(t.table.ownerDocument);
		if (t.content.nodeName == "IFRAME") {
			if (t.freezer) { t.in_resize = false; return; } // avoid resizing when we are loading a new page
			var frame_win = getIFrameWindow(t.content);
			var frame = frame_win.document;
			if (!frame_win || !frame_win.layout || !frame || !frame.body) {
				setTimeout(t.resize, 10);
				t.in_resize = false;
				return;
			}
			var h = 0;
			if (t.header) h += getHeight(t.header);
			if (t.buttons_tr) h += getHeight(t.buttons_tr);
			t.content_container.style.width = (win.getWindowWidth()-20)+"px";
			t.content_container.style.height = (win.getWindowHeight()-20-h)+"px";
			t.content_container.style.overflow = "";
			t.content.style.width = (win.getWindowWidth()-20)+"px";
			t.content.style.height = (win.getWindowHeight()-20-h)+"px";
			x = t._computeFrameWidth(frame.body);
			y = t._computeFrameHeight(frame.body);
			if (x > win.getWindowWidth()-20) {
				x = win.getWindowWidth()-20;
				// anticipate scroll bar
				y += window.top.browser_scroll_bar_size;
			} else if (frame.body.scrollLeft > 0)
					frame.body.scrollLeft = 0;
			if (y > win.getWindowHeight()-20-h) {
				y = win.getWindowHeight()-20-h;
				// anticipate scroll bar
				if (x < win.getWindowWidth()-20) x += window.top.browser_scroll_bar_size;
				if (x > win.getWindowWidth()-20) x = win.getWindowWidth()-20;
			} else if (frame.body.scrollTop > 0)
				frame.body.scrollTop = 0;
			getIFrameDocument(t.content).body.style.overflow = "hidden";
			setWidth(t.content_container, x);
			setHeight(t.content_container, y);
			setWidth(t.content, x);
			setHeight(t.content, y);
			t.content_container.overflow = "hidden";
			getIFrameDocument(t.content).body.style.overflow = "";
			if (y < win.getWindowHeight()-20-h) {
				// there should be no scroll bar, fix Chrome bug
				if (frame.body.scrollHeight > frame.body.clientHeight) {
					frame.body.style.position = "fixed";
					frame.body.style.top = "0px";
					frame.body.style.left = "0px";
				}
			}
			if (t.content._last_popup_resize_w != t.content.offsetWidth || t.content._last_popup_resize_h != t.content.offsetHeight) {
				frame_win.layout.invalidate(frame_win.document.body);
				t.content._last_popup_resize_w = t.content.offsetWidth;
				t.content._last_popup_resize_h = t.content.offsetHeight;
			}
			x = win.getWindowWidth()/2 - x/2;
			y = win.getWindowHeight()/2 - (y+t.header.scrollHeight+(t.buttons_tr ? t.buttons_tr.scrollHeight : 0))/2;
		} else {
			t.content_container.style.height = "";
			t.content_container.style.width = "";
			t.content_container.style.overflow = "";
			var h = 0;
			if (t.header) h += win.getHeight(t.header);
			if (t.buttons_tr) h += win.getHeight(t.buttons_tr);
			var content_h = win.getHeight(t.content_container);
			y = win.getWindowHeight()/2 - (h+content_h)/2;
			if (y < 5) {
				y = 5;
				t.content_container.style.overflowX = "auto";
				t.content_container.style.height = (win.getWindowHeight()-20-h)+"px";
				if (t.content_container.offsetWidth > t.content_container.clientWidth) {
					t.content_container.style.width = (t.content_container.offsetWidth+(t.content_container.offsetWidth-t.content_container.clientWidth))+"px"; 
				}
			}
			var content_w = win.getWidth(t.content_container);
			var header_w = win.getWidth(t.header);
			if (header_w > content_w) content_w = header_w;
			x = win.getWindowWidth()/2 - content_w/2;
			if (x < 5) {
				x = 5;
				t.content_container.style.overflow = "auto";
				t.content_container.style.width = (win.getWindowWidth()-20)+"px";
				t.table.style.width = (win.getWindowWidth()-20)+"px";
			}
			t.content_container.style.position = "static";
		}
		t.table.style.top = Math.floor(y)+"px";
		t.table.style.left = Math.floor(x)+"px";
		t.in_resize = false;
	};
	
	t.blink = function() {
		t.table.className = "popup_window blink";
		setTimeout(function() { t.table.className = "popup_window"; },100);
		setTimeout(function() { t.table.className = "popup_window blink"; },200);
		setTimeout(function() { t.table.className = "popup_window"; },300);
		setTimeout(function() { t.table.className = "popup_window blink"; },400);
		setTimeout(function() { t.table.className = "popup_window"; },500);
	};
	
	t.disableClose = function() {
		t.close_button_td.onclick = null;
	};
	t.enableClose = function() {
		t.close_button_td.onclick = function() { t.close(); };
	};
	
	t.freeze = function(freeze_content) {
		if (t.freezer) {
			t.freezer.usage_counter++;
			if (freeze_content)
				t.set_freeze_content(freeze_content);
			return;
		}
		t.freezer = t.table.ownerDocument.createElement("DIV");
		t.freezer.usage_counter = 1;
		t.freezer.style.position = "absolute";
		t.freezer.style.top = "0px";
		t.freezer.style.left = "0px";
		t.freezer.style.width = "100%";
		t.freezer.style.height = "100%";
		t.freezer.style.backgroundColor = "rgba(128,128,128,0.5)";
		if (freeze_content)
			set_lock_screen_content(t.freezer, freeze_content);
		t.content_container.parentNode.style.position = "relative";
		t.content_container.parentNode.appendChild(t.freezer);
		t.freeze_button_status = [];
		for (var i = 0; i < t.buttons.length; ++i) {
			t.freeze_button_status[i] = t.buttons[i].disabled;
			t.buttons[i].disabled = 'disabled';
		}
		t.close_button_td.onclick = null;
	};
	t.freeze_progress = function(message, total, onready) {
		require("progress_bar.js", function() {
			var div = document.createElement("DIV");
			div.style.textAlign = "center";
			var span = document.createElement("SPAN");
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
	t.set_freeze_content = function(content) {
		if (!t.freezer) return;
		set_lock_screen_content(t.freezer, content);
	};
	t.unfreeze = function() {
		if (!t.freezer) return;
		if (--t.freezer.usage_counter > 0) return;
		t.content_container.parentNode.removeChild(t.freezer);
		t.freezer = null;
		for (var i = 0; i < t.buttons.length; ++i)
			t.buttons[i].disabled = t.freeze_button_status[i];
		t.freeze_button_status = null;
		t.close_button_td.onclick = function() { t.close(); };
		if (t.content.nodeName == "IFRAME")
			t.resize(); // we interrupt resize during freeze
	};
	t.isFrozen = function() {
		return t.freezer != null;
	};
	
	/** Close this popup window
	 * @method popup_window#close
	 * @param keep_content_hidden
	 */
	t.close = function(keep_content_hidden) {
		if (!t.table) return;
		if (t.locker)
			unlock_screen(t.locker);
		else {
			var parent_popup = get_popup_window_from_frame(window);
			if(parent_popup && parent_popup.table) parent_popup.unfreeze();
		}
		getWindowFromDocument(t.table.ownerDocument).layout.removeHandler(t.table, t.resize);
		var table = t.table;
		if (t.onclose) t.onclose();
		t.table = null;
		var do_close = function() {
			if (keep_content_hidden || t.keep_content_on_close) {
				t.content.parentNode.removeChild(t.content);
				t.content.style.position = 'absolute';
				t.content.style.visibility = 'hidden';
				t.content.style.top = '-10000px';
				t.content.ownerDocument.body.appendChild(t.content);
			}
			if (table.parentNode)
				table.parentNode.removeChild(table);
		};
		if (t.content.nodeName == "IFRAME") t.content._no_loading = true;
		if (typeof animation != 'undefined') {
			if (t.anim) animation.stop(t.anim);
			animation.fadeOut(table, 200, do_close);
		} else
			do_close();
	};
	t.hide = function() { t.close(); };
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
	if (win.frameElement && win.parent.get_popup_window_from_element)
		return win.parent.get_popup_window_from_element(win.frameElement);
	return null;
}
