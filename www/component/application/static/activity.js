if (window == window.top) {
	/**
	 * @class window.top.pnapplication
	 */
	window.pnapplication = {
		/** list of windows */
		windows: [],
		/** register a new window */
		register_window: function(w) { window.top.pnapplication.windows.push(w); },
		/** unregister a window (when it is closed) */
		unregister_window: function(w) {
			window.top.pnapplication.windows.remove(w);
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
