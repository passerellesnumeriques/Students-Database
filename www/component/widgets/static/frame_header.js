if (typeof require != 'undefined') {
	require("vertical_layout.js");
	require("horizontal_layout.js");
	require("animation.js");
}
if (typeof theme != 'undefined')
	theme.css("frame_header.css");

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
	this.start_url = start_url;
	this.button_type = button_type;
	tooltip(this.link, tooltip_content);
}

function frame_header(container, frame_name, header_height, css, menu_valign) {
	if (typeof container == 'string') container = document.getElementById(container);
	if (!css) css = "white";
	if (!menu_valign) menu_valign = "bottom";
	container.style.width = "100%";
	container.style.height = "100%";
	var t=this;
	t.container = container;
	
	t.setTitle = function(icon, title) {
		if (!icon && !title) {
			if (t.header_title) {
				t.header.removeChild(t.header_title);
				t.header_title = null;
				layout.invalidate(t.header);
			}
		} else {
			if (!t.header_title) {
				t.header_title = document.createElement("DIV");
				t.header_title.className = "title";
				t.header.insertBefore(t.header_title, t.header.childNodes[0]);
			}
			t.header_title.innerHTML = "";
			if (icon) {
				var img = document.createElement("IMG");
				img.src = icon;
				img.onload = function() { layout.invalidate(t.header_title); };
				t.header_title.appendChild(img);
			}
			if (typeof title == 'string')
				t.header_title.appendChild(document.createTextNode(title));
			else
				t.header_title.appendChild(title);
			layout.invalidate(t.header);
		}
	};
	t.addFooter = function() {
		if (t.footer) return t.footer;
		t.footer = document.createElement("DIV");
		t.footer.className = "frame_footer "+css;
		container.appendChild(t.footer);
		layout.invalidate(container);
		return t.footer;
	};

	t._menu_items = [];
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
	t.getMenuItems = function() { return t._menu_items; };
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
	
	t.addLeftControl = function(control, tooltip_content) {
		if (typeof control == 'string') {
			var div = document.createElement("DIV");
			div.innerHTML = control;
			control = div;
		}
		control.style.display = "inline-block";
		if (tooltip_content) tooltip(control, tooltip_content);
		if (!t.header_left) {
			t.header_left = document.createElement("DIV");
			t.header_left.className = "left_controls";
			t.header.insertBefore(t.header_left, t.header_menu);
			require("vertical_align.js",function() {
				if (t.header_left.parentNode)
					t.header_left._valign = new vertical_align(t.header_left, menu_valign);
			});
		}
		t.header_left.appendChild(control);
		layout.invalidate(t.header);
	};
	t.addRightControl = function(control, tooltip_content) {
		if (typeof control == 'string') {
			var div = document.createElement("DIV");
			div.innerHTML = control;
			control = div;
		}
		control.style.display = "inline-block";
		if (tooltip_content) tooltip(control, tooltip_content);
		if (!t.header_right) {
			t.header_right = document.createElement("DIV");
			t.header_right.className = "right_controls";
			t.header.appendChild(t.header_right);
			t.header_right.appendChild(control);
		} else
			t.header_right.insertBefore(control, t.header_right.childNodes[0]);
		layout.invalidate(t.header);
	};
	
	t.resetMenu = function() {
		if (t.header_menu.widget)
			t.header_menu.widget.removeAll();
		else {
			// horizontal_menu not yet loaded
			t.header_menu.innerHTML = "";
		}
	};
	t.resetLeftControls = function() {
		if (!t.header_left) return;
		if (t.header_left._valign) t.header_left._valign.remove();
		t.header.removeChild(t.header_left);
		t.header_left = null;
		layout.invalidate(t.header);
	};
	t.resetRightControls = function() {
		if (!t.header_right) return;
		t.header.removeChild(t.header_right);
		t.header_right = null;
		layout.invalidate(t.header);
	};
	t.resetHeader = function() {
		t.resetLeftControls();
		t.resetRightControls();
		t.resetMenu();
	};
	
	t._init = function() {
		// header
		t.header = document.createElement("DIV");
		t.header.className = "frame_header "+css;
		if (header_height) t.header.setAttribute("layout", header_height);
		t.header.appendChild(t.header_menu = document.createElement("DIV"));

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
		t.frame.onload = function() { t.frame_load(); };
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
		t.frame.setAttribute("layout", "fill");
		require("vertical_layout.js",function(){
			new vertical_layout(container);
		});
		t.header_menu.setAttribute("layout", "fill");
		require("horizontal_layout.js",function(){
			new horizontal_layout(t.header);
		});
		require("horizontal_menu.js",function(){
			var div = document.createElement("DIV");
			div.className = "button";
			div.innerHTML = "<img src='"+theme.icons_16.more_menu+"'/> More";
			t.header_menu.appendChild(div);
			new horizontal_menu(t.header_menu, menu_valign);
		});
	};
	t.frame_unload = function() {
	};
	t.frame_load = function() {
		listenEvent(getIFrameWindow(t.frame),'unload',function() { t.frame_unload(); });
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
}