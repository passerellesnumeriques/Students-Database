if (typeof require != 'undefined')
	require("animation.js");
if (typeof theme != 'undefined')
	theme.css("section.css");

function section_from_html(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	var icon = null;
	var title = "";
	var collapsable = false;
	var fill_height = false;
	var css = null;
	if (container.hasAttribute("icon")) {
		icon = container.getAttribute("icon");
		container.removeAttribute("icon");
	}
	if (container.hasAttribute("title")) {
		title = container.getAttribute("title");
		container.removeAttribute("title");
	}
	if (container.hasAttribute("collapsable")) {
		collapsable = container.getAttribute("collapsable") == "true" ? true : false;
		container.removeAttribute("collapsable");
	}
	if (container.hasAttribute("fill_height")) {
		fill_height = container.getAttribute("fill_height") == "true" ? true : false;
		container.removeAttribute("fill_height");
	}
	if (container.hasAttribute("css")) {
		css = container.getAttribute("css");
		container.removeAttribute("css");
	}
	var content = document.createElement("DIV");
	while (container.childNodes.length > 0) content.appendChild(container.childNodes[0]);
	var s = new section(icon,title,content,collapsable,fill_height,css);
	container.appendChild(s.element);
	layout.invalidate(container);
	return s;
}

function section(icon, title, content, collapsable, fill_height, css) {
	var t=this;
	this.element = document.createElement("DIV");
	this.element.className = "section"+(css ? " "+css : "");
	
	this.addTool = function(element) {
		if (typeof element == 'string') { var d = document.createElement("DIV"); d.innerHTML = element; element = d; }
		this.toolbar.appendChild(element);
		element.style.display = "inline-block";
		layout.invalidate(this.element);
	};
	this.addToolLeft = function(element) {
		if (typeof element == 'string') { var d = document.createElement("DIV"); d.innerHTML = element; element = d; }
		this.toolbar_left.appendChild(element);
		element.style.display = "inline-block";
		layout.invalidate(this.element);
	};
	this.resetToolLeft = function() {
		while (this.toolbar_left.childNodes.length > 0) this.toolbar_left.removeChild(this.toolbar_left.childNodes[0]);
		layout.invalidate(this.element);
	};
	this.addToolRight = function(element) {
		if (typeof element == 'string') { var d = document.createElement("DIV"); d.innerHTML = element; element = d; }
		this.toolbar_right.appendChild(element);
		element.style.display = "inline-block";
		layout.invalidate(this.element);
	};
	this.resetToolRight = function() {
		while (this.toolbar_right.childNodes.length > 0) this.toolbar_right.removeChild(this.toolbar_right.childNodes[0]);
		layout.invalidate(this.element);
	};
	this.addToolBottom = function(element) {
		if (typeof element == 'string') {
			var div = document.createElement("DIV");
			div.style.display = "inline-block";
			div.innerHTML = element;
			element = div;
		}
		this.footer.appendChild(element);
		this.footer.className = "section_footer";
		layout.invalidate(this.element);
	};
	this.resetToolBottom = function() {
		this.footer.className = "section_footer_empty";
		while (this.footer.childNodes.length > 0) this.footer.removeChild(this.footer.childNodes[0]);
		layout.invalidate(this.element);
	};
	
	this._init = function() {
		this.header = document.createElement("DIV");
		this.header.className = "section_header";
		this.element.appendChild(this.header);

		this.title_container = document.createElement("DIV");
		this.title_container.setAttribute("layout", "fill");
		this.header.appendChild(this.title_container);
		if (icon) {
			this.icon = document.createElement("IMG");
			this.icon.src = icon;
			this.icon.onload = function() { layout.invalidate(t.element); };
			this.title_container.appendChild(this.icon);
		}
		this.title = document.createElement("DIV");
		this.title.innerHTML = title;
		this.title.style.display = "inline-block";
		this.title_container.appendChild(this.title);
		this.toolbar_left = document.createElement("DIV");
		this.header.appendChild(this.toolbar_left);
		this.toolbar = document.createElement("DIV");
		this.header.appendChild(this.toolbar);
		this.toolbar_right = document.createElement("DIV");
		this.header.appendChild(this.toolbar_right);
		if (collapsable) {
			this.collapse_container = document.createElement("DIV");
			this.collapse_container.style.padding = "4px";
			this.collapse_button = document.createElement("IMG");
			this.collapse_button.src = get_script_path("section.js")+"collapse.png";
			this.collapse_button.onload = function() { layout.invalidate(t.element); };
			this.collapse_button.style.cursor = 'pointer';
			this.collapsed = false;
			this.collapse_button.onclick = function() { t.toggleCollapseExpand(); }; 
			this.collapse_container.appendChild(this.collapse_button);
			this.header.appendChild(this.collapse_container);
		}
		require("horizontal_layout.js",function(){
			new horizontal_layout(t.header);
		});
		require("vertical_align.js",function(){
			new vertical_align(t.title_container, "middle");
		});
		
		this.content_container = document.createElement("DIV");
		this.content_container.style.backgroundColor = "#ffffff";
		this.content_container.appendChild(content);
		this.element.appendChild(this.content_container);
		this.footer = document.createElement("DIV");
		this.footer.className = "section_footer_empty";
		this.element.appendChild(this.footer);
		layout.addHandler(this.element, function() {
			t.header.style.display = "inline-block";
			t.content_container.style.display = "inline-block";
			t.footer.style.display = "inline-block";
			t.header.style.display = "";
			t.content_container.style.display = "";
			t.footer.style.display = "";
			if (fill_height)
				t.content_container.style.height = (t.element.clientHeight-getHeight(t.header)-getHeight(t.footer))+"px";
		});
	};
	
	this.toggleCollapseExpand = function() {
		if (this.collapsed) {
			this.collapse_button.src = get_script_path("section.js")+"collapse.png";
			this.collapsed = false;
			require("animation.js",function() {
				if (t.content_container.anim1) animation.stop(t.content_container.anim1);
				if (t.content_container.anim2) animation.stop(t.content_container.anim2);
				t.content_container.anim1 = animation.create(t.content_container, 0, t.content_container.originalHeight, 500, function(value, element) {
					element.style.height = Math.floor(value)+'px';
					element.style.overflow = "hidden";
					if (value == t.content_container.originalHeight) layout.invalidate(t.element.parentNode);
				});
				t.content_container.anim2 = animation.fadeIn(t.content_container, 600, function() {
					t.content_container.style.position = 'static';
					t.content_container.style.visibility = 'visible';
					t.content_container.style.height = "";
					t.content_container.style.overflow = "";
				});
			});
			this.content_container.style.position = 'static';
			this.content_container.style.visibility = 'visible';
			this.header.className = "section_header";
			this.footer.style.position = 'static';
			this.footer.style.visibility = 'visible';
		} else {
			this.collapse_button.src = get_script_path("section.js")+"expand.png";
			this.collapsed = true;
			require("animation.js",function() {
				if (t.content_container.anim1) animation.stop(t.content_container.anim1);
				if (t.content_container.anim2) animation.stop(t.content_container.anim2);
				var start = t.content_container.offsetHeight;
				t.content_container.originalHeight = start;
				t.content_container.anim1 = animation.create(t.content_container, start, 0, 600, function(value, element) {
					element.style.height = Math.floor(value)+'px';
					element.style.overflow = "hidden";
					if (value == 0) layout.invalidate(t.element.parentNode);
				});
				t.content_container.anim2 = animation.fadeOut(t.content_container, 500, function() {
					t.content_container.style.position = 'absolute';
					t.content_container.style.visibility = 'hidden';
					t.content_container.style.top = '-10000px';
					t.content_container.style.left = '-10000px';
				});
			});
			t.footer.style.position = 'absolute';
			t.footer.style.visibility = 'hidden';
			t.footer.style.top = '-10000px';
			t.footer.style.left = '-10000px';
			this.header.className = "section_header collapsed";
		}
	};
	
	this._init();
}