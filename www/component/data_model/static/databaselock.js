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
	_check_time: 30000,
	_timeout_time: 120000,
	_check: function() {
		var now = new Date().getTime();
		var popup = false;
		var t = this;
		for (var i = 0; i < this._locks.length; ++i) {
			if (now - this._locks[i].time > this._timeout_time) {
				add_javascript("/static/widgets/popup_window.js",function() {
					var p = new popup_window("",null);
					p.setContentFrame("/static/data_model/databaselock_inactivity.html");
					p.onclose = function() {
						setTimeout("window.database_locks._check();", t._check_time);
					};
					p.show();
				});
				popup = true;
				break;
			} else
				ajax.post_parse_result("/dynamic/data_model/service/update_db_lock","id="+this._locks[i].id,function(result){});
		}
		if (!popup)
			setTimeout("window.database_locks._check();", this._check_time);		
	},
	_user_inactive: function() {
		var remaining = this._locks.length;
		if (remaining == 0) return;
		var closed = function() {
			if (--remaining == 0)
				window.top.frames[0].location.href = "/dynamic/application/page/enter";
		};
		for (var i = 0; i < this._locks.length; ++i)
			ajax.post_parse_result("/dynamic/data_model/service/close_db_lock","id="+this._locks[i].id,function(result){
				setTimeout(closed,1);
			});
	},
	_close_lock: function(id,foreground) {
		ajax.post_parse_result("/dynamic/data_model/service/close_db_lock","id="+id,function(result){
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
}
if (window.top == window)
	setTimeout("window.database_locks._check();",database_locks._check_time);
else
	init_databaselock();