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
	
	t._position = function(prev_h,call) {
		var w = t.element.offsetWidth;
		var h = t.element.offsetHeight;
		var sw = t.separator.offsetWidth;
		var x = Math.floor(w*t.position - sw/2);
		setWidth(t.part1, x);
		setHeight(t.part1, h);
		t.separator.style.left = x+"px";
		t.separator.style.height = h+"px";
		t.part2.style.left = (x+sw)+"px";
		setWidth(t.part2, w-x-sw-1);
		setHeight(t.part2, h);
		fireLayoutEventFor(t.part1);
		fireLayoutEventFor(t.part2);
		if (t.element.offsetHeight != h) {
			if (!prev_h || (t.element.offsetHeight != prev_h && call < 3)) t._position(t.element.offsetHeight, call ? 1 : call+1);
		}
	};
	
	t.element.style.position = "relative";
	t.separator = document.createElement("DIV");
	t.separator.style.position = "absolute";
	t.separator.style.top = "0px";
	t.separator.className = "splitter_vertical_separator";
	t.separator.style.backgroundImage = "url(\""+get_script_path('splitter_vertical.js')+"splitter_vertical.gif\")";
	t.part1.style.position = "absolute";
	t.part1.style.top = "0px";
	t.part1.style.left = "0px";
	t.part2.style.position = "absolute";
	t.part2.style.top = "0px";
	element.insertBefore(t.separator, t.part2);
	t._position();
	
	t.positionChanged = new Custom_Event();
	
	addLayoutEvent(t.element, t._position);
	
	t._stop_move = function() {
		unlistenEvent(window, 'mouseup', t._stop_move);
		unlistenEvent(window, 'blur', t._stop_move);
		unlistenEvent(window, 'mousemove', t._moving);
		setTimeout(function(){fireLayoutEventFor(t.element);},1);
	};
	t._moving = function(event) {
		if (!event) event = window.event;
		var diff = event.clientX - t.mouse_pos;
		if (diff == 0) return;
		var w = t.element.offsetWidth;
		var x = w*t.position;
		x += diff;
		t.position = x/w;
		t.mouse_pos = event.clientX;
		t._position();
		t.positionChanged.fire(t);
	};
	t.mouse_pos = 0;
	t.separator.onmousedown = function(event) {
		if (!event) event = window.event;
		t.mouse_pos = event.clientX;
		listenEvent(window, 'mouseup', t._stop_move);
		listenEvent(window, 'blur', t._stop_move);
		listenEvent(window, 'mousemove', t._moving);
		return false;
	};
	
	t.hide_left = function() {
		var w = t.element.offsetWidth;
		var h = t.element.offsetHeight;
		t.part1.style.visibility = 'hidden';
		t.separator.style.visibility = 'hidden';
		t.part2.style.left = '0px';
		t.part2.style.top = '0px';
		t.part2.style.width = w+'px';
		t.part2.style.height = h+'px';
	};
	t.show_left = function() {
		t.part1.style.visibility = 'visible';
		t.separator.style.visibility = 'visible';
		t._position();
	};
	t.hide_right = function() {
		var w = t.element.offsetWidth;
		var h = t.element.offsetHeight;
		t.part2.style.visibility = 'hidden';
		t.separator.style.visibility = 'hidden';
		t.part1.style.left = '0px';
		t.part1.style.top = '0px';
		t.part1.style.width = w+'px';
		t.part1.style.height = h+'px';
	};
	t.show_right = function() {
		t.part2.style.visibility = 'visible';
		t.separator.style.visibility = 'visible';
		t._position();
	};
	
	t.remove = function() {
		removeLayoutEvent(t.element, t._position);
		t.element.removeChild(t.separator);
		t.part1.style.position = 'static';
		t.part2.style.position = 'static';
	};
}