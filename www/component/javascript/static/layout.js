layout = {
	// Layout handlers attached to elements
	_layout_handlers: [],
	addHandler: function(element, handler) {
		layout._layout_handlers.push({element:element,handler:handler});
		layout._last_layout_activity = new Date().getTime();
	},
	removeHandler: function(element, handler) {
		for (var i = 0; i < layout._layout_handlers.length; ++i) {
			if (layout._layout_handlers[i].element == element && layout._layout_handlers[i].handler == handler) {
				layout._layout_handlers.splice(i,1);
				i--;
			}
		}
	},
	_getHandlers: function(element) {
		var handlers = [];
		for (var i = 0; i < layout._layout_handlers.length; ++i)
			if (layout._layout_handlers[i].element == element)
				handlers.push(layout._layout_handlers[i].handler);
		return handlers;
	},
	
	// layout process
	_invalidated: [],
	_process_timeout: null,
	_multiple_process_counter: 0,
	invalidate: function(element) {
		if (element == null) {
			try { throw "null element given to layout.invalidate"; }
			catch (e) { log_exception(e); return; }
		}
		if (getWindowFromDocument(element.ownerDocument) != window) {
			getWindowFromDocument(element.ownerDocument).layout.invalidate(element);
			return;
		}
		if (!layout._invalidated.contains(element))
			layout._invalidated.push(element);
		layout._layout_needed();
	},
	
	_noresize_event: false,
	cancelResizeEvent: function() {
		this._noresize_event = true;
	},
	
	_layout_needed: function() {
		if (layout._process_timeout != null) return;
		var f = function() {
			layout._process_timeout = null;
			layout._process(); 
			if (layout._process_timeout != null) {
				// the processing raised the need of new layout
				layout._multiple_process_counter++;
				if (layout._multiple_process_counter < 3) {
					// ok, we continue fast: 1ms
				} else if (layout._multiple_process_counter <= 5) {
					// delay a little bit more to avoid too heavy processing: 10ms
					clearTimeout(layout._process_timeout);
					layout._process_timeout = setTimeout(f, 10);
				} else if (layout._multiple_process_counter < 10) {
					// delay even more: 50ms
					clearTimeout(layout._process_timeout);
					layout._process_timeout = setTimeout(f, 10);
				} else {
					// delay even more: 200ms
					clearTimeout(layout._process_timeout);
					layout._process_timeout = setTimeout(f, 10);
				}
			} else
				layout._multiple_process_counter = 0;
		};
		layout._process_timeout = setTimeout(f,1);
	},
	_process: function() {
		if (layout._invalidated.length == 0) return; // nothing to do
		/*
		 * Process:
		 *  1- layout the higher level elements (the parents/top elements)
		 *  2- layout each top element
		 *  3- for each top element, go through children to process layout in case they hanve a layout handler
		 *  4- in case a child changed, go back to step 2
		 */
		// check we don't have the full document
		var found = false;
		for (var i = 0; i < layout._invalidated.length; ++i)
			if (layout._invalidated[i].nodeName == 'BODY' || layout._invalidated[i].nodeName == "HTML") { found = true; break; }
		// find the higher level elements
		var top_elements = [];
		if (found) {
			top_elements.push(document.body);
		} else {
			// first, only keep the first handled container of invalidated elements
			var new_list = [];
			for (var i = 0; i < layout._invalidated.length; ++i) {
				var e = layout._invalidated[i];
				var handled = null;
				do {
					var h = layout._getHandlers(e);
					if (h.length > 0) { handled = e; break; }
					e = e.parentNode;
				} while (e && e.nodeName != 'BODY' && e.nodeName != 'HTML');
				if (handled == null) {
					// no container
					top_elements = [document.body];
					break;
					//handled = e;
				} else
					if (!new_list.contains(handled)) new_list.push(handled);
			}
			if (top_elements[0] != document.body) {
				top_elements.push(new_list[0]);
				for (var i = 1; i < new_list; ++i) {
					var e = new_list[i];
					// check if e is already contained in a top_elements
					var p = e.parentNode;
					var found = false;
					while (p != null && p.nodeName != 'BODY' && p.nodeName != 'HTML') {
						if (top_elements.contains(p)) { found = true; break; }
						p = p.parentNode;
					}
					if (found) continue; // it is contained, skip it
					// if e is a parent of some top_elements, remove those top_elements
					for (var j = 0; j < top_elements.length; ++j) {
						var te = top_elements[j];
						p = te.parentNode;
						found = false;
						while (p != null && p.nodeName != 'BODY' && p.nodeName != 'HTML') {
							if (p == e) { found = true; break; }
							p = p.parentNode;
						}
						if (found) {
							// it is contained, remove it
							top_elements.splice(j,1);
							j--;
						}
					}
					// add this new top element
					top_elements.push(e);
				}
			}
		}
		layout._invalidated = [];
		// process each top element
		for (var i = 0; i < top_elements.length; ++i) {
			var handlers = layout._getHandlers(top_elements[i]);
			for (var j = 0; j < handlers.length; ++j)
				handlers[j]();
			if (top_elements[i].scrollHeight != top_elements[i]._layout_scroll_height || top_elements[i].scrollWidth != top_elements[i]._layout_scroll_width) {
				top_elements[i]._layout_scroll_height = top_elements[i].scrollHeight;
				top_elements[i]._layout_scroll_width = top_elements[i].scrollWidth;
				// it changed, we may need to re-layout parents
				var p = top_elements[i].parentNode;
				while (p != null && p.nodeName != 'BODY' && p.nodeName != 'HTML') {
					var h = layout._getHandlers(p);
					if (h.length > 0) { layout.invalidate(p); break; }
					p = p.parentNode;
				}
				// if we are in a frame, let's layout the frame
				var win = getWindowFromDocument(top_elements[i].ownerDocument); 
				if (win.frameElement) getWindowFromDocument(win.frameElement.ownerDocument).layout.invalidate(win.frameElement);
			}
		}
		// process the children of the top elements
		for (var i = 0; i < top_elements.length; ++i) {
			layout._processElement(top_elements[i]);
		}
		// check if a top element changed after the children have been processed
		for (var i = 0; i < top_elements.length; ++i) {
			if (top_elements[i].scrollHeight != top_elements[i]._layout_scroll_height || top_elements[i].scrollWidth != top_elements[i]._layout_scroll_width) {
				top_elements[i]._layout_scroll_height = top_elements[i].scrollHeight;
				top_elements[i]._layout_scroll_width = top_elements[i].scrollWidth;
				// it changed, let's process again
				layout.invalidate(top_elements[i]);
				// we may need to re-layout parents
				var p = top_elements[i].parentNode;
				while (p != null && p.nodeName != 'BODY' && p.nodeName != 'HTML') {
					var h = layout._getHandlers(p);
					if (h.length > 0) { layout.invalidate(p); break; }
					p = p.parentNode;
				}
				// if we are in a frame, let's layout the frame
				var win = getWindowFromDocument(top_elements[i].ownerDocument); 
				if (win.frameElement) getWindowFromDocument(win.frameElement.ownerDocument).layout.invalidate(win.frameElement);
			}
		}
		// mark activity, to adjust automatic layout timing
		layout._last_layout_activity = new Date().getTime();
	},
	_processElement: function(element, call_handlers) {
		var handlers = layout._getHandlers(element);
		if (handlers.length > 0 && call_handlers) {
			for (var i = 0; i < handlers.length; ++i)
				handlers[i]();
			if (element.scrollHeight != element._layout_scroll_height || element.scrollWidth != element._layout_scroll_width) {
				// size changed, let's do it again later
				element._layout_scroll_height = element.scrollHeight;
				element._layout_scroll_width = element.scrollWidth;
				layout.invalidate(element);
				return;
			}
		}
		var children_changed = false;
		for (var i = 0; i < element.childNodes.length; ++i) {
			var c = element.childNodes[i];
			if (c.nodeType != 1) continue; // skip non-element nodes
			layout._processElement(c, true);
			if (handlers.length > 0 && (c.scrollHeight != c._layout_scroll_height || c.scrollWidth != c._layout_scroll_width)) {
				c._layout_scroll_height = c.scrollHeight;
				c._layout_scroll_width = c.scrollWidth;
				children_changed = true;
			}
		}
		if (children_changed) {
			// at least one child changed its size: we need to re-process this parent
			layout.invalidate(element);
		} else if (handlers.length > 0 && call_handlers) {
			if (element.scrollHeight != element._layout_scroll_height || element.scrollWidth != element._layout_scroll_width) {
				// size changed, let's do it again later
				element._layout_scroll_height = element.scrollHeight;
				element._layout_scroll_width = element.scrollWidth;
				layout.invalidate(element);
				return;
			}
		}

	},
	
	// Layout activity and regular layout done to handle scroll bars changes
	_last_layout_activity: 0,
	_layout_auto: function() {
		/*
		// go through all handled elements, and check if size of the element or one of its children changed
		for (var i = 0; i < layout._layout_handlers.length; ++i) {
			var e = layout._layout_handlers[i].element;
			if (e.scrollHeight != e._layout_scroll_height || e.scrollWidth != e._layout_scroll_width) {
				// it changed
				layout.invalidate(e);
				continue;
			}
			for (var j = 0; j < e.childNodes.length; ++j) {
				var c = e.childNodes[j];
				if (c.nodeType != 1) continue;
				if (c.scrollHeight != c._layout_scroll_height || c.scrollWidth != c._layout_scroll_width) {
					// it changed
					layout.invalidate(e);
					break;
				}
			}
		}
		*/
		// try to find new images that may invalidate the layouts
		var images = document.getElementsByTagName("IMG");
		for (var i = 0; i < images.length; ++i) {
			var img = images[i];
			if (img._layout_done) continue; // already processed
			img._layout_done = true;
			if (img.complete || img.height != 0) continue; // already loaded
			// not yet loaded, add an event
			listenEvent(img,'load',function() {
				if (this.parentNode)
					layout.invalidate(this.parentNode);
				else
					this._layout_done = false;
			});
		}
	}
};

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
		img[i]._layout_done = true;
		listenEvent(img[i],'load',_all_images_loaded);
	}
}

