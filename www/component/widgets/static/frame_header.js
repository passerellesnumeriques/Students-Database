if (typeof require != 'undefined') {
	require("animation.js");
}
if (typeof theme != 'undefined')
	theme.css("frame_header.css");

/**
 * Used by frame_header to store information about an item of the menu
 * @param {String} id identifier
 * @param {String|null} icon URL of the icon
 * @param {String} text text
 * @param {String} link URL of the page
 * @param {String} tooltip_content HTML code to display in a tooltip when the mouse is over the item
 * @param {String} start_url helps to detect on which menu item the current page belongs to
 * @param {Boolean} button_type if true, the item looks like a button
 */
function frame_header_menu_item(id, icon, text, link, tooltip_content, start_url, button_type) {
	this.id = id;
	this.link = document.createElement("A");
	this.link.className = "menu_item"+(button_type ? " menu_button" : "");
	if (icon) {
		this.icon = document.createElement("IMG");
		this.icon.src = icon;
		this.link.appendChild(this.icon);
	}
	this.text = document.createTextNode(text);
	this.link.appendChild(this.text);
	this.link.href = link;
	this.original_url = link;
	this.start_url = start_url;
	this.button_type = button_type;
	tooltip(this.link, tooltip_content);
}

/**
 * A Frame header is a header bar on top of an IFRAME, the header bar containing a menu to navigate among pages in the IFRAME.
 * Optionnaly, a footer can be also displayed below the IFRAME.
 * @param {Element} container where to put it
 * @param {String} frame_name name to give to the IFRAME
 * @param {Number} header_height height of the header bar
 * @param {String} css class name
 * @param {String} menu_valign how to align menu items (top,bottom,middle)
 */
