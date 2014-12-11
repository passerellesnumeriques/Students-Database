/**
 * Initialize: create structure, listen click, mousemove, beforeunload
 */
function initPNApplication() {
	if (typeof listenEvent == 'undefined' || window.top.frames.length == 0 || typeof Custom_Event == 'undefined') {
		setTimeout(initPNApplication, 20);
		return;
	}
	var activity_listener = function() {
		if (!window || !window.top || !window.top.pnapplication || !window.top.pnapplication.userIsActive) return;
		window.top.pnapplication.userIsActive();
	};
	if (window == window.top) {
		if (typeof window.top.pnapplication == 'undefined') {
		/**
		 * Handle windows events at application level: list of frames, event when a frame is closed, user activity...
		 */
		window.top.pnapplication = {
			/** Event raised when the user logout, so we can clean some objects that may be on the top window */
			onlogout: new Custom_Event(),
			/** Event raised when the user login */
			onlogin: new Custom_Event(),
			/** Indicates if the user is currently logged or not */
			logged_in: false,
			/** list of windows (private: registerWindow and unregisterWindow must be used) */
			_windows: [],
			/** event when a window/frame is closed. The window is given as parameter to the listeners. */ 
			onwindowclosed: new Custom_Event(),
			_window_click_listener: function(ev) {
				for (var i = 0; i < window.top.pnapplication._onclick_listeners.length; ++i)
					window.top.pnapplication._onclick_listeners[i][1](ev, ev.view, window.top.pnapplication._onclick_listeners[i][0]);
			},
			_window_mousemove_listener: function(ev){
				if (!window.top.pnapplication) return;
				var w_pos = getAbsoluteCoordinatesRelativeToWindowTop(ev.view);
				var cev = getCompatibleMouseEvent(ev);
				for (var i = 0; i < window.top.pnapplication._onmousemove_listeners.length; ++i) {
					var target = window.top.pnapplication._onmousemove_listeners[i][0];
					var listener = window.top.pnapplication._onmousemove_listeners[i][1];
					var target_pos = getAbsoluteCoordinatesRelativeToWindowTop(target);
					var x = cev.x + w_pos.x - target_pos.x;
					var y = cev.y + w_pos.y - target_pos.y;
					listener(x,y);
				}
			},
			_window_mouseup_listener: function(ev){
				for (var i = 0; i < window.top.pnapplication._onmouseup_listeners.length; ++i)
					window.top.pnapplication._onmouseup_listeners[i][1](ev, ev.view, window.top.pnapplication._onmouseup_listeners[i][0]);
			}, 
			/** register a new window
			 * @param {window} w new window/frame 
			 */
			registerWindow: function(w) { 
				if (w.frameElement && !w.frameElement._loading_frame)
					new LoadingFrame(w.frameElement);
				if (w.frameElement && w.frameElement._loading_frame)
					w.frameElement._loading_frame.startLoading();
				window.top.pnapplication._windows.push(w);
				listenEvent(w,'click',window.top.pnapplication._window_click_listener);
				listenEvent(w,'mousemove',window.top.pnapplication._window_mousemove_listener);
				listenEvent(w,'mouseup',window.top.pnapplication._window_mouseup_listener);
			},
			_reserved_names: [],
			/** unregister a window (when it is closed)
			 * @param {window} w window/frame which has been closed 
			 */
			unregisterWindow: function(w) {
				if (w.frameElement && !w.frameElement._loading_frame)
					new LoadingFrame(w.frameElement);
				if (w.frameElement && w.frameElement._loading_frame)
					w.frameElement._loading_frame.startUnloading();
				window.top.pnapplication._windows.remove(w);
				unlistenEvent(w,'click',window.top.pnapplication._window_click_listener);
				unlistenEvent(w,'mousemove',window.top.pnapplication._window_mousemove_listener);
				unlistenEvent(w,'mouseup',window.top.pnapplication._window_mouseup_listener);
				for (var i = 0; i < this._onclick_listeners.length; ++i)
					if (this._onclick_listeners[i][0] == w) {
						this._onclick_listeners.splice(i,1);
						i--;
					}
				for (var i = 0; i < this._onmousemove_listeners.length; ++i)
					if (this._onmousemove_listeners[i][0] == w) {
						this._onmousemove_listeners.splice(i,1);
						i--;
					}
				for (var i = 0; i < this._onmouseup_listeners.length; ++i)
					if (this._onmouseup_listeners[i][0] == w) {
						this._onmouseup_listeners.splice(i,1);
						i--;
					}
				for (var id in this._customs) {
					for (var i = 0; i < this._customs[id].length; ++i)
						if (this._customs[id][i].win == w) {
							this._customs[id].splice(i,1);
							i--;
						}
				}
				w.closing = true;
				window.top.pnapplication.onwindowclosed.fire({top:window.top,win:w});
				// clean up
				w.document.body.removeAllChildren();
				if (w.to_cleanup) {
					var list = [];
					for (var i = 0; i < w.to_cleanup.length; ++i) list.push(w.to_cleanup[i]);
					w.to_cleanup = [];
					for (var i = 0; i < list.length; ++i)
						if (list[i] && list[i].cleanup) list[i].cleanup();
					w.to_cleanup = null;
				}
				for (var name in w)
					if (name != "closing" && name != "name" && name != "frameElement" &&
						name != "self" && name != "parent" && name != "top" &&
						!window.top.pnapplication._reserved_names.contains(name) &&
						typeof Window.prototype[name] == 'undefined' &&
						name != "id" && name != "_domRemoved")
						w[name] = null;
				var head = w.document.getElementsByTagName("HEAD");
				if (head && head.length > 0) {
					head = head[0];
					while (head.childNodes.length > 0)
						head.removeChild(head.childNodes[0]);
				}
				w.self = null;
				w.frameElement = null;
				w.document = null;
				for (var name in w) if (name != "closing" && name != "name" && name != "_domRemoved") try { w[name] = null; } catch(e){}
			},
			
			/** List of listeners to be called when the user clicks somewhere in the application. (private: registerOnclick and unregisterOnclick must be used) */
			_onclick_listeners: [],
			/** Register the given listener, which will be called when the user clicks somewhere in the application (not only on the window, but on all frames)
			 * @param {window} from_window window containing the listener (used to automatically remove the listener when the window is closed)
			 * @param {Function} listener function to be called
			 */
			registerOnclick: function(from_window, listener) {
				this._onclick_listeners.push([from_window,listener]);
			},
			/** Remove a listener, previously registered by registerOnclick
			 * @param {Function} listener function to be removed, previously registered through registerOnclick 
			 */
			unregisterOnclick: function(listener) {
				for (var i = 0; i < this._onclick_listeners.length; ++i)
					if (this._onclick_listeners[i][1] == listener) {
						this._onclick_listeners.splice(i,1);
						break;
					}
			},
			/** List of listeners to be called when the user moves the mouse somewhere in the application.*/
			_onmousemove_listeners: [],
			/** Register the given listener, which will be called when the user moves the mouse somewhere in the application (not only on the window, but on all frames)
			 * @param {window} from_window window containing the listener (used to automatically remove the listener when the window is closed)
			 * @param {Function} listener function to be called, it takes 2 parameters: <code>x</code> and <code>y</code>
			 */
			registerOnMouseMove: function(from_window, listener) {
				this._onmousemove_listeners.push([from_window,listener]);
			},
			/** Remove a listener, previously registered by registerOnMouseMove
			 * @param {Function} listener function to be removed, previously registered through registerOnMouseMove 
			 */
			unregisterOnMouseMove: function(listener) {
				for (var i = 0; i < this._onmousemove_listeners.length; ++i)
					if (this._onmousemove_listeners[i][1] == listener) {
						this._onmousemove_listeners.splice(i,1);
						break;
					}
			},
			/** List of listeners to be called when the a mouse button goes up somewhere in the application.*/
			_onmouseup_listeners: [],
			/** Register the given listener
			 * @param {window} from_window window containing the listener (used to automatically remove the listener when the window is closed)
			 * @param {Function} listener function to be called
			 */
			registerOnMouseUp: function(from_window, listener) {
				this._onmouseup_listeners.push([from_window,listener]);
			},
			/** Remove a listener, previously registered by registerOnMouseUp
			 * @param {Function} listener function to be removed, previously registered through registerOnMouseUp 
			 */
			unregisterOnMouseUp: function(listener) {
				for (var i = 0; i < this._onmouseup_listeners.length; ++i)
					if (this._onmouseup_listeners[i][1] == listener) {
						this._onmouseup_listeners.splice(i,1);
						break;
					}
			},
			
			_customs: {},
			registerCustom: function(win, id, value) {
				if (!this._customs[id])
					this._customs[id] = [];
				this._customs[id].push({win:win,value:value});
			},
			getCustoms: function(id) {
				var values = [];
				if (this._customs[id])
					for (var i = 0; i < this._customs[id].length; ++i)
						values.push(this._customs[id][i].value);
				return values;
			},
			
			/** Event when the whole application is closing */
			onclose: new Custom_Event(),
			/** Called when the top window is closing, meaning the application */
			closeWindow: function() {
				window.top.pnapplication.onclose.fire();
			},
			/** event fired when user activity is detected */
			onactivity: new Custom_Event(),
			/** time of the last activity of the user */
			last_activity: new Date().getTime(),
			/** signals the user is active: fire onactivity event on each window */
			userIsActive: function() {
				for (var i = 0; i < window.top.pnapplication._windows.length; ++i)
					if (window.top.pnapplication._windows[i].pnapplication) window.top.pnapplication._windows[i].pnapplication.onactivity.fire();
				window.top.pnapplication.last_activity = new Date().getTime();
			},
			/** check if the user is not inactive since long time: if this is the case, automatically logout */
			checkInactivity: function() {
				var time = new Date().getTime();
				time -= window.top.pnapplication.last_activity;
				for (var i = 0; i < window.top.pnapplication._windows.length; ++i) {
					if (window.top.pnapplication._windows[i] == window.top) continue;
					if (window.top.pnapplication._windows[i].closed) {
						window.top.pnapplication.unregisterWindow(window.top.pnapplication._windows[i]);
						window.top.pnapplication.checkInactivity();
						return;
					}
					if (window.top.pnapplication._windows[i].pnapplication)
						for (var j = 0; j < window.top.pnapplication._windows[i].pnapplication._inactivity_listeners.length; ++j) {
							var il = window.top.pnapplication._windows[i].pnapplication._inactivity_listeners[j];
							if (il.time <= time)
								il.listener();
						}
				}
			}
		};
		for (var name in Window.prototype)
			window.top.pnapplication._reserved_names.push(name);
		window.top.pnapplication.registerWindow(window.top);
		setInterval(window.top.pnapplication.checkInactivity, 2000);
		}
	} else {
		/**
		 * Handle events on the current window, transfered to the top window
		 */
		window.pnapplication = {
			/** event fired when user activity is detected */
			onactivity: new Custom_Event(),
			/** event fired when the current window is closing */
			onclose: new Custom_Event(),
			/** indicates the current window is closing */
			closeWindow: function() {
				unlistenEvent(window,'click',activity_listener);
				unlistenEvent(window,'mousemove',activity_listener);
				activity_listener = null;
				if (window.pnapplication._frame_unload_listener)
					unlistenEvent(window.frameElement, 'unload', window.pnapplication._frame_unload_listener);
				this.onclose.fire();
				if (window.top.pnapplication)
					window.top.pnapplication.unregisterWindow(window);
			},
			/** Internal list of {time,function} to call when the user is inactive */
			_inactivity_listeners: [],
			/**
			 * Register a listener to be called when the user is inactive for the given amount of time.
			 * @param {Number} inactivity_time time in milliseconds of the inactivity
			 * @param {Function} listener function to be called
			 */
			addInactivityListener: function(inactivity_time, listener) {
				this._inactivity_listeners.push({time:inactivity_time,listener:listener});
			},
			/** {Array} list of identifiers of data which need to be saved on the page */
			_data_unsaved: [],
			/** Indicates the the given data needs to be saved
			 * @param {String} id identifier of the data which must be unique
			 */
			dataUnsaved: function(id) {
				if (!this._data_unsaved.contains(id)) {
					this._data_unsaved.push(id);
					if (this._data_unsaved.length == 1) // first one
						this.ondatatosave.fire();
				}
			},
			/** Indicates the the given data has been saved
			 * @param {String} id identifier of the data which must be unique
			 */
			dataSaved: function(id) {
				this._data_unsaved.remove(id);
				if (this._data_unsaved.length == 0)
					this.onalldatasaved.fire();
			},
			/** Indicates if any data on the window needs to be saved
			 * @returns {Boolean} true if some data need to be saved
			 */
			hasDataUnsaved: function() { 
				return this._data_unsaved.length > 0; 
			},
			/** Check if there are data with given ID to be saved
			 * @param {String} id identifier of the data
			 * @returns {Boolean} true if the given data needs to be saved
			 */
			isDataUnsaved: function(id) {
				return this._data_unsaved.contains(id);
			},
			hasDataUnsavedStartingWith: function(start) {
				for (var i = 0; i < this._data_unsaved.length; ++i)
					if (this._data_unsaved[i].startsWith(start))
						return true;
				return false;
			},
			getDataUnsavedIds: function() { return this._data_unsaved; },
			dataSavedStartingWith: function(start) {
				for (var i = 0; i < this._data_unsaved.length; ++i)
					if (this._data_unsaved[i].startsWith(start)) {
						this._data_unsaved.splice(i,1);
						i--;
					}
				if (this._data_unsaved.length == 0)
					this.onalldatasaved.fire();
			},
			/** Mark all data as saved */
			cancelDataUnsaved: function() { 
				this._data_unsaved = [];
				this.onalldatasaved.fire();
			},
			/** Event raised when some data need to be saved */
			ondatatosave: new Custom_Event(),
			/** Event raised when no more data need to be saved */
			onalldatasaved: new Custom_Event(),
			autoDisableSaveButton: function(button) {
				if (typeof button == 'string') button = document.getElementById(button);
				button.disabled = this.hasDataUnsaved() ? "" : "disabled"; 
				this.ondatatosave.add_listener(function() { button.disabled = ""; });
				this.onalldatasaved.add_listener(function() { button.disabled = "disabled"; });
			}
		};
		var f = function() {
			if (!window.top.pnapplication) {
				setTimeout(f,25);
				return;
			}
			window.top.pnapplication.registerWindow(window);
			f = null;
		};
		f();
	}
		
	listenEvent(window,'click',activity_listener);
	listenEvent(window,'mousemove',activity_listener);
	window._windowCloseRaised = false;
	if (window.frameElement) {
		window.pnapplication._frame_unload_listener = function(ev) {
			if (window._windowCloseRaised === false && window.pnapplication) {
				window._windowCloseRaised = true;
				window.pnapplication.closeWindow();
			}
			unlistenEvent(window.frameElement, 'unload', window.pnapplication._frame_unload_listener);
			window.pnapplication._frame_unload_listener = null;
		};
		listenEvent(window.frameElement, 'unload', window.pnapplication._frame_unload_listener);
	}
	listenEvent(window, 'unload', function() {
		if (window._windowCloseRaised === false && window.pnapplication) {
			window._windowCloseRaised = true;
			window.pnapplication.closeWindow();
		}
	});
	var keep_browser = window.browser;
	listenEvent(window, 'beforeunload', function(ev) {
		if (window.pnapplication && window.pnapplication._data_unsaved && window.pnapplication._data_unsaved.length > 0) {
			ev.returnValue = "The page contains unsaved data";
			return "The page contains unsaved data";
		}
		if (window._windowCloseRaised === false && window.pnapplication) {
			window._windowCloseRaised = true;
			window.pnapplication.closeWindow();
		}
		if (keep_browser.IE == 0)
			return null;
	});
};
initPNApplication();

