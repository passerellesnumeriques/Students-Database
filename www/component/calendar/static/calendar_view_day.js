if (typeof get_script_path != 'undefined') {
	/** url to access the same directory */
	var url = get_script_path("calendar_view_day.js");
	add_javascript(url+"day_column_layout.js");
	add_javascript(url+"day_row_layout.js");
}
/**
 * View of a single day
 * @param {CalendarView} view the view manager
 * @param {Element} container where to display
 */
function calendar_view_day(view, container) {

	/** {Date} The day to display */
	this.start_date = view.cursor_date;
	/** {Date} The day to display */
	this.end_date = new Date(this.start_date.getTime()+24*60*60*1000-1);
	/** Indicates zoom is supported by this view */
	this.zoom_supported = true;
	/** List of events */
	this.events = [];
	var t=this;
	
	/** Returns a text to describe the zoom value for this view
	 * @param {Number} zoom current zoom value
	 * @returns {String} the text
	 */
	this.getZoomText = function(zoom) {
		var d = new Date();
		d.setHours(0, zoom, 0, 0);
		var text = "";
		if (d.getHours() > 0)
			text += d.getHours()+"h";
		if (d.getHours() == 0 || d.getMinutes() > 0)
			text += d.getMinutes()+"m";
		return text;
	};
	/** Returns a text to describe the current position of the view
	 * @param {Number} shorter indicates an index of how small we should try to make the text
	 * @returns {String} the text
	 */
	this.getPositionText = function(shorter) {
		switch (shorter) {
		case 0:
			// normal
			return this.start_date.getDate() + " " + getMonthName(this.start_date.getMonth()+1) + " " + this.start_date.getFullYear();
		case 1:
			// short month name
			return this.start_date.getDate() + " " + getMonthShortName(this.start_date.getMonth()+1) + " " + this.start_date.getFullYear();
		case 2:
			// remove the year
			return this.start_date.getDate() + " " + getMonthShortName(this.start_date.getMonth()+1) + (new Date().getFullYear() == this.start_date.getFullYear() ? "" : " " + this.start_date.getFullYear());
		case 3:
			// month number
			return this.start_date.getDate() + " " + _2digits(this.start_date.getMonth()+1) + (new Date().getFullYear() == this.start_date.getFullYear() ? "" : " " + this.start_date.getFullYear());
		};
		return null;
	};
	
	/** Goes one day back */
	this.back = function() {
		this.start_date = new Date(this.start_date.getTime()-24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+24*60*60*1000-1);
		view.cursor_date = this.start_date;
		this._showNow();
		this.day_title.innerHTML = this.start_date.toDateString();
		if (this.day_column)
			this.day_column.removeEvents();
		if (this.row_layout)
			this.row_layout.removeEvents();
		this.events = [];
		view.loadEvents();
	};
	/** Goes 7 days back */
	this.backStep = function() {
		this.start_date = new Date(this.start_date.getTime()-7*24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+1*24*60*60*1000-1);
		view.cursor_date = this.start_date;
		this._showNow();
		this.day_title.innerHTML = this.start_date.toDateString();
		if (this.day_column)
			this.day_column.removeEvents();
		if (this.row_layout)
			this.row_layout.removeEvents();
		this.events = [];
		view.loadEvents();
	};
	/** Goes one day forward */
	this.forward = function() {
		this.start_date = new Date(this.start_date.getTime()+24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+24*60*60*1000-1);
		view.cursor_date = this.start_date;
		this._showNow();
		this.day_title.innerHTML = this.start_date.toDateString();
		if (this.day_column)
			this.day_column.removeEvents();
		if (this.row_layout)
			this.row_layout.removeEvents();
		this.events = [];
		view.loadEvents();
	};
	/** Goes 7 days forward */
	this.forwardStep = function() {
		this.start_date = new Date(this.start_date.getTime()+7*24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+1*24*60*60*1000-1);
		view.cursor_date = this.start_date;
		this._showNow();
		this.day_title.innerHTML = this.start_date.toDateString();
		if (this.day_column)
			this.day_column.removeEvents();
		if (this.row_layout)
			this.row_layout.removeEvents();
		this.events = [];
		view.loadEvents();
	};
	
	/** Create the display */
	this._init = function() {
		var has_fixed_height = getHeight(container) > 0;
		this.header = document.createElement("DIV");
		this.header.style.borderBottom = "1px solid black";
		this.day_row_container_ = document.createElement("DIV");
		this.day_row_container = document.createElement("DIV");
		this.day_row_container.style.position = "relative";
		this.day_row_container.style.width = "100%";
		this.day_row_container.style.height = "100%";
		this.day_row_container_.appendChild(this.day_row_container);
		this.content_ = document.createElement("DIV");
		this.content = document.createElement("DIV");
		this.content.style.width = "100%";
		this.content.style.height = "100%";
		this.content_.appendChild(this.content);
		container.appendChild(this.header);
		container.appendChild(this.day_row_container_);
		container.appendChild(this.content_);
		if (has_fixed_height) {
			t.header.setAttribute("layout", "20");
			t.day_row_container_.setAttribute("layout", "10");
			t.content_.setAttribute("layout", "fill");
			t.content.style.overflow = "auto";
			require("vertical_layout.js", function() { new vertical_layout(container); t._layout(); });
		} else {
			t.header.style.height = "20px";
			t.day_row_container.style.height = "10px";
		}
		require("day_row_layout.js", function() { t.row_layout = new day_row_layout(view.calendar_manager); t._layout(); });
		
		this.corner = document.createElement("DIV");
		this.corner.setAttribute("layout", "50");
		var tz = -(new Date().getTimezoneOffset());
		this.corner.innerHTML = "GMT";
		if (tz != 0) {
			if (tz > 0) this.corner.innerHTML += "+"; else { this.corner.innerHTML += "-"; tz=-tz; }
			this.corner.innerHTML += _2digits(Math.floor(tz/60));
			tz -= Math.floor(tz/60)*60;
			if (tz > 0) this.corner.innerHTML += ":"+_2digits(tz);
		}
		this.day_title = document.createElement("DIV");
		this.day_title.setAttribute("layout", "fill");
		this.day_title.style.borderLeft = "1px solid black";
		this.day_title.style.textAlign = "center";
		this.day_title.innerHTML = this.start_date.toDateString();
		this.header.appendChild(this.corner);
		this.header.appendChild(this.day_title);
		this.day_box = document.createElement("DIV");
		this.day_box.style.borderLeft = "1px solid black";
		this.day_box.style.borderBottom = "1px solid black";
		this.day_box.style.height = "10px";
		this.day_box.style.position = "absolute";
		this.day_box.style.left = "50px";
		this.day_row_container.appendChild(this.day_box);
		require("horizontal_layout.js", function() { new horizontal_layout(t.header); t._layout(); });
		
		this.content.style.position = "relative";
		this.time_title = document.createElement("DIV");
		this.time_title.style.position = "absolute";
		this.time_title.style.width = "50px";
		this.time_title.style.left = "0px";
		this.time_title.style.top = "0px";
		this.time_title.style.borderRight = "1px solid black";
		this.content.appendChild(this.time_title);
		this.day_content = document.createElement("DIV");
		this.day_content.style.position = "absolute";
		this.day_content.style.left = "51px";
		this.day_content.style.top = "0px";
		this.content.appendChild(this.day_content);
		
		this._createTimeScale();
		add_javascript(get_script_path("calendar_view_day.js")+"day_column_layout.js",function(){
			t.day_column = new DayColumnLayout(view.calendar_manager);
			t._layout();
		});
		this._showNow();
		this._layout();
	};
	
	/** Stores the rows representing the time lines */
	this._time_lines = [];
	/** Create the lines representing the time lines */
	this._createTimeScale = function() {
		while (this.time_title.childNodes.length > 0)
			this.time_title.removeChild(this.time_title.childNodes[0]);
		for (var i = 0; i < this._time_lines.length; ++i) this.content.removeChild(this._time_lines[i]);
		this._time_lines = [];
		
		var time = view.zoom;
		while (time < 24*60) {
			var y = Math.floor(time*20/view.zoom);
			var line = document.createElement("DIV");
			line.style.borderTop = "1px dotted #808080";
			line.style.height = "1px";
			line.style.position = "absolute";
			line.style.left = "51px";
			line.style.top = y+"px";
			this.content.appendChild(line);
			this._time_lines.push(line);
			var d = document.createElement("DIV");
			var date = new Date();
			date.setHours(0, time, 0, 0);
			d.innerHTML = _2digits(date.getHours())+":"+_2digits(date.getMinutes());
			d.style.top = (y-8)+"px";
			d.style.position = "absolute";
			d.style.width = "50px";
			d.style.textAlign = "right";
			d.style.left = "0px";
			this.time_title.appendChild(d);
			time += view.zoom;
		}
		var y = Math.floor(24*60*20/view.zoom);
		this.time_title.style.height = y+"px";
		this.day_content.style.height = y+"px";
		if (!t.content_.hasAttribute("layout"))
			t.content_.style.height = y+"px";
	};
	/** {Element} line which indicates the actual time */
	this._now = null;
	/** Displays/layout the line indicating the actual time */
	this._showNow = function() {
		var now = new Date();
		if (t._now) { t._now.parentNode.removeChild(t._now); t._now = null; }
		if (t._showNowTimeout) clearTimeout(t._showNowTimeout);
		var d = t.start_date;
		t._showNowTimeout = setTimeout(t._showNow,10000);
		if (d.getFullYear() != now.getFullYear()) return;
		if (d.getMonth() != now.getMonth()) return;
		if (d.getDate() != now.getDate()) return;
		t._now = document.createElement("DIV");
		t._now.style.position = 'absolute';
		var seconds = now.getHours()*60*60+now.getMinutes()*60+now.getSeconds();
		t._now.style.top = Math.floor(20*seconds/60/view.zoom)+"px";
		t._now.style.left = "0px";
		t._now.style.borderTop = "1px solid #FF0000";
		t._now.style.borderBottom = "1px solid #808000";
		t._now.style.height = "0px";
		t._now.style.width = getWidth(t.day_content)+"px";
		t._now.style.zIndex = 3;
		t.day_content.appendChild(t._now);
	};
	/** Layout and display the events */
	this._layout = function() {
		if (!this.day_content) return;
		if (!t._timeout)
			t._timeout = setTimeout(function(){
				var w = container.clientWidth-51;
				w -= (t.content.offsetWidth-t.content.clientWidth);
				if (t._now) t._now.style.width = w+"px";
				t.day_content.style.width = w+"px";
				t.day_box.style.width = w+"px";
				for (var i = 0; i < t._time_lines.length; ++i)
					t._time_lines[i].style.width = w+"px";
				if (t.day_column) {
					var list = [];
					for (var j = 0; j < t.events.length; ++j)
						if (!t.events[j].all_day) list.push(t.events[j]);
					t.day_column.layout(list, t.day_content, 0, w, 0, view.zoom, 20);
				}
				if (t.row_layout) {
					var list = [];
					for (var j = 0; j < t.events.length; ++j)
						if (t.events[j].all_day) list.push(t.events[j]);
					var h = t.row_layout.layout(list, [t.day_box], t.start_date);
					if (t.day_row_container.hasAttribute("layout"))
						t.day_row_container_.setAttribute("layout",h);
					else
						t.day_row_container_.style.height = h+"px";
					if (container.widget) container.widget.layout();
				}
				t._timeout = null;
			},10);
	};
	
	/** Called by the CalendarView when a new event should be displayed.
	 * @param {Object} ev the event to display
	 */
	this.addEvent = function(ev) {
		this.events.push(ev);
		this._layout();
	};
	/** Called by the CalendarView when an event needs to be removed from the dislpay.
	 * @param {String} uid the uid of the event to remove
	 */
	this.removeEvent = function(uid) {
		for (var i = 0; i < this.events.length; ++i)
			if (this.events[i].uid == uid) {
				this.events.splice(i, 1);
				i--;
			}
		this._layout();
	};
	
	this._init();
	layout.addHandler(container, function() { t._layout(); });
}