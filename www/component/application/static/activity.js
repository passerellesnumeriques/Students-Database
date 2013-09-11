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
			window.top.pnapplication.check_inactivity();
		},
		/** check if the user is not inactive since long time: if this is the case, automatically logout */
		check_inactivity: function() {
			var time = new Date().getTime();
			time -= window.top.pnapplication.last_activity;
			if (window.top.frames.length == 0) return;
			var status = window.top.frames[0].document.getElementById('inactivity_status');
			if (status == null) return;
			clearInterval(window.top.pnapplication.update_inactivity_interval);
			if (time < 10000) {
				status.style.visibility = 'hidden';
				status.style.position = 'absolute';
				window.top.pnapplication.update_inactivity_interval = setInterval(window.top.pnapplication.check_inactivity, 2000);
			} else if (time > 30*60*1000) {
				window.top.frames[0].location = "/dynamic/application/page/logout?from=inactivity";
			} else {
				status.style.visibility = 'visible';
				var t = window.top.frames[0].document.getElementById('inactivity_time');
				var s = "";
				if (time >= 60*1000) {
					s += Math.floor(time/(60*1000))+"m";
					time = time % (60*1000);
				}
				s += Math.floor(time/1000)+"s";
				t.innerHTML = s;
				window.top.pnapplication.update_inactivity_interval = setInterval(window.top.pnapplication.check_inactivity, 1000);
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
			window.pnapplication.update_inactivity_interval = setInterval(window.pnapplication.check_inactivity, 2000);
	}
};
init_pnapplication();