/**
 * Hide a frame which is loading, and display a loading image
 * @param {Element} frame_element the frame which is loading
 */
function LoadingFrame(frame_element) {
	if (!frame_element.ownerDocument) return;
	if (frame_element._no_loading) return;
	if (frame_element._loading_frame) return;
	frame_element._loading_frame = this;
	var t=this;
	/** {Number} Indicates what is the status of the frame: 0 for pending, 1 for loading, -1 for unloading */
	this.step = 0; // pending
	/** {Element} table containing the loading image in front of the frame */
	this.table = document.createElement("TABLE");
	this.table.innerHTML = "<tr><td valign=middle align=center><img src='/static/application/loading_page.gif'/></td></tr>";
	this.table.style.position = "absolute";
	var radius = getBorderRadius(frame_element);
	setBorderRadius(this.table, radius[0], radius[0], radius[1], radius[1], radius[3], radius[3], radius[2], radius[2]);
	var z = 1;
	var p = frame_element;
	do {
		var style = getComputedStyle(p);
		var zi = typeof style["z-index"] != 'undefined' ? style["z-index"] : typeof style.zIndex != 'undefined' ? style.zIndex : "auto";
		if (zi != "auto") { z = zi; break; }
		p = p.parentNode;
	} while (p && p.nodeName != "BODY" && p.nodeName != "HTML");
	p = null;
	this.table.style.zIndex = z;
	this.table.style.backgroundColor = "#d0d0d0";

	/** Check if the page inside the frame is ready (completely loaded) */
	this._isReady = function() {
		var win = getIFrameWindow(frame_element);
		if (!win) return false;
		if (win._static_page) return true;
		return this.step == 1 && win.document && win._page_ready && win.layout && win.layout._changes.length == 0 /*&& win.layout.everythingOnPageLoaded()*/;
	};
	/** Check if the page inside the frame has been closed */
	this._isClosed = function() {
		var win = getIFrameWindow(frame_element);
		return this.step == -1 && !win;
	};
	/** Check if the frame is inside a frozen popup window */
	this._inFrozenPopup = function() {
		var e = frame_element;
		while (e.parentNode != null && e.parentNode != e && e.parentNode != document.body && e.parentNode.className != 'popup_window') e = e.parentNode;
		if (e.parentNode != null && e.parentNode.className == 'popup_window') {
			var popup = e.parentNode.data;
			if (!popup) return false;
			if (popup.isFrozen()) return true;
		}
		return false;
	};

	/** {Animation} fadeIn */
	this.anim = null;
	/** {Boolean} indicates if the loading is hidden or not (because inside a frozen popup) */
	this._hidden = false;
	if (this._inFrozenPopup()) {
		setOpacity(this.table, 0);
		this._hidden = true;
	} else {
		if (typeof animation != 'undefined') {
			setOpacity(this.table, 0);
			frame_element.ownerDocument.body.appendChild(this.table);
			this.anim = animation.fadeIn(this.table, 250, null, 0, 90, function() {
				if (t._isClosed() || t._isReady()) animation.stop(t.anim);
			});
		} else {
			setOpacity(this.table, 90);
			frame_element.ownerDocument.body.appendChild(this.table);
		}
	}
	/** {Number} timestamp when the frame started to load */
	this._start = new Date().getTime();
	
	/** Indicates the frame starts to load */
	this.startLoading = function() {
		this.step = 1;
		this._start = new Date().getTime();
	};
	/** Indicates the frame starts to unload/change page */
	this.startUnloading = function() {
		this.step = -1;
		this._start = new Date().getTime();
	};
	
	/** Refresh the size and position of the loading, according to the size and position of the frame */
	this._position = function() {
		this.table.style.top = (absoluteTop(frame_element))+"px";
		this.table.style.left = (absoluteLeft(frame_element))+"px";
		this.table.style.width = frame_element.offsetWidth+"px";
		this.table.style.height = frame_element.offsetHeight+"px";
	};

	/** Call the _update function */
	var updater = function() { if(t) t._update(); };

	/** Remove the loading */
	this.remove = function() {
		if (frame_element._loading_frame != this) return;
		layout.unlistenElementSizeChanged(frame_element, updater);
		updater = null;
		if (this.anim) {
			animation.stop(this.anim);
			this.anim = null;
		}
		if (this.table.parentNode) {
			if (typeof animation != 'undefined') {
				animation.fadeOut(this.table, 200, function() {
					if (t.table && t.table.parentNode)
						t.table.parentNode.removeChild(t.table);
					t = null;
				});
			} else {
				this.table.parentNode.removeChild(this.table);
				t = null;
			}
		} else {
			t = null;
		}
		frame_element._loading_frame = null;
		frame_element = null;
		//console.log("Frame "+frame_element.name+" loaded in "+(new Date().getTime()-this._start)+"ms.");
	};
	
	/** Check what is the current status, and remove the loading if needed */
	this._update = function(renew) {
		if (!t || !frame_element) return;
		if (!frame_element.parentNode ||
			!frame_element.ownerDocument ||
			!getWindowFromDocument(frame_element.ownerDocument) ||
			getWindowFromDocument(frame_element.ownerDocument).closing ||
			window.closing
		) {
			// frame disappeared
			this.remove();
			return;
		}
		if (this._isReady()) {
			this.remove();
			return;
		}
		if (this._isClosed()) {
			this.remove();
			return;
		}
		if (this._hidden && !this._inFrozenPopup()) {
			// popup not anymore frozen: show the loading
			setOpacity(this.table, 1);
		}
		var now = new Date().getTime();
		if (now-this._start > 10000) {
			/*
			var win = getIFrameWindow(frame_element);
			if (!win) console.error("Frame loading timeout: window is null");
			else if (this.step == 1) {
				if (!win.document) console.error("Frame loading timeout: window.document is null");
				else if (!win._page_ready) console.error("Frame loading timeout: _page_ready is false");
				else if (!win.layout)  console.error("Frame loading timeout: no layout");
				else if (win.layout._changes.length > 0) console.error("Frame loading timeout: still something to layout");
				//else if (!win.layout.everythingOnPageLoaded()) console.error("Frame loading timeout: script, css or image not yet loaded: "+win.layout.whatIsNotYetLoaded());
			} else console.error("Frame loading timeout: step = "+this.step);
			*/
			this.remove();
			return;
		}
		this._position();
		if (renew)
			setTimeout(function() { if (t) t._update(true); }, now-this._start < 2000 ? 50 : 100);
	};
	
	this._position();
	this._update(true);
	setTimeout(updater, 10);
	layout.listenElementSizeChanged(frame_element, updater);
}

// override addJavascript and addStylesheet
//window._addJavascript_original = window.addJavascript;
//window.addJavascript = function(url, onload) {
//	if (!window.top._loading_application_status) {
//		if (window.top.StatusMessage) {
//			window.top._loading_application_status = new window.top.StatusMessage(window.top.Status_TYPE_PROCESSING, "Loading...");
//			window.top._loading_application_nb = 0;
//		} else {
//			window._addJavascript_original(url, onload);
//			return;
//		}
//	}
//	var p = new URL(url).path;
//	var load = !_scripts_loaded.contains(p);
//	if (load) {
//		window.top._loading_application_nb++;
//		if (window.top._loading_application_nb == 1)
//			window.top.status_manager.add_status(window.top._loading_application_status);
//	}
//	window._addJavascript_original(url, function() {
//		if (onload) onload();
//		if (load) {
//			window.top._loading_application_nb--;
//			if (window.top._loading_application_nb == 0)
//				window.top.status_manager.remove_status(window.top._loading_application_status);
//		}
//	});
//};
