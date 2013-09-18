function CalendarView(calendar_manager, view_name, container, onready) {
	if (!view_name) view_name = 'week';
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	this.cursor_date = new Date();
	this.cursor_date.setHours(0, 0, 0, 0);
	this.zoom = 30;
	this.view_name = view_name;
	
	this._init = function() {
		while (container.childNodes.length > 0)
			container.removeChild(container.childNodes[0]);
		this.header = document.createElement("DIV");
		this.header.setAttribute("layout", "30");
		this.header.style.backgroundColor = "#D8D8D8";
		this.header.style.borderBottom = "1px solid #A0A0A0";
		this.view_container = document.createElement("DIV");
		this.view_container.setAttribute("layout", "fill");
		this.view_container.style.overflow = "auto";
		container.appendChild(this.header);
		container.appendChild(this.view_container);
		var ready_count = 0;
		var ready = function() {
			if (++ready_count == 2 && onready)
				onready();
		};
		this.change_view(view_name, ready);
		require("vertical_layout.js",function(){
			new vertical_layout(container);
			ready();
		});
		require("horizontal_layout.js",function(){
			new horizontal_layout(t.header);
			require("mac_tabs.js",function() {
				t.view_tabs = new mac_tabs();
				t.view_tabs.addItem("Day", "day");
				t.view_tabs.addItem("Week", "week");
				t.view_tabs.addItem("Month", "month");
				t.view_tabs.addItem("Year", "year");
				t.view_tabs.addItem("Agenda", "agenda");
				t.view_tabs.select(view_name);
				t.header.appendChild(t.view_tabs.element);
				t.view_tabs.onselect = function(view_name) {
					t.change_view(view_name);
				};
				t.position_div = document.createElement("DIV");
				t.position_div.setAttribute("layout","fill");
				t.position_div.style.textAlign = "center";
				t.position_div.style.marginTop = "5px";
				t.position_div.style.whiteSpace = "nowrap";
				t.position_minus = document.createElement("IMG"); t.position_div.appendChild(t.position_minus);
				t.position_text = document.createElement("SPAN"); t.position_div.appendChild(t.position_text);
				t.position_plus = document.createElement("IMG"); t.position_div.appendChild(t.position_plus);
				t.position_minus.style.verticalAlign = "bottom";
				t.position_plus.style.verticalAlign = "bottom";
				t.position_minus.style.paddingRight = "3px";
				t.position_plus.style.paddingLeft = "3px";
				t.position_minus.onload = function() { t.header.widget.layout(); };
				t.position_minus.src = "/static/calendar/left.png";
				t.position_plus.onload = function() { t.header.widget.layout(); };
				t.position_plus.src = "/static/calendar/right.png";
				t.position_minus.style.cursor = "pointer";
				t.position_plus.style.cursor = "pointer";
				t.position_minus.onclick = function() { if (t.view) t.view.back(); t.update_position(); };
				t.position_plus.onclick = function() { if (t.view) t.view.forward(); t.update_position(); };
				t.update_position();
				addLayoutEvent(t.header, function() { t.update_position(); });
				t.header.appendChild(t.position_div);
				t.zoom_div = document.createElement("DIV");
				t.zoom_div.innerHTML = "<img src='"+theme.icons_16.zoom+"' style='vertical-align:bottom'/> Zoom: ";
				t.zoom_div.style.marginTop = "5px";
				t.zoom_minus = document.createElement("IMG"); t.zoom_div.appendChild(t.zoom_minus);
				t.zoom_text = document.createElement("SPAN"); t.zoom_div.appendChild(t.zoom_text);
				t.zoom_plus = document.createElement("IMG"); t.zoom_div.appendChild(t.zoom_plus);
				t.zoom_minus.style.verticalAlign = "bottom";
				t.zoom_plus.style.verticalAlign = "bottom";
				t.zoom_minus.style.paddingRight = "3px";
				t.zoom_plus.style.paddingLeft = "3px";
				t.zoom_minus.onload = function() { t.header.widget.layout(); };
				t.zoom_plus.onload = function() { t.header.widget.layout(); };
				t.zoom_minus.src = "/static/calendar/down.png";
				t.zoom_plus.src = "/static/calendar/up.png";
				t.zoom_minus.style.cursor = "pointer";
				t.zoom_plus.style.cursor = "pointer";
				t.zoom_minus.onclick = function() {
					if (t.zoom == 5) return;
					if (t.zoom == 15) t.zoom = 10; else t.zoom = Math.floor(t.zoom/2);
					if (t.zoom == 5) {
						t.zoom_minus.style.cursor = "";
					} else {
						t.zoom_minus.style.cursor = "pointer";
					}
					t.update_zoom();
					t.change_view(t.view_name);
				};
				t.zoom_plus.onclick = function() {
					if (t.zoom == 10) t.zoom = 15; else t.zoom *= 2;
					t.zoom_minus.style.cursor = "pointer";
					t.update_zoom();
					t.change_view(t.view_name);
				};
				t.update_zoom();
				
				if (t.view && t.view.zoom_supported)
					t.header.appendChild(t.zoom_div);
				t.header.widget.layout();
			});
		});
	};
	this.update_zoom = function() {
		this.zoom_text.innerHTML = "";
		var d = new Date();
		d.setHours(0, this.zoom, 0, 0);
		if (d.getHours() > 0)
			this.zoom_text.innerHTML += d.getHours()+"h";
		this.zoom_text.innerHTML += d.getMinutes()+"m";
	};
	this.update_position = function() {
		if (!this.position_text) return;
		if (this.view) {
			var d1 = this.view.start_date;
			var d2 = this.view.end_date;
			if (d2.getTime() == d1.getTime()) d2 = null;
			this.position_text.innerHTML = this.view.start_date.toLocaleDateString();
			if (d2) this.position_text.innerHTML += " - " + this.view.end_date.toLocaleDateString();
		} else {
			this.position_text.innerHTML = "";
		}
	};
	
	this.change_view = function(view_name, onready) {
		while (this.view_container.childNodes.length > 0)
			this.view_container.removeChild(this.view_container.childNodes[0]);
		if (t.view && t.view.zoom_supported && t.zoom_div)
			t.header.removeChild(t.zoom_div);
		require("calendar_view_"+view_name+".js",function() {
			t.view_name = view_name;
			t.view = new window["calendar_view_"+view_name](t, t.view_container);
			t.load_events();
			if (t.view && t.view.zoom_supported && t.zoom_div)
				t.header.appendChild(t.zoom_div);
			t.update_position();
			if (t.header.widget)
				t.header.widget.layout();
			if (onready) onready();
		});
	};
	
	this.load_events = function() {
		for (var i = 0; i < calendar_manager.calendars.length; ++i) {
			var cal = calendar_manager.calendars[i];
			for (var j = 0; j < cal.events.length; ++j)
				t.add_event(cal.events[j]);
		}
	};
	this.add_event = function(ev) {
		if (ev.start.getTime() > this.view.end_date.getTime()) return; // after end
		if (ev.frequency == null) {
			// single instance
			if (ev.end.getTime() < this.view.start_date.getTime()) return; // before start
			this.view.add_event(ev);
			return;
		}
		// TODO
	};
	this.remove_event = function(ev) {
		this.view.remove_event(ev.uid);
	};
	
	calendar_manager.onloading = function(cal) {
		window.top.status_manager.add_status(cal.loading_status = new window.top.StatusMessage(window.top.Status_TYPE_PROCESSING,"Updating calendar "+cal.name));
	};
	calendar_manager.onloaded = function(cal) {
		window.top.status_manager.remove_status(cal.loading_status);
	};
	calendar_manager.on_event_added = function(ev) { t.add_event(ev); };
	calendar_manager.on_event_removed = function(ev) { t.remove_event(ev); };
	calendar_manager.on_event_updated = function(ev) {
		t.view.remove_event(ev);
		t.view.add_event(ev);
	};
	this._init();
}
