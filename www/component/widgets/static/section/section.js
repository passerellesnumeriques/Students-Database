if (typeof require != 'undefined')
	require("animation.js");
if (typeof theme != 'undefined')
	theme.css("section.css");

/** Create a section widget, from a HTML div using its attributes
 * @param {Element} container the div which will be used for the section
 * @returns {section} the section
 */
function sectionFromHTML(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	var icon = null;
	var title = "";
	var collapsable = false;
	var fill_height = false;
	var css = null;
	var collapsed = false;
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
	if (container.hasAttribute("collapsed")) {
		collapsed = container.getAttribute("collapsed") == "true" ? true : false;
		container.removeAttribute("collapsed");
	}
	if (container.hasAttribute("fill_height")) {
		fill_height = container.getAttribute("fill_height") == "true" ? true : false;
		container.removeAttribute("fill_height");
	}
	if (container.hasAttribute("css")) {
		css = container.getAttribute("css");
		container.removeAttribute("css");
	}
	var content = -1;
	for (var i = 0; i < container.childNodes.length; ++i) {
		var child = container.childNodes[i];
		if (child.nodeType != 1) continue;
		if (content == -1) content = i;
		else content = -2;
	}
	if (content >= 0)
		content = container.childNodes[content];
	else {
		content = document.createElement("DIV");
		while (container.childNodes.length > 0) content.appendChild(container.childNodes[0]);
	}
	var s = new section(icon,title,content,collapsable,fill_height,css,collapsed);
	if (container.hasAttribute("style"))
		s.element.style.cssText = s.element.style.cssText+";"+container.getAttribute("style");
	if (container.id) s.element.id = container.id;
	var parent = container.parentNode;
	parent.insertBefore(s.element, container);
	parent.removeChild(container);
	layout.changed(parent);
	return s;
}

/** Create a new section, but do not put it in the page (created in element attribute)
 * @param {String} icon URL of the icon or null
 * @param {String} title title of the section
 * @param {Element} content html element of the content
 * @param {Boolean} collapsable indicates if the section can be collapsed or not
 * @param {Boolean} fill_height if true, the content will fill the height of the section, meaning the container MUST have a fixed height
 * @param {String} css style or null for the default one
 * @param {Boolean} collapsed if collapsable is true, it indicates if the section will be collapsed at the beginning or not
 */
