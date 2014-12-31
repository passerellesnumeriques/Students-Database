if (typeof require != 'undefined') {
	require("horizontal_menu.js");
}

if (typeof theme != 'undefined')
	theme.css("header_bar.css");

/**
 * Header bar with a title, and some controls
 * @param {Element} container where to put it
 * @param {String} style CSS class
 */
function header_bar(container, style) {
	if (typeof container == 'string') container = document.getElementById(container);
	container.className = "header_bar"+(style ? "_"+style : "");
	var t=this;
	
	/**
	 * Set a title
	 * @param {String|null} icon URL of the icon
	 * @param {String} text text
	 */
	this.setTitle = function(icon, text) {
		this.title.removeAllChildren();
		if (icon) {
			var img = document.createElement("IMG");
			img.src = icon;
			img.onload = function() { if (layout) layout.changed(container); };
			this.title.appendChild(img);
		}
		this.title.appendChild(document.createTextNode(text));
		layout.changed(container);
	};
	
	/**
	 * Set a custom title
	 * @param {String|Element} html HTML to use as title
	 */
	this.setTitleHTML = function(html) {
		if (typeof html == 'string')
			this.title.innerHTML = html;
		else {
			this.title.removeAllChildren();
			this.title.appendChild(html);
		}
		layout.changed(container);
	};
	
	/**
	 * Add something in the header
	 * @param {Element|String} html HTML to put in the header
	 */
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
	/**
	 * Add a button in the menu
	 * @param {String|null} icon URL of the icon
	 * @param {String} text text of the button
	 * @param {Function} onclick called when the button is clicked
	 */
	this.addMenuButton = function(icon, text, onclick) {
		var button = document.createElement("BUTTON");
		button.className = "flat";
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
	/** Remove everything in the menu */
	this.resetMenu = function() {
		if (t.menu)
			t.menu.removeAll();
	};
	
	/** Creation of the screen */
	t._init = function() {
		container.style.display = "flex";
		container.style.flexDirection = "row";
		// title section
		t.title = document.createElement("DIV");
		t.title.style.flex = "none";
		t.title.className = "header_bar_title";
		
		// menu section
		t.menu_container = document.createElement("DIV");
		t.menu_container.style.flex = "1 1 auto";
		
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
		require("horizontal_menu.js",function() { 
			t.more_menu = document.createElement("BUTTON");
			t.more_menu.className = "flat";
			t.more_menu.innerHTML = "<img src='"+theme.icons_16.more_menu+"'/> More";
			t.menu_container.appendChild(t.more_menu);
			t.menu = new horizontal_menu(t.menu_container, "middle"); 
		});
	};
	
	t._init();
	
	container.ondomremoved(function() {
		t.title = null;
		t.menu = null;
		t.menu_container = null;
		t.more_menu = null;
	});
}