function frame_header(container, frame_name, header_height, css, menu_valign) {
	if (typeof container == 'string') container = document.getElementById(container);
	if (!css) css = "white";
	if (!menu_valign) menu_valign = "bottom";
	container.style.width = "100%";
	container.style.height = "100%";
	var t=this;
	t.container = container;
	
	/** Set an icon and a title in the header
	 * @param {String|null} icon URL of the icon
	 * @param {String} title text
	 */
	t.setTitle = function(icon, title) {
		if (!icon && !title) {
			if (t.header_title) {
				t.header.removeChild(t.header_title);
				t.header_title = null;
				layout.changed(t.header);
			}
		} else {
			if (!t.header_title) {
				t.header_title = document.createElement("DIV");
				t.header_title.className = "title";
				t.header_title.style.flex = "none";
				t.header.insertBefore(t.header_title, t.header.childNodes[0]);
			}
			t.header_title.removeAllChildren();
			if (icon) {
				var img = document.createElement("IMG");
				img.src = icon;
				img.onload = function() { layout.changed(t.header_title); };
				t.header_title.appendChild(img);
			}
			if (typeof title == 'string') {
				var div = document.createElement("DIV");
				div.style.display = "inline-block";
				div.innerHTML = title;
				title = div;
			}
			t.header_title.appendChild(title);
			layout.changed(t.header);
		}
	};
	/** Add a footer section below the IFRAME */
	t.addFooter = function() {
		if (t.footer) return t.footer;
		t.footer = document.createElement("DIV");
		t.footer.className = "frame_footer "+css;
		container.appendChild(t.footer);
		layout.changed(container);
		return t.footer;
	};

	/** List of items */
	t._menu_items = [];
	/** Add an item in the menu
	 * @param {String} id identifier
	 * @param {String|null} icon URL of the icon
	 * @param {String} text text
	 * @param {String} link URL of the page
	 * @param {String} tooltip HTML code to display in a tooltip when the mouse is over the item
	 * @param {String} start_url helps to detect on which menu item the current page belongs to
	 * @param {Boolean} button_type if true, the item looks like a button
	 */
	t.addMenu = function(id, icon, text, link, tooltip, start_url, button_type) {
		var item = new frame_header_menu_item(id, icon, text, link, tooltip, start_url, button_type);
		item.link.target = t.frame.name;
		t._menu_items.push(item);
		if (t.header_menu.widget)
			t.header_menu.widget.addItem(item.link);
		else {
			// horizontal_menu not yet loaded
			t.header_menu.appendChild(item.link);
		}
		return item;
	};
	/** Get the list of items
	 * @returns {Array} list of frame_header_menu_item
	 */
	t.getMenuItems = function() { return t._menu_items; };
	/** Add a custom HTML element in the menu
	 * @param {Element|String} control the element, or the HTML code, to add in the menu
	 * @param {String|null} tooltip_content HTML code to display in a tooltip when the mouse is over the control
	 */
	t.addMenuControl = function(control, tooltip_content) {
		if (typeof control == 'string') {
			var div = document.createElement("DIV");
			div.innerHTML = control;
			control = div;
		}
		control.style.display = "inline-block";
		if (tooltip_content) tooltip(control, tooltip_content);
		if (t.header_menu.widget)
			t.header_menu.widget.addItem(control);
		else {
			// horizontal_menu not yet loaded
			t.header_menu.appendChild(control);
		}
	};
	/**
	 * Add a custom HTML element on the left of the menu
	 * @param {Element|String} control the element, or the HTML code, to add on the left
	 * @param {String|null} tooltip_content HTML code to display in a tooltip when the mouse is over the control
	 */
	t.addLeftControl = function(control, tooltip_content) {
		if (typeof control == 'string') {
			var div = document.createElement("DIV");
			div.innerHTML = control;
			div.style.verticalAlign = "top";
			control = div;
		}
		control.style.display = "inline-block";
		if (tooltip_content) tooltip(control, tooltip_content);
		if (!t.header_left) {
			t.header_left = document.createElement("DIV");
			t.header_left.style.flex = "none";
			t.header_left.className = "left_controls";
			t.header.insertBefore(t.header_left, t.header_menu);
			t.header_left.style.display = "flex";
			t.header_left.style.flexDirection = "row";
			switch (menu_valign) {
			default:
			case "bottom": t.header_left.style.justifyContent = "flex-end"; break;
			case "top": t.header_left.style.justifyContent = "flex-start"; break;
			case "middle": t.header_left.style.justifyContent = "center"; break;
			}
		}
		t.header_left.appendChild(control);
		layout.changed(t.header);
	};
	/**
	 * Add a custom HTML element on the right of the menu
	 * @param {Element|String} control the element, or the HTML code, to add on the right
	 * @param {String|null} tooltip_content HTML code to display in a tooltip when the mouse is over the control
	 */
	t.addRightControl = function(control, tooltip_content) {
		if (typeof control == 'string') {
			var div = document.createElement("DIV");
			div.innerHTML = control;
			div.style.verticalAlign = "top";
			control = div;
		}
		control.style.display = "inline-block";
		if (tooltip_content) tooltip(control, tooltip_content);
		if (!t.header_right) {
			t.header_right = document.createElement("DIV");
			t.header_right.style.flex = "none";
			t.header_right.className = "right_controls";
			t.header.appendChild(t.header_right);
			t.header_right.appendChild(control);
		} else
			t.header_right.insertBefore(control, t.header_right.childNodes[0]);
		layout.changed(t.header);
	};
	/** Remove content of the menu */
	t.resetMenu = function() {
		if (t.header_menu.widget)
			t.header_menu.widget.removeAll();
		else {
			// horizontal_menu not yet loaded
			t.header_menu.removeAllChildren();
		}
	};
	/** Remove controls on the left of the menu */
	t.resetLeftControls = function() {
		if (!t.header_left) return;
		if (t.header_left._valign) t.header_left._valign.remove();
		t.header.removeChild(t.header_left);
		t.header_left = null;
		layout.changed(t.header);
	};
	/** Remove controls on the right of the menu */
	t.resetRightControls = function() {
		if (!t.header_right) return;
		t.header.removeChild(t.header_right);
		t.header_right = null;
		layout.changed(t.header);
	};
	/** Remove content of the menu, as well as controls on its left and on its right */
	t.resetHeader = function() {
		t.resetLeftControls();
		t.resetRightControls();
		t.resetMenu();
	};
	
	/** Creation of the screen */
	t._init = function() {
		container.style.display = "flex";
		container.style.flexDirection = "column";
		// header
		t.header = document.createElement("DIV");
		t.header.className = "frame_header "+css;
		t.header.style.flex = "none";
		t.header.style.display = "flex";
		t.header.style.flexDirection = "row";
		if (header_height) t.header.style.height = header_height+"px";
		t.header.appendChild(t.header_menu = document.createElement("DIV"));
		t.header_menu.style.flex = "1 1 auto";

		// set title if specified
		if (container.hasAttribute("title")) {
			var title = container.getAttribute("title");
			container.removeAttribute("title");
			var icon = null;
			if (container.hasAttribute("icon")) {
				icon = container.getAttribute("icon");
				container.removeAttribute("icon");
			}
			t.setTitle(icon, title);
		}
		
		// frame
		t.frame = document.createElement("IFRAME");
		t.frame.style.flex = "1 1 auto";
		t.frame.onload = function() { t.frameLoaded(); };
		if (!frame_name) frame_name = container.id+"_content"; 
		t.frame.name = frame_name;
		t.frame.id = frame_name;
		t.frame.frameBorder = 0;
		t.frame.style.width = "100%";
		if (container.hasAttribute("page")) {
			t.frame.src = container.getAttribute("page");
			container.removeAttribute("page");
		}
		
		// populate menu if elements in HTML
		while (container.childNodes.length > 0) {
			if (container.childNodes[0].nodeType != 1)
				container.removeChild(container.childNodes[0]);
			else if (container.childNodes[0].hasAttribute("text") && container.childNodes[0].hasAttribute("link")) {
				var id = container.childNodes[0].id;
				var icon = container.childNodes[0].hasAttribute("icon") ? container.childNodes[0].getAttribute("icon") : null;
				var text = container.childNodes[0].getAttribute("text");
				var link = container.childNodes[0].getAttribute("link");
				var tooltip = container.childNodes[0].hasAttribute("tooltip") ? container.childNodes[0].getAttribute("tooltip") : null;
				var start_url = container.childNodes[0].hasAttribute("start_url") ? container.childNodes[0].getAttribute("start_url") : null;
				t.addMenu(id, icon, text, link, tooltip, start_url);
				container.removeChild(container.childNodes[0]);
			} else
				t.header_menu.appendChild(container.removeChild(container.childNodes[0]));
		}
		
		// set layout
		container.appendChild(t.header);
		container.appendChild(t.frame);
		require("horizontal_menu.js",function(){
			var div = document.createElement("BUTTON");
			div.className = "flat";
			div.innerHTML = "<img src='"+theme.icons_16.more_menu+"'/> More";
			t.header_menu.appendChild(div);
			new horizontal_menu(t.header_menu, menu_valign);
		});
	};
	/** Called when the frame is unloaded */
	t.frameUnloaded = function() {
	};
	/** Called when a page is loaded in the frame */
	t.frameLoaded = function() {
		listenEvent(getIFrameWindow(t.frame),'unload',function() { if(t) t.frameUnloaded(); });
		var url = new URL(getIFrameWindow(t.frame).location.href);
		for (var i = 0; i < t._menu_items.length; ++i) {
			var item = t._menu_items[i];
			var start = item.start_url ? new URL(item.start_url) : null;
			if (start && url.path.startsWith(start.path))
				item.link.className = "menu_item"+(item.button_type ? " menu_button" : "")+" selected";
			else {
				var u = new URL(item.link.href);
				if (u.path == url.path)
					item.link.className = "menu_item"+(item.button_type ? " menu_button" : "")+" selected";
				else
					item.link.className = "menu_item"+(item.button_type ? " menu_button" : "");
			}
		}
	};	
	
	t._init();
	
	container.ondomremoved(function() {
		container = null;
		t.container = null;
		t.header_title = null;
		t.header = null;
		t.header_left = null;
		t.header_right = null;
		t.header_menu = null;
		t.footer = null;
		t.frame = null;
		t._menu_items = null;
		t = null;
	});
}