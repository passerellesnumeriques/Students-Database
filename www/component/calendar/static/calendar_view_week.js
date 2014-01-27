if (typeof get_script_path != 'undefined') {
	/** url to access the same directory */
	var url = get_script_path("calendar_view_week.js");
	add_javascript(url+"day_column_layout.js");
	add_javascript(url+"day_row_layout.js");
}
/**
 * View of a week
 * @param {CalendarView} view the view manager
 * @param {DOMNode} container where to display
 */
function calendar_view_week(view, container) {

	this.start_date = view.cursor_date;
	/** First day of the week to display */
	if (this.start_date.getDay() == 0) this.start_date = new Date(this.start_date.getTime()-6*24*60*60*1000);
	else if (this.start_date.getDay() > 1) this.start_date = new Date(this.start_date.getTime()-(this.start_date.getDay()-1)*24*60*60*1000);
	/** Last day of the week to display */
	this.end_date = new Date(this.start_date.getTime()+7*24*60*60*1000-1);
	/** Indicates zoom is supported by this view */
	this.zoom_supported = true;
	/** Stores the list of events by day */
	this.events = [[],[],[],[],[],[],[]];
	var t=this;
	
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
	this.getPositionText = function(shorter) {
		switch (shorter) {
		case 0: // normal
			return this.start_date.getDate() + " " + getMonthName(this.start_date.getMonth()+1) + " " + this.start_date.getFullYear() + " - " + this.end_date.getDate() + " " + getMonthName(this.end_date.getMonth()+1) + " " + this.end_date.getFullYear();
		case 1: // short month name
			return this.start_date.getDate() + " " + getMonthShortName(this.start_date.getMonth()+1) + " " + this.start_date.getFullYear() + " - " + this.end_date.getDate() + " " + getMonthShortName(this.end_date.getMonth()+1) + " " + this.end_date.getFullYear();
		case 2: // remove the year
			return this.start_date.getDate() + " " + getMonthShortName(this.start_date.getMonth()+1) + (new Date().getFullYear() == this.start_date.getFullYear() ? "" : " " + this.start_date.getFullYear()) + " - " + this.end_date.getDate() + " " + getMonthShortName(this.end_date.getMonth()+1);
		case 3: // month number
			return this.start_date.getDate() + " " + _2digits(this.start_date.getMonth()+1) + (new Date().getFullYear() == this.start_date.getFullYear() ? "" : " " + this.start_date.getFullYear()) + " - " + this.end_date.getDate() + " " + _2digits(this.end_date.getMonth()+1);
		};
		return null;
	};
	
	/** Goes one day before */
	this.back = function() {
		this.start_date = new Date(this.start_date.getTime()-1*24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+7*24*60*60*1000-1);
		view.cursor_date = this.start_date;
		for (var i = 0; i < 7; ++i) {
			var d = new Date(t.start_date.getTime()+i*24*60*60*1000);
			t._setDayTitle(t.day_title[i], t.day_box[i].clientWidth, d);
			if (this.day_column)
				this.day_column[i].removeEvents();
			if (this.row_layout)
				this.row_layout.removeEvents();
			this.day_content[i].date = d;
		}
		this.events = [[],[],[],[],[],[],[]];
		this._showNow();
		view.loadEvents();
	};
	/** Goes one week before */
	this.back_step = function() {
		this.start_date = new Date(this.start_date.getTime()-7*24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+7*24*60*60*1000-1);
		view.cursor_date = this.start_date;
		for (var i = 0; i < 7; ++i) {
			var d = new Date(t.start_date.getTime()+i*24*60*60*1000);
			t._setDayTitle(t.day_title[i], t.day_box[i].clientWidth, d);
			if (this.day_column)
				this.day_column[i].removeEvents();
			if (this.row_layout)
				this.row_layout.removeEvents();
			this.day_content[i].date = d;
		}
		this.events = [[],[],[],[],[],[],[]];
		this._showNow();
		view.loadEvents();
	};
	/** Goes one day after */
	this.forward = function() {
		this.start_date = new Date(this.start_date.getTime()+1*24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+7*24*60*60*1000-1);
		view.cursor_date = this.start_date;
		for (var i = 0; i < 7; ++i) {
			var d = new Date(t.start_date.getTime()+i*24*60*60*1000);
			t._setDayTitle(t.day_title[i], t.day_box[i].clientWidth, d);
			if (this.day_column)
				this.day_column[i].removeEvents();
			if (this.row_layout)
				this.row_layout.removeEvents();
			this.day_content[i].date = d;
		}
		this.events = [[],[],[],[],[],[],[]];
		this._showNow();
		view.loadEvents();
	};
	/** Goes one week after */
	this.forward_step = function() {
		this.start_date = new Date(this.start_date.getTime()+7*24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+7*24*60*60*1000-1);
		view.cursor_date = this.start_date;
		for (var i = 0; i < 7; ++i) {
			var d = new Date(t.start_date.getTime()+i*24*60*60*1000);
			t._setDayTitle(t.day_title[i], t.day_box[i].clientWidth, d);
			if (this.day_column)
				this.day_column[i].removeEvents();
			if (this.row_layout)
				this.row_layout.removeEvents();
			this.day_content[i].date = d;
		}
		this.events = [[],[],[],[],[],[],[]];
		this._showNow();
		view.loadEvents();
	};
	
	/** Create the display */
	this._init = function() {
		this.header = document.createElement("DIV");
		this.header.setAttribute("layout", "16");
		this.header.style.borderBottom = "1px solid black";
		this.day_row_container_ = document.createElement("DIV");
		this.day_row_container_.setAttribute("layout", "10");
		this.day_row_container = document.createElement("DIV");
		this.day_row_container.style.position = "relative";
		this.day_row_container.style.width = "100%";
		this.day_row_container.style.height = "100%";
		this.day_row_container_.appendChild(this.day_row_container);
		this.content_ = document.createElement("DIV");
		this.content_.setAttribute("layout", "fill");
		this.content = document.createElement("DIV");
		this.content.style.overflow = "auto";
		this.content.style.width = "100%";
		this.content.style.height = "100%";
		this.content_.appendChild(this.content);
		container.appendChild(this.header);
		container.appendChild(this.day_row_container_);
		container.appendChild(this.content_);
		require("vertical_layout.js", function() { new vertical_layout(container); t._layout(); });
		require("day_row_layout.js", function() { t.row_layout = new day_row_layout(view.calendar_manager); t._layout(); });
		
		this.corner = document.createElement("DIV");
		this.corner.style.position = "absolute";
		this.corner.style.width = "45px";
		this.corner.style.height = "15px";
		this.corner.style.fontSize = '8pt';
		var tz = -(new Date().getTimezoneOffset());
		this.corner.innerHTML = "GMT";
		if (tz != 0) {
			if (tz > 0) this.corner.innerHTML += "+"; else { this.corner.innerHTML += "-"; tz=-tz; }
			this.corner.innerHTML += this._2digits(Math.floor(tz/60));
			tz -= Math.floor(tz/60)*60;
			if (tz > 0) this.corner.innerHTML += ":"+this._2digits(tz);
		}
		this.header.appendChild(this.corner);
		this.day_title = [];
		this.day_box = [];
		for (var i = 0; i < 7; ++i) {
			this.day_title[i] = document.createElement("DIV");
			this.day_title[i].style.borderLeft = "1px solid black";
			if (i == 6)
				this.day_title[i].style.borderRight = "1px solid black";
			this.day_title[i].style.textAlign = "center";
			this.day_title[i].style.height = "15px";
			this.day_title[i].style.position = "absolute";
			this.day_title[i].style.fontSize = "9pt";
			this.day_title[i].style.backgroundColor = "#D0D0D0";
			this.header.appendChild(this.day_title[i]);
			this.day_box[i] = document.createElement("DIV");
			this.day_box[i].style.borderLeft = "1px solid black";
			if (i == 6)
				this.day_box[i].style.borderRight = "1px solid black";
			this.day_box[i].style.borderBottom = "1px solid black";
			this.day_box[i].style.height = "10px";
			this.day_box[i].style.position = "absolute";
			this.day_row_container.appendChild(this.day_box[i]);
		}
		this.header.style.position = "relative";
		
		this.content.style.position = "relative";
		this.time_title = document.createElement("DIV");
		this.time_title.style.position = "absolute";
		this.time_title.style.width = "45px";
		this.time_title.style.left = "0px";
		this.time_title.style.top = "0px";
		this.time_title.style.borderRight = "1px solid black";
		this.content.appendChild(this.time_title);
		this.day_content = [];
		for (var i = 0; i < 7; ++i) {
			this.day_content[i] = document.createElement("DIV");
			this.day_content[i].style.position = "absolute";
			this.day_content[i].style.top = "0px";
			this.day_content[i].style.borderRight = "1px solid black";
			this.day_content[i].date = new Date(t.start_date.getTime()+i*24*60*60*1000);
			this.day_content[i].onclick = function(e) {
				var date = new Date(this.date.getTime());
				var mev = getCompatibleMouseEvent(e);
				var y = mev.y-absoluteTop(this);
				var time = y/20*view.zoom;
				date.setHours(0, time, 0, 0);
				// TODO adjust minutes according to zoom
				require("event_screen.js",function() {
					event_screen(null,view.calendar_manager.calendars[view.calendar_manager.default_calendar_index],date,false);
				});
				stopEventPropagation(e);
				return false;
			};
			this.content.appendChild(this.day_content[i]);
		}
		
		this._createTimeScale();
		for (var i = 0; i < this.time_title.childNodes.length; ++i)
			if (this.time_title.childNodes[i].time.getHours() > 6) {
				scrollTo(this.time_title.childNodes[i]);
				break;
			}
		add_javascript(get_script_path("calendar_view_week.js")+"day_column_layout.js",function(){
			t.day_column = [];
			for (var i = 0; i < 7; ++i)
				t.day_column[i] = new DayColumnLayout(view.calendar_manager);
			t._layout();
		});
		this._showNow();
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
			line.style.left = "46px";
			line.style.top = y+"px";
			line.style.pointerEvents = 'none';
			this.content.appendChild(line);
			this._time_lines.push(line);
			var d = document.createElement("DIV");
			var date = new Date();
			date.setHours(0, time, 0, 0);
			d.innerHTML = this._2digits(date.getHours())+":"+this._2digits(date.getMinutes());
			d.time = date;
			d.style.top = (y-8)+"px";
			d.style.position = "absolute";
			d.style.width = "45px";
			d.style.textAlign = "right";
			d.style.left = "0px";
			this.time_title.appendChild(d);
			time += view.zoom;
		}
		var y = Math.floor(24*60*20/view.zoom);
		this.time_title.style.height = y+"px";
		for (var i = 0; i < 7; ++i)
			this.day_content[i].style.height = y+"px";
	};
	this._now = null;
	this._showNow = function() {
		var now = new Date();
		if (t._now) { t._now.parentNode.removeChild(t._now); t._now = null; }
		if (t._showNowTimeout) clearTimeout(t._showNowTimeout);
		for (var i = 0; i < 7; ++i) {
			var d = t.day_content[i].date;
			if (d.getFullYear() != now.getFullYear()) continue;
			if (d.getMonth() != now.getMonth()) continue;
			if (d.getDate() != now.getDate()) continue;
			t._now = document.createElement("DIV");
			t._now.style.position = 'absolute';
			var seconds = now.getHours()*60*60+now.getMinutes()*60+now.getSeconds();
			t._now.style.top = Math.floor(20*seconds/60/view.zoom)+"px";
			t._now.style.left = "0px";
			t._now.style.borderTop = "1px solid #FF0000";
			t._now.style.borderBottom = "1px solid #808000";
			t._now.style.height = "0px";
			t._now.style.width = getWidth(t.day_content[i])+"px";
			t._now.style.zIndex = 3;
			t.day_content[i].appendChild(t._now);
			break;
		}
		t._showNowTimeout = setTimeout(t._showNow,10000);
	};
	/** Add a 0 if the number is only 1 digit
	 * @param {Number} n the number
	 */
	this._2digits = function(n) {
		var s = ""+n;
		while (s.length < 2) s = "0"+s;
		return s;
	};
	/** Layout and display the events */
	this._layout = function() {
		if (!this.day_content) return;
		if (!t._timeout)
			t._timeout = setTimeout(function(){
				var w = container.clientWidth-51;
				w -= (t.content.offsetWidth-t.content.clientWidth);
				for (var i = 0; i < t._time_lines.length; ++i)
					t._time_lines[i].style.width = w+"px";
				var dw = Math.floor(w/7);
				if (t._now) t._now.style.width = dw+"px"; 
				for (var i = 0; i < 7; ++i) {
					t.day_title[i].style.left = (dw*i+45)+"px";
					t.day_title[i].style.width = (dw-(i==6?1:0))+"px";
					t.day_box[i].style.left = (dw*i+45)+"px";
					t.day_box[i].style.width = (dw-(i==6?1:0))+"px";
					t._setDayTitle(t.day_title[i], dw-(i==6?1:0), t.day_content[i].date);
					t.day_content[i].style.left = (dw*i+45)+"px";
					t.day_content[i].style.width = dw+"px";
					if (t.day_column) {
						var list = [];
						for (var j = 0; j < t.events[i].length; ++j)
							if (!t.events[i][j].all_day) list.push(t.events[i][j]);
						t.day_column[i].layout(list, t.day_content[i], 0, dw, 0, view.zoom, 20);
					}
				}
				if (t.row_layout) {
					var list = [];
					for (var i = 0; i < 7; ++i)
						for (var j = 0; j < t.events[i].length; ++j)
							if (t.events[i][j].all_day) list.push(t.events[i][j]);
					var h = t.row_layout.layout(list, t.day_box, t.start_date);
					t.day_row_container_.setAttribute("layout",h);
					if (container.widget) container.widget.layout();
				}
				t._timeout = null;
			},10);
	};
	this._setDayTitle = function(box, w, date) {
		var day = date.getDay()-1;
		if (day == -1) day = 6;
		var div = document.createElement("DIV");
		div.style.position = "absolute";
		div.style.visibility = "hidden";
		div.style.fontSize = '9pt';
		document.body.appendChild(div);
		// try with full day name
		div.innerHTML = getDayName(day)+" "+date.getDate();
		if (div.offsetWidth > w) {
			// try with short day name
			div.innerHTML = getDayShortName(day)+" "+date.getDate();
			if (div.offsetWidth > w) {
				// try with one letter day
				div.innerHTML = getDayLetter(day)+" "+date.getDate();
				if (div.offsetWidth > w) {
					// try with letter and no space
					div.innerHTML = getDayLetter(day)+""+date.getDate();
				}
			}
		}
		box.innerHTML = div.innerHTML;
		document.body.removeChild(div);
	};
	
	/** Called by the CalendarView when a new event should be displayed.
	 * @param {Object} ev the event to display
	 */
	this.addEvent = function(ev) {
		var i = ev.start.getDay();
		if (i == 0) i = 7;
		this.events[i-1].push(ev);
		this._layout();
	};
	/** Called by the CalendarView when an event needs to be removed from the dislpay.
	 * @param {String} uid the uid of the event to remove
	 */
	this.removeEvent = function(uid) {
		for (var j = 0; j < 7; ++j)
		for (var i = 0; i < this.events[j].length; ++i)
			if (this.events[j][i].uid == uid) {
				this.events[j].splice(i, 1);
				i--;
			}
		this._layout();
	};
	
	this._init();
	addLayoutEvent(container, function() { t._layout(); });
}