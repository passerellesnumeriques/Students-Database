// call onresize of window when all images are loaded, to trigger re-layout if needed
var resize_triggered = false;
function _all_images_loaded() {
	var img = document.getElementsByTagName("IMG");
	for (var i = 0; i < img.length; ++i) {
		if (img[i].complete || img[i].height != 0) continue;
		return;
	}
	if (!resize_triggered) {
		resize_triggered = true;
		setTimeout(function() {
			resize_triggered = false;
			triggerEvent(window, 'resize');
		},10);
	}
}
function _init_images() {
	var img = document.getElementsByTagName("IMG");
	for (var i = 0; i < img.length; ++i) {
		img[i]._loaded_event = true;
		listenEvent(img[i],'load',_all_images_loaded);
	}
}

var _last_layout_activity = 0;

// handle resize event for layout, in hierarchy order
var _layout_events = [];
function addLayoutEvent(element, handler) {
	_layout_events.push({element:element,handler:handler});
	element._layoutW = element.scrollWidth;
	element._layoutH = element.scrollHeight;
	_last_layout_activity = new Date().getTime();
}
function removeLayoutEvent(element, handler) {
	for (var i = 0; i < _layout_events.length; ++i) {
		if (_layout_events[i].element == element && _layout_events[i].handler == handler) {
			_layout_events.splice(i,1);
			i--;
		}
	}
}
function _fire_layout_events() {
	if (window.top.pause_layout) return;
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
			list[i].element._layoutW = list[i].element.scrollWidth;
			list[i].element._layoutH = list[i].element.scrollHeight;
		}
	} while (changed && ++count < 5);
	if (count > 0) _last_layout_activity = new Date().getTime();
}
var check_images = false;
function fireLayoutEventFor(element) {
	if (window.top.pause_layout) return;
	if (getWindowFromDocument(element.ownerDocument) != window) {
		getWindowFromDocument(element.ownerDocument).fireLayoutEventFor(element);
		return;
	}
	// handle possible new images
	if (!check_images) {
		check_images = true;
		setTimeout(function() {
			var img = document.getElementsByTagName("IMG");
			for (var i = 0; i < img.length; ++i) {
				if (img[i]._loaded_event) continue;
				listenEvent(img[i],'load',_all_images_loaded);
				img[i]._loaded_event = true;
			}
			check_images = false;
		},10);
	}

	if (element == document.body) {
		triggerEvent(window, 'resize');
		return; 
	}
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
	if (list.length == 0) {
		// nothing inside, let's go to the parent
		var parent = element.parentNode; 
		while (parent && parent != document.body) {
			var found = false;
			for (var j = 0; j < _layout_events.length; ++j) {
				if (_layout_events[j].element == parent) {
					list.push(_layout_events[j]);
					found = true;
					break;
				}
			}
			if (found) break;
			parent = parent.parentNode;
		}
		if (list.length == 0) {
			// still nothing => general layout
			triggerEvent(window, 'resize');
			return;
		}
	}
	var changed, has_a_change = false;
	do {
		changed = false;
		for (var i = 0; i < list.length; ++i) {
			var w = list[i].element.offsetWidth;
			var h = list[i].element.offsetHeight;
			list[i].handler(list[i].element);
			changed |= w != list[i].element.offsetWidth || h != list[i].element.offsetHeight;
			has_a_change |= changed;
			if (list[i].element._layoutW && list[i].element.scrollWidth != list[i].element._layoutW)
				has_a_change = true;
			else if (list[i].element._layoutH && list[i].element.scrollHeight != list[i].element._layoutH)
				has_a_change = true;
			list[i].element._layoutW = list[i].element.scrollWidth;
			list[i].element._layoutH = list[i].element.scrollHeight;
		}
		if (changed) _last_layout_activity = new Date().getTime();
	} while (changed);
	if (has_a_change) {
		// fire for parents
		var todo = [];
		for (var i = 0; i < list.length; ++i) {
			var parent = list[i].element; 
			if (parent == document.body) { todo=[];break; }
			parent = parent.parentNode;
			while (parent && parent != document.body) {
				var found = false;
				for (var j = 0; j < _layout_events.length; ++j) {
					if (_layout_events[j].element == parent) {
						if (!todo.contains(parent)) {
							found = false;
							for (var k = 0; k < list.length; ++k) {
								if (list[k].element == parent) {
									found = true;
									break;
								}
							}
							if (!found)
								todo.push(parent);
						}
						found = true;
						break;
					}
				}
				if (found) break;
				parent = parent.parentNode;
			}
		}
		for (var i = 0; i < todo.length; ++i) {
			fireLayoutEventFor(todo[i]);
		}
	}
}

window.top.pause_layout = false;
function _layout_auto() {
	if (window.top.pause_layout) return;
	var done = [];
	for (var i = 0; i < _layout_events.length; ++i) {
		var e = _layout_events[i].element;
		if (e.scrollHeight != e._layoutH || e.scrollWidth != e._layoutW)
			fireLayoutEventFor(e);
		done.push(e);
	}
	// process the containers, until body
	for (var i = 0; i < _layout_events.length; ++i) {
		var e = _layout_events[i].element;
		e = e.parentNode;
		while (e != null && e != document.body) {
			if (!done.contains(e)) {
				if (e.scrollHeight != e._layoutH || e.scrollWidth != e._layoutW) {
					fireLayoutEventFor(e);
					e._layoutH = e.scrollHeight; 
					e._layoutW = e.scrollWidth; 
				}
				done.push(e);
			}
			e = e.parentNode;
		}
	}
	var now = new Date().getTime();
	var timing;
	if (now - _last_layout_activity < 5000) timing = 1000;
	else if (now - _last_layout_activity < 10000) timing = 2000;
	else if (now - _last_layout_activity < 20000) timing = 4000;
	else timing = 5000;
	if (_layout_interval_time != timing) {
		clearInterval(_layout_interval);
		_layout_interval = setInterval(_layout_auto, timing);
		_layout_interval_time = timing;
	}
}
var _layout_interval_time = 1000;
var _layout_interval = setInterval(_layout_auto,1000);

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
function getWidth(element) {
	var s = getComputedStyleSizes(element);
	var w = element.offsetWidth;
	w += parseInt(s.marginLeft) + parseInt(s.marginRight);
	return w;
}
function getHeight(element) {
	var s = getComputedStyleSizes(element);
	var h = element.offsetHeight;
	h += parseInt(s.marginTop) + parseInt(s.marginBottom);
	return h;
}
function getComputedStyleSizes(e) {
	if (e.nodeType != 1) {
		return {
			borderLeftWidth: 0, borderRightWidth: 0, borderTopWidth: 0, borderBottomWidth: 0,
			marginLeft: 0, marginRight: 0, marginTop: 0, marginBottom: 0,
			paddingTop: 0, paddingBottom: 0, paddingLeft: 0, paddingRight: 0
		};
	}
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
	s.paddingTop = _stylePadding(ss.paddingTop);
	s.paddingBottom = _stylePadding(ss.paddingBottom);
	s.paddingLeft = _stylePadding(ss.paddingLeft);
	s.paddingRight = _stylePadding(ss.paddingRight);
	return s;
};
function _styleBorderValue(t, s) {
	if (t == "none") return "0px";
	if (s == "medium") return "4px";
	if (s == "thick") return "6px";
	if (s.length == 0) return "0px";
	return s;
};
function _styleMargin(s) {
	if (s == "auto") return "0px";
	if (s.length == 0) return "0px";
	return s;
};
function _stylePadding(s) {
	if (s.length == 0) return "0px";
	return s;
};

