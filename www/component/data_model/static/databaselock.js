/** Manage locks on current window, and globally on the application */
window.databaselock = {
	/** Register a lock, present on the page, so when the page is closed, the lock will be released automatically
	 * @param {Number} id lock id
	 */
	addLock: function(id) {
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
			win: window
		});
	},
	/** Unregister a lock
	 * @param {Number} id lock id
	 */
	removeLock: function(id) {
		for (var i = 0; i < window.top.databaselock._locks.length; ++i)
			if (window.top.databaselock._locks[i].id == id) {
				window.top.databaselock._locks.splice(i,1);
				break;
			}
		for (var i = 0; i < this._locks.length; ++i)
			if (this._locks[i].id == id)
				this._locks.splice(i,1);
	},
	/**
	 * Call the unlock service for the given id. If it succeed, the removeLock function will be called.
	 * @param {Number} id the id of the lock
	 * @param {Function} handler the function to call once done, with the result as parameter (true on succcess)
	 */
	unlock: function(id, handler) {
		service.json("data_model", "unlock", {lock:id}, function(res) {
			if (res) databaselock.removeLock(id);
			handler(res);
		});
	},
	
	/** List of locks on the application */
	_locks: [],
	/** Called when the user is inactive, so that we can release the locks and redirect the user to the home page */
	_userInactive: function() {
		if (this._locks.length == 0) return;
		var locks = [];
		var windows = [];
		for (var i = 0; i < this._locks.length; ++i) {
			locks.push(this._locks[i].id);
			if (!windows.contains(this._locks[i].win))
				windows.push(this._locks[i].win);
		}
		this._locks = [];
		service.json("data_model","unlock",{locks:locks},function(result){
			var need_redirection = false;
			for (var i = 0; i < windows.length; ++i) {
				if (windows[i].onuserinactive) {
					// if there is a function handling it
					windows[i].onuserinactive();
				} else if (windows[i].frameElement && windows[i].parent.getPopupFromElement && windows[i].parent.getPopupFromElement(windows[i].frameElement)) {
					// if in popup, close it
					windows[i].parent.getPopupFromElement(windows[i].frameElement).close();
				} else {
					need_redirection = true;
					break;
				}
			}
			if (need_redirection)
				window.top.frames[0].location.href = "/dynamic/application/page/enter";
		});
	},
	/** Called when the window is closed, so we can release all the locks */
	_closeWindow: function() {
		var ids = [];
		for (var i = 0; i < this._locks.length; ++i) {
			ids.push(this._locks[i].id);
			for (var j = 0; j < window.top.databaselock._locks.length; ++j)
				if (window.top.databaselock._locks[j].id == this._locks[i].id) {
					window.top.databaselock._locks.splice(j,1);
					break;
				}
		}
		if (ids.length > 0)
			service.json("data_model","unlock",{locks:ids},function(result){},true);
	},
	/** Called regularely, to extend the expiration time of the lock still used */
	_update: function() {
		var ids = [];
		for (var i = 0; i < this._locks.length; ++i) ids.push(this._locks[i].id);
		if (ids.length > 0)
			service.json("data_model","update_locks",{locks:ids},function(res){});
	}
};

/** Initialize functionalities: register to events on the application to detect user inactivity, and window close */
function initDatabaselock() {
	if (typeof window.pnapplication == 'undefined') {
		setTimeout(initDatabaselock, 100);
		return;
	}
	var w = window;
	window.pnapplication.onclose.addListener(function() {
		w.databaselock._closeWindow();
		w = null;
	});
	window.pnapplication.addInactivityListener(3*60*1000, function() {
		if (window.databaselock._has_popup) return;
		var popup = false;
		window.databaselock._has_popup = true;
		if (window.databaselock._locks.length > 0) {
			addJavascript("/static/widgets/popup_window.js",function() {
				var p = new popup_window("You are inactive",theme.icons_16.warning,null,true);
				p.setContentFrame("/static/data_model/databaselock_inactivity.html");
				p.onclose = function() {
					window.databaselock._has_popup = false;
				};
				p.show();
			});
			popup = true;
		}
		if (!popup)
			window.databaselock._has_popup = false;
	});
}
if (window.top != window)
	initDatabaselock();
else
	setInterval(function() { window.top.databaselock._update(); }, 30000);