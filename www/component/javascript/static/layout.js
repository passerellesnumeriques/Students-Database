// call onresize of window when all images are loaded, to trigger re-layout if needed
function _all_images_loaded() {
	var img = document.getElementsByTagName("IMG");
	for (var i = 0; i < img.length; ++i) {
		if (img[i].complete || img[i].height != 0) continue;
		return;
	}
	triggerEvent(window, 'resize');
}
function _init_images() {
	var img = document.getElementsByTagName("IMG");
	for (var i = 0; i < img.length; ++i) {
		listenEvent(img[i],'load',_all_images_loaded);
	}
}

// handle resize event for layout, in hierarchy order
var _layout_events = [];
function addLayoutEvent(element, handler) {
	_layout_events.push({element:element,handler:handler});
}
function _fire_layout_events() {
	// order elements
	var list = [];
	for (var i = 0; i < _layout_events.length; ++i) {
		var e = _layout_events[i];
		var found = false;
		for (var j = 0; j < list.length; ++j) {
			if (list[j].element == document.body) continue;
			var p = list[j].element.parentNode;
			while (p && p != document.body) {
				if (p == e.element) {
					// list[j] is a child of e => insert e before
					list.splice(j,0,e);
					found = true;
					break;
				}
				p = p.parentNode;
			}
			if (found) break;
		}
		if (!found) list.push(e);
	}
	var changed;
	var count = 0;
	do {
		changed = false;
		for (var i = 0; i < list.length; ++i) {
			var w = list[i].element.offsetWidth;
			var h = list[i].element.offsetHeight;
			list[i].handler(list[i].element);
			changed |= w != list[i].element.offsetWidth || h != list[i].element.offsetHeight;
		}
	} while (changed && ++count < 5);
}
function fireLayoutEventFor(element) {
	if (element == document.body) { _fire_layout_events(); return; }
	// order elements
	var list = [];
	for (var i = 0; i < _layout_events.length; ++i) {
		var e = _layout_events[i];
		if (e.element == document.body) continue;
		var p = e.element;
		var found = false;
		while (p && p != document.body) {
			if (p == element) { found = true; break; }
			p = p.parentNode;
		}
		if (!found) continue; // not a child of given element
		found = false;
		for (var j = 0; j < list.length; ++j) {
			if (list[j].element == document.body) continue;
			var p = list[j].element.parentNode;
			while (p && p != document.body) {
				if (p == e.element) {
					// list[j] is a child of e => insert e before
					list.splice(j,0,e);
					found = true;
					break;
				}
				p = p.parentNode;
			}
			if (found) break;
		}
		if (!found) list.push(e);
	}
	var changed;
	do {
		changed = false;
		for (var i = 0; i < list.length; ++i) {
			var w = list[i].element.offsetWidth;
			var h = list[i].element.offsetHeight;
			list[i].handler(list[i].element);
			changed |= w != list[i].element.offsetWidth || h != list[i].element.offsetHeight;
		}
	} while (changed);
}

if (typeof listenEvent != 'undefined') {
	listenEvent(window, 'load', _all_images_loaded);
	_init_images();
	listenEvent(window, 'resize', _fire_layout_events);
}

// useful functions to set height and width, taking into account borders, margins, and paddings
function setWidth(element, width) {
	var s = getComputedStyleSizes(element);
	width -= parseInt(s.borderLeftWidth);
	width -= parseInt(s.borderRightWidth);
	width -= parseInt(s.marginLeft);
	width -= parseInt(s.marginRight);
	width -= parseInt(s.paddingLeft);
	width -= parseInt(s.paddingRight);
	element.style.width = width+"px";
}
function setHeight(element, height) {
	var s = getComputedStyleSizes(element);
	height -= parseInt(s.borderTopWidth);
	height -= parseInt(s.borderBottomWidth);
	height -= parseInt(s.marginTop);
	height -= parseInt(s.marginBottom);
	height -= parseInt(s.paddingTop);
	height -= parseInt(s.paddingBottom);
	element.style.height = height+"px";
}
function getComputedStyleSizes(e) {
	var ss = getComputedStyle(e);
	var s = {};
	if (ss.width.indexOf('%') > 0 || ss.width == "auto") s.width = e.scrollWidth+'px'; else s.width = ss.width;
	if (ss.height.indexOf('%') > 0 || ss.height == "auto") s.height = e.scrollHeight+'px'; else s.height = ss.height;
	s.borderLeftWidth = _styleBorderValue(ss.borderLeftStyle, ss.borderLeftWidth);
	s.borderRightWidth = _styleBorderValue(ss.borderRightStyle, ss.borderRightWidth);
	s.borderTopWidth = _styleBorderValue(ss.borderTopStyle, ss.borderTopWidth);
	s.borderBottomWidth = _styleBorderValue(ss.borderBottomStyle, ss.borderBottomWidth);
	s.marginLeft = _styleMargin(ss.marginLeft);
	s.marginRight = _styleMargin(ss.marginRight);
	s.marginTop = _styleMargin(ss.marginTop);
	s.marginBottom = _styleMargin(ss.marginBottom);
	s.paddingTop = ss.paddingTop;
	s.paddingBottom = ss.paddingBottom;
	s.paddingLeft = ss.paddingLeft;
	s.paddingRight = ss.paddingRight;
	return s;
};
function _styleBorderValue(t, s) {
	if (t == "none") return "0px";
	if (s == "medium") return "4px";
	if (s == "thick") return "6px";
	return s;
};
function _styleMargin(s) {
	if (s == "auto") return "0px";
	return s;
};

