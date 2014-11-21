if (typeof window.top.require != 'undefined') {
	window.top.require("position.js");
}
if (typeof theme != 'undefined') theme.css("mini_popup.css");

function mini_popup(title) {
	
	this.showBelowElement = function(element, min_width_from_element) {
		this._div.style.display = "";
		var t=this;
		window.top.require("position.js",function() {
			window.top.positionBelowElement(t._div, element, min_width_from_element);
			setOpacity(t._div, 1);
		});
		element.ondomremoved(function() { t.close(); });
	};
	this.showAboveElement = function(element, min_width_from_element) {
		this._div.style.display = "";
		var t=this;
		window.top.require("position.js",function() {
			window.top.positionAboveElement(t._div, element, min_width_from_element);
			setOpacity(t._div, 1);
		});
		element.ondomremoved(function() { t.close(); });
	};
	
	this.close = function() {
		if (!this._div) return;
		this._div.parentNode.removeChild(this._div);
		this._div = null;
	};
	
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
		this._div.style.display = "none";
		this._div.style.position = "fixed";
		this._div.style.top = "-10000px";
		window.top.document.body.appendChild(this._div);
		var t=this;
		this._close.onclick = function() { t.close(); };
	};
	this._init();
}