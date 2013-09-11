if (typeof require != 'undefined') {
	require("context_menu.js");
}

function HorizontalMenuItem(element) {
	this.element = element;
	this.visible_class = element.className;
	if (element.className == 'context_menu_item')
		this.always_in_menu = true;
	else
		this.always_in_menu = false;
}

function horizontal_menu(menu) {
	if (typeof menu == 'string') menu = document.getElementById(menu);
	menu.widget = this;
	var t = this;
	
	t.items = [];
	
	t.addItem = function(element) {
		t.items.push(new HorizontalMenuItem(element));
		t.update();
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
		while (menu.childNodes.length > 0) menu.removeChild(menu.childNodes[0]);
		var w = menu.offsetWidth;
		var total = 0;
		for (var i = 0; i < t.items.length; ++i) {
			if (t.items[i].always_in_menu) continue; // skip if this item is only for context menu
			t.items[i].element.className = t.items[i].visible_class;
			t.items[i].element.style.display = 'inline-block';
			t.items[i].element.style.whiteSpace = 'nowrap';
			menu.appendChild(t.items[i].element);
			var iw = t.items[i].element.offsetWidth;
			total += iw;
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
				t.items[i].element.className = 'context_menu_item';
				t.items[i].element.style.display = 'block';
				m.addItem(t.items[i].element);
			}
			m.showBelowElement(t.more_item);
		});
	};
	
	menu.style.visibility = 'visible';
	t.update();
	addLayoutEvent(menu, function() { t.update(); });
}