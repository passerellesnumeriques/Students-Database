window.database_locks = {
	add_lock: function(id) {
		for (var i = 0; i < this._locks.length; ++i)
			if (this._locks[i].id == id) return;
		if (window != window.top) {
			this._locks.push({id:id});
			for (var i = 0; i < window.top.database_locks._locks.length; ++i)
				if (window.top.database_locks._locks[i].id == id)
					return;
		}
		window.top.database_locks._locks.push({
			id: id,
			time: new Date().getTime()
		});
	},
	remove_lock: function(id) {
		for (var i = 0; i < window.top.database_locks._locks.length; ++i)
			if (window.top.database_locks._locks[i].id == id)
				window.top.database_locks._locks.splice(i,1);
		for (var i = 0; i < this._locks.length; ++i)
			if (this._locks[i].id == id)
				this._locks.splice(i,1);
	},
	
	_locks: [],
	_user_inactive: function() {
		var remaining = this._locks.length;
		if (remaining == 0) return;
		var closed = function() {
			if (--remaining == 0)
				window.top.frames[0].location.href = "/dynamic/application/page/enter";
		};
		for (var i = 0; i < this._locks.length; ++i)
			service.json("data_model","unlock",{lock:this._locks[i].id},function(result){
				setTimeout(closed,1);
			});
	},
	_close_lock: function(id,foreground) {
		service.json("data_model","unlock",{lock:id},function(result){
		},foreground);
		this.remove_lock(id);
	},
	_close_window: function() {
		while (this._locks.length > 0)
			this._close_lock(this._locks[0].id, true);
	}
};

function init_databaselock() {
	if (typeof window.pnapplication == 'undefined') {
		setTimeout(init_databaselock, 100);
		return;
	}
	var w = window;
	window.pnapplication.onactivity.add_listener(function() {
		for (var i = 0; i < w.database_locks._locks.length; ++i)
			w.database_locks._locks[i].time = new Date().getTime();
	});
	window.pnapplication.onclose.add_listener(function() {
		w.database_locks._close_window();
	});
	window.pnapplication.add_inactivity_listener(2*60*1000, function() {
		if (window.database_locks._has_popup) return;
		var now = new Date().getTime();
		var popup = false;
		window.database_locks._has_popup = true;
		for (var i = 0; i < window.database_locks._locks.length; ++i) {
			if (now - window.database_locks._locks[i].time >= 2*60*1000) {
				add_javascript("/static/widgets/popup_window.js",function() {
					var p = new popup_window("You are inactive",theme.icons_16.warning,null,true);
					p.setContentFrame("/static/data_model/databaselock_inactivity.html");
					p.onclose = function() {
						window.database_locks._has_popup = false;
					};
					p.show();
				});
				popup = true;
				break;
			} else
				ajax.post_parse_result("/dynamic/data_model/service/update_db_lock","id="+window.database_locks._locks[i].id,function(result){});
		}
		if (!popup)
			window.database_locks._has_popup = false;
	});
}
if (window.top != window)
	init_databaselock();