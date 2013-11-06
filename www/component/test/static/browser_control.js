browser_control = {
	
	win: null,
	frame: null,
	_wait: null,
	_ondone: null,
	_action: null,
	
	run: function(ondone, actions, onaction) {
		this._ondone = function(error) {
			if (browser_control.win) browser_control.win.close();
			ondone(error);
		};
		var next_action = function(pos) {
			if (pos == actions.length) {
				browser_control._ondone(null);
				return;
			}
			var action = actions[pos];
			browser_control._action = action[0];
			onaction(action[0]);
			var fct = browser_control[action[1]];
			if (fct == null) {
				browser_control._ondone(browser_control._action+": Invalid browser function '"+action[1]+"'");
				return;
			}
			var args = [];
			for (var i = 2; i < action.length; ++i) args.push(action[i]);
			args.push(function() {
				next_action(pos+1);
			});
			fct.apply(browser_control,args);
		};
		next_action(0);
	},
	
	_wait_time: 0,
	wait: function(onready) {
		if (this._wait == null || this._wait()) {
			this._wait = null;
			onready(); 
			return;
		}
		if (++this._wait_time == 100) {
			this._ondone(this._action+": Timeout");
			return;
		}
		setTimeout(function() { browser_control.wait(onready); }, 100);
	},
	
	start: function(onready) {
		this.win = null;
		var u = new URL(window.top.location.href);
		u.path = "/dynamic/application/page/logout";
		this.win = window.open(u.toString(), "_blank");
		this.frame = this.win;
		this._wait_time = 0,
		this._wait = function() { return browser_control.win && browser_control.win.pn_loading_end && !browser_control.win.pn_loading_visible; };
		this.wait(function() {
			browser_control.enter_frame("pn_application_frame", onready);
		});
	},
	
	execute_code: function(code, onready) {
		try { this.frame.eval(code); }
		catch (e) {
			this._ondone(this._action+": error executing code ["+code+"]: "+e);
			return;
		}
		onready();
	},
	
	sleep: function(time, onready) {
		setTimeout(onready, time);
	},
	
	enter_frame: function(name, onready) {
		this.frame = this.frame.frames[name];
		if (this.frame == null) { this._ondone(this._action+": No frame "+name+" found."); return; };
		onready();
	},
	frame_up: function(onready) {
		this.frame = this.frame.parent;
		onready();
	},
	
	wait_element_id: function(id, onready) {
		this._wait_time = 0,
		this._wait = function() { return browser_control.frame.document.getElementById(id) != null; };
		this.wait(onready);
	},
	wait_element_class: function(classname, onready) {
		this._wait_time = 0,
		this._wait = function() { return browser_control.frame.document.getElementsByClassName(classname).length > 0; };
		this.wait(onready);
	},
	click_element_id: function(id, onready) {
		var element = browser_control.frame.document.getElementById(id);
		if (element == null) { this._ondone(this._action+": No element id "+id); return; }
		browser_control.frame.triggerEvent(element, "click");
		onready();
	},
	click_element_class: function(classname, onready) {
		var elements = browser_control.frame.document.getElementsByClassName(classname);
		if (elements.length == 0) { this._ondone(this._action+": No element with class "+classname); return; }
		for (var i = 0; i < elements.length; ++i)
			browser_control.frame.triggerEvent(elements[i], "click");
		onready();
	},
		
};