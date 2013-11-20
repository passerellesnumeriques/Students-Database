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
		this.menu_container.appendChild(html);
	};
	this.resetMenu = function() {
		while (this.menu_container.childNodes.length > 0)
			this.menu_container.removeChild(this.menu_container.childNodes[0]);
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
		icon.style.verticalAlign = "middle";
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
		require("vertical_align.js", function() {
			new vertical_align(t.menu_container, "middle");
		});
	};
	
	t._init();
}