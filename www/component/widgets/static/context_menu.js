if (typeof window.top.require != 'undefined') {
	window.top.require("animation.js");
	window.top.require("position.js");
}
if (typeof theme != 'undefined')
	theme.css("context_menu.css");
/**
 * Create a contextual menu.
 * If an element is given, each item inside this element will be identified by having the class 'context_menu_item'
 * @param {Element|null} menu a div element containing the menu, or the id of the element, or null if you will create items dynamically
 */
function context_menu(menu) {
	if (typeof menu == "string") menu = document.getElementById(menu);
	if (menu != null && menu.parentNode != null && menu.parentNode.nodeType == 1)
		menu.parentNode.removeChild(menu);
	
	var t = this;
	/** {Boolean} Indicate if the menu should be removed when closed, or only hidden */
	t.removeOnClose = menu ? false : true;

	if (menu == null) {
		menu = document.createElement("DIV");
		menu.className = 'context_menu';
	}
	menu.style.display = "none";
	window.top.document.body.appendChild(menu);
	menu.context_menu=this;
	this.element = menu;
	/** {Function} Called when the menu is closed */
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
	
	/** Event fired when an element is clicked */
	t.element_clicked = new Custom_Event();
	
	/** Append an item to the menu
	 * @param {Element} element the html element to append
	 * @param {Boolean} keep_onclick if true, the menu will not be closed when the user click on it.
	 */
	t.addItem = function(element, keep_onclick) {
		element.style.position = 'static';
		menu.appendChild(element);
		if (element.nodeName == "A") {
			// this is a link: onclick, close the menu and follow the link
			element.onclick = function() {
				t.element_clicked.fire(element);
				t.hide();
				return true;
			};
		} else if (!keep_onclick) {
			if (typeof element.onclickset == 'undefined' && element.onclick && !element.data)
				element.data = element.onclick;
			element.onclick = function() {
				t.element_clicked.fire(element);
				t.hide();
				if (this.data) this.data();
				return false;
			};
			element.onclickset = true;
		}
		return element;
	};
	/** Append an item to the menu.
	 * @param {String} icon url of the icon of the item
	 * @param {String} text the text of the item
	 * @param {Function} onclick called when the user click on the item
	 * @param {Object} onclick_parameter if given, it will be passed as second parameter of the onclick function (first parameter being the click event)
	 * @returns {Element} the html element corresponding to the item
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
	/**
	 * Append an item to the menu
	 * @param {String|Element} html the HTML representing the item
	 * @param {Function} onclick function to be called when the given item is clicked
	 */
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
	 * @param {String} icon url of the icon of the item
	 * @param {String} text the text of the item
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
	/**
	 * Append a separator (horizontal line)
	 */
	t.addSeparator = function() {
		var sep = document.createElement("DIV");
		sep.style.borderBottom = "1px solid black";
		sep.style.marginTop = "3px";
		sep.style.marginBottom = "3px";
		t.addItem(sep);
	};
	/**
	 * Add an item, which when clicked will display a sub-menu
	 * @param {String|null} icon URL of the icon
	 * @param {String} text the text
	 * @param {Function} provider when the item is clicked, this function is called to get the content of the sub-menu. This function takes 2 parameters: <ul><li>a context_menu to be filled</li><li>A function to be called when the sub-menu is ready/filled and can be displayed</li></ul>
	 */
	t.addSubMenuItem = function(icon, text, provider) {
		var div = document.createElement("DIV");
		div._sub_menu = true;
		div.style.display = "flex";
		div.style.flexDirection = "row";
		if (icon) {
			var img = document.createElement("IMG");
			img.onload = function() { t.resize(); };
			img.src = icon;
			img.style.verticalAlign = "bottom";
			img.style.marginRight = "5px";
			img.style.flex = "none";
			div.appendChild(img);
		}
		var span = document.createElement("SPAN");
		span.style.flex = "1 1 auto";
		span.appendChild(document.createTextNode(text));
		div.appendChild(span);
		var img = document.createElement("IMG");
		img.src = theme.icons_10.arrow_right_black;
		img.style.flex = "none";
		div.appendChild(img);
		div.onclick = function(ev) {
			var sub_menu = new context_menu();
			provider(sub_menu, function() {
				sub_menu.element_clicked.addListener(function(elem) {
					if (elem._sub_menu) return;
					t.hide();
				});
				sub_menu.showAtRightOfElement(div);
			});
		};
		div.className = "context_menu_item";
		t.addItem(div, true);
	};
	/** Return the items contained in this menu
	 * @returns {Array} the list of html elements contained in the menu
	 */
	t.getItems = function() { return menu.childNodes; };
	/** Remove all items from this menu */
	t.clearItems = function() {
		while (menu.childNodes.length > 0)
			menu.childNodes[0].parentNode.removeChild(menu.childNodes[0]);
	};
	
	/** Display the menu below the given element
	 * @param {Element} from the element below which the menu will be displayed
	 * @param {Boolean} min_width_is_from if true, the context menu will have its minimum width set to the width of the from element
	 * @param {Function} ondisplayed function to be called when the menu has been displayed
	 */
	t.showBelowElement = function(from, min_width_is_from, ondisplayed) {
		window.top.require("position.js", function() {
			menu.style.visibility = "visible";
			menu.style.display = "";
			t.show_from = from;
			window.top.positionBelowElement(menu, from, min_width_is_from, function(x,y) { t._shownAt(x,y,from,ondisplayed); });
		});
	};
	/** Display the menu above the given element
	 * @param {Element} from the element above which the menu will be displayed
	 * @param {Boolean} min_width_is_from if true, the context menu will have its minimum width set to the width of the from element
	 * @param {Function} ondisplayed function to be called when the menu has been displayed
	 */
	t.showAboveElement = function(from, min_width_is_from, ondisplayed) {
		window.top.require("position.js", function() {
			menu.style.visibility = "visible";
			menu.style.display = "";
			t.show_from = from;
			window.top.positionAboveElement(menu, from, min_width_is_from, function(x,y) { t._shownAt(x,y,from,ondisplayed); });
		});
	};
	/** Display the menu at the right side of the given element
	 * @param {Element} from the element
	 */
	t.showAtRightOfElement = function(from) {
		window.top.require("position.js", function() {
			menu.style.visibility = "visible";
			menu.style.display = "";
			t.show_from = from;
			window.top.positionAtRightOfElement(menu, from, function(x,y) { t._shownAt(x,y,from); });
		});
	};
	/** Display the menu at the given position (using absolute positioning)
	 * @param {Number} x horizontal position
	 * @param {Number} y vertical position
	 */
	t.showAt = function(x,y) {
		menu.style.visibility = "visible";
		menu.style.display = "";
		menu.style.position = "fixed";
		menu.style.top = y+"px";
		menu.style.left = x+"px";
		t._showAt(x,y,null);
	};
	/** How the menu should appear, used for animation */
	t._appear_orientation = 'center';
	/** Internal function called when the position of the menu has been set
	 * @param {Number} x horizontal position
	 * @param {Number} y vertical position
	 * @param {Element} from from which element the menu has been displayed
	 * @param {Function} ondisplayed function to call when the menu has been displayed
	 */
	t._shownAt = function(x,y,from,ondisplayed) {
		var e = from;
		var from_inside_menu = false;
		while (e && e != from.ownerDocument.body) { if (e.className == 'context_menu') { from_inside_menu = true; break; } e = e.parentNode; }
		if (from_inside_menu) {
			t.parent_menu = e.context_menu;
			t.parent_menu_listener = t.parent_menu.hideIfOutsideMenu;
			t.parent_menu.hideIfOutsideMenu = function(){};
		}
		t.show_at = [x,y];
//		for (var i = 0; i < document.body.childNodes.length; ++i)
//			if (document.body.childNodes[i].style) document.body.childNodes[i].style.zIndex = -10;
		menu.style.zIndex = 100;
		if (typeof window.top.animation != 'undefined') {
			if (menu.anim) window.top.animation.stop(menu.anim);
		}
		if (typeof window.top.animation != 'undefined')
			menu.style.visibility = 'hidden';
		window.top.pnapplication.onwindowclosed.addListener(t._windowCloseListener);
		setTimeout(function() {
			//listenEvent(window,'click',t._listener);
			window.top.pnapplication.registerOnclick(window, t._listener);
		},1);
		t._appear_orientation = 'center';
		if (from) {
			var win = getWindowFromElement(from);
			var pos = win.getFixedPosition(from);
			if (y > pos.y) t._appear_orientation = 'top';
			else t._appear_orientation = 'bottom';
		}
		if (typeof window.top.animation != 'undefined') {
			menu.anim = window.top.animation.appear(menu,200,function(){menu.anim = null;if (ondisplayed) ondisplayed();},t._appear_orientation);
		} else if (ondisplayed)
			ondisplayed();
	};
	/** Keep track of the window in which the context menu is displayed */
	t._this_win = window;
	/**
	 * Function called when the window containing the menu is closed
	 * @param {Object} c close information from pnapplication
	 */
	t._windowCloseListener = function(c) {
		if (!t) return;
		if (c.win != t._this_win) return;
		c.top.pnapplication.onwindowclosed.removeListener(t._windowCloseListener);
		t._this_win = null;
		c.top.document.body.removeChild(menu);
	};
	/** Hide the menu: call <code>onclose</code> if specified, then hide or remove the html element of the menu depending on <code>removeOnClose</code> 
	 */
	t.hide = function() {
		if (!t) return;
		window.top.pnapplication.onwindowclosed.removeListener(t._windowCloseListener);
		if (t.onclose) t.onclose();
		if (t.parent_menu) {
			setTimeout(function(){
				t.parent_menu.hideIfOutsideMenu = t.parent_menu_listener;
			},1);
		}
		if (typeof window.top.animation != 'undefined') {
			if (menu.anim) window.top.animation.stop(menu.anim);
			menu.anim = window.top.animation.disappear(menu,300,function() {
				if (!t) return;
				if (t.removeOnClose)
					try { menu.parentNode.removeChild(menu); } catch (e) {}
			},t._appear_orientation);
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
	/** Listen to mouse events to check when the user is clicking outside
	 * @param {Event} ev the event
	 * @param {Window} win the window in which the event occured
	 * @param {Window} orig_win the window of the context menu
	 */
	t._listener = function(ev, win, orig_win) {
		if (!t) return;
		t.hideIfOutsideMenu(ev, win, orig_win);
	};
	/** hide the menu if the click is outside the menu
	 * @param {Event} ev the click event
	 * @param {Window} win the window in which the user clicked
	 * @param {Window} orig_win the window of the menu
	 */
	t.hideIfOutsideMenu = function(ev, win, orig_win) {
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
	
	/** Resize the menu based on its content */
	t.resize = function() {
		if (menu.parentNode != window.top.document.body) return;
		menu.style.top = "";
		menu.style.left = "";
		if (t.show_from)
			t.showBelowElement(t.show_from);
		else
			t.showAt(t.show_at[0], t.show_at[1]);
	};
	/** Close the menu. Alias of hide. */
	t.close = function() { if(t) t.hide(); };
	
	menu.ondomremoved(function() {
		menu.context_menu = null;
		t.show_from = null;
		t.parent_menu = null;
		menu.anim = null;
		t = null;
		menu = null;
	});
}
