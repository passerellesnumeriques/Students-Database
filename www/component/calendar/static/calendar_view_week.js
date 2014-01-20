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

	/** First day of the week to display */
	this.start_date = view.cursor_date;
	if (this.start_date.getDay() == 0) this.start_date = new Date(this.start_date.getTime()-6*24*60*60*1000);
	else if (this.start_date.getDay() > 1) this.start_date = new Date(this.start_date.getTime()-(this.start_date.getDay()-1)*24*60*60*1000);
	/** Last day of the week to display */
	this.end_date = new Date(this.start_date.getTime()+7*24*60*60*1000-1);
	/** Indicates zoom is supported by this view */
	this.zoom_supported = true;
	/** Stores the list of events by day */
	this.events = [[],[],[],[],[],[],[]];
	var t=this;
	
	/** Goes one week before */
	this.back = function() {
		this.start_date = new Date(this.start_date.getTime()-7*24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+7*24*60*60*1000-1);
		view.cursor_date = this.start_date;
		for (var i = 0; i < 7; ++i) {
			this.day_title[i].innerHTML = new Date(this.start_date.getTime()+i*24*60*60*1000).toDateString();
			if (this.day_column)
				this.day_column[i].removeEvents();
			if (this.row_layout)
				this.row_layout.removeEvents();
		}
		this.events = [[],[],[],[],[],[],[]];
		view.loadEvents();
	};
	/** Goes one week after */
	this.forward = function() {
		this.start_date = new Date(this.start_date.getTime()+7*24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+7*24*60*60*1000-1);
		view.cursor_date = this.start_date;
		for (var i = 0; i < 7; ++i) {
			this.day_title[i].innerHTML = new Date(this.start_date.getTime()+i*24*60*60*1000).toDateString();
			if (this.day_column)
				this.day_column[i].removeEvents();
			if (this.row_layout)
				this.row_layout.removeEvents();
		}
		this.events = [[],[],[],[],[],[],[]];
		view.loadEvents();
	};
	
	/** Create the display */
	this._init = function() {
		this.header = document.createElement("DIV");
		this.header.setAttribute("layout", "20");
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
		require("day_row_layout.js", function() { t.row_layout = new day_row_layout(); t._layout(); });
		
		this.corner = document.createElement("DIV");
		this.corner.style.position = "absolute";
		this.corner.style.width = "50px";
		this.corner.style.height = "20px";
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
			this.day_title[i].style.height = "20px";
			this.day_title[i].style.position = "absolute";
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
		this.time_title.style.width = "50px";
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
				var y = mev.y-absoluteTop(this)+t.content.scrollTop;
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
		add_javascript(get_script_path("calendar_view_week.js")+"day_column_layout.js",function(){
			t.day_column = [];
			for (var i = 0; i < 7; ++i)
				t.day_column[i] = new DayColumnLayout();
			t._layout();
		});
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
			line.style.pointerEvents = 'none';
			this.content.appendChild(line);
			this._time_lines.push(line);
			var d = document.createElement("DIV");
			var date = new Date();
			date.setHours(0, time, 0, 0);
			d.innerHTML = this._2digits(date.getHours())+":"+this._2digits(date.getMinutes());
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
		for (var i = 0; i < 7; ++i)
			this.day_content[i].style.height = y+"px";
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
				for (var i = 0; i < 7; ++i) {
					t.day_title[i].style.left = (dw*i+50)+"px";
					t.day_title[i].style.width = (dw-(i==6?1:0))+"px";
					t.day_box[i].style.left = (dw*i+50)+"px";
					t.day_box[i].style.width = (dw-(i==6?1:0))+"px";
					switch (i) {
					case 0: t.day_title[i].innerHTML = "Monday "+new Date(t.start_date.getTime()+i*24*60*60*1000).getDate(); break; 
					case 1: t.day_title[i].innerHTML = "Tuesday "+new Date(t.start_date.getTime()+i*24*60*60*1000).getDate(); break; 
					case 2: t.day_title[i].innerHTML = "Wednesday "+new Date(t.start_date.getTime()+i*24*60*60*1000).getDate(); break; 
					case 3: t.day_title[i].innerHTML = "Thursday "+new Date(t.start_date.getTime()+i*24*60*60*1000).getDate(); break; 
					case 4: t.day_title[i].innerHTML = "Friday "+new Date(t.start_date.getTime()+i*24*60*60*1000).getDate(); break; 
					case 5: t.day_title[i].innerHTML = "Saturday "+new Date(t.start_date.getTime()+i*24*60*60*1000).getDate(); break; 
					case 6: t.day_title[i].innerHTML = "Sunday "+new Date(t.start_date.getTime()+i*24*60*60*1000).getDate(); break; 
					}
					t.day_content[i].style.left = (dw*i+50)+"px";
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
					t.day_row_container.setAttribute("layout",h);
					container.widget.layout();
				}
				setTimeout(function() {
					var ok = true;
					for (var i = 0; i < 7; ++i)
						if (t.day_title[i].offsetHeight < t.day_title[i].scrollHeight) { ok = false; break; }
					if (ok) return;
					for (var i = 0; i < 7; ++i)
						switch (i) {
						case 0: t.day_title[i].innerHTML = "Mon "+new Date(t.start_date.getTime()+i*24*60*60*1000).getDate(); break; 
						case 1: t.day_title[i].innerHTML = "Tue "+new Date(t.start_date.getTime()+i*24*60*60*1000).getDate(); break; 
						case 2: t.day_title[i].innerHTML = "Wed "+new Date(t.start_date.getTime()+i*24*60*60*1000).getDate(); break; 
						case 3: t.day_title[i].innerHTML = "Thu "+new Date(t.start_date.getTime()+i*24*60*60*1000).getDate(); break; 
						case 4: t.day_title[i].innerHTML = "Fri "+new Date(t.start_date.getTime()+i*24*60*60*1000).getDate(); break; 
						case 5: t.day_title[i].innerHTML = "Sat "+new Date(t.start_date.getTime()+i*24*60*60*1000).getDate(); break; 
						case 6: t.day_title[i].innerHTML = "Sun "+new Date(t.start_date.getTime()+i*24*60*60*1000).getDate(); break; 
						}
				},1);
				t._timeout = null;
			},10);
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