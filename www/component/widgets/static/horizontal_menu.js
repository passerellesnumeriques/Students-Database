if (typeof require != 'undefined') {
	require("context_menu.js");
}

function HorizontalMenuItem(element) {
	this.element = element;
	if (element.className == 'context_menu_item')
		this.always_in_menu = true;
	else
		this.always_in_menu = false;
	this.originalMargin = getStyleSizes(element,[]).marginTop;
}

function horizontal_menu(menu, valign) {
	if (typeof menu == 'string') menu = document.getElementById(menu);
	menu.widget = this;
	var t = this;
	
	t.items = [];
	t.valign = valign;
	
	menu.ondomremoved(function() {
		layout.unlistenElementSizeChanged(menu, t.update);
		menu.widget = null;
		t.items = null;
		t.more_item = null;
		menu = null;
		t = null;
	});
	
	t.addItem = function(element) {
		t.items.push(new HorizontalMenuItem(element));
		layout.changed(menu);
	};
	t.removeAll = function() {
		t.items = [];
		layout.changed(menu);
	};
	
	while (menu.childNodes.length > 0) {
		if (menu.childNodes[0].nodeType == 1)
			t.items.push(new HorizontalMenuItem(menu.childNodes[0]));
		menu.removeChild(menu.childNodes[0]);
	}
	// get the last item, which is the 'more' item, and should be always visible
	t.more_item = t.items[t.items.length-1].element;
	t.items.splice(t.items.length-1, 1);
	t.more_item.style.display = 'inline-block';
	menu.appendChild(t.more_item);
	t.more_width = t.more_item.offsetWidth;
	t.more_item.onclick = function() { t.showMoreMenu(); };
	// check if we have elements that should always be in the context menu
	t.always_more = false;
	for (var i = 0; i < t.items.length; ++i)
		if (t.items[i].always_in_menu) { t.always_more = true; break; }
	
	t.update = function() {
		if (!t.items) return;
		while (menu.childNodes.length > 0) menu.removeChild(menu.childNodes[0]);
		var w = menu.clientWidth;
		var h = menu.clientHeight;
		var total = 0;
		for (var i = 0; i < t.items.length; ++i) {
			if (t.items[i].always_in_menu) continue; // skip if this item is only for context menu
			if (t.items[i].element._in_context_menu) continue;
			t.items[i].element.style.display = 'inline-block';
			t.items[i].element.style.whiteSpace = 'nowrap';
			menu.appendChild(t.items[i].element);
			var iw = getWidth(t.items[i].element,[]);
			total += iw;
			t.items[i].element.style.marginTop = (t.items[i].element.originalMargin)+'px';
			if (t.valign) {
				if (t.valign == "middle") {
					if (t.items[i].element.offsetHeight > 0)
						t.items[i].element.style.marginTop = Math.floor((h-t.items[i].element.offsetHeight)/2)+'px';
				} else if (t.valign == "bottom") {
					if (t.items[i].element.offsetHeight > 0) {
						var s = getStyleSizes(t.items[i].element,[]);
						t.items[i].element.style.marginTop = (h-t.items[i].element.offsetHeight-s.marginBottom)+'px';
					}
				}
			}
		}
		if (t.always_more) {
			menu.appendChild(t.more_item);
			total += t.more_width;
		}
		if (total > w) {
			// we need the more
			if (!t.always_more) {
				menu.appendChild(t.more_item);
				w -= t.more_width;
			}
			while (total > w && menu.childNodes.length > 1) {
				var i = menu.childNodes[menu.childNodes.length-2];
				total -= i.offsetWidth;
				menu.removeChild(i);
			}
		}
	};
	
	t.showMoreMenu = function() {
		require("context_menu.js", function() {
			var m = new context_menu();
			for (var i = 0; i < t.items.length; ++i) {
				if (t.items[i].element.parentNode == menu) continue;
				t.items[i].element.previousClassName = t.items[i].element.className;
				t.items[i].element.previousMargin = t.items[i].element.style.margin;
				t.items[i].element.style.margin = "0px";
				t.items[i].element.className = 'context_menu_item';
				t.items[i].element.style.display = 'block';
				t.items[i].element._in_context_menu = true;
				m.addItem(t.items[i].element);
			}
			m.showBelowElement(t.more_item);
			m.onclose = function() {
				for (var i = 0; i < t.items.length; ++i) {
					if (t.items[i].element.parentNode == menu) continue;
					t.items[i].element.className = t.items[i].element.previousClassName;
					t.items[i].element.style.margin = t.items[i].element.previousMargin;
					t.items[i].element._in_context_menu = false;
				}
			};
		});
	};
	
	menu.style.visibility = 'visible';
	t.update();
	layout.listenElementSizeChanged(menu, t.update);
}