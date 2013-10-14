if (typeof require != 'undefined') {
	require("animation.js");
}
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
	if (menu == null) {
		menu = document.createElement("DIV");
		menu.className = 'context_menu';
	}
	menu.context_menu=this;
	/** Indicate if the menu should be removed when closed, or only hidden
	 * @member {boolean} context_menu#removeOnClose
	 */
	t.removeOnClose = false;
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
		if (!keep_onclick) {
			if (typeof element.onclickset == 'undefined' && element.onclick && !element.data)
				element.data = element.onclick;
			element.onclick = function() {
				t.hide();
				if (this.data) this.data();
				return false;
			};
			element.onclickset = true;
		}
	};
	/** Append an item to the menu.
	 * @method context_menu#addIconItem
	 * @param {string} icon url of the icon of the item
	 * @param {string} text the text of the item
	 * @param {function} onclick called when the user click on the item
	 * @returns the html element corresponding to the item
	 */
	t.addIconItem = function(icon, text, onclick) {
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
		div.onclick = onclick;
		div.className = "context_menu_item";
		t.addItem(div);
		return div;
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
	t.showBelowElement = function(from) {
		menu.style.visibility = "visible";
		menu.style.position = "absolute";
		t.show_from = from;
		menu.style.width = "";
		menu.style.height = "";
		document.body.appendChild(menu);
		var x = absoluteLeft(from);
		var y = absoluteTop(from);
		var w = menu.offsetWidth;
		var h = menu.offsetHeight;
		if (y+from.offsetHeight+h > getWindowHeight()) {
			// not enough space below
			var space_below = getWindowHeight()-(y+from.offsetHeight);
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
		if (x+w > getWindowWidth()) {
			x = getWindowWidth()-w;
		}
		document.body.removeChild(menu);
		t.showAt(x,y,from);
	};
	/** Display the menu above the given element
	 * @method context_menu#showAboveElement
	 * @param from the element above which the menu will be displayed
	 */
	t.showAboveElement = function(from) {
		menu.style.visibility = "visible";
		menu.style.position = "absolute";
		t.show_from = from;
		menu.style.width = "";
		menu.style.height = "";
		document.body.appendChild(menu);
		var x = absoluteLeft(from);
		var y = absoluteTop(from);
		var w = menu.offsetWidth;
		var h = menu.offsetHeight;
		if (y-h < 0) {
			// not enough space above
			var space_below = getWindowHeight()-(y+from.offsetHeight);
			var space_above = y;
			if (space_below > space_above) {
				y = y+from.offsetHeight;
				if (y+h > getWindowHeight()) {
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
		if (x+w > getWindowWidth()) {
			x = getWindowWidth()-w;
		}
		document.body.removeChild(menu);
		t.showAt(x,y,from);
	};
	/** Display the menu at the given position (using absolute positioning)
	 * @member context_menu#showAt
	 */
	t.showAt = function(x,y,from) {
		var e = from;
		var from_inside_menu = false;
		while (e && e != document.body) { if (e.className == 'context_menu') { from_inside_menu = true; break; } e = e.parentNode; }
		if (from_inside_menu) {
			t.parent_menu = e.context_menu;
			t.parent_menu_listener = t.parent_menu.hide_if_outside_menu;
			t.parent_menu.hide_if_outside_menu = function(){}
		}
		menu.style.visibility = "visible";
		menu.style.position = "absolute";
		menu.style.top = y+"px";
		menu.style.left = x+"px";
		t.show_at = [x,y];
//		for (var i = 0; i < document.body.childNodes.length; ++i)
//			if (document.body.childNodes[i].style) document.body.childNodes[i].style.zIndex = -10;
		document.body.appendChild(menu);
		menu.style.zIndex = 100;
		setTimeout(function() {
			//listenEvent(window,'click',t._listener);
			window.top.pnapplication.register_onclick(window, t._listener);
		},1);
		if (typeof animation != 'undefined') {
			if (menu.anim) animation.stop(menu.anim);
			menu.anim = animation.fadeIn(menu,300);
		}
	};
	/** Hide the menu: call <code>onclose</code> if specified, then hide or remove the html element of the menu depending on <code>removeOnClose</code> 
	 * @member context_menu#hide
	 */
	t.hide = function() {
		if (t.onclose) t.onclose();
		if (t.parent_menu) {
			setTimeout(function(){
				t.parent_menu.hide_if_outside_menu = t.parent_menu_listener;
			},1);
		}
		if (typeof animation != 'undefined') {
			if (menu.anim) animation.stop(menu.anim);
			menu.anim = animation.fadeOut(menu,300,function() {
				if (t.removeOnClose)
					document.body.removeChild(menu);
			});
		} else {
			if (t.removeOnClose)
				document.body.removeChild(menu);
			else {
				menu.style.visibility = "hidden";
				menu.style.top = "-10000px";
			}
		}
//		for (var i = 0; i < document.body.childNodes.length; ++i)
//			if (document.body.childNodes[i].style) document.body.childNodes[i].style.zIndex = 1;
		//unlistenEvent(window, 'click', t._listener);
		window.top.pnapplication.unregister_onclick(t._listener);
	};
	t._listener = function(ev, win, orig_win) {
		t.hide_if_outside_menu(ev, win, orig_win);
	};
	t.hide_if_outside_menu = function(ev, win, orig_win) {
		if (win == orig_win) {
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
			}
			if (is_inside(ev.target, menu)) return;
			// check if this is inside
			ev = getCompatibleMouseEvent(ev);
			var x = absoluteLeft(menu);
			var y = absoluteTop(menu);
			if (ev.x >= x && ev.x < x+menu.offsetWidth &&
				ev.y >= y && ev.y < y+menu.offsetHeight) return;
		}
		t.hide();
	};
	
	t.resize = function() {
		if (menu.parentNode != document.body) return;
		document.body.removeChild(menu);
		menu.style.top = "";
		menu.style.left = "";
		if (t.show_from)
			t.showBelowElement(t.show_from);
		else
			t.showAt(t.show_at[0], t.show_at[1]);
	};
}
