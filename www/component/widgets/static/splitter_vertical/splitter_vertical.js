if (typeof theme != 'undefined')
	theme.css("splitter_vertical.css");

function splitter_vertical(element, position) {
	if (typeof element == 'string') element = document.getElementById(element);
	var t = this;
	
	t.element = element;
	t.element.style.overflow = "hidden"; // do not allow to scroll, as we must fill all the area
	t.element.widget = this;
	t.position = position;
	t.element.data = t;
	for (var i = 0; i < element.childNodes.length; ++i) {
		var e = element.childNodes[i];
		if (e.nodeType != 1) {
			element.removeChild(e);
			i--;
			continue;
		}
	}
	t.part1 = element.childNodes[0];
	t.part1.style.display = "inline-block";
	t.part1.style.verticalAlign = "top";
	t.part2 = element.childNodes[1];
	t.part2.style.display = "inline-block";
	t.part2.style.verticalAlign = "top";
	
	t._position = function() {
		var h = t.element.clientHeight;
		setHeight(t.part1, h);
		setHeight(t.part2, h);
		setHeight(t.separator, h);
		var w = t.element.clientWidth;
		if (t.part1.style.visibility == "visible") {
			if (t.part2.style.visibility == "visible") {
				// all visible
				var sw = 7;
				var x = Math.floor(w*t.position - sw/2);
				setWidth(t.part1, x);
				t.part1.style.minWidth = t.part1.style.width;
				t.part1.style.maxWidth = t.part1.style.width;
				t.separator.style.left = x+"px";
				t.part2.style.left = (x+sw)+"px";
				setWidth(t.part2, w-x-sw-1);
				t.part2.style.minWidth = t.part2.style.width;
				t.part2.style.maxWidth = t.part2.style.width;
			} else {
				// only left part
				setWidth(t.part1, w);
				t.part1.style.minWidth = t.part1.style.width;
				t.part1.style.maxWidth = t.part1.style.width;
			}
		} else {
			// only right part
			setWidth(t.part2, w);
			t.part2.style.minWidth = t.part2.style.width;
			t.part2.style.maxWidth = t.part2.style.width;
		}
	};
	
	t.separator = document.createElement("DIV");
	t.separator.className = "splitter_vertical_separator";
	t.separator.style.backgroundImage = "url(\""+get_script_path('splitter_vertical.js')+"splitter_vertical.gif\")";
	t.separator.style.display = "inline-block";
	t.separator.style.verticalAlign = "top";
	t.part1.style.visibility = "visible";
	t.part2.style.visibility = "visible";
	element.insertBefore(t.separator, t.part2);
	layout.invalidate(t.element);
	
	t.positionChanged = new Custom_Event();
	
	layout.addHandler(t.element, t._position);
	
	t._stop_move = function(ev, origin_w, this_w) {
		unlistenEvent(this_w, 'blur', t._stop_move);
		window.top.pnapplication.unregisterOnMouseMove(t._moving);
		window.top.pnapplication.unregisterOnMouseUp(t._stop_move);
	};
	t._moving = function(mouse_x, mouse_y) {
		var diff = mouse_x - t.mouse_pos;
		if (diff == 0) return;
		var w = t.element.offsetWidth;
		var x = w*t.position;
		x += diff;
		t.position = x/w;
		t.mouse_pos = mouse_x;
		layout.invalidate(t.element);
		t.positionChanged.fire(t);
	};
	t.mouse_pos = 0;
	t.separator.onmousedown = function(event) {
		if (!event) event = window.event;
		t.mouse_pos = event.clientX;
		listenEvent(window, 'blur', t._stop_move);
		window.top.pnapplication.registerOnMouseMove(window, t._moving);
		window.top.pnapplication.registerOnMouseUp(window, t._stop_move);
		return false;
	};
	
	t.hide_left = function() {
		var w = t.element.clientWidth;
		t.part1.style.visibility = 'hidden';
		t.part1.style.width = "0px";
		t.part1.style.display = 'none';
		t.separator.style.visibility = 'hidden';
		t.separator.style.width = "0px";
		t.separator.style.display = 'none';
		t.part2.style.width = w+'px';
		layout.invalidate(t.element);
	};
	t.show_left = function() {
		t.part1.style.visibility = 'visible';
		t.separator.style.visibility = 'visible';
		t.part1.style.display = '';
		t.separator.style.display = '';
		layout.invalidate(t.element);
	};
	t.hide_right = function() {
		var w = t.element.clientWidth;
		t.part2.style.visibility = 'hidden';
		t.part2.style.width = "0px";
		t.part2.style.display = 'none';
		t.separator.style.visibility = 'hidden';
		t.separator.style.width = "0px";
		t.separator.style.display = 'none';
		t.part1.style.width = w+'px';
		layout.invalidate(t.element);
	};
	t.show_right = function() {
		t.part2.style.visibility = 'visible';
		t.separator.style.visibility = 'visible';
		t.part2.style.display = '';
		t.separator.style.display = '';
		layout.invalidate(t.element);
	};
	
	t.remove = function() {
		layout.removeHandler(t.element, t._position);
		t.element.removeChild(t.separator);
		t.part1.style.width = '';
		t.part2.style.width = '';
		layout.invalidate(t.element);
	};

	t._position();
}