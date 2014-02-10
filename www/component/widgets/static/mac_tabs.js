if (typeof theme != 'undefined')
	theme.css("mac_tabs.css");

function mac_tabs() {
	var t=this;
	t.element = document.createElement("DIV");
	t.element.className = "mac_tabs";
	setBorderRadius(t.element, 5, 5, 5, 5, 5, 5, 5, 5);
	t.addItem = function(content,id) {
		var div = document.createElement("DIV");
		div.className = "mac_tab";
		if (typeof content == "string")
			div.innerHTML = content;
		else
			div.appendChild(content);
		div.data = id;
		t.element.appendChild(div);
		div.onclick = function() { t.select(this.data); };
		t.update_radius();
	};
	t.update_radius = function() {
		for (var i = 0; i < t.element.childNodes.length; ++i) {
			var tl = 0, tr = 0, bl = 0, br = 0;
			if (i == 0) { tl = 5; bl = 5; }
			if (i == t.element.childNodes.length-1) { tr = 5; br = 5; }
			setBorderRadius(t.element.childNodes[i], tl, tl, tr, tr, bl, bl, br, br);
		}
	};
	t.select = function(id) {
		for (var i = 0; i < t.element.childNodes.length; ++i) {
			var tab = t.element.childNodes[i];
			if (tab.data == id)
				tab.className = "mac_tab selected";
			else
				tab.className = "mac_tab";
		}
		if (t.onselect) t.onselect(id);
	};
}