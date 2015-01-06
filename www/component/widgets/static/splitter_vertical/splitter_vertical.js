if (typeof theme != 'undefined')
	theme.css("splitter_vertical.css");

/**
 * Split a screen vertically into 2 parts, with a separator which can be moved to grow/shrink the 2 parts
 * @param {Element} element element to split. It must contain 2 and only 2 children
 * @param {Number} position position of the separator (between 0 and 1)
 */
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
	/** First part */
	t.part1 = element.childNodes[0];
	/** Second part */
	t.part2 = element.childNodes[1];
	
	/** Refresh the position */
	t._position = function() {
		var w = t.element.clientWidth;
		var h = t.element.clientHeight;
		var knowledge = [];
		if (t.part1.style.visibility == "visible") {
			if (t.part2.style.visibility == "visible") {
				// all visible
				var sw = 7;
				var x = Math.floor(w*t.position - sw/2);
				setWidth(t.part1, x, knowledge);
				setHeight(t.part1, h, knowledge);
				t.separator.style.left = x+"px";
				t.separator.style.height = h+"px";
				t.part2.style.left = (x+sw)+"px";
				setWidth(t.part2, w-x-sw-1, knowledge);
				setHeight(t.part2, h, knowledge);
				layout.changed(t.part1);
				layout.changed(t.part2);
				layout.changed(t.separator);
			} else {
				// only left part
				setWidth(t.part1, w, knowledge);
				setHeight(t.part1, h, knowledge);
				layout.changed(t.part1);
			}
		} else {
			// only right part
			setWidth(t.part2, w, knowledge);
			setHeight(t.part2, h, knowledge);
			layout.changed(t.part2);
		}
	};
	
	if (t.element.style && (t.element.style.position != 'relative' && t.element.style.position != 'absolute'))
		t.element.style.position = "relative";
	/** Separator */
	t.separator = document.createElement("DIV");
	t.separator.style.position = "absolute";
	t.separator.style.top = "0px";
	t.separator.className = "splitter_vertical_separator";
	t.separator.style.backgroundImage = "url(\""+getScriptPath('splitter_vertical.js')+"splitter_vertical.gif\")";
	t.part1.style.position = "absolute";
	t.part1.style.top = "0px";
	t.part1.style.left = "0px";
	t.part1.style.visibility = "visible";
	t.part2.style.position = "absolute";
	t.part2.style.top = "0px";
	t.part2.style.visibility = "visible";
	element.insertBefore(t.separator, t.part2);
	layout.changed(t.element);
	
	/** Event called when the user moved the separator */
	t.positionChanged = new Custom_Event();
	
	layout.listenElementSizeChanged(t.element, t._position);
	
	/** Internal function when the mouse is released
	 * @param {Event} ev event
	 * @param {Window} origin_w window from which we catch the event
	 * @param {Window} this_w window of this splitter
	 */
	t._stopMove = function(ev, origin_w, this_w) {
		unlistenEvent(this_w, 'blur', t._stopMove);
		window.top.pnapplication.unregisterOnMouseMove(t._moving);
		window.top.pnapplication.unregisterOnMouseUp(t._stopMove);
	};
	/** Internal function called when the user is moving the separator
	 * @param {Number} mouse_x position of the mouse
	 * @param {Number} mouse_y position of the mouse
	 */
	t._moving = function(mouse_x, mouse_y) {
		var diff = mouse_x - t.mouse_pos;
		if (diff == 0) return;
		var w = t.element.offsetWidth;
		var x = w*t.position;
		x += diff;
		t.position = x/w;
		t.mouse_pos = mouse_x;
		t._position();
		layout.changed(t.element);
		t.positionChanged.fire(t);
	};
	/** Internal: position of the mouse */
	t.mouse_pos = 0;
	t.separator.onmousedown = function(event) {
		if (!event) event = window.event;
		t.mouse_pos = event.clientX;
		listenEvent(window, 'blur', function(ev) { t._stopMove(ev,window,window); });
		window.top.pnapplication.registerOnMouseMove(window, t._moving);
		window.top.pnapplication.registerOnMouseUp(window, t._stopMove);
		return false;
	};
	
	/** Hide the left part */
	t.hideLeft = function() {
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
		layout.changed(t.element);
	};
	/** Show the left part */
	t.showLeft = function() {
		t.part1.style.visibility = 'visible';
		t.part1.style.top = "0px";
		t.separator.style.visibility = 'visible';
		layout.changed(t.element);
	};
	/** Hide the right part */
	t.hideRight = function() {
		var w = t.element.clientWidth;
		var h = t.element.clientHeight;
		t.part2.style.visibility = 'hidden';
		t.part2.style.top = "-10000px";
		t.separator.style.visibility = 'hidden';
		t.part1.style.left = '0px';
		t.part1.style.top = '0px';
		t.part1.style.width = w+'px';
		t.part1.style.height = h+'px';
		layout.changed(t.element);
	};
	/** Show the right part */
	t.showRight = function() {
		t.part2.style.visibility = 'visible';
		t.part2.style.top = "0px";
		t.separator.style.visibility = 'visible';
		layout.changed(t.element);
	};
	
	/** Remove this splitter */
	t.remove = function() {
		layout.unlistenElementSizeChanged(t.element, t._position);
		t.element.removeChild(t.separator);
		t.part1.style.position = 'static';
		t.part2.style.position = 'static';
		layout.changed(t.element);
	};

	t._position();
}