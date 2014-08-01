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
	t.part2 = element.childNodes[1];
	
	t._position = function() {
		var w = t.element.clientWidth;
		var h = t.element.clientHeight;
		if (t.part1.style.visibility == "visible") {
			if (t.part2.style.visibility == "visible") {
				// all visible
				var sw = 7;
				var x = Math.floor(w*t.position - sw/2);
				setWidth(t.part1, x);
				setHeight(t.part1, h);
				t.separator.style.left = x+"px";
				t.separator.style.height = h+"px";
				t.part2.style.left = (x+sw)+"px";
				setWidth(t.part2, w-x-sw-1);
				setHeight(t.part2, h);
			} else {
				// only left part
				setWidth(t.part1, w);
				setHeight(t.part1, h);
			}
		} else {
			// only right part
			setWidth(t.part2, w);
			setHeight(t.part2, h);
		}
	};
	
	if (t.element.style && (t.element.style.position != 'relative' && t.element.style.position != 'absolute'))
		t.element.style.position = "relative";
	t.separator = document.createElement("DIV");
	t.separator.style.position = "absolute";
	t.separator.style.top = "0px";
	t.separator.className = "splitter_vertical_separator";
	t.separator.style.backgroundImage = "url(\""+get_script_path('splitter_vertical.js')+"splitter_vertical.gif\")";
	t.part1.style.position = "absolute";
	t.part1.style.top = "0px";
	t.part1.style.left = "0px";
	t.part1.style.visibility = "visible";
	t.part2.style.position = "absolute";
	t.part2.style.top = "0px";
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
		var h = t.element.clientHeight;
		t.part1.style.visibility = 'hidden';
		t.part1.style.top = "-10000px";
		t.separator.style.visibility = 'hidden';
		t.separator.style.left = '-1000px';
		t.part2.style.left = '0px';
		t.part2.style.top = '0px';
		t.part2.style.width = w+'px';
		t.part2.style.height = h+'px';
		layout.invalidate(t.element);
	};
	t.show_left = function() {
		t.part1.style.visibility = 'visible';
		t.part1.style.top = "0px";
		t.separator.style.visibility = 'visible';
		layout.invalidate(t.element);
	};
	t.hide_right = function() {
		var w = t.element.clientWidth;
		var h = t.element.clientHeight;
		t.part2.style.visibility = 'hidden';
		t.part2.style.top = "-10000px";
		t.separator.style.visibility = 'hidden';
		t.part1.style.left = '0px';
		t.part1.style.top = '0px';
		t.part1.style.width = w+'px';
		t.part1.style.height = h+'px';
		layout.invalidate(t.element);
	};
	t.show_right = function() {
		t.part2.style.visibility = 'visible';
		t.part2.style.top = "0px";
		t.separator.style.visibility = 'visible';
		layout.invalidate(t.element);
	};
	
	t.remove = function() {
		layout.removeHandler(t.element, t._position);
		t.element.removeChild(t.separator);
		t.part1.style.position = 'static';
		t.part2.style.position = 'static';
		layout.invalidate(t.element);
	};

	t._position();
}