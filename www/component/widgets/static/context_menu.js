if (typeof window.top.require != 'undefined')
	window.top.require("animation.js");
if (typeof theme != 'undefined')
	theme.css("context_menu.css");
/**
 * Create a contextual menu.
 * If an element is given, each item inside this element will be identified by having the class 'context_menu_item'
 * @constructor
 * @param menu a div element containing the menu, or the id of the element, or null if you will create items dynamically
 */
function context_menu(menu) {
	if (typeof menu == "string") menu = document.getElementById(menu);
	if (menu != null && menu.parentNode != null && menu.parentNode.nodeType == 1)
		menu.parentNode.removeChild(menu);
	
	var t = this;
	/** Indicate if the menu should be removed when closed, or only hidden
	 * @member {boolean} context_menu#removeOnClose
	 */
	t.removeOnClose = menu ? false : true;

	if (menu == null) {
		menu = document.createElement("DIV");
		menu.className = 'context_menu';
	}
	menu.context_menu=this;
	this.element = menu;
	/** Called when the menu is closed
	 * @member {function} context_menu#onclose
	 */
	t.onclose = null;
	
	// populate the items from the given element: take every child having the class 'context_menu_item'
	for (var i = 0; i < menu.childNodes.length; ++i)
		if (menu.childNodes[i].nodeType == 1 && menu.childNodes[i].className == "context_menu_item") {
			if (typeof menu.childNodes[i].onclickset == 'undefined' && menu.childNodes[i].onclick && !menu.childNodes[i].data)
				menu.childNodes[i].data = menu.childNodes[i].onclick;
			menu.childNodes[i].onclick = function() {
				t.hide();
				if (this.data) this.data();
				return false;
			};
			menu.childNodes[i].onclickset = true;
		}
	
	/** Append an item to the menu
	 * @method context_menu#addItem
	 * @param element the html element to append
	 * @param {boolean} keep_onclick if true, the menu will not be closed when the user click on it.
	 */
	t.addItem = function(element, keep_onclick) {
		element.style.position = 'static';
		menu.appendChild(element);
		if (element.nodeName == "A") {
			// this is a link: onclick, close the menu and follow the link
			element.onclick = function() {
				t.hide();
				return true;
			};
		} else if (!keep_onclick) {
			if (typeof element.onclickset == 'undefined' && element.onclick && !element.data)
				element.data = element.onclick;
			element.onclick = function() {
				t.hide();
				if (this.data) this.data();
				return false;
			};
			element.onclickset = true;
		}
		return element;
	};
	/** Append an item to the menu.
	 * @method context_menu#addIconItem
	 * @param {string} icon url of the icon of the item
	 * @param {string} text the text of the item
	 * @param {function} onclick called when the user click on the item
	 * @returns the html element corresponding to the item
	 */
	t.addIconItem = function(icon, text, onclick, onclick_parameter) {
		var div = document.createElement("DIV");
		if (icon) {
			var img = document.createElement("IMG");
			img.onload = function() { t.resize(); };
			img.src = icon;
			img.style.verticalAlign = "bottom";
			img.style.marginRight = "5px";
			div.appendChild(img);
		}
		div.appendChild(document.createTextNode(text));
		div.onclick = function(ev) { if (onclick) onclick(ev, onclick_parameter); };
		div.className = "context_menu_item";
		t.addItem(div);
		return div;
	};
	t.addHtmlItem = function(html, onclick) {
		if (typeof html == 'string') {
			var div = document.createElement("DIV");
			div.innerHTML = html;
			html = div;
		}
		html.className = "context_menu_item";
		if (onclick) html.onclick = onclick;
		t.addItem(html);
		return html;
	};
	/**
	 * Append a title to the menu
	 * @param {string} icon url of the icon of the item
	 * @param {string} text the text of the item
	 */
	t.addTitleItem = function(icon, text) {
		var div = document.createElement("DIV");
		if (icon) {
			var img = document.createElement("IMG");
			img.onload = function() { t.resize(); };
			img.src = icon;
			img.style.verticalAlign = "bottom";
			img.style.marginRight = "5px";
			div.appendChild(img);
		}
		div.appendChild(document.createTextNode(text));
		div.className = "context_menu_title";
		t.addItem(div);
		return div;
	};
	t.addSeparator = function() {
		var sep = document.createElement("DIV");
		sep.style.borderBottom = "1px solid black";
		sep.style.marginTop = "3px";
		sep.style.marginBottom = "3px";
		t.addItem(sep);
	};
	/** Return the items contained in this menu
	 * @method context_menu#getItems
	 * @returns the list of html elements contained in the menu
	 */
	t.getItems = function() { return menu.childNodes; };
	/** Remove all items from this menu
	 * @method context_menu#clearItems
	 */
	t.clearItems = function() {
		while (menu.childNodes.length > 0)
			menu.childNodes[0].parentNode.removeChild(menu.childNodes[0]);
	};
	
	/** Display the menu below the given element
	 * @method context_menu#showBelowElement
	 * @param from the element below which the menu will be displayed
	 */
	t.showBelowElement = function(from, min_width_is_from) {
		menu.style.visibility = "visible";
		menu.style.position = "fixed";
		t.show_from = from;
		menu.style.top = "0px";
		menu.style.left = "0px";
		menu.style.width = "";
		menu.style.height = "";
		window.top.document.body.appendChild(menu);
		var win = getWindowFromElement(from);
		var x,y,w,h;
		var pos = win.getFixedPosition(from);
		x = pos.x;
		y = pos.y;
		w = menu.offsetWidth;
		h = menu.offsetHeight;
		if (min_width_is_from && w < from.offsetWidth) {
			setWidth(menu, w = from.offsetWidth, []);
		}
		if (y+from.offsetHeight+h > window.top.getWindowHeight()) {
			// not enough space below
			var space_below = window.top.getWindowHeight()-(y+from.offsetHeight);
			var space_above = y;
			if (space_above > space_below) {
				y = y-h;
				if (y < 0) {
					// not enough space: scroll bar
					y = 0;
					menu.style.overflowY = 'scroll';
					menu.style.height = space_above+"px";
				}
			} else {
				// not enough space: scroll bar
				y = y+from.offsetHeight;
				menu.style.overflowY = 'scroll';
				menu.style.height = space_below+"px";
			}
		} else {
			// by default, show it below
			y = y+from.offsetHeight;
		}
		if (x+w > window.top.getWindowWidth()) {
			x = window.top.getWindowWidth()-w;
		}
		window.top.document.body.removeChild(menu);
		t.showAt(x,y,from);
	};
	/** Display the menu above the given element
	 * @method context_menu#showAboveElement
	 * @param from the element above which the menu will be displayed
	 */
	t.showAboveElement = function(from, min_width_is_from) {
		menu.style.visibility = "visible";
		menu.style.position = "fixed";
		t.show_from = from;
		menu.style.top = "0px";
		menu.style.width = "0px";
		menu.style.width = "";
		menu.style.height = "";
		window.top.document.body.appendChild(menu);
		var win = getWindowFromElement(from);
		var x,y,w,h;
		var pos = win.getFixedPosition(from);
		x = pos.x;
		y = pos.y;
		w = menu.offsetWidth;
		h = menu.offsetHeight;
		if (min_width_is_from && w < from.offsetWidth) {
			setWidth(menu, w = from.offsetWidth, []);
		}
		if (y-h < 0) {
			// not enough space above
			var space_below = window.top.getWindowHeight()-(y+from.offsetHeight);
			var space_above = y;
			if (space_below > space_above) {
				y = y+from.offsetHeight;
				if (y+h > window.top.getWindowHeight()) {
					// not enough space: scroll bar
					y = 0;
					menu.style.overflowY = 'scroll';
					menu.style.height = space_above+"px";
				}
			} else {
				// not enough space: scroll bar
				y = 0;
				menu.style.overflowY = 'scroll';
				menu.style.height = space_below+"px";
			}
		} else {
			// by default, show it above
			y = y-h;
		}
		if (x+w > window.top.getWindowWidth()) {
			x = window.top.getWindowWidth()-w;
		}
		window.top.document.body.removeChild(menu);
		t.showAt(x,y,from);
	};
	/** Display the menu at the given position (using absolute positioning)
	 * @member context_menu#showAt
	 */
	t.showAt = function(x,y,from) {
		var e = from;
		var from_inside_menu = false;
		while (e && e != from.ownerDocument.body) { if (e.className == 'context_menu') { from_inside_menu = true; break; } e = e.parentNode; }
		if (from_inside_menu) {
			t.parent_menu = e.context_menu;
			t.parent_menu_listener = t.parent_menu.hide_if_outside_menu;
			t.parent_menu.hide_if_outside_menu = function(){};
		}
		menu.style.visibility = "visible";
		menu.style.position = "fixed";
		menu.style.top = y+"px";
		menu.style.left = x+"px";
		t.show_at = [x,y];
//		for (var i = 0; i < document.body.childNodes.length; ++i)
//			if (document.body.childNodes[i].style) document.body.childNodes[i].style.zIndex = -10;
		menu.style.zIndex = 100;
		if (typeof window.top.animation != 'undefined') {
			if (menu.anim) window.top.animation.stop(menu.anim);
		}
		if (typeof window.top.animation != 'undefined')
			menu.style.visibility = 'hidden';
		window.top.document.body.appendChild(menu);
		window.top.pnapplication.onwindowclosed.add_listener(t._window_close_listener);
		setTimeout(function() {
			//listenEvent(window,'click',t._listener);
			window.top.pnapplication.registerOnclick(window, t._listener);
		},1);
		if (typeof window.top.animation != 'undefined')
			menu.anim = window.top.animation.fadeIn(menu,300,function(){menu.anim = null;});
	};
	t._this_win = window;
	t._window_close_listener = function(c) {
		if (c.win != t._this_win) return;
		c.top.document.body.removeChild(menu);
		c.top.pnapplication.onwindowclosed.remove_listener(t._window_close_listener);
		t._this_win = null;
	};
	/** Hide the menu: call <code>onclose</code> if specified, then hide or remove the html element of the menu depending on <code>removeOnClose</code> 
	 * @member context_menu#hide
	 */
	t.hide = function() {
		window.top.pnapplication.onwindowclosed.remove_listener(t._window_close_listener);
		if (t.onclose) t.onclose();
		if (t.parent_menu) {
			setTimeout(function(){
				t.parent_menu.hide_if_outside_menu = t.parent_menu_listener;
			},1);
		}
		if (typeof window.top.animation != 'undefined') {
			if (menu.anim) window.top.animation.stop(menu.anim);
			menu.anim = window.top.animation.fadeOut(menu,300,function() {
				if (t.removeOnClose)
					try { menu.parentNode.removeChild(menu); } catch (e) {}
			});
		} else {
			if (t.removeOnClose)
				try { menu.parentNode.removeChild(menu); } catch (e) {}
			else {
				menu.style.visibility = "hidden";
				menu.style.top = "-10000px";
			}
		}
//		for (var i = 0; i < document.body.childNodes.length; ++i)
//			if (document.body.childNodes[i].style) document.body.childNodes[i].style.zIndex = 1;
		//unlistenEvent(window, 'click', t._listener);
		if (window)
			window.top.pnapplication.unregisterOnclick(t._listener);
	};
	t._listener = function(ev, win, orig_win) {
		t.hide_if_outside_menu(ev, win, orig_win);
	};
	t.hide_if_outside_menu = function(ev, win, orig_win) {
		var is_inside = function(child,parent) {
			// check if the target is inside
			if (child) {
				do {
					if (child == parent) return true;
					if (child.parentNode == child) break;
					child = child.parentNode;
					if (child == null || child == document.body || child == window) break;
				} while (true);
			}
			return false;
		};
		var child_win = getWindowFromElement(ev.target);
		var parent_win = getWindowFromElement(menu);
		if (child_win != parent_win) {
			t.hide();
			return;
		}
		if (is_inside(ev.target, menu)) return;
		// check if this is inside
		ev = getCompatibleMouseEvent(ev);
		var x = absoluteLeft(menu);
		var y = absoluteTop(menu);
		if (ev.x >= x && ev.x < x+menu.offsetWidth &&
			ev.y >= y && ev.y < y+menu.offsetHeight) return;
		t.hide();
	};
	
	t.resize = function() {
		if (menu.parentNode != window.top.document.body) return;
		window.top.document.body.removeChild(menu);
		menu.style.top = "";
		menu.style.left = "";
		if (t.show_from)
			t.showBelowElement(t.show_from);
		else
			t.showAt(t.show_at[0], t.show_at[1]);
	};
	
	t.close = function() { t.hide(); };
}
