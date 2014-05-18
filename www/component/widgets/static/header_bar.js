if (typeof require != 'undefined') {
	require("horizontal_layout.js");
	require("horizontal_menu.js");
}

if (typeof theme != 'undefined')
	theme.css("header_bar.css");

function header_bar(container, style) {
	if (typeof container == 'string') container = document.getElementById(container);
	container.className = "header_bar"+(style ? "_"+style : "");
	var t=this;
	
	this.setTitle = function(icon, text) {
		this.title.removeAllChildren();
		if (icon) {
			var img = document.createElement("IMG");
			img.src = icon;
			img.onload = function() { layout.invalidate(container); };
			this.title.appendChild(img);
		}
		this.title.appendChild(document.createTextNode(text));
		layout.invalidate(container);
	};
	
	this.setTitleHTML = function(html) {
		if (typeof html == 'string')
			this.title.innerHTML = html;
		else {
			this.title.removeAllChildren();
			this.title.appendChild(html);
		}
		layout.invalidate(container);
	};
	
	this.addMenuItem = function(html) {
		if (!t.menu) { setTimeout(function(){t.addMenuItem(html);},10); return; }
		if (typeof html == 'string') {
			var d = document.createElement("DIV");
			d.style.display = 'inline-block';
			d.innerHTML = html;
			html = d;
		}
		t.menu.addItem(html);
	};
	this.addMenuButton = function(icon, text, onclick) {
		var button = document.createElement("BUTTON");
		button.className = "button_verysoft";
		if (icon) {
			var img = document.createElement("IMG");
			img.src = icon;
			img.style.marginRight = "3px";
			button.appendChild(img);
		}
		button.appendChild(document.createTextNode(text));
		button.onclick = onclick;
		this.addMenuItem(button);
	};
	this.resetMenu = function() {
		if (t.menu)
			t.menu.removeAll();
	};
	
	t._init = function() {
		// title section
		t.title = document.createElement("DIV");
		t.title.className = "header_bar_title";
		
		// menu section
		t.menu_container = document.createElement("DIV");
		
		// initialization from HTML, if any
		if (container.hasAttribute("title")) {
			var text = container.getAttribute("title");
			container.removeAttribute("title");
			var icon = null;
			if (container.hasAttribute("icon")) {
				icon = container.getAttribute("icon");
				container.removeAttribute("icon");
			}
			t.setTitle(icon, text);
		}
		while (container.childNodes.length > 0)
			t.menu_container.appendChild(container.removeChild(container.childNodes[0]));
		
		// layout
		container.appendChild(t.title);
		container.appendChild(t.menu_container);
		t.menu_container.setAttribute("layout", "fill");
		require("horizontal_layout.js", function() { new horizontal_layout(container); });
		require("horizontal_menu.js",function() { 
			t.more_menu = document.createElement("DIV");
			t.more_menu.className = "button";
			t.more_menu.innerHTML = "<img src='"+theme.icons_16.more_menu+"'/> More";
			t.menu_container.appendChild(t.more_menu);
			t.menu = new horizontal_menu(t.menu_container, "middle"); 
		});
	};
	
	t._init();
}