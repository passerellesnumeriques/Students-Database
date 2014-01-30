/** Manage locks on current window, and globally on the application */
window.databaselock = {
	/** Register a lock, present on the page, so when the page is closed, the lock will be released automatically
	 * @param {Number} id lock id
	 */
	add_lock: function(id) {
		for (var i = 0; i < this._locks.length; ++i)
			if (this._locks[i].id == id) return;
		if (window != window.top) {
			this._locks.push({id:id});
			for (var i = 0; i < window.top.databaselock._locks.length; ++i)
				if (window.top.databaselock._locks[i].id == id)
					return;
		}
		window.top.databaselock._locks.push({
			id: id,
			time: new Date().getTime()
		});
	},
	/** Unregister a lock
	 * @param {Number} id lock id
	 */
	remove_lock: function(id) {
		for (var i = 0; i < window.top.databaselock._locks.length; ++i)
			if (window.top.databaselock._locks[i].id == id)
				window.top.databaselock._locks.splice(i,1);
		for (var i = 0; i < this._locks.length; ++i)
			if (this._locks[i].id == id)
				this._locks.splice(i,1);
	},
	
	/** List of locks on the application */
	_locks: [],
	/** Called when the user is inactive, so that we can release the locks and redirect the user to the home page */
	_user_inactive: function() {
		if (this._locks.length == 0) return;
		var locks = [];
		for (var i = 0; i < this._locks.length; ++i)
			locks.push(this._locks[i].id);
		service.json("data_model","unlock",{locks:locks},function(result){
			window.top.frames[0].location.href = "/dynamic/application/page/enter";
		});
	},
	/** Release a lock
	 * @param {Number} id lock id
	 * @param {Boolean} foreground blocking mode or asynchronous mode
	 */
	_close_lock: function(id,foreground) {
		service.json("data_model","unlock",{lock:id},function(result){
		},foreground);
		this.remove_lock(id);
	},
	/** Called when the window is closed, so we can release all the locks */
	_close_window: function() {
		while (this._locks.length > 0)
			this._close_lock(this._locks[0].id, true);
	}
};

/** Initialize functionalities: register to events on the application to detect user inactivity, and window close */
function initDatabaselock() {
	if (typeof window.pnapplication == 'undefined') {
		setTimeout(initDatabaselock, 100);
		return;
	}
	var w = window;
	window.pnapplication.onactivity.add_listener(function() {
		for (var i = 0; i < w.databaselock._locks.length; ++i)
			w.databaselock._locks[i].time = new Date().getTime();
	});
	window.pnapplication.onclose.add_listener(function() {
		w.databaselock._close_window();
	});
	window.pnapplication.addInactivityListener(2*60*1000, function() {
		if (window.databaselock._has_popup) return;
		var now = new Date().getTime();
		var popup = false;
		window.databaselock._has_popup = true;
		for (var i = 0; i < window.databaselock._locks.length; ++i) {
			if (now - window.databaselock._locks[i].time >= 2*60*1000) {
				add_javascript("/static/widgets/popup_window.js",function() {
					var p = new popup_window("You are inactive",theme.icons_16.warning,null,true);
					p.setContentFrame("/static/data_model/databaselock_inactivity.html");
					p.onclose = function() {
						window.databaselock._has_popup = false;
					};
					p.show();
				});
				popup = true;
				break;
			} else
				ajax.post_parse_result("/dynamic/data_model/service/update_db_lock","id="+window.databaselock._locks[i].id,function(result){});
		}
		if (!popup)
			window.databaselock._has_popup = false;
	});
}
if (window.top != window)
	initDatabaselock();