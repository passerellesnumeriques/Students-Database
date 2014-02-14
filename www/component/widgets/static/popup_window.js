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
	 * @method popup_window#setContentFrame
	 * @param {string} url url to load in the frame
	 */
	t.setContentFrame = function(url, onload) {
		t.content = document.createElement("IFRAME");
		t.content.style.border = "0px";
		t.content.src = url;
		t.content.onload = function() {
			//t.table.style.width = "80%";
			t.content.style.width = "100%";
			t.content.style.height = "100%";
			t.resize();
			setTimeout(function() { t.resize(); }, 1);
			setTimeout(function() { t.resize(); }, 100);
			if (onload) onload(t.content);
		};
		if (t.content_container) {
			while (t.content_container.childNodes.length > 0) t.content_container.removeChild(t.content_container.childNodes[0]);
			t.content_container.appendChild(t.content);
			t.resize();
		}
		return t.content;
	};
	
	/** Add a button at the bottom of the popup.
	 * @method popup_window#addButton
	 * @param {string} html html to put inside the button 
	 * @param {string} id id of the button, that can be used to refer it later on
	 * @param {function} onclick onclick event handler
	 */
	t.addButton = function(html, id, onclick) {
		var b = document.createElement("BUTTON");
		b.innerHTML = html;
		b.id = id;
		b.onclick = onclick;
		t.buttons.push(b);
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
	/** Add 2 buttons to the window: Ok and Cancel. When Cancel is pressed, the window is closed.
	 * @method popup_window#addOkCancelButtons
	 * @param {function} onok handler to be called when the Ok button is pressed. 
	 * @param {function} (optional) oncancel handler to be called when the Cancel button is pressed. 
	 */
	t.addOkCancelButtons = function(onok, oncancel) {
		t.addButton("<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> Ok", 'ok', onok);
		if(oncancel)
			t.addButton("<img src='"+theme.icons_16.cancel+"' style='vertical-align:bottom'/> Cancel", 'cancel', function(){oncancel(); t.close()});
		else
			t.addButton("<img src='"+theme.icons_16.cancel+"' style='vertical-align:bottom'/> Cancel", 'cancel', function() { t.close(); });
	};
	/** Add 2 buttons to the window: Yes and No. When No is pressed, the window is closed.
	 * @method popup_window#addYesNoButtons
	 * @param {function} onyes handler to be called when the Yes button is pressed. 
	 */
	t.addYesNoButtons = function(onyes) {
		t.addButton("<img src='"+theme.icons_16.yes+"' style='vertical-align:bottom'/> Yes", 'yes', onyes);
		t.addButton("<img src='"+theme.icons_16.no+"' style='vertical-align:bottom'/> No", 'no', function() { t.close(); });
	};
	
	t.isShown = function() {
		return t.table != null;
	};
	
	t.showPercent = function(width, height) {
		var win = t._buildTable();
		t.resize = function() {
			t.table.style.left = (win.getWindowWidth()*(100-width)/200)+"px";
			t.table.style.top = (win.getWindowHeight()*(100-height)/200)+"px";
			t.table.style.width = (win.getWindowWidth()*width/100)+"px";
			t.table.style.height = (win.getWindowHeight()*height/100)+"px";
		};
		t.resize();
		win.listenEvent(win, "resize", function() {
			t.resize();
		});
	};
	
	/** Display the popup window
	 * @method popup_window#show
	 */
	t.show = function() {
		var win = t._buildTable();
		var move_handler = function(ev) {
			if (!ev) ev = window.event;
			var diff_x = ev.clientX - t._move_x;
			var diff_y = ev.clientY - t._move_y;
			if (diff_x == 0 && diff_y == 0) return;
			t._move_x = ev.clientX;
			t._move_y = ev.clientY;
			var x = absoluteLeft(t.table);
			x += diff_x;
			if (x < 5) x = 5;
			if (x + t.table.offsetWidth > win.getWindowWidth()-10) x = win.getWindowWidth()-5-t.table.offsetWidth;
			var y = absoluteTop(t.table);
			y += diff_y;
			if (y < 5) y = 5;
			if (y + t.table.offsetHeight > win.getWindowHeight()-10) y = win.getWindowHeight()-5-t.table.offsetHeight;
			t.table.style.top = y+"px";
			t.table.style.left = x+"px";
		};
		var up_handler = null; // only to remove the warning
		up_handler = function(ev) {
			unlistenEvent(window,'mousemove',move_handler);
			unlistenEvent(window,'mouseup',up_handler);
			unlistenEvent(window,'mouseout',up_handler);
		};
		t.header.onmousedown = function(ev) {
			if (!ev) ev = window.event;
			t._move_x = ev.clientX;
			t._move_y = ev.clientY;
			listenEvent(window,'mousemove',move_handler);
			listenEvent(window,'mouseup',up_handler);
			listenEvent(window,'mouseout',up_handler);
			return false;
		};
		t.resize();
		fireLayoutEventFor(t.content_container);
		if (typeof animation != 'undefined') {
			if (t.anim) animation.stop(t.anim);
			t.anim = animation.fadeIn(t.table, 200);
		}
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
		t.content_container = doc.createElement("DIV");
		t.content_container.style.width = "100%";
		t.content_container.style.height = "100%";
		td.appendChild(t.content_container);
		td.style.padding = "0px";
		td.style.margin = "0px";
		if (t.buttons.length > 0) {
			var tr = doc.createElement("TR");
			tr.className = 'popup_window_buttons';
			t.table.appendChild(tr);
			td = doc.createElement("TD"); tr.appendChild(td);
			td.colSpan = 2;
			for (var i = 0; i < t.buttons.length; ++i)
				td.appendChild(t.buttons[i]);
			t.buttons_tr = tr;
		}
		doc.body.appendChild(t.table);
		if (typeof t.content == 'string') t.content_container.innerHTML = t.content;
		else {
			t.content_container.appendChild(t.content);
			t.content.style.position = 'static';
			t.content.style.visibility = 'visible';
		}
		return win;
	};
	
	t._computeFrameWidth = function(body) {
		var win = getIFrameWindow(t.content);
		var max = 0;
		for (var i = 0; i < body.childNodes.length; ++i) {
			var e = body.childNodes[i];
			var w = null;
			if (e.nodeType != 1) continue;
			if (e.nodeName == "DIV") {
				e._display = e.style && e.style.display ? e.style.display : "";
				e._whiteSpace = e.style && e.style.whiteSpace ? e.style.whiteSpace : "";
				e.style.display = 'inline-block';
				e.style.whiteSpace = 'nowrap';
			}
			if (e.nodeName == "FORM")
				w = win.absoluteLeft(e) + t._computeFrameWidth(e);
			if (w == null) w = win.absoluteLeft(e)+(win.getWidth ? win.getWidth(e) : getWidth(e));
			if (w > max) max = w;
			if (e.nodeName == "DIV") {
				e.style.display = e._display;
				e.style.whiteSpace = e._whiteSpace;
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
			if (e.nodeName == "DIV") {
				e._display = e.style && e.style.display ? e.style.display : "";
				e._whiteSpace = e.style && e.style.whiteSpace ? e.style.whiteSpace : "";
				e.style.display = 'inline-block';
				e.style.whiteSpace = 'nowrap';
			}
			if (e.nodeName == "FORM")
				h = win.absoluteTop(e) + t._computeFrameHeight(e);
			if (h == null) h = win.absoluteTop(e)+(win.getHeight ? win.getHeight(e) : getHeight(e));
			if (h > max) max = h;
			if (e.nodeName == "DIV") {
				e.style.display = e._display;
				e.style.whiteSpace = e._whiteSpace;
			}
		}
		return max;
	};
		
	/** Resize the window according to its content: this is normally automatically called. 
	 * @method popup_window#resize
	 */
	t.resize = function() {
		if (!t.table) return;
		if (t.in_resize) return;
		t.in_resize = true;
		var x, y;
		var win = getWindowFromDocument(t.table.ownerDocument);
		if (t.content.nodeName == "IFRAME") {
			t.content_container.style.width = (win.getWindowWidth()-30)+"px";
			t.content_container.style.height = (win.getWindowHeight()-30)+"px";
			t.content_container.style.overflow = "";
			var frame = getIFrameDocument(t.content); 
			x = t._computeFrameWidth(frame.body);
			y = t._computeFrameHeight(frame.body);
			frame.body.style.margin = "0px";
			frame.body.style.padding = "0px";
			frame.body.style.border = "none";
			var h = 0;
			if (t.header) h += getHeight(t.header);
			if (t.buttons_tr) h += getHeight(t.buttons_tr);
			if (x > win.getWindowWidth()-30) {
				x = win.getWindowWidth()-30;
				// anticipate scroll bar
				y += 20;
			}
			if (y > win.getWindowHeight()-30-h) {
				y = win.getWindowHeight()-30-h;
				// anticipate scroll bar
				if (x < win.getWindowWidth()-30) x += 20;
				if (x > win.getWindowWidth()-30) x = win.getWindowWidth()-30;
			}
			getIFrameDocument(t.content).body.style.overflow = "hidden";
			setWidth(t.content_container, x);
			setHeight(t.content_container, y);
			t.content_container.overflow = "hidden";
			getIFrameDocument(t.content).body.style.overflow = "";
			x = win.getWindowWidth()/2 - x/2;
			y = win.getWindowHeight()/2 - (y+t.header.scrollHeight)/2;
		} else {
			t.content_container.style.height = "";
			t.content_container.style.width = "";
			t.content_container.style.overflow = "";
			y = win.getWindowHeight()/2 - t.table.scrollHeight/2;
			if (y < 5) {
				y = 5;
				t.content_container.style.overflowX = "auto";
				var h = 0;
				if (t.header) h += getHeight(t.header);
				if (t.buttons_tr) h += getHeight(t.buttons_tr);
				t.content_container.style.height = (win.getWindowHeight()-30-h)+"px";
				if (t.content_container.offsetWidth > t.content_container.clientWidth) {
					t.content_container.style.width = (t.content_container.offsetWidth+(t.content_container.offsetWidth-t.content_container.clientWidth))+"px"; 
				}
			}
			x = win.getWindowWidth()/2 - t.table.scrollWidth/2;
			if (x < 5) {
				x = 5;
				t.content_container.style.overflow = "auto";
				t.content_container.style.width = (win.getWindowWidth()-30)+"px";
			}
		}
		t.table.style.top = y+"px";
		t.table.style.left = x+"px";
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
	
	t.freeze = function(freeze_content) {
		if (t.freezer) return;
		t.freezer = t.table.ownerDocument.createElement("DIV");
		t.freezer.style.position = "absolute";
		t.freezer.style.top = "0px";
		t.freezer.style.left = "0px";
		t.freezer.style.width = "100%";
		t.freezer.style.height = "100%";
		t.freezer.style.backgroundColor = "rgba(128,128,128,0.5)";
		if (freeze_content)
			set_lock_screen_content(t.freezer, freeze_content);
		t.content_container.style.position = "relative";
		t.content_container.appendChild(t.freezer);
		t.freeze_button_status = [];
		for (var i = 0; i < t.buttons.length; ++i) {
			t.freeze_button_status[i] = t.buttons[i].disabled;
			t.buttons[i].disabled = 'disabled';
		}
		t.close_button_td.onclick = null;
	};
	t.set_freeze_content = function(content) {
		if (!t.freezer) return;
		set_lock_screen_content(t.freezer, content);
	};
	t.unfreeze = function() {
		if (!t.freezer) return;
		t.content_container.removeChild(t.freezer);
		t.freezer = null;
		for (var i = 0; i < t.buttons.length; ++i)
			t.buttons[i].disabled = t.freeze_button_status[i];
		t.freeze_button_status = null;
		t.close_button_td.onclick = function() { t.close(); };
	};
	
	/** Close this popup window
	 * @method popup_window#close
	 * @param keep_content_hidden
	 */
	t.close = function(keep_content_hidden) {
		if (t.locker)
			unlock_screen(t.locker);
		else {
			var parent_popup = get_popup_window_from_frame(window);
			if(parent_popup) parent_popup.unfreeze();
		}
		if (t.onclose) t.onclose();
		var table = t.table;
		t.table = null;
		var do_close = function() {
			if (keep_content_hidden || t.keep_content_on_close) {
				t.content.parentNode.removeChild(t.content);
				t.content.style.position = 'absolute';
				t.content.style.visibility = 'hidden';
				t.content.style.top = '-10000px';
				t.content.ownerDocument.body.appendChild(t.content);
			}
			table.ownerDocument.body.removeChild(table);
		};
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
	if (win.frameElement && win.parent.get_popup_window_from_element)
		return win.parent.get_popup_window_from_element(win.frameElement);
	return null;
}
