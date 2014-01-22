if (typeof require != 'undefined') require("color.js");

function section_from_html(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	var icon = null;
	var title = "";
	var collapsable = false;
	var border_color = null;
	var title_background_from = null;
	var title_background_to = null;
	if (container.hasAttribute("icon")) {
		icon = container.getAttribute("icon");
		container.removeAttribute("icon");
	}
	if (container.hasAttribute("title")) {
		title = container.getAttribute("title");
		container.removeAttribute("title");
	}
	if (container.hasAttribute("border_color")) {
		border_color = container.getAttribute("border_color");
		container.removeAttribute("border_color");
	}
	if (container.hasAttribute("title_background_from")) {
		title_background_from = container.getAttribute("title_background_from");
		container.removeAttribute("title_background_from");
	}
	if (container.hasAttribute("title_background_to")) {
		title_background_to = container.getAttribute("title_background_to");
		container.removeAttribute("title_background_to");
	}
	if (container.hasAttribute("collapsable")) {
		collapsable = container.getAttribute("collapsable") == "true" ? true : false;
		container.removeAttribute("collapsable");
	}
	var content = document.createElement("DIV");
	while (container.childNodes.length > 0) content.appendChild(container.childNodes[0]);
	var s = new section(icon,title,content,collapsable,border_color,title_background_from,title_background_to);
	container.appendChild(s.element);
}

function section(icon, title, content, collapsable, border_color, title_background_from, title_background_to, title_style) {
	if (!border_color) border_color = "#000000";
	if (!title_background_from) title_background_from = "#FFFFFF";
	if (!title_background_to) title_background_to = "#A0A0C0";

	var t=this;
	this.element = document.createElement("DIV");
	
	this._init = function() {
		this.element.style.border = "1px solid "+border_color;
		setBorderRadius(this.element, 5, 5, 5, 5, 5, 5, 5, 5);
		this.title_container = document.createElement("DIV");
		this.element.appendChild(this.title_container);
		setBorderRadius(this.title_container, 5, 5, 5, 5, 0, 0, 0, 0);
		this.title_container.style.borderBottom = "1px solid "+border_color;
		require("color.js",function() {
			var col_from = parse_color(title_background_from);
			var col_to = parse_color(title_background_to);
			var intermediate_color = color_string(color_between(col_from, col_to, 20));
			setBackgroundGradient(t.title_container, "vertical", [{pos:0,color:title_background_from},{pos:30,color:intermediate_color},{pos:100,color:title_background_to}]);
		});
		this.title_container.style.height = "25px";
		if (icon) {
			this.icon = document.createElement("IMG");
			this.icon.src = icon;
			this.title_container.appendChild(this.icon);
		}
		this.title = document.createElement("DIV");
		this.title.innerHTML = title;
		this.title.setAttribute("layout", "fill");
		this.title.style.fontWeight = "bold";
		this.title.style.color = border_color;
		if (title_style)
			for (var att in title_style) this.title.style[att] = title_style[att];
		this.title_container.appendChild(this.title);
		this.toolbar = document.createElement("DIV");
		this.title_container.appendChild(this.toolbar);
		require("horizontal_layout.js",function(){
			new horizontal_layout(t.title_container);
		});
		require("vertical_align.js",function(){
			new vertical_align(t.title_container, "middle");
		});
		
		this.content_container = document.createElement("DIV");
		this.element.appendChild(this.content_container);
		this.content_container.appendChild(content);
	};
	this._init();
}