layout = {
	// Layout handlers attached to elements
	_layout_handlers: [],
	_w: window,
	addHandler: function(element, handler) {
		var w = getWindowFromElement(element);
		if (w != layout._w) {
			w.layout.addHandler(element, handler);
			return;
		}
		layout._layout_handlers.push({element:element,handler:handler});
		layout._last_layout_activity = new Date().getTime();
	},
	removeHandler: function(element, handler) {
		var w = getWindowFromElement(element);
		if (!w || !w.layout) return;
		if (w != window) {
			w.layout.removeHandler(element, handler);
			return;
		}
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
	invalidate: function(element) {
		if (element == null) {
			try { throw new Error("null element given to layout.invalidate"); }
			catch (e) { log_exception(e); return; }
		}
		if (window.frameElement && window.frameElement.style && window.frameElement.style.visibility == 'hidden') return;
		var p = element;
		while (p != null && p.nodeName != 'BODY' && p.nodeName != 'HTML') {
			if (p.style && p.style.visibility == "hidden") return;
			p = p.parentNode;
		}
		var w = getWindowFromElement(element);
		if (w != window) {
			w.layout.invalidate(element);
			return;
		}
		if (!layout._invalidated.contains(element)) {
			layout._invalidated.push(element);
			layout._layout_needed();
		}
	},
	
	_noresize_event: false,
	cancelResizeEvent: function() {
		this._noresize_event = true;
	},
	
	forceLayout: function() {
		if (layout._process_timeout) clearTimeout(layout._process_timeout);
		layout._process_timeout = null;
		layout._process();
		if (layout._invalidated.length > 0) {
			if (layout._process_timeout) clearTimeout(layout._process_timeout);
			layout._process_timeout = null;
			layout._process();
		}
		if (layout._invalidated.length > 0) {
			if (layout._process_timeout) clearTimeout(layout._process_timeout);
			layout._process_timeout = null;
			layout._process();
		}
	},
	
	_process_timeout: null,
	_layouts_short_time: 0,
	_layout_needed: function() {
		if (layout._process_timeout != null) return;
		var f = function() {
			if (layout._last_layout_activity < new Date().getTime() - 1000)
				layout._layouts_short_time = 0;
			layout._process_timeout = null;
			layout._process();
		};
		var timing = 1; // by default 1ms
		if (layout._last_layout_activity > new Date().getTime() - 1000) {
			layout._layouts_short_time++;
			if (layout._layouts_short_time < 2)
				timing = 10; // first time, delay a little: 10ms
			else if (layout._layouts_short_time < 4)
				timing = 50; // start to have a lot, delay of 50ms
			else
				timing = 300; // a lot: delay of 300ms
		} else {
			layout._layouts_short_time = 0;
		}
		layout._process_timeout = setTimeout(f,timing);
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
				// or siblings
				p = top_elements[i].previousSibling;
				while (p != null) {
					var h = layout._getHandlers(p);
					if (h.length > 0) { layout.invalidate(p); break; }
					p = p.previousSibling;
				}
				p = top_elements[i].nextSibling;
				while (p != null) {
					var h = layout._getHandlers(p);
					if (h.length > 0) { layout.invalidate(p); break; }
					p = p.nextSibling;
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
				// size changed, let's do it again later, from its parent
				element._layout_scroll_height = element.scrollHeight;
				element._layout_scroll_width = element.scrollWidth;
				if (element.nodeName != "BODY")
					layout.invalidate(element.parentNode);
				else
					layout.invalidate(element);
				//return;
			}
		}
		var children_changed = false;
		for (var i = 0; i < element.childNodes.length; ++i) {
			var c = element.childNodes[i];
			if (c.nodeType != 1) continue; // skip non-element nodes
			var prev_w = c.scrollWidth;
			var prev_h = c.scrollHeight;
			layout._processElement(c, true);
			if (handlers.length > 0 && (c.scrollHeight != prev_h || c.scrollWidth != prev_w)) {
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
				// size changed, let's do it again later, from its parent
				element._layout_scroll_height = element.scrollHeight;
				element._layout_scroll_width = element.scrollWidth;
				layout.invalidate(element.parentNode);
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
	},
	
	computeContentSize: function(body) {
		var win = getWindowFromElement(body);
		var max_width = 0, max_height = 0;
		for (var i = 0; i < body.childNodes.length; ++i) {
			var e = body.childNodes[i];
			var w = null, h = null;
			if (e.nodeType != 1) continue;
			if (e.nodeName == "SCRIPT") continue;
			if (e.style && e.style.position && (e.style.position == "absolute" || e.style.position == "fixed")) continue;
			if (e.nodeName == "FORM") {
				var size = layout.computeContentSize(e);
				w = win.absoluteLeft(e) + size.x;
				h = win.absoluteTop(e) + size.y;
			} else {
				e._display = e.style && e.style.display ? e.style.display : "";
				e._whiteSpace = e.style && e.style.whiteSpace ? e.style.whiteSpace : "";
				e._width = e.style && e.style.width ? e.style.width : "";
				e._height = e.style && e.style.height ? e.style.height : "";
				e.style.display = 'inline-block';
				e.style.whiteSpace = 'nowrap';
				if (e._width.indexOf('%') == -1)
					e.style.width = "";
				if (e._height.indexOf('%') == -1)
					e.style.height = "";
			}
			if (w == null) w = win.absoluteLeft(e)+(win.getWidth ? win.getWidth(e) : getWidth(e));
			if (w > max_width) max_width = w;
			if (h == null) h = win.absoluteTop(e)+(win.getHeight ? win.getHeight(e) : getHeight(e));
			if (h > max_height) max_height = h;
			if (e.nodeName != "FORM") {
				e.style.display = e._display;
				e.style.whiteSpace = e._whiteSpace;
				e.style.width = e._width;
				e.style.height = e._height;
			}
		}
		return {x:max_width, y:max_height};
	},

	autoResizeIFrame: function(frame, onresize) {
		var resize = function() {
			var win = getIFrameWindow(frame);
			if (!win) return;
			frame.style.position = "absolute";
			frame.style.width = "10000px";
			frame.style.height = "10000px";
			var size = layout.computeContentSize(win.document.body);
			frame.style.position = "static";
			frame.style.width = size.x+"px";
			frame.style.height = size.y+"px";
			if (onresize) onresize(frame);
		};
		var check_ready = function() {
			// check the frame is still there
			var p = frame.parentNode;
			while (p != null && p.nodeName != 'BODY') p = p.parentNode;
			if (!p) return;
			var win = getIFrameWindow(frame);
			if (!win || !win.layout || !win._page_ready) {
				setTimeout(check_ready, 10);
				return;
			}
			var b = win.document.body;
			win.layout.cancelResizeEvent();
			win.layout.addHandler(b, resize);
			for (var i = 0; i < b.childNodes.length; ++i) 
				win.layout.addHandler(b.childNodes[i], resize);
			resize();
		};
		check_ready();
		listenEvent(frame, 'load', check_ready);
	},
	
	everythingOnPageLoaded: function() {
		var head = document.getElementsByTagName("HEAD")[0];
		for (var i = 0; i < head.childNodes.length; ++i) {
			var e = head.childNodes[i];
			if (e.nodeType != 1) continue;
			if (e.nodeName == "SCRIPT" && e.src && e.src != "" && !e._loaded && !e._bg) return false;
			if (e.nodeName == "LINK" && !e._loaded) return false;
		}
		var images = document.getElementsByTagName("IMG");
		for (var i = 0; i < images.length; ++i) {
			var img = images[i];
			if (img._layout_done) continue; // already processed
			if (img.complete || img.height != 0) continue; // already loaded
			if (img._bg) continue; // background loading
			return false;
		}
		return true;
	},
	whatIsNotYetLoaded: function() {
		var head = document.getElementsByTagName("HEAD")[0];
		for (var i = 0; i < head.childNodes.length; ++i) {
			var e = head.childNodes[i];
			if (e.nodeType != 1) continue;
			if (e.nodeName == "SCRIPT" && e.src && e.src != "" && !e._loaded && !e._bg) return e.src;
			if (e.nodeName == "LINK" && !e._loaded) return e.href;
		}
		var images = document.getElementsByTagName("IMG");
		for (var i = 0; i < images.length; ++i) {
			var img = images[i];
			if (img._layout_done) continue; // already processed
			if (img.complete || img.height != 0) continue; // already loaded
			if (img._bg) continue; // background loading
			return img.src;
		}
		return null;
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
		if (document.body)
			layout.invalidate(document.body); 
	};
	listenEvent(window, 'resize', listener);
}

var _layout_add_css = window.addStylesheet;
window.addStylesheet = function(url, onload) {
	_layout_add_css(url, function() {
		layout.invalidate(document.body);
		if (onload) onload();
	});
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
