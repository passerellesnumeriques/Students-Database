if (typeof window.top.require != 'undefined') {
	window.top.require("position.js");
}
if (typeof theme != 'undefined') theme.css("mini_popup.css");

/**
 * A mini popup is a popup which is typically shown below or above a button.
 * This is an alternative to context_menu.
 * Compared to a context_menu, a mini_popup as a title, a close button, and is not automatically closed when the user clicks outside.
 * Note that the content of a mini_popup is by default smaller (9pt instead of 10pt).
 * @param {String} title the title
 * @param {Boolean} do_not_scroll if true, and there is not enough space below or above, we will display it as big as possible to avoid scrolling.
 */
function mini_popup(title, do_not_scroll) {

	/** Show the popup below the given element
	 * @param {Element} element the popup will be shown below this element
	 * @param {Boolean} min_width_from_element if true, the popup will have a minimum width corresponding to the width of the given element
	 */
	this.showBelowElement = function(element, min_width_from_element) {
		if (!do_not_scroll) this._div.style.display = "";
		var t=this;
		window.top.require("position.js",function() {
			window.top.positionBelowElement(t._div, element, min_width_from_element);
			if (do_not_scroll) t._div.style.overflow = "";
			setOpacity(t._div, 1);
			layout.listenElementSizeChanged(t._div,function() {
				if (!t || !t._div) return;
				window.top.positionBelowElement(t._div, element, min_width_from_element);
			});
		});
		element.ondomremoved(function() { t.close(); });
	};
	/** Show the popup above the given element
	 * @param {Element} element the popup will be shown above this element
	 * @param {Boolean} min_width_from_element if true, the popup will have a minimum width corresponding to the width of the given element
	 */
	this.showAboveElement = function(element, min_width_from_element) {
		if (!do_not_scroll) this._div.style.display = "";
		var t=this;
		window.top.require("position.js",function() {
			window.top.positionAboveElement(t._div, element, min_width_from_element);
			if (do_not_scroll) t._div.style.overflow = "";
			setOpacity(t._div, 1);
			layout.listenElementSizeChanged(t._div,function() {
				if (!t || !t._div) return;
				window.top.positionAboveElement(t._div, element, min_width_from_element);
			});
		});
		element.ondomremoved(function() { t.close(); });
	};
	/** Close the popup */
	this.close = function() {
		if (!this._div) return;
		this._div.parentNode.removeChild(this._div);
		this._div = null;
	};
	/** Add an element in the footer
	 * @param {Element} element the element to add in the footer
	 */
	this.addFooter = function(element) {
		if (!this._footer) {
			this._footer = document.createElement("DIV");
			this._footer.className = "mini_popup_footer";
			if (do_not_scroll) this._footer.style.flex = "none";
			this._div.appendChild(this._footer);
		}
		this._footer.appendChild(element);
		layout.changed(this._footer);
	};
	/** Add a button in the footer
	 * @param {String|null} icon URL of the icon
	 * @param {String} text text which can contain HTML code
	 * @param {Function} onclick function to call when the button is clicked
	 * @param {Object} onclick_param if given, it will be given as second parameter of the onclick function (the first one being the MouseEvent of the click)
	 */
	this.addButton = function(icon, text, onclick, onclick_param) {
		var button = document.createElement("BUTTON");
		button.className = "flat";
		button.innerHTML = (icon ? "<img src='"+icon+"' style='padding-bottom:3px'/> " : "")+text;
		button.onclick = function(ev) {
			onclick(ev, onclick_param);
		};
		this.addFooter(button);
	};
	/** Add a Ok button in the footer
	 * @param {Function} onclick function to call when the button is clicked
	 * @param {Object} onclick_param if given, it will be given as second parameter of the onclick function (the first one being the MouseEvent of the click)
	 */
	this.addOkButton = function(onclick, onclick_param) {
		var t=this;
		this.addButton(theme.icons_10.ok, "Ok", function(ev, p) {
			if (onclick(ev,p)) t.close();
		},onclick_param);
	};
	/** Creation of the popup */
	this._init = function() {
		this._div = document.createElement("DIV");
		this._div.className = "mini_popup";
		setOpacity(this._div, 0);
		this._header = document.createElement("DIV");
		this._header.className = "mini_popup_header";
		this._div.appendChild(this._header);
		this._title = document.createElement("DIV");
		this._title.className = "mini_popup_title";
		this._title.appendChild(document.createTextNode(title));
		this._header.appendChild(this._title);
		this._close = document.createElement("DIV");
		this._close.className = "mini_popup_close";
		this._header.appendChild(this._close);
		this.content = document.createElement("DIV");
		this.content.className = "mini_popup_content";
		this._div.appendChild(this.content);
		this._footer = null;
		this._div.style.display = "none";
		this._div.style.position = "fixed";
		this._div.style.top = "-10000px";
		if (do_not_scroll) {
			this._div.style.display = "flex";
			this._div.style.flexDirection = "column";
			this._header.style.flex = "none";
			this.content.style.flex = "1 1 auto";
		}
		window.top.document.body.appendChild(this._div);
		var t=this;
		this._close.onclick = function() { t.close(); };
	};
	this._init();
}