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
			layout.listenElementSizeChanged(t._div,function() {
				if (!t || !t._div) return;
				window.top.positionBelowElement(t._div, element, min_width_from_element);
			});
		});
		element.ondomremoved(function() { t.close(); });
	};
	this.showAboveElement = function(element, min_width_from_element) {
		this._div.style.display = "";
		var t=this;
		window.top.require("position.js",function() {
			window.top.positionAboveElement(t._div, element, min_width_from_element);
			setOpacity(t._div, 1);
			layout.listenElementSizeChanged(t._div,function() {
				if (!t || !t._div) return;
				window.top.positionAboveElement(t._div, element, min_width_from_element);
			});
		});
		element.ondomremoved(function() { t.close(); });
	};
	
	this.close = function() {
		if (!this._div) return;
		this._div.parentNode.removeChild(this._div);
		this._div = null;
	};
	
	this.addFooter = function(element) {
		if (!this._footer) {
			this._footer = document.createElement("DIV");
			this._footer.className = "mini_popup_footer";
			this._div.appendChild(this._footer);
		}
		this._footer.appendChild(element);
		layout.changed(this._footer);
	};
	
	this.addButton = function(icon, text, onclick, onclick_param) {
		var button = document.createElement("BUTTON");
		button.className = "flat";
		button.innerHTML = (icon ? "<img src='"+icon+"' style='padding-bottom:3px'/> " : "")+text;
		button.onclick = function(ev) {
			onclick(ev, onclick_param);
		};
		this.addFooter(button);
	};
	
	this.addOkButton = function(onclick, onclick_param) {
		var t=this;
		this.addButton(theme.icons_10.ok, "Ok", function(ev, p) {
			if (onclick(ev,p)) t.close();
		},onclick_param);
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
		this._footer = null;
		this._div.style.display = "none";
		this._div.style.position = "fixed";
		this._div.style.top = "-10000px";
		window.top.document.body.appendChild(this._div);
		var t=this;
		this._close.onclick = function() { t.close(); };
	};
	this._init();
}