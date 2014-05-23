browser_control = {
	
	win: null,
	frame: null,
	_wait: null,
	_ondone: null,
	_action: null,
	
	run: function(actions, onaction, ondone) {
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
			if (browser_control.win && browser_control.win.frames && browser_control.win.frames.pn_application_frame) {
				var doc = getIFrameDocument(browser_control.win && browser_control.win.frames && browser_control.win.frames.pn_application_frame);
				doc.getElementById('test_ui_action_name').innerHTML = action[0];
				doc.getElementById('test_ui_wait_time').removeAllChildren();
				doc.getElementById('test_ui_footer').widget.layout();
			}
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
		var ok = this._wait == null;
		var what = "";
		if (!ok) {
			what = this._wait();
			if (what == null) ok = true;
		}
		if (ok) {
			if (browser_control.win && browser_control.win.frames && browser_control.win.frames.pn_application_frame) {
				var doc = getIFrameDocument(browser_control.win.frames.pn_application_frame);
				var wait_time = doc.getElementById('test_ui_wait_time');
				if (wait_time) wait_time.removeAllChildren();
				if (doc.getElementById('test_ui_footer').widget) doc.getElementById('test_ui_footer').widget.layout();
			}
			this._wait = null;
			onready(); 
			return;
		}
		if (browser_control.win && browser_control.win.frames && browser_control.win.frames.pn_application_frame) {
			var doc = getIFrameDocument(browser_control.win.frames.pn_application_frame);
			var wait_time = doc.getElementById('test_ui_wait_time');
			if (wait_time) {
				wait_time.innerHTML = Math.floor((10000-this._wait_time*100)/1000)+"s. ("+what+")";
				if (doc.getElementById('test_ui_footer').widget) doc.getElementById('test_ui_footer').widget.layout();
			}
		}
		if (++this._wait_time == 100) {
			if (browser_control.win && browser_control.win.frames && browser_control.win.frames.pn_application_frame) {
				var doc = getIFrameDocument(browser_control.win.frames.pn_application_frame);
				var wait_time = doc.getElementById('test_ui_wait_time');
				if (wait_time) {
					wait_time.innerHTML = "Timeout ("+what+")";
					var icon = doc.getElementById('test_ui_play');
					icon.src = "/static/test/close_50.png";
					icon.style.cursor = 'pointer';
					var t=this;
					icon.onclick = function() {
						t._ondone(t._action+": Timeout ("+what+")");
					};
					if (doc.getElementById('test_ui_footer').widget) doc.getElementById('test_ui_footer').widget.layout();
				} else
					this._ondone(this._action+": Timeout ("+what+")");
			} else
				this._ondone(this._action+": Timeout ("+what+")");
			return;
		}
		setTimeout(function() { browser_control.wait(onready); }, 100);
	},
	
	error: function(message) {
		var doc = getIFrameDocument(browser_control.win.frames.pn_application_frame);
		doc.getElementById('test_ui_action_name').innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+message;
		doc.getElementById('test_ui_action_name').style.color = 'red';
		doc.getElementById('test_ui_wait_time').removeAllChildren();
		var icon = doc.getElementById('test_ui_play');
		icon.src = "/static/test/close_50.png";
		icon.style.cursor = 'pointer';
		var t=this;
		icon.onclick = function() {
			t._ondone(message);
		};
		doc.getElementById('test_ui_footer').widget.layout();
	},
	
	start: function(onready) {
		this.win = null;
		var u = new URL(window.top.location.href);
		u.path = "/dynamic/test/page/ui";
		this.win = window.open(u.toString(), "_blank", "height=600,width=800,left=10,top=10,location=yes,menubar=no,resizable=yes,status=no,toolbar=no,titlebar=yes");
		this.frame = this.win;
		this._wait_time = 0,
		this._wait = function() { 
			if (!browser_control.win) return "Window";
			if (!browser_control.win.pn_loading_end) return "PN Application loading page"; 
			if (browser_control.win.pn_loading_visible) return "PN Application loaded";
			if (!browser_control.win.frames) return "PN Application page"; 
			if (!browser_control.win.frames.pn_application_frame) return "PN Application page loaded";
			if (!getIFrameDocument(browser_control.win.frames.pn_application_frame) || !getIFrameDocument(browser_control.win.frames.pn_application_frame).getElementById('test_ui_wait_time')) return "PN Application page ready";
			if (!browser_control.win.frames.pn_application_frame.frames.test_ui_frame) return "First page loaded";
			return null;
		};
		this.wait(function() {
			browser_control.enter_frame("pn_application_frame", function() {
				browser_control.enter_frame("test_ui_frame", onready);
			});
		});
	},
	
	fill_form: function(form_name, elements, onready) {
		var form = this.frame.forms[form_name];
		if (form == null) { error(this._action+": No form '"+form_name+"'"); return; }
		for (var name in elements) {
			var e = form.elements[name];
			if (!e) { error(this._action+": No element '"+name+"' in form '"+form_name+"'"); return; }
			e.value = elements[name];
		}
		onready();
	},
	submit_form: function(form_name, onready) {
		var form = this.frame.forms[form_name];
		if (form == null) { error(this._action+": No form '"+form_name+"'"); return; }
		form.submit();
		onready();
	},
	
	login: function(username, onready) {
		var t=this;
		t.wait_element_id("login_table", function() {
			t.fill_form("login_form",{domain:'Test',username:username}, function() {
				t.execute_code("login();", function() {
					t.wait_element_id("pn_application_container", onready);
				});
			});
		});
	},
	
	user_check: function(message, onready) {
		var doc = getIFrameDocument(browser_control.win.frames.pn_application_frame);
		var msg_div = doc.getElementById('test_ui_message');
		msg_div.innerHTML = message;
		var time = 30;
		var time_span;
		var go;
		var to;
		to = function() {
			if (time == -1) return;
			time--;
			time_span.innerHTML = time+"s.";
			if (time > 0) {
				setTimeout(to, 1000);
				return;
			}
			go();
		};
		var icon = doc.getElementById('test_ui_play');
		go = function() {
			time = -1;
			icon.style.cursor = '';
			icon.src = "/static/test/wait_50.gif";
			icon.onclick = null;
			doc.getElementById('test_ui_message').removeAllChildren();
			onready();
		};
		icon.style.cursor = 'pointer';
		icon.src = "/static/test/play_50.png";
		icon.onclick = function() {
			go();
		};
		msg_div.appendChild(document.createElement("BR"));
		var span = document.createElement("SPAN");
		span.innerHTML = "The scenario will automatically continue in ";
		time_span = document.createElement("SPAN");
		time_span.innerHTML = "10s.";
		span.appendChild(time_span);
		var link_stop = document.createElement("A");
		link_stop.href = '#';
		link_stop.onclick = function() { time = -1; };
		link_stop.innerHTML = "stop";
		link_stop.style.fontSize = "small";
		span.appendChild(link_stop);
		msg_div.appendChild(span);
		doc.getElementById('test_ui_footer').widget.layout();
		setTimeout(to, 1000);
	},
	
	execute_code: function(code, onready) {
		try { this.frame.eval(code); }
		catch (e) {
			var msg = this._action+": error executing code ["+code+"]: "+e;
			var stack = null;
			if (e.stack)
				stack = e.stack;
			else if(e.stacktrace)
				stack = e.stacktrace;
			else {
				var s = "";
			    var currentFunction = arguments.callee.caller;
			    while (currentFunction) {
			      var fn = currentFunction.toString();
			      var fname = fn.substring(0, fn.indexOf('{'));;
			      s += fname+"\r\n";
			      currentFunction = currentFunction.caller;
			    }
			    stack = s;
			}
			if (stack != null) msg += "<br/>"+stack;
			this.error(msg);
			return;
		}
		onready();
	},
	
	sleep: function(time, onready) {
		setTimeout(onready, time);
	},
	
	enter_frame: function(name, onready) {
		this.frame = this.frame.frames[name];
		if (this.frame == null) { this.error(this._action+": No frame "+name+" found."); return; };
		onready();
	},
	frame_up: function(onready) {
		this.frame = this.frame.parent;
		onready();
	},
	
	wait_element_id: function(id, onready) {
		this._wait_time = 0,
		this._wait = function() { 
			if (browser_control.frame.document.getElementById(id) == null) return "Element id '"+id+"'";
			return null;
		};
		this.wait(onready);
	},
	wait_element_class: function(classname, onready) {
		this._wait_time = 0,
		this._wait = function() {
			if (browser_control.frame.document.getElementsByClassName(classname).length == 0) return "Element with class='"+classname+"'";
			return null;
		};
		this.wait(onready);
	},
	click_element_id: function(id, onready) {
		var element = browser_control.frame.document.getElementById(id);
		if (element == null) { this.error(this._action+": No element id "+id); return; }
		browser_control.frame.triggerEvent(element, "click");
		onready();
	},
	click_element_class: function(classname, onready) {
		var elements = browser_control.frame.document.getElementsByClassName(classname);
		if (elements.length == 0) { this.error(this._action+": No element with class "+classname); return; }
		for (var i = 0; i < elements.length; ++i)
			browser_control.frame.triggerEvent(elements[i], "click");
		onready();
	},
		
};