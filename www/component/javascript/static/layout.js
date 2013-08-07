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