function _layout_auto() {
	layout._layout_auto();
	var now = new Date().getTime();
	var timing;
	if (now - layout._last_layout_activity < 5000) timing = 1000;
	else if (now - layout._last_layout_activity < 10000) timing = 2000;
	else if (now - layout._last_layout_activity < 20000) timing = 4000;
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
	var listener = function(ev) {
		if (layout._noresize_event) {
			unlistenEvent(window,'resize',listener);
			return;
		}
		layout.invalidate(document.body); 
	};
	listenEvent(window, 'resize', listener);
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
	if (ss == null)
		return {
			borderLeftWidth: 0, borderRightWidth: 0, borderTopWidth: 0, borderBottomWidth: 0,
			marginLeft: 0, marginRight: 0, marginTop: 0, marginBottom: 0,
			paddingTop: 0, paddingBottom: 0, paddingLeft: 0, paddingRight: 0
		};
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
if (!window.top.browser_scroll_bar_size) {
	window.top.browser_scroll_bar_size = 20;
	var container = window.top.document.createElement("DIV");
	container.style.position = "fixed";
	container.style.top = "-300px";
	container.style.width = "100px";
	container.style.height = "100px";
	container.style.overflow = "scroll";
	window.top.document.body.appendChild(container);
	window.top.browser_scroll_bar_size = 100 - container.clientWidth;
	window.top.document.body.removeChild(container);
}
