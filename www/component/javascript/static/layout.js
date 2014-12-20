/**
 * Functionalities to automatically adjust the layout
 */
window.layout = {
	/**
	 * Listen when the size of an element changed
	 * @param {Element} element the element we want to watch
	 * @param {Function} listener the function to called when the element changed size
	 */
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
	/**
	 * Listen when something changed inside an element (something added, removed, changed its size...)
	 * @param {Element} element the element to watch
	 * @param {Function} listener the function to call
	 */
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
	/** Stop listening
	 * @param {Element} element the element which was watched
	 * @param {Function} listener the function previously given to listenElementSizeChanged
	 */
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
	/** Stop listening
	 * @param {Element} element the element which was watched
	 * @param {Function} listener the function previously given to listenInnerElementsChanged
	 */
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
	/**
	 * Signal some changes have been done on this element
	 * @param {Element} element the element which has been changed
	 */
	changed: function(element) {
		if (this._changes === null) return;
		if (element == null) {
			window.top.console.error("null element given to layout.changed");
			return;
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
		if (!element.id) element.id = generateID();
		layout._changes.push(element);
		layout._layout_needed();
	},
	
	/** Force to re-compute the layout */
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
	
	/** Listeners for element size */
	_element_size_listeners: [],
	/** Listeners for modifications */
	_element_inside_listeners: [],
	/** List of changes made */
	_changes: [],
	
	/** The layout should not process */
	_paused: false,
	/** Ask to do not process layout anymore */
	pause: function() {
		this._paused = true;
	},
	/** Ask to resume processing the layout, after a previous call to pause */
	resume: function() {
		this._paused = false;
		this._layout_needed();
	},
	/** Indicates if the layout is processing or not
	 * @returns {Boolean} true if the layout is not processing
	 */
	isPaused: function() { return this._paused; },
	
	/** timeout used to fire processing of layout */
	_process_timeout: null,
	/** counter to know if we are getting a lot of layout processing in a short time */
	_layouts_short_time: 0,
	/** Called when we will need to process the layout */
	_layout_needed: function() {
		if (layout._process_timeout != null || this._paused) return;
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
	/** Called to process the layout events */
	_process: function() {
		if (window.closing || layout._changes === null || this._paused) return;
		if (layout._changes.length == 0) return; // nothing to do
		// filter changes
		var changes = layout._changes;
		layout._changes = [];
		if (window.frameElement && window.frameElement.style && (window.frameElement.style.visibility == 'hidden' || window.frameElement.style.display == "none")) return;
		var list = {};
		for (var i = 0; i < changes.length; ++i) {
			if (typeof list[changes[i].id] != 'undefined') continue; // duplicate
			if (changes.length < 1000) {
				var p = changes[i];
				var hidden = false;
				while (p != null && p.nodeName != 'BODY' && p.nodeName != 'HTML') {
					if (p.style && (p.style.visibility == "hidden" || p.style.display == "none")) { hidden = true; break; }
					p = p.parentNode;
				}
				if (hidden) continue;
			}
			layout._changes.push(changes[i]);
			list[changes[i].id] = true;
		}
		changes = null;
		list = null;
		
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
	/** Given a list of elements, return only the leaves, meaning the deepest elements in the DOM among them
	 * @param {Array} elements the list of element
	 * @returns {Array} the deepest one (no element can be the parent of another one)
	 */
	_getLeavesElements: function(elements) {
		var leaves = [];
		var leaves_ids = {};
		var parents = [];
		var parents_ids = {};
		for (var i = 0; i < elements.length; ++i) {
			leaves.push(elements[i]);
			leaves_ids[elements[i].id] = true;
			var p = elements[i].parentNode;
			if (!p) continue;
			if (!p.id) p.id = generateID();
			else if (typeof parents_ids[p.id] != 'undefined') parents_ids[p.id] = true;
			parents.push(p);
			parents_ids[p.id] = true;
		}
		parents_ids = null;
		while (parents.length > 0) {
			var new_parents = [];
			var new_parents_ids = {};
			for (var i = 0; i < parents.length; ++i) {
				if (parents[i].id) {
					if (typeof leaves_ids[parents[i].id] != 'undefined') {
						leaves.removeUnique(parents[i]);
						delete leaves_ids[parents[i].id];
					}
				}
				var p = parents[i].parentNode;
				if (!p) continue;
				if (!p.id) p.id = generateID();
				else if (typeof new_parents[p.id] != 'undefined') continue;
				new_parents.push(p);
				new_parents_ids[p.id] = true;
			}
			parents = new_parents;
		}
		return leaves;
	},
	/** Process the listeners of inner element changed */
	_processInsideChanged: function(elements) {
		var parents = [];
		var parents_ids = {};
		for (var i = 0; i < elements.length; ++i) {
			if (elements[i]._layout_info && elements[i]._layout_info.from_inside)
				for (var j = 0; j < elements[i]._layout_info.from_inside.length; ++j)
					elements[i]._layout_info.from_inside[j]();
			if (elements[i].nodeName == "BODY") continue;
			var p = elements[i].parentNode;
			if (!p) continue;
			if (!p.id) p.id = generateID();
			else if (typeof parents_ids[p.id] != 'undefined') continue;
			parents.push(p);
			parents_ids[p.id] = true;
		}
		if (parents.length == 0) return;
		layout._processInsideChanged(parents);
	},
	
	/** Indicate we should not fire event if the window is resized */
	_noresize_event: false,
	/** Do not re-layout when the window is resized. May be useful on IFRAME being already handled by a layout functionality */
	cancelResizeEvent: function() {
		this._noresize_event = true;
	},
	
	/** Calculate the best size to contain the given BODY
	 * @param {Element} body the BODY
	 * @param {Boolean} keep_styles if true, we won't try to temporarly adjust the style to be able to compute the best size
	 * @returns {Object} x,y: the width and height
	 */
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

	/**
	 * Automatically resize the given frame, according to its content, and re-compute each time the content changed
	 * @param {Element} frame the frame
	 * @param {Function} onresize called when we updated the frame size because its content changed
	 */
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
			// check if supposed to be height 100%
			var first_child = null;
			for (var i = 0; i < win.document.body.childNodes.length; ++i) {
				var c = win.document.body.childNodes[i];
				if (c.nodeType != 1) continue;
				if (c.nodeName == "SCRIPT" || c.nodeName == "STYLE") continue;
				first_child = c;
				break;
			}
			if (first_child && first_child.style.height == "100%" && size.y > frame.parentNode.clientHeight)
				frame.style.height = frame.parentNode.clientHeight+"px";
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
	/** Stop to automatically resize the frame (with a previous call to autoResizeIFrame)
	 * @param {Element} frame the frame
	 */
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
	
	/**
	 * Check if everything is loaded on the page (Javascripts, CSS, images)
	 * @returns {Boolean} true if done
	 */
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
			if (img._layout_done || img.hasAttribute("no_wait")) continue; // already processed
			if (img.complete || img.height != 0) continue; // already loaded
			if (img._bg) continue; // background loading
			return false;
		}
		return true;
	},
	/**
	 * In case the function everythingOnPageLoaded returns false, indicates what is still pending
	 * @returns {String} the URL of the pending element
	 */
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
	
	/** List of functions to be called to modify the DOM */
	_dom_modifications: [],
	/** List of functions to read the layout information */
	_layout_reading: [],
	/** Timeout used to fire processing */
	_process_steps_timeout: null,
	/** Process the dom modifications and layout reading listeners */
	_process_steps: function() {
		if (this._process_steps_timeout) return;
		this._process_steps_timeout = setTimeout(function() {
			if (window.closing || !layout) return;
			layout._process_steps_timeout = null;
			while (layout._dom_modifications.length > 0) {
				var list = layout._dom_modifications;
				layout._dom_modifications = [];
				for (var i = 0; i < list.length; ++i)
					list[i]();
			}
			while (layout._layout_reading.length > 0) {
				var list = layout._layout_reading;
				layout._layout_reading = [];
				for (var i = 0; i < list.length; ++i)
					list[i]();
			}
			if (layout._dom_modifications.length > 0 || layout._layout_reading.length > 0)
				layout._process_steps();
		},1);
	},
	
	/** Functionality needing 3 steps to process. See function modifyDOM for more info.
	 * @param {Function} step1_dom_modification step 1
	 * @param {Function} step2_layout_reading step 2
	 * @param {Function} step3_dom_modification step 3
	 */
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
	/** Functionality needing 2 steps to process. See function modifyDOM for more info.
	 * @param {Function} step1_layout_reading step 1
	 * @param {Function} step2_dom_modification step 2
	 */
	two_steps_process: function(step1_layout_reading, step2_dom_modification) {
		this._layout_reading.push(function() {
			var r1 = step1_layout_reading();
			layout._dom_modifications.push(function() { step2_dom_modification(r1); });
		});
		this._process_steps();
	},
	/** When working on the DOM, what can make the performance very poor is the alternance between modifying the DOM
	 * and styles, and reading layout information (i.e. clientWidth, scrollHeight, style...).. This is because each time
	 * we modify the DOM, the browser knowns that the layout may have changed and need to be processed. But each time
	 * we read layout information, it forces the browser to re-compute the layout immediately to be able to give the correct
	 * layout information. This may be very slow if done too many times.
	 * In order to optimize, we need to try to first do as much as possible modifications without reading any layout information,
	 * then read as mush as possible the layout information without modifying anything, and so on...
	 * 
	 * @param {Function} handler function that will do DOM modifications, without reading any layout information
	 */
	modifyDOM: function(handler) {
		this._dom_modifications.push(handler);
		this._process_steps();
	},
	/** See modifyDOM for more exlaination
	 * @param {Function} handler function that will read layout information without modifying anything
	 */
	readLayout: function(handler) {
		this._layout_reading.push(handler);
		this._process_steps();
	},
	
	/** Cleanup before unloading page, and avoid memory leaks */
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
/** @no_doc */
window.layout._w = window;
/** List of functions to call before to close/unload a window, so that we can clean-up and avoid memory leaks */
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