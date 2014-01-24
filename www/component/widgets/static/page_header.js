if (typeof require != 'undefined') {
	require("vertical_layout.js");
	require("horizontal_layout.js");
	require("animation.js");
}
function page_header(container, small) {
	if (typeof container == 'string') container = document.getElementById(container);
	container.style.width = "100%";
	container.className = "page_header"+(small ? "_small" : "");
	var t=this;
	
	this.addMenuItem = function(html) {
		if (typeof html == 'string') {
			var d = document.createElement("DIV");
			d.style.display = 'inline-block';
			d.innerHTML = html;
			html = d;
		}
		if (t.more_menu && t.more_menu.parentNode == this.menu_container)
			this.menu_container.insertBefore(html, t.more_menu);
		else
			this.menu_container.appendChild(html);
	};
	this.resetMenu = function() {
		var to_remove = [];
		for (var i = 0; i < this.menu_container.childNodes.length; ++i)
			if (this.menu_container.childNodes[i] != t.more_menu)
				to_remove.push(this.menu_container.childNodes[i]);
		for (var i = 0; i < to_remove.length; ++i)
			this.menu_container.removeChild(to_remove[i]);
	};
	
	this.setTitle = function(html) {
		if (typeof html == 'string')
			this.header_title.innerHTML = html;
		else {
			while (this.header_title.childNodes.length > 0) this.header_title.removeChild(this.header_title.childNodes[0]);
			this.header_title.appendChild(html);
		}
		fireLayoutEventFor(container);
	};
	
	t._init = function() {
		// menu
		t.menu_container = document.createElement("DIV");
		while (container.childNodes.length > 0)
			t.menu_container.appendChild(container.removeChild(container.childNodes[0]));
		
		// header
		container.appendChild(t.header_title = document.createElement("DIV"));
		t.header_title.className = "page_header_title";
		var icon = document.createElement('IMG');
		icon.src = container.getAttribute("icon");
		icon.onload = function() { fireLayoutEventFor(container); };
		t.header_title.appendChild(icon);
		var title = document.createElement("SPAN");
		title.innerHTML = container.getAttribute("title");
		t.header_title.appendChild(title);
		container.removeAttribute("icon");
		container.removeAttribute("title");
		
		container.appendChild(t.menu_container);
		container.style.whiteSpace = "nowrap";
		container.style.overflow = "hidden";
		
		// set layout
		t.header_title.setAttribute("layout", "fixed");
		t.menu_container.setAttribute("layout", "fill");
		require("horizontal_layout.js",function(){
			new horizontal_layout(container);
		});
		require("horizontal_menu.js",function(){
			t.more_menu = document.createElement("DIV");
			t.more_menu.className = "button";
			t.more_menu.innerHTML = "<img src='"+theme.icons_16.more_menu+"'/> More";
			t.menu_container.appendChild(t.more_menu);
			new horizontal_menu(t.menu_container);
		});
	};
	
	t._init();
}