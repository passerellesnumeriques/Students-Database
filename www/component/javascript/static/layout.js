window.layout = {
	listenElementSizeChanged: function(element, listener) {
		var w = getWindowFromElement(element);
		if (!w) return;
		if (w != layout._w) {
			if (w.layout)
				w.layout.listenElementSizeChanged(element, listener);
			return;
		}
		if (typeof element._layout_info == 'undefined')
			element._layout_info = {};
		if (typeof element._layout_info.size == 'undefined')
			element._layout_info.size = {scrollWidth:element.scrollWidth, scrollHeight:element.scrollHeight, clientWidth: element.clientWidth, clientHeight: element.clientHeight};
		layout._element_size_listeners.push({element:element,listener:listener});
	},
	listenInnerElementsChanged: function(element, listener) {
		var w = getWindowFromElement(element);
		if (!w) return;
		if (w != layout._w) {
			if (w.layout)
				w.layout.listenInnerElementsChanged(element, listener);
			return;
		}
		if (typeof element._layout_info == 'undefined')
			element._layout_info = {};
		if (typeof element._layout_info.from_inside == 'undefined')
			element._layout_info.from_inside = [];
		if (element._layout_info.from_inside.contains(listener)) return;
		element._layout_info.from_inside.push(listener);
		layout._element_inside_listeners.push({element:element,listener:listener});
	},
	unlistenElementSizeChanged: function(element, listener) {
		var w = getWindowFromElement(element);
		if (!w) return;
		if (!layout._w) return;
		if (w != layout._w) {
			if (w.layout)
				w.layout.unlistenElementSizeChanged(element, listener);
			return;
		}
		for (var i = 0; i < layout._element_size_listeners.length; ++i)
			if (layout._element_size_listeners[i].element == element && layout._element_size_listeners[i].listener == listener) {
				layout._element_size_listeners.splice(i,1);
				i--;
			}
	},
	unlistenInnerElementsChanged: function(element, listener) {
		var w = getWindowFromElement(element);
		if (!w) return;
		if (w != layout._w) {
			if (w.layout)
				w.layout.unlistenInnerElementsChanged(element, listener);
			return;
		}
		for (var i = 0; i < layout._element_inside_listeners.length; ++i)
			if (layout._element_inside_listeners[i].element == element && layout._element_inside_listeners[i].listener == listener) {
				layout._element_inside_listeners.splice(i,1);
				i--;
			}
		if (element._layout_info && element._layout_info.from_inside)
			element._layout_info.from_inside.remove(listener);
	},
	changed: function(element) {
		if (this._changes === null) return;
		if (element == null) {
			try { throw new Error("null element given to layout.changed"); }
			catch (e) { log_exception(e); return; }
		}
		if (typeof getWindowFromElement == 'undefined') {
			setTimeout(function(){layout.changed(element);},25);
			return;
		}
		var w = getWindowFromElement(element);
		if (w != layout._w) {
			w.layout.changed(element);
			return;
		}
		layout._changes.push(element);
		layout._layout_needed();
	},
	
	forceLayout: function() {
		if (layout._process_timeout) clearTimeout(layout._process_timeout);
		layout._process_timeout = null;
		layout._process();
		if (layout._changes.length > 0) {
			if (layout._process_timeout) clearTimeout(layout._process_timeout);
			layout._process_timeout = null;
			layout._process();
		}
		if (layout._changes.length > 0) {
			if (layout._process_timeout) clearTimeout(layout._process_timeout);
			layout._process_timeout = null;
			layout._process();
		}
	},
	
	_element_size_listeners: [],
	_element_inside_listeners: [],
	_changes: [],
	
	_paused: false,
	pause: function() {
		this._paused = true;
	},
	resume: function() {
		this._paused = false;
		this._layout_needed();
	},
	
	_process_timeout: null,
	_layouts_short_time: 0,
	_layout_needed: function() {
		if (layout._process_timeout != null) return;
		var f = function() {
			if (window.closing) return;
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
		if (window.closing || layout._changes === null || this._paused) return;
		if (layout._changes.length == 0) return; // nothing to do
		// filter changes
		var changes = layout._changes;
		layout._changes = [];
		if (window.frameElement && window.frameElement.style && (window.frameElement.style.visibility == 'hidden' || window.frameElement.style.display == "none")) return;
		for (var i = 0; i < changes.length; ++i) {
			if (layout._changes.contains(changes[i])) continue; // duplicate
			var p = changes[i];
			var hidden = false;
			while (p != null && p.nodeName != 'BODY' && p.nodeName != 'HTML') {
				if (p.style && (p.style.visibility == "hidden" || p.style.display == "none")) { hidden = true; break; }
				p = p.parentNode;
			}
			if (hidden) continue;
			layout._changes.push(changes[i]);
		}
		changes = null;
		
		// first, process the elements inside changed, starting from the leaves of the tree
		if (layout._element_inside_listeners.length > 0)
			layout._processInsideChanged(layout._getLeavesElements(layout._changes));
		// reset changes
		//var changes = layout._changes;
		layout._changes = [];
		if (layout._process_timeout) {
			clearTimeout(layout._process_timeout);
			layout._process_timeout = null;
		}
		// then, process the element size changed
		if (layout._element_size_listeners.length > 0) {
			var to_call = [];
			var sizes = [];
			var list = [];
			for (var i = 0; i < layout._element_size_listeners.length; ++i) {
				list.push(layout._element_size_listeners[i]);
				var e = layout._element_size_listeners[i].element;
				var size = {scrollWidth:e.scrollWidth,scrollHeight:e.scrollHeight,clientWidth:e.clientWidth,clientHeight:e.clientHeight};
				sizes.push(size);
				if (size.scrollWidth != e._layout_info.size.scrollWidth || size.scrollHeight != e._layout_info.size.scrollHeight ||
					size.clientWidth != e._layout_info.size.clientWidth || size.clientHeight != e._layout_info.size.clientHeight)
					if (!to_call.contains(layout._element_size_listeners[i].listener))
						to_call.push(layout._element_size_listeners[i].listener);
			}
			for (var i = 0; i < layout._element_size_listeners.length; ++i)
				layout._element_size_listeners[i].element._layout_info.size = sizes[i];
			for (var i = 0; i < to_call.length; ++i)
				to_call[i]();
		}
		
		layout._last_layout_activity = new Date().getTime();
	},
	_getLeavesElements: function(elements) {
		var leaves = [];
		var parents = [];
		for (var i = 0; i < elements.length; ++i) {
			leaves.push(elements[i]);
			var p = elements[i].parentNode;
			if (!p) continue;
			parents.push(p);
		}
		while (parents.length > 0) {
			var new_parents = [];
			for (var i = 0; i < parents.length; ++i) {
				var j = leaves.indexOf(parents[i]);
				if (j != -1) leaves.splice(j,1);
				var p = parents[i].parentNode;
				if (p && !new_parents.contains(p)) new_parents.push(p); 
			}
			parents = new_parents;
		}
		return leaves;
	},
	_processInsideChanged: function(elements) {
		var parents = [];
		for (var i = 0; i < elements.length; ++i) {
			if (elements[i]._layout_info && elements[i]._layout_info.from_inside)
				for (var j = 0; j < elements[i]._layout_info.from_inside.length; ++j)
					elements[i]._layout_info.from_inside[j]();
			if (elements[i].nodeName == "BODY") continue;
			var p = elements[i].parentNode;
			if (p && !parents.contains(p)) parents.push(p);
		}
		if (parents.length == 0) return;
		layout._processInsideChanged(parents);
	},
	
	_noresize_event: false,
	cancelResizeEvent: function() {
		this._noresize_event = true;
	},
	
	computeContentSize: function(body,keep_styles) {
		var win = getWindowFromElement(body);
		var max_width = 0, max_height = 0;
		for (var i = 0; i < body.childNodes.length; ++i) {
			var e = body.childNodes[i];
			var w = null, h = null;
			if (e.nodeType != 1) continue;
			if (e.nodeName == "SCRIPT") continue;
			if (e.nodeName == "STYLE") continue;
			if (e.style && e.style.position && (e.style.position == "absolute" || e.style.position == "fixed")) continue;
			if (e.nodeName == "FORM") {
				var size = layout.computeContentSize(e);
				w = win.absoluteLeft(e) + size.x;
				h = win.absoluteTop(e) + size.y;
			} else if (!keep_styles) {
				e._display = e.style && e.style.display ? e.style.display : "";
				//e._whiteSpace = e.style && e.style.whiteSpace ? e.style.whiteSpace : "";
				e._width = e.style && e.style.width ? e.style.width : "";
				e._height = e.style && e.style.height ? e.style.height : "";
				e.style.display = 'inline-block';
				//e.style.whiteSpace = 'nowrap';
				if (e._width.indexOf('%') == -1)
					e.style.width = "";
				if (e._height.indexOf('%') == -1)
					e.style.height = "";
			}
			var knowledge = [];
			if (w == null) w = win.absoluteLeft(e)+(win.getWidth ? win.getWidth(e,knowledge) : getWidth(e,knowledge));
			if (w > max_width) max_width = w;
			if (h == null) h = win.absoluteTop(e)+(win.getHeight ? win.getHeight(e,knowledge) : getHeight(e,knowledge));
			if (h > max_height) max_height = h;
			if (e.nodeName != "FORM" && !keep_styles) {
				e.style.display = e._display;
				//e.style.whiteSpace = e._whiteSpace;
				e.style.width = e._width;
				e.style.height = e._height;
			}
		}
		return {x:max_width, y:max_height};
	},

	autoResizeIFrame: function(frame, onresize) {
		if (frame._autoresize) return;
		frame._autoresize = function() {
			if (!frame._check_ready) return; // stopped
			var win = getIFrameWindow(frame);
			if (!win) return;
			frame.style.position = "absolute";
			var win_container = getWindowFromElement(frame);
			frame.style.width = Math.floor(win_container.getWindowWidth()*0.95)+"px";
			frame.style.height = Math.floor(win_container.getWindowHeight()*0.95)+"px";
			var size = layout.computeContentSize(win.document.body);
			frame.style.width = size.x+"px";
			frame.style.height = size.y+"px";
			frame.style.position = "static";
			// check again the size
			var size2 = layout.computeContentSize(win.document.body,true);
			if (size2.y > size.y)
				frame.style.height = size2.y+"px";
			if (size2.x > size.x)
				frame.style.width = size2.x+"px";
			if (onresize) onresize(frame);
			if (frame._loading_frame) frame._loading_frame._position();
		};
		frame._check_ready = function() {
			// check the frame is still there
			var p = frame.parentNode;
			while (p != null && p.nodeName != 'BODY') p = p.parentNode;
			if (!p) return;
			var win = getIFrameWindow(frame);
			if (!win || !win.layout || !win._page_ready) {
				setTimeout(frame._check_ready, 10);
				return;
			}
			if (!frame._check_ready) return; // stopped
			var b = win.document.body;
			win.layout.cancelResizeEvent();
			win.layout.listenElementSizeChanged(b, frame._autoresize);
			for (var i = 0; i < b.childNodes.length; ++i) 
				win.layout.listenElementSizeChanged(b.childNodes[i], frame._autoresize);
			frame._autoresize();
		};
		frame._check_ready();
		listenEvent(frame, 'load', frame._check_ready);
	},
	stopResizingIFrame: function(frame) {
		if (!frame._check_ready) return;
		unlistenEvent(frame, 'load', frame._check_ready);
		frame._check_ready = null;
		var win = getIFrameWindow(frame);
		if (win && win.layout && win.document && win.document.body) {
			win.layout.unlistenElementSizeChanged(win.document.body, frame._autoresize);
			win.layout._no_resize_event = false;
		}
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
	},
	
	_dom_modifications: [],
	_layout_reading: [],
	_process_steps_timeout: null,
	_process_steps: function() {
		if (this._process_steps_timeout) return;
		this._process_steps_timeout = setTimeout(function() {
			layout._process_steps_timeout = null;
			var dom_changed = false;
			while (layout._dom_modifications.length > 0) {
				dom_changed = true;
				layout._dom_modifications[0]();
				layout._dom_modifications.splice(0,1);
			}
			if (dom_changed) { layout._process_steps(); return; }
			while (layout._layout_reading.length > 0) {
				layout._layout_reading[0]();
				layout._layout_reading.splice(0,1);
			}
			if (layout._dom_modifications.length > 0 || layout._layout_reading.length > 0)
				layout._process_steps();
		},1);
	},
	
	three_steps_process: function(step1_dom_modification, step2_layout_reading, step3_dom_modification) {
		this._dom_modifications.push(function() {
			var r1 = step1_dom_modification();
			layout._layout_reading.push(function() {
				var r2 = step2_layout_reading(r1);
				layout._dom_modifications.push(function() { step3_dom_modification(r2); });
			});
		});
		this._process_steps();
	},
	two_steps_process: function(step1_layout_reading, step2_dom_modification) {
		this._layout_reading.push(function() {
			var r1 = step1_layout_reading();
			layout._dom_modifications.push(function() { step2_dom_modification(r1); });
		});
		this._process_steps();
	},
	modifyDOM: function(handler) {
		this._dom_modifications.push(handler);
		this._process_steps();
	},
	readLayout: function(handler) {
		this._layout_reading.push(handler);
		this._process_steps();
	},
	
	cleanup: function() {
		if (!this._w) return;
		if (this._w._layout_interval) clearInterval(this._w._layout_interval);
		this._w._layout_interval = null;
		if (layout._process_timeout) clearTimeout(layout._process_timeout);
		layout._process_timeout = null;
		this._noresize_event = true;
		this._element_size_listeners = null;
		this._element_inside_listeners = null;
		this._changes = null;
		this._w = null;
	}
};
window.layout._w = window;
if (!window.to_cleanup) window.to_cleanup = [];
window.to_cleanup.push(layout);

// fire layout when an image is loaded and when window size change

var _resize_triggered_on_images_loaded = false;
var _all_images_loaded_timeout = null;
function _all_images_loaded() {
	if (window.closing) return;
	_all_images_loaded_timeout = null;
	var img = document.getElementsByTagName("IMG");
	for (var i = 0; i < img.length; ++i) {
		if (img[i].complete || img[i].height != 0) continue;
		return;
	}
	if (!_resize_triggered_on_images_loaded) {
		_resize_triggered_on_images_loaded = true;
		setTimeout(function() {
			if (window.closing) return;
			_resize_triggered_on_images_loaded = false;
			triggerEvent(window, 'resize');
		},10);
	}
}
function _init_images() {
	var img = document.getElementsByTagName("IMG");
	for (var i = 0; i < img.length; ++i) {
		img[i]._layout_done = true;
		listenEvent(img[i],'load',function() {
			if (window.closing) return;
			if (!layout) return;
			if (_all_images_loaded_timeout) return;
			_all_images_loaded_timeout = setTimeout(_all_images_loaded,10);
		});
	}
}

function _layout_auto() {
	// due to scroll bars, and flex box model, which can resize elements without notice, we re-check regularly
	/*for (var i = 0; i < layout._element_size_listeners.length; ++i) {
		var e = layout._element_size_listeners[i].element;
		var size = {scrollWidth:e.scrollWidth,scrollHeight:e.scrollHeight,clientWidth:e.clientWidth,clientHeight:e.clientHeight};
		if (size.scrollWidth != e._layout_info.size.scrollWidth || size.scrollHeight != e._layout_info.size.scrollHeight ||
			size.clientWidth != e._layout_info.size.clientWidth || size.clientHeight != e._layout_info.size.clientHeight)
			layout.changed(e);
	}*/
	// try to find new images that may invalidate the layouts
	var images = document.getElementsByTagName("IMG");
	for (var i = 0; i < images.length; ++i) {
		var img = images[i];
		if (img._layout_done) continue; // already processed
		img._layout_done = true;
		if (img.complete || img.height != 0) {
			// already loaded
			layout.changed(img);
			continue;
		}
		// not yet loaded, add an event
		listenEvent(img,'load',function() {
			if (this.parentNode) {
				this._layout_done = true;
				if (window.closing) return;
				if (!layout) return;
				layout.changed(this);
			} else
				this._layout_done = false;
		});
	}
	// reschedule
	var now = new Date().getTime();
	var timing;
	if (now - layout._last_layout_activity < 1000) timing = 2000;
	else if (now - layout._last_layout_activity < 5000) timing = 3500;
	else if (now - layout._last_layout_activity < 20000) timing = 5000;
	else timing = 10000;
	if (_layout_interval_time != timing) {
		clearInterval(_layout_interval);
		_layout_interval = setInterval(_layout_auto, timing);
		_layout_interval_time = timing;
	}
}
var _layout_interval_time = 5000;
var _layout_interval = setInterval(_layout_auto,5000);

if (typeof listenEvent != 'undefined') {
	listenEvent(window, 'load', function() {
		if (_all_images_loaded_timeout) return;
		_all_images_loaded_timeout = setTimeout(_all_images_loaded,10);
	});
	_init_images();
	var listener = function(ev) {
		if (!layout) return;
		if (layout._noresize_event) {
			unlistenEvent(window,'resize',listener);
			return;
		}
		if (document.body)
			layout.changed(document.body); 
	};
	listenEvent(window, 'resize', listener);
}

var _layout_add_css = window.addStylesheet;
window.addStylesheet = function(url, onload) {
	_layout_add_css(url, function() {
		if (!document.body || window.closing) return;
		layout.changed(document.body);
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