function section(icon, title, content, collapsable, fill_height, css, collapsed) {
	var t=this;
	/** HTML Element of the section, which you can put where you want in the page */
	this.element = document.createElement("DIV");
	this.element.className = "section"+(css ? " "+css : "");
	this.content = content;
	
	/** Add an element in the title bar
	 * @param {Element|String} element the HTML element to add
	 */
	this.addTool = function(element) {
		if (typeof element == 'string') { var d = document.createElement("DIV"); d.innerHTML = element; element = d; }
		this.toolbar.appendChild(element);
		element.style.flex = "none";
		layout.changed(this.element);
	};
	/** Add an element on the left of the title bar
	 * @param {Element|String} element the HTML element to add
	 */
	this.addToolLeft = function(element) {
		if (typeof element == 'string') { var d = document.createElement("DIV"); d.innerHTML = element; element = d; }
		this.toolbar_left.appendChild(element);
		element.style.flex = "none";
		layout.changed(this.element);
	};
	/** Remove all elements on the left of the title bar (previously added using addToolLeft */
	this.resetToolLeft = function() {
		this.toolbar_left.removeAllChildren();
		layout.changed(this.element);
	};
	/** Add an element on the right of the title bar
	 * @param {Element|String} element the HTML element to add
	 */
	this.addToolRight = function(element) {
		if (typeof element == 'string') { var d = document.createElement("DIV"); d.innerHTML = element; element = d; }
		this.toolbar_right.appendChild(element);
		element.style.flex = "none";
		layout.changed(this.element);
	};
	/** Remove all elements on the right of the title bar (previously added using addToolRight */
	this.resetToolRight = function() {
		this.toolbar_right.removeAllChildren();
		layout.changed(this.element);
	};
	/** Add an element in the footer. The footer will become visible when the first element will be added.
	 * @param {Element|String} element the HTML element to add
	 */
	this.addToolBottom = function(element) {
		if (typeof element == 'string') {
			var div = document.createElement("DIV");
			div.style.display = "inline-block";
			div.innerHTML = element;
			element = div;
		}
		this.footer.appendChild(element);
		this.footer.className = "footer";
		layout.changed(this.element);
	};
	this.addButton = function(icon, text, css, onclick) {
		var button = document.createElement("BUTTON");
		button.className = css;
		if (icon) {
			var img = document.createElement("IMG");
			img.src = icon;
			button.appendChild(img);
		}
		button.appendChild(document.createTextNode(text));
		button.onclick = onclick;
		this.addToolBottom(button);
		return button;
	};
	/** Remove all elements on the footer, and hide the footer */
	this.resetToolBottom = function() {
		this.footer.className = "footer_empty";
		this.footer.removeAllChildren();
		layout.changed(this.element);
	};
	
	/** Creates the section */
	this._init = function() {
		this.header = document.createElement("DIV");
		this.header.className = "header";
		this.element.appendChild(this.header);

		this.title_container = document.createElement("DIV");
		this.header.appendChild(this.title_container);
		if (icon) {
			this.icon = document.createElement("IMG");
			this.icon.src = icon;
			this.icon.onload = function() { layout.changed(t.element); };
			this.title_container.appendChild(this.icon);
		}
		this.title = document.createElement("DIV");
		if (typeof title == 'string')
			this.title.innerHTML = title;
		else
			this.title.appendChild(title);
		this.title.style.display = "inline-block";
		this.title_container.appendChild(this.title);
		this.toolbar_left = document.createElement("DIV");
		this.toolbar_left.style.flex = "none";
		this.toolbar_left.style.display = "flex";
		this.toolbar_left.style.flexDirection = "row";
		this.toolbar_left.style.justifyContent = "center";
		this.toolbar_left.style.alignItems = "center";
		this.header.appendChild(this.toolbar_left);
		this.toolbar = document.createElement("DIV");
		this.toolbar.style.flex = "none";
		this.toolbar.style.display = "flex";
		this.toolbar.style.flexDirection = "row";
		this.toolbar.style.justifyContent = "center";
		this.toolbar.style.alignItems = "center";
		this.header.appendChild(this.toolbar);
		this.toolbar_right = document.createElement("DIV");
		this.toolbar_right.style.flex = "none";
		this.toolbar_right.style.display = "flex";
		this.toolbar_right.style.flexDirection = "row";
		this.toolbar_right.style.justifyContent = "center";
		this.toolbar_right.style.alignItems = "center";
		this.header.appendChild(this.toolbar_right);
		
		this.content_container = document.createElement("DIV");
		this.content_container.style.backgroundColor = "#ffffff";
		this.content_container.appendChild(content);
		this.element.appendChild(this.content_container);
		this.footer = document.createElement("DIV");
		this.footer.className = "footer_empty";
		this.element.appendChild(this.footer);

		if (collapsable) {
			this.collapsed = collapsed ? true : false;
			this.collapse_container = document.createElement("DIV");
			this.collapse_container.style.padding = "4px";
			this.collapse_button = document.createElement("IMG");
			this.collapse_button.src = get_script_path("section.js")+(collapsed?"expand.png":"collapse.png");
			this.collapse_button.onload = function() { if (layout) layout.changed(t.element); };
			this.collapse_button.style.cursor = 'pointer';
			this.collapse_button.onclick = function() { t.toggleCollapseExpand(); }; 
			this.collapse_container.appendChild(this.collapse_button);
			this.header.appendChild(this.collapse_container);
			if (collapsed) {
				t.content_container.style.position = 'absolute';
				t.content_container.style.visibility = 'hidden';
				t.content_container.style.top = '-10000px';
				t.content_container.style.left = '-10000px';
				t.footer.style.position = 'absolute';
				t.footer.style.visibility = 'hidden';
				t.footer.style.top = '-10000px';
				t.footer.style.left = '-10000px';
				this.header.className = "header collapsed";
			}
		}
		
		if (fill_height) {
			this.element.style.display = "flex";
			this.element.style.flexDirection = "column";
			this.header.style.flex = "none";
			this.content_container.style.flex = "1 1 auto";
			this.content_container.style.display = "flex";
			this.content_container.style.flexDirection = "row";
			content.style.flex = "1 1 auto";
			content.style.overflowY = "auto";
			this.footer.style.flex = "none";
			layout.listenElementSizeChanged(content,function() {
				if (content.scrollHeight > content.clientHeight)
					content.style.paddingRight = window.top.browser_scroll_bar_size+"px";
				else
					content.style.paddingRight = "0px";
			});
		}
	};
	
	/** Toogle between collapsed and expanded */
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
					if (value == t.content_container.originalHeight) layout.changed(t.element.parentNode);
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
			this.header.className = "header";
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
					if (value == 0) layout.changed(t.element.parentNode);
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
			this.header.className = "header collapsed";
		}
	};
	
	this._init();
	
	this.element.ondomremoved(function() {
		t.element = null;
		t.content = null;
		t.toolbar = null;
		t.toolbar_left = null;
		t.toolbar_right = null;
		t.footer = null;
		t.header = null;
		t.title = null;
		t.icon = null;
		t.title_container = null;
		if (t.content_container) {
			t.content_container.anim1 = null;
			t.content_container.anim2 = null;
		}
		t.content_container = null;
		t.collapse_container = null;
		t.collapse_button = null;
		t = null;
	});
}