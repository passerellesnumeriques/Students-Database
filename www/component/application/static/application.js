if (window == window.top) {
	/**
	 * @class window.top.pnapplication
	 */
	window.pnapplication = {
		/** list of windows */
		windows: [],
		/** register a new window */
		register_window: function(w) { 
			window.top.pnapplication.windows.push(w);
			listenEvent(w,'click',function(ev){
				for (var i = 0; i < window.top.pnapplication.onclick_listeners.length; ++i)
					window.top.pnapplication.onclick_listeners[i][1](ev, w, window.top.pnapplication.onclick_listeners[i][0]);
			});
		},
		/** unregister a window (when it is closed) */
		unregister_window: function(w) {
			window.top.pnapplication.windows.remove(w);
			for (var i = 0; i < this.onclick_listeners.length; ++i)
				if (this.onclick_listeners[i][0] == w) {
					this.onclick_listeners.splice(i,1);
					i--;
				}
			for (var i = 0; i < this.app_events_listeners.length; ++i) {
				if (this.app_events_listeners[i][0] == w) {
					this.app_events_listeners.splice(i,1);
					i--;
				}
			}
		},
		onclick_listeners: [],
		register_onclick: function(from_window, listener) {
			this.onclick_listeners.push([from_window,listener]);
		},
		unregister_onclick: function(listener) {
			for (var i = 0; i < this.onclick_listeners.length; ++i)
				if (this.onclick_listeners[i][1] == listener) {
					this.onclick_listeners.splice(i,1);
					break;
				}
		},
		app_events_listeners: [],
		register_app_event_listener: function(from_window, listener_id, listener) {
			this.app_events_listeners.push([from_window,listener_id,listener]);
		},
		unregister_app_event_listener: function(id) {
			for (var i = 0; i < this.app_events_listeners.length; ++i)
				if (this.app_events_listeners[i][1] == id) {
					this.app_events_listeners.splice(i,1);
					break;
				}
		},
		raise_app_event: function(listener_id, data) {
			for (var i = 0; i < this.app_events_listeners.length; ++i)
				if (this.app_events_listeners[i][1] == listener_id) {
					this.app_events_listeners[i][2](data);
					break;
				}
		},
		/** Event when the whole application is closing */
		onclose: new Custom_Event(),
		close_window: function() {
			window.top.pnapplication.onclose.fire();
		},
		/** time of the last activity of the user */
		last_activity: new Date().getTime(),
		/** signals the user is active: fire onactivity event on each window */
		user_is_active: function() {
			for (var i = 0; i < window.top.pnapplication.windows.length; ++i)
				window.top.pnapplication.windows[i].pnapplication.onactivity.fire();
			window.top.pnapplication.last_activity = new Date().getTime();
		},
		/** check if the user is not inactive since long time: if this is the case, automatically logout */
		check_inactivity: function() {
			var time = new Date().getTime();
			time -= window.top.pnapplication.last_activity;
			for (var i = 0; i < window.top.pnapplication.windows.length; ++i) {
				if (window.top.pnapplication.windows[i].closed) {
					window.top.pnapplication.unregister_window(window.top.pnapplication.windows[i]);
					window.top.pnapplication.check_inactivity();
					return;
				}
				for (var j = 0; j < window.top.pnapplication.windows[i].pnapplication._inactivity_listeners.length; ++j) {
					var il = window.top.pnapplication.windows[i].pnapplication._inactivity_listeners[j];
					if (il.time <= time)
						il.listener();
				}
			}
		}
	};
} else if (typeof Custom_Event != 'undefined'){
	/**
	 * @class window.pnapplication
	 */
	window.pnapplication = {
		/** event fired when user activity is detected */
		onactivity: new Custom_Event(),
		/** event fired when the current window is closing */
		onclose: new Custom_Event(),
		/** indicates the current window is closing */
		close_window: function() {
			this.onclose.fire();
			window.top.pnapplication.unregister_window(window);
		},
		_inactivity_listeners: [],
		add_inactivity_listener: function(inactivity_time, listener) {
			this._inactivity_listeners.push({time:inactivity_time,listener:listener});
		},
		register_app_event_listener: function(type, identifier, listener, handler) {
			service.json("application","register_app_event",{type:type,identifier:identifier},function(result){
				if (result) {
					window.top.pnapplication.register_app_event_listener(window, result.id, listener);
					if (handler)
						handler(result.id);
				}
			});
		},
		unregister_app_event_listener: function(id) {
			window.top.pnapplication.unregister_app_event_listener(id);
			service.json("application","unregister_app_event",{id:id},function(){});
		}
	};
	window.top.pnapplication.register_window(window);
}

function init_pnapplication() {
	if (typeof listenEvent == 'undefined' || window.top.frames.length == 0)
		setTimeout(init_pnapplication, 100);
	else {
		var listener = function() {
			if (!window || !window.top || !window.top.pnapplication) return;
			window.top.pnapplication.user_is_active();
		};
		listenEvent(window,'click',listener);
		listenEvent(window,'mousemove',listener);
		window.onbeforeunload = function() {
			if (window.pnapplication)
				window.pnapplication.close_window();
		};
		if (window==window.top)
			setInterval(window.pnapplication.check_inactivity, 2000);
	}
};
init_pnapplication();


// override add_javascript and add_stylesheet
window._add_javascript_original = window.add_javascript;
window.add_javascript = function(url, onload) {
	if (!window.top._loading_application_status) {
		if (window.top.StatusMessage) {
			window.top._loading_application_status = new window.top.StatusMessage(window.top.Status_TYPE_PROCESSING, "Loading...");
			window.top._loading_application_nb = 0;
		} else {
			window._add_javascript_original(url, onload);
			return;
		}
	}
	var p = new URL(url).path;
	var load = !_scripts_loaded.contains(p);
	if (load) {
		window.top._loading_application_nb++;
		if (window.top._loading_application_nb == 1)
			window.top.status_manager.add_status(window.top._loading_application_status);
	}
	window._add_javascript_original(url, function() {
		if (onload) onload();
		if (load) {
			window.top._loading_application_nb--;
			if (window.top._loading_application_nb == 0)
				window.top.status_manager.remove_status(window.top._loading_application_status);
		}
	});
};
