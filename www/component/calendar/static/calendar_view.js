if (typeof require != 'undefined')
	require("calendar_objects.js");

/**
 * Manage the display of calendars from the given calendar manager. It instantiates the correct view (day, week, month...) for the display, and manage the switch between views.
 * @param {CalendarManager} calendar_manager the manager containing the list of calendars to display
 * @param {String} view_name name of the view to display first, or null for the default view (week)
 * @param {Number} zoom the initial zoom value, or null to use a default value
 * @param {DOMNode} container HTML element, or it's id, where to display calendar
 * @param {Function} onready called when the display is ready
 */
function CalendarView(calendar_manager, view_name, zoom, container, onready) {
	if (!view_name) view_name = 'week';
	if (!zoom) zoom = 60;
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;

	this.calendar_manager = calendar_manager;
	/** date to be displayed, by default it is today */ 
	this.cursor_date = new Date();
	this.cursor_date.setHours(0, 0, 0, 0);
	/** zoom in minutes */
	this.zoom = zoom;
	/** name of the view to be displayed */
	this.view_name = view_name;
	
	/** create the UI elements to display calendars */
	this._init = function() {
		while (container.childNodes.length > 0)
			container.removeChild(container.childNodes[0]);
		var has_fixed_height = getHeight(container) > 0;
		this.header = document.createElement("DIV");
		this.header.style.fontSize = '9pt';
		//this.header.style.backgroundColor = "#D8D8D8";
		this.header.className = "header";
		this.view_container_container = document.createElement("DIV");
		this.view_container_container.style.backgroundColor = "white";
		this.view_container = document.createElement("DIV");
		this.view_container.style.position = "relative";
		this.view_container_container.appendChild(this.view_container);
		if (view_name != "upcoming")
			container.appendChild(this.header);
		container.appendChild(this.view_container_container);
		var ready_count = 0;
		var ready = function() {
			if (++ready_count == 2) {
				if (onready) onready();
				layout.invalidate(container);
			}
		};
		if (has_fixed_height)
			require("vertical_layout.js",function(){
				t.header.setAttribute("layout", "28");
				t.view_container_container.setAttribute("layout", "fill");
				t.view_container_container.style.overflow = "auto";
				t.view_container.style.width = "100%";
				t.view_container.style.height = "100%";
				new vertical_layout(container);
				t.changeView(t.view_name, ready);
				ready();
			});
		else {
			this.changeView(this.view_name, ready);
			ready();
		}
		require("mac_tabs.js",function() {
			t.view_tabs = new mac_tabs();
			t.view_tabs.addItem("Day", "day");
			t.view_tabs.addItem("Week", "week");
			t.view_tabs.addItem("Month", "month");
			t.view_tabs.addItem("Year", "year");
			t.view_tabs.addItem("Agenda", "agenda");
			t.view_tabs.select(t.view_name);
			t.view_tabs.element.style.display = "inline-block";
			t.header.appendChild(t.view_tabs.element);
			t.view_tabs.onselect = function(view_name) {
				t.changeView(view_name);
			};
			t.position_div = document.createElement("DIV");
			t.position_div.style.display = "inline-block";
			t.position_div.style.textAlign = "center";
			t.position_div.style.marginTop = "3px";
			t.position_div.style.whiteSpace = "nowrap";
			t.position_back_step = document.createElement("IMG"); t.position_div.appendChild(t.position_back_step);
			t.position_back = document.createElement("IMG"); t.position_div.appendChild(t.position_back);
			t.position_text = document.createElement("SPAN"); t.position_div.appendChild(t.position_text);
			t.position_forward = document.createElement("IMG"); t.position_div.appendChild(t.position_forward);
			t.position_forward_step = document.createElement("IMG"); t.position_div.appendChild(t.position_forward_step);
			t.position_text.style.paddingLeft = "2px";
			t.position_text.style.paddingRight = "2px";
			t.position_back.style.verticalAlign = "middle";
			t.position_back.style.cursor = "pointer";
			t.position_back.style.padding = "1px";
			t.position_back.style.margin = "0px";
			t.position_back.className = "button_verysoft";
			t.position_back.onload = function() { t.updateHeader(); };
			t.position_back.src = "/static/calendar/back.png";
			t.position_back_step.style.verticalAlign = "middle";
			t.position_back_step.style.cursor = "pointer";
			t.position_back_step.style.padding = "1px";
			t.position_back_step.style.margin = "0px";
			t.position_back_step.className = "button_verysoft";
			t.position_back_step.onload = function() { t.updateHeader(); };
			t.position_back_step.src = "/static/calendar/back_step.png";
			t.position_forward.style.verticalAlign = "middle";
			t.position_forward.style.cursor = "pointer";
			t.position_forward.style.padding = "1px";
			t.position_forward.style.margin = "0px";
			t.position_forward.className = "button_verysoft";
			t.position_forward.onload = function() { t.updateHeader(); };
			t.position_forward.src = "/static/calendar/forward.png";
			t.position_forward_step.style.verticalAlign = "middle";
			t.position_forward_step.style.cursor = "pointer";
			t.position_forward_step.style.padding = "1px";
			t.position_forward_step.style.margin = "0px";
			t.position_forward_step.className = "button_verysoft";
			t.position_forward_step.onload = function() { t.updateHeader(); };
			t.position_forward_step.src = "/static/calendar/forward_step.png";
			t.position_back.onclick = function() { if (t.view) t.view.back(); t.updateHeader(); };
			t.position_back_step.onclick = function() { if (t.view) t.view.backStep(); t.updateHeader(); };
			t.position_forward.onclick = function() { if (t.view) t.view.forward(); t.updateHeader(); };
			t.position_forward_step.onclick = function() { if (t.view) t.view.forwardStep(); t.updateHeader(); };
			t.header.appendChild(t.position_div);
			t.zoom_div = document.createElement("DIV");
			t.zoom_div.style.display = "inline-block";
			t.zoom_div.style.marginTop = "3px";
			t.zoom_minus = document.createElement("IMG"); t.zoom_div.appendChild(t.zoom_minus);
			t.zoom_text = document.createElement("SPAN"); t.zoom_div.appendChild(t.zoom_text);
			t.zoom_plus = document.createElement("IMG"); t.zoom_div.appendChild(t.zoom_plus);
			t.zoom_minus.style.verticalAlign = "middle";
			t.zoom_plus.style.verticalAlign = "middle";
			t.zoom_minus.style.padding = "1px";
			t.zoom_plus.style.padding = "1px";
			t.zoom_minus.style.paddingRight = "3px";
			t.zoom_plus.style.paddingLeft = "3px";
			t.zoom_minus.className = "button_verysoft";
			t.zoom_plus.className = "button_verysoft";
			t.zoom_minus.onload = function() { t.updateHeader(); };
			t.zoom_plus.onload = function() { t.updateHeader(); };
			t.zoom_minus.src = theme.icons_16.zoom_in;
			t.zoom_plus.src = theme.icons_16.zoom_out;
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
				t.updateHeader();
				t.changeView(t.view_name);
			};
			t.zoom_plus.onclick = function() {
				if (t.zoom == 10) t.zoom = 15; else t.zoom *= 2;
				t.zoom_minus.style.cursor = "pointer";
				t.updateHeader();
				t.changeView(t.view_name);
			};
			
			if (t.view && t.view.zoom_supported)
				t.header.appendChild(t.zoom_div);

			t.updateHeader();
			layout.addHandler(t.header, function() { t.updateHeader(); });
			layout.invalidate(container);
		});
	};
	/** Called when the zoom is changed, to update the text displaying zoom information */
	this.updateHeader = function() {
		if (!this.view_tabs || !this.view) return;
		var w = this.header.clientWidth;
		w -= this.view_tabs.element.offsetWidth;
		this.position_div.style.position = "absolute";
		this.position_div.style.visibility = "hidden";
		this.position_div.style.top = "-10000px";
		this.position_div.style.width = "";
		this.zoom_div.style.position = "absolute";
		this.zoom_div.style.visibility = "hidden";
		this.zoom_div.style.top = "-10000px";

		var zoom_width = 0;
		if (this.view && this.view.zoom_supported) {
			this.zoom_div.style.position = "static";
			this.zoom_div.style.visibility = "visible";
			this.zoom_text.innerHTML = this.view.getZoomText(this.zoom);
			zoom_width = this.zoom_div.offsetWidth;
		}
		
		this.position_text.innerHTML = this.view.getPositionText(0); 
		var position_width = this.position_div.offsetWidth;

		var shorter = 1;
		while (w < position_width + zoom_width) {
			// try to reduce text
			var text = this.view.getPositionText(shorter++);
			if (text == null) break; // cannot reduce anymore
			this.position_text.innerHTML = text; 
			position_width = this.position_div.offsetWidth;
		}
		if (w < position_width + zoom_width && zoom_width > 0) {
			// try to reduce zoom, by removing its text
			this.zoom_text.innerHTML = "";
			zoom_width = this.zoom_div.offsetWidth;
		}
		if (w < position_width + zoom_width) {
			// reduce again, by removing position text!
			this.position_text.innerHTML = "";
			position_width = this.position_div.offsetWidth;
		}		
		if (w < position_width + zoom_width) {
			// we cannot show all
			// TODO ? menu ? / only one ?...
		} else {
			this.position_div.style.position = "static";
			this.position_div.style.visibility = "visible";
			this.zoom_div.style.position = "static";
			this.zoom_div.style.visibility = "visible";
			this.position_div.style.width = (w-zoom_width-5)+'px';
		}
	};
	
	/** Change the view
	 * @param {String} view_name name of the new view
	 * @param {Function} onready called when the change has been made and the new view is ready
	 */
	this.changeView = function(view_name, onready) {
		while (this.view_container.childNodes.length > 0)
			this.view_container.removeChild(this.view_container.childNodes[0]);
		if (t.view && t.view.zoom_supported && t.zoom_div)
			t.header.removeChild(t.zoom_div);
		require("calendar_view_"+view_name+".js",function() {
			t.view_name = view_name;
			t.view = new window["calendar_view_"+view_name](t, t.view_container);
			t.loadEvents();
			if (t.view && t.view.zoom_supported && t.zoom_div)
				t.header.appendChild(t.zoom_div);
			t.updateHeader();
			if (onready) onready();
		});
	};
	
	/** Set the zoom for the current view
	 * @param {Number} zoom the new zoom value
	 */
	this.setZoom = function(zoom) {
		this.zoom = zoom;
		t.updateHeader();
		t.changeView(t.view_name);		
	};
	
	/** Load all events from all calendars: iterate on all events, and call <code>addEvent</code> for each */
	this.loadEvents = function() {
		for (var i = 0; i < this.calendar_manager.calendars.length; ++i) {
			var cal = this.calendar_manager.calendars[i];
			for (var j = 0; j < cal.events.length; ++j)
				t.addEvent(cal.events[j]);
		}
	};
	/** Add a new event and display it
	 * @param {Object} ev the event to display
	 * @param {Number} try_counter internal use only, in case the view does not exist, we retry several time until the view is created, or we cancel the operation
	 */
	this.addEvent = function(ev, try_counter) {
		if (!container.parentNode || !container.ownerDocument || !getWindowFromDocument(container.ownerDocument)) return;
		if (this.view == null || typeof this.view == 'undefined') {
			if (!try_counter) try_counter = 0;
			if (try_counter == 100) {
				return;
			}
			setTimeout(function() { t.addEvent(ev, try_counter+1); }, 100); 
		};
		var e = copyCalendarEvent(ev);
		e.original_event = ev;
		if (ev.start.getTime() > this.view.end_date.getTime()) return; // after end
		if (ev.frequency == null) {
			// single instance
			if (ev.end.getTime() < this.view.start_date.getTime()) return; // before start
			this.view.addEvent(e);
			return;
		}
		
		if (ev.until && ev.until.getTime() < this.view.start_date.getTime()) return; // stop before
		
		if (ev.end.getTime() >= this.view.start_date.getTime()) // not before start
			this.view.addEvent(e); // add the first instance
		
		var event_duration = ev.end.getTime()-ev.start.getTime();
		if (ev.frequency && ev.frequency.frequency == "YEARLY") {
			var year = ev.start.getFullYear();
			var instance = 1; // the initial one
			if (ev.frequency.interval) year -= ev.frequency.interval; else year--;
			do {
				if (ev.frequency.interval) year += ev.frequency.interval; else year++;
				var instances = this._yearlyInstances(ev, year);
				if (ev.frequency.by_setpos) {
					var positions = ev.frequency.by_setpos.split(",");
					var stop = false;
					for (var i = 0; i < positions.length; ++i) {
						var pos = parseInt(positions[i]);
						if (pos < 0) pos = instances.length+pos;
						if (pos >= instances.length || pos <= 0) continue; // does not exist
						if (instances[pos].getTime() == ev.start.getTime()) { instance++; continue; } // same as initial one
						if (ev.frequency.until && instances[pos].getTime() > ev.frequency.until.getTime()) { stop = true; break; } // reach the until
						if (ev.frequency.count && instance > ev.frequency.count) { stop = true; break; } // reach the count
						if ((instances[pos].getTime() >= this.view.start_date.getTime() && 
							 instances[pos].getTime() <= this.view.end_date.getTime())
							||
							(instances[pos].getTime() + event_duration >= this.view.start_date.getTime() && 
							 instances[pos].getTime() + event_duration <= this.view.end_date.getTime()))
							this.view.addEvent(this.createEventInstance(ev, instances[pos]));
						instance++;
					}
					if (stop) break; else continue;
				} else {
					for (var i = 0; i < instances.length; ++i) {
						if (instances[i].getTime() == ev.start.getTime()) { instance++; continue; } // same as initial one
						if (ev.frequency.until && instances[i].getTime() > ev.frequency.until.getTime()) break; // reach the until
						if (ev.frequency.count && instance > ev.frequency.count) break; // reach the count
						if ((instances[i].getTime() >= this.view.start_date.getTime() && 
							 instances[i].getTime() <= this.view.end_date.getTime())
							||
							(instances[i].getTime() + event_duration >= this.view.start_date.getTime() && 
							 instances[i].getTime() + event_duration <= this.view.end_date.getTime()))
							this.view.addEvent(this.createEventInstance(ev, instances[i]));
						instance++;
					}
				}
			} while (new Date(year,0,1,0,0,0,0).getTime() < this.view.end_date.getTime());
		} else if (ev.frequency && ev.frequency.frequency == "MONTHLY") {
			var date = new Date(ev.start.getTime());
			var instance = 1; // the initial one
			if (!ev.frequency.interval) ev.frequency.interval = 1;
			date.setMonth(date.getMonth()-ev.frequency.interval);
			var months = null;
			if (ev.frequency.by_month) {
				months = ev.frequency.by_month.split(",");
				for (var i = 0; i < months.length; ++i) months[i] = parseInt(months[i])-1;
			}
			do {
				date.setMonth(date.getMonth()+ev.frequency.interval);
				// by month
				if (months != null) {
					if (!months.contains(date.getMonth())) continue;
				}
				var instances = this._monthlyInstances(ev, date);
				if (ev.frequency.by_setpos) {
					var positions = ev.frequency.by_setpos.split(",");
					var stop = false;
					for (var i = 0; i < positions.length; ++i) {
						var pos = parseInt(positions[i]);
						if (pos < 0) pos = instances.length+pos;
						if (pos >= instances.length || pos <= 0) continue; // does not exist
						if (instances[pos].getTime() == ev.start.getTime()) { instance++; continue; } // same as initial one
						if (ev.frequency.until && instances[pos].getTime() > ev.frequency.until.getTime()) { stop = true; break; } // reach the until
						if (ev.frequency.count && instance > ev.frequency.count) { stop = true; break; } // reach the count
						if ((instances[pos].getTime() >= this.view.start_date.getTime() && 
							 instances[pos].getTime() <= this.view.end_date.getTime())
							||
							(instances[pos].getTime() + event_duration >= this.view.start_date.getTime() && 
							 instances[pos].getTime() + event_duration <= this.view.end_date.getTime()))
							this.view.addEvent(this.createEventInstance(ev, instances[pos]));
						instance++;
					}
					if (stop) break; else continue;
				} else {
					for (var i = 0; i < instances.length; ++i) {
						if (instances[i].getTime() == ev.start.getTime()) { instance++; continue; } // same as initial one
						if (ev.frequency.until && instances[i].getTime() > ev.frequency.until.getTime()) break; // reach the until
						if (ev.frequency.count && instance > ev.frequency.count) break; // reach the count
						if ((instances[i].getTime() >= this.view.start_date.getTime() && 
							 instances[i].getTime() <= this.view.end_date.getTime())
							||
							(instances[i].getTime() + event_duration >= this.view.start_date.getTime() && 
							 instances[i].getTime() + event_duration <= this.view.end_date.getTime()))
							this.view.addEvent(this.createEventInstance(ev, instances[i]));
						instance++;
					}
				}
			} while (date < this.view.end_date.getTime());
		} else if (ev.frequency && ev.frequency.frequency == "WEEKLY") {
			var date = new Date(ev.start.getTime());
			var instance = 1; // the initial one
			if (!ev.frequency.interval) ev.frequency.interval = 1;
			var months = null;
			if (ev.frequency.by_month) {
				months = ev.frequency.by_month.split(",");
				for (var i = 0; i < months.length; ++i) months[i] = parseInt(months[i])-1;
			}
			var days;
			if (ev.frequency.by_week_day) {
				days = ev.frequency.by_week_day.split(",");
				for (var j = 0; j < days.length; ++j)
					days[j] = parseByDay(days[j]);
			} else {
				days = [[null,ev.start.getDay()]];
			}
			var wkst = 1;
			if (ev.frequency.week_start) wkst = parseWeekDay(ev.frequency.week_start);
			// start at the beginning of the week
			while (date.getDay() != wkst) date.setTime(date.getTime()-24*60*60*1000);
			date.setTime(date.getTime()-ev.frequency.interval*7*24*60*60*1000);
			do {
				date.setTime(date.getTime()+ev.frequency.interval*7*24*60*60*1000);
				var end = new Date(date.getTime()+7*24*60*60*1000);
				end.setHours(0,0,0,0);
				// by month
				if (months != null) {
					if (!months.contains(date.getMonth())) continue;
				}
				var instances = [];
				for (var i = 0; i < days.length; ++i) {
					if (days[i][0] == null) {
						// add this day of the week
						var d = new Date(date.getTime());
						while (d.getDay() != days[i][1]) d.setTime(d.getTime()+24*60*60*1000);
						if (d.getTime() < ev.start.getTime()) continue; // this is before the 1rst instance
						instances.push(d);
					} else {
						var d = getDateByDay(date.getFullYear(), date.getMonth(), days[i][0], days[i][1]);
						if (d == null) continue; // does not exist in this month
						if (d.getTime() < date.getTime() || d.getTime() >= end.getTime()) continue; // not in this week
						instances.push(d);
					}
				}
				if (ev.frequency.by_setpos) {
					var positions = ev.frequency.by_setpos.split(",");
					var stop = false;
					for (var i = 0; i < positions.length; ++i) {
						var pos = parseInt(positions[i]);
						if (pos < 0) pos = instances.length+pos;
						if (pos >= instances.length || pos <= 0) continue; // does not exist
						if (instances[pos].getTime() == ev.start.getTime()) { instance++; continue; } // same as initial one
						if (ev.frequency.until && instances[pos].getTime() > ev.frequency.until.getTime()) { stop = true; break; } // reach the until
						if (ev.frequency.count && instance > ev.frequency.count) { stop = true; break; } // reach the count
						if ((instances[pos].getTime() >= this.view.start_date.getTime() && 
							 instances[pos].getTime() <= this.view.end_date.getTime())
							||
							(instances[pos].getTime() + event_duration >= this.view.start_date.getTime() && 
							 instances[pos].getTime() + event_duration <= this.view.end_date.getTime()))
							this.view.addEvent(this.createEventInstance(ev, instances[pos]));
						instance++;
					}
					if (stop) break; else continue;
				} else {
					for (var i = 0; i < instances.length; ++i) {
						if (instances[i].getTime() == ev.start.getTime()) { instance++; continue; } // same as initial one
						if (ev.frequency.until && instances[i].getTime() > ev.frequency.until.getTime()) break; // reach the until
						if (ev.frequency.count && instance > ev.frequency.count) break; // reach the count
						if ((instances[i].getTime() >= this.view.start_date.getTime() && 
							 instances[i].getTime() <= this.view.end_date.getTime())
							||
							(instances[i].getTime() + event_duration >= this.view.start_date.getTime() && 
							 instances[i].getTime() + event_duration <= this.view.end_date.getTime()))
							this.view.addEvent(this.createEventInstance(ev, instances[i]));
						instance++;
					}
				}
			} while (date < this.view.end_date.getTime());
		} else if (ev.frequency && ev.frequency.frequency == "DAILY") {
			// TODO
		} else if (ev.frequency && ev.frequency.frequency == "HOURLY") {
			// TODO
		}
	};
	/** Create a copy of the given event, and set its date to the given date
	 * @param {Object} ev the event to copy
	 * @param {Date} date the date to set as the start date in the copy
	 * @returns {Object} the new event
	 */
	this.createEventInstance = function(ev, date) {
		var e = copyCalendarEvent(ev);
		e.original_event = ev;
		e.start = date;
		e.end = new Date(date.getTime()+(ev.end.getTime()-ev.start.getTime()));
		return e;
	};
	/**
	 * Calculates yearly instances of a recurring event.
	 * @param {Object} ev the recurring event
	 * @param {Number} year the year to compute
	 * @returns {Array} the list of instances in the given year
	 */
	this._yearlyInstances = function(ev, year) {
		var instances = [];
		// by default, yearly = only one
		instances.push(new Date(year, ev.start.getMonth(), ev.start.getDate(), ev.start.getHours(), ev.start.getMinutes(), 0, 0));
		// check by_month
		if (ev.frequency.by_month) {
			var months = ev.frequency.by_month.split(",");
			for (var i = 0; i < months.length; ++i) months[i] = parseInt(months[i])-1;
			if (months.length == 1) {
				// only one, it may change the month
				instances[0].setMonth(months[0]);
			} else {
				// more than one, it increases the number of instances
				instances = [];
				for (var i = 0; i < months.length; ++i)
					instances.push(new Date(year, months[i], ev.start.getDate(), ev.start.getHours(), ev.start.getMinutes(), 0, 0));
			}
		}
		// week number
		if (ev.frequency.by_week_no) {
			var wkst = 1; // default is MO 
			if (ev.frequency.week_start) wkst = parseWeekDay(ev.frequency.week_start);
			var week_nums = ev.frequency.by_week_no.split(",");
			for (var i = 0; i < week_nums.length; ++i) week_nums[i] = parseInt(week_nums[i]);
			var days;
			if (ev.frequency.by_week_day) {
				days = ev.frequency.by_week_day.split(",");
				for (var i = 0; i < days.length; ++i) {
					days[i] = parseWeekDay(days[i]);
					if (days[i] == -1) {
						// there is a number, this has no meaning...
						days.splice(i,1);
						i--;
					}
				}
				if (days.length == 0) days = [ev.start.getDay()];
			} else
				days = [ev.start.getDay()];
			if (instances.length == 1 && !ev.frequency.by_month) {
				// change the initial instance
				instances = [];
				for (var i = 0; i < week_nums.length; ++i) {
					var date = goToWeekNo(year, week_nums[i], wkst);
					for (var j = 0; j < days.length; ++j) {
						var d = new Date(date.getTime());
						if (d.getDay() != days[j]) {
							if (d.getDay() < days[j])
								d.setTime(d.getTime()+(days[j]-d.getDay())*24*60*60*1000);
							else
								d.setTime(d.getTime()+(7+days[j]-d.getDay())*24*60*60*1000);
						}
						instances.push(d);
					}
				}
			} else {
				// there was already months specified
				var base = instances;
				instances = [];
				for (var i = 0; i < week_nums.length; ++i) {
					var date = goToWeekNo(year, week_nums[i], wkst);
					for (var j = 0; j < days.length; ++j) {
						var d = new Date(date.getTime());
						if (d.getDay() != days[j]) {
							if (d.getDay() < days[j])
								d.setTime(d.getTime()+(days[j]-d.getDay())*24*60*60*1000);
							else
								d.setTime(d.getTime()+(7+days[j]-d.getDay())*24*60*60*1000);
						}
						for (var k = 0; k < base.length; ++k)
							if (base[k].getMonth() == d.getMonth()) {
								// compliant with the month
								instances.push(d);
								break;
							}
					}
				}
			}
		}
		// by year day
		if (ev.frequency.by_year_day) {
			var year_days = ev.frequency.by_year_day.split(",");
			for (var i = 0; i < year_days.length; i++) year_days[i] = parseInt(year_days[i]);
			if (!ev.frequency.by_month && !ev.frequency.by_week_no) {
				// just select that days in the year
				instances = [];
				for (var i = 0; i < year_days.length; ++i) {
					var d;
					if (year_days[i] > 0) {
						d = new Date(year, 0, 1, 0, 0, 0, 0);
						d.setTime(d.getTime()+(year_days[i]-1)*24*60*60*1000);
					} else {
						d = new Date(year, 11, 31, 0, 0, 0, 0);
						d.setTime(d.getTime()+(year_days[i]+1)*24*60*60*1000);
					}
					instances.push(d);
				}
			} else {
				// there was already months or week numbers: filter only to match the year day
				for (var i = 0; i < instances.length; ++i) {
					var ok = false;
					for (var j = 0; j < year_days.length; ++j) {
						var d;
						if (year_days[i] > 0) {
							d = new Date(year, 0, 1, 0, 0, 0, 0);
							d.setTime(d.getTime()+(year_days[i]-1)*24*60*60*1000);
						} else {
							d = new Date(year, 11, 31, 0, 0, 0, 0);
							d.setTime(d.getTime()+(year_days[i]+1)*24*60*60*1000);
						}
						if (instances[i].getFullYear() == d.getFullYear() &&
							instances[i].getMonth() == d.getMonth() &&
							instances[i].getDate() == d.getDate()
							) { ok = true; break; }
					}
					if (!ok) {
						instances.splice(i,1);
						i--;
					}
				}
			}
		}
		// by month day
		if (ev.frequency.by_month_day) {
			var month_days = ev.frequency.by_month_day.split(",");
			for (var i = 0; i < month_days.length; i++) month_days[i] = parseInt(month_days[i]);
			if (ev.frequency.by_week_no || ev.frequency.by_year_day) {
				// quite strange, let's remove the instances not compatible
				for (var i = 0; i < instances.length; ++i) {
					var ok = false;
					for (var j = 0; j < month_days.length; ++j)
						if (instances[i].getDate() == month_days[j]) { ok = true; break; }
					if (!ok) {
						instances.splice(i,1);
						i--;
					}
				}
			} else {
				if (month_days.length == 1) {
					// change the day
					for (var i = 0; i < instances.length; ++i)
						instances[i].setDate(month_days[0]);
				} else {
					// multiply
					base = instances;
					instances = [];
					for (var i = 0; i < base.length; ++i)
						for (var j = 0; j < month_days.length; ++j) {
							var d = new Date(base[i].getTime());
							d.setDate(month_days[j]);
							instances.push(d);
						}
				}
			}
		}
		// byday
		if (ev.frequency.by_week_day && !ev.frequency.by_week_no) {
			var days = ev.frequency.by_week_day.split(",");
			for (var j = 0; j < days.length; ++j)
				days[j] = parseByDay(days[j]);
			if (ev.frequency.by_year_day || ev.frequency.by_month_day) {
				// strange, let's remove the non-compliant
				for (var i = 0; i < instances.length; ++i) {
					var ok = false;
					for (var j = 0; j < days.length; ++j) {
						if (days[j][0] == null) {
							if (instances[i].getDay() == days[j][1]) { ok = true; break; }
						} else {
							var d = getDateByDay(year, instances[i].getMonth(), days[j][0], days[j][1]);
							if (d == null) continue; // does not exist in this month
							if (d.getDate() == instances[i].getDate()) { ok = true; break; }
						}
					}
					if (!ok) {
						instances.splice(i,1);
						i--;
					}
				}
			} else {
				// change/multiply
				base = instances;
				instances = [];
				for (var i = 0; i < base.length; ++i) {
					for (j = 0; j < days.length; ++j) {
						if (days[j][0] == null) {
							// take the next week day ?
							var d = base[i];
							while (d.getDay() != days[j][1]) d.setTime(d.getTime()+24*60*60*1000);
							instances.push(d);
						} else {
							var d = getDateByDay(year, base[i].getMonth(), days[j][0], days[j][1]);
							if (d != null) instances.push(d);
						}
					}
				}
			}
		}
		// TODO by_hour ?
		
		return instances;
	};
	/**
	 * Calculates monthly instances of a recurring event.
	 * @param {Object} ev the recurring event
	 * @param {Date} date the date to start to compute
	 * @returns {Array} the list of instances
	 */
	this._monthlyInstances = function(ev, date) {
		instances = [];
		instances.push(new Date(date.getTime()));
		if (ev.frequency.by_month_day) {
			var month_days = ev.frequency.by_month_day.split(",");
			for (var i = 0; i < month_days.length; i++) month_days[i] = parseInt(month_days[i]);
			instances = [];
			for (var i = 0; i < month_days.length; ++i) {
				var d = new Date(date.getFullYear(), date.getMonth(), month_days[i], date.getHours(), date.getMinutes(), 0, 0);
				if (d.getDate() != month_days[i]) continue;
				instances.push(d);
			}
		}
		if (ev.frequency.by_week_day) {
			var days = ev.frequency.by_week_day.split(",");
			for (var j = 0; j < days.length; ++j)
				days[j] = parseByDay(days[j]);
			if (ev.frequency.by_month_day) {
				// strange, remove the non compliant
				for (var i = 0; i < instances.length; ++i) {
					var ok = false;
					for (var j = 0; j < days.length; ++j) {
						if (days[j][0] == null) {
							if (instances[i].getDay() == days[j][1]) { ok = true; break; }
						} else {
							var d = getDateByDay(year, instances[i].getMonth(), days[j][0], days[j][1]);
							if (d == null) continue; // does not exist in this month
							if (d.getDate() == instances[i].getDate()) { ok = true; break; }
						}
					}
					if (!ok) {
						instances.splice(i,1);
						i--;
					}
				}
			} else {
				// multiply/change
				instances = [];
				for (i = 0; i < days.length; ++i) {
					if (days[i][0] == null) {
						// take the next week day ?
						var d = new Date(date.getTime());
						while (d.getDay() != days[i][1]) d.setTime(d.getTime()+24*60*60*1000);
						instances.push(d);
					} else {
						var d = getDateByDay(date.getFullYear(), date.getMonth(), days[i][0], days[i][1]);
						if (d != null) instances.push(d);
					}
				}
			}
		}
		return instances;
	};
	/** Remove an event from the display
	 * @param {Object} ev the event to remove
	 */
	this.removeEvent = function(ev) {
		if (!container.parentNode || !container.ownerDocument || !getWindowFromDocument(container.ownerDocument) || !this.view) return;
		this.view.removeEvent(ev.uid);
	};
	
	require("calendar_objects.js",function() {
		t.calendar_manager.on_event_added.add_listener(function(ev) { t.addEvent(ev); });
		t.calendar_manager.on_event_removed.add_listener(function(ev) { t.removeEvent(ev); });
		t.calendar_manager.on_event_updated.add_listener(function(ev) {
			t.view.removeEvent(ev);
			t.view.addEvent(ev);
		});
		t._init();
	});
}

/**
 * Compute the week number (in the year) of the given date
 * @param {Date} date the date
 * @param {Number} wkst first day of the week
 * @returns {Number} the week number on the year
 */
function getWeekNumber(date, wkst) {
	var w = 1;
	var d = new Date();
	d.setTime(date.getTime());
	d.setDate(1);
	d.setMonth(0);
	if (d.getDay() == wkst) {
		if (d.getDate() == date.getDate() && d.getMonth() == date.getMonth()) return 1;
		d.setDate(2);
	}
	while (d.getDate() != date.getDate() || d.getMonth() != date.getMonth()) {
		d.setDate(d.getDate()+1);
		if (d.getDay() == wkst) w++;
	}
	return w;
}
/**
 * Calculate the date corresponding to the first day of the given week
 * @param {Number} year year
 * @param {Number} week_no week number
 * @param {Number} wkst first day of the week
 * @returns {Date} the calculated date
 */
function goToWeekNo(year, week_no, wkst) {
	if (week_no > 0) {
		var w = 1;
		var d = new Date();
		d.setDate(1);
		d.setMonth(0);
		d.setFullYear(year);
		d.setHours(0,0,0,0);
		if (week_no == 1) return d;
		if (d.getDay() != wkst) {
			w = 2;
			while (d.getDay() != wkst) d.setTime(d.getTime()+24*60*60*1000);
		}
		while (w < week_no) {
			w++;
			d.setTime(d.getTime()+7*24*60*60*1000);
		}
		return d;
	}
	var d = new Date();
	d.setDate(31);
	d.setMonth(11);
	d.setFullYear(year);
	d.setHours(0,0,0,0);
	var w = -1;
	if (week_no == -1) return d;
	if (d.getDay() != wkst) {
		w = -2;
		while (d.getDay() != wkst) d.setTime(d.getTime()-24*60*60*1000);
	}
	while (w > week_no) {
		w--;
		d.setTime(d.getTime()-7*24*60*60*1000);
	}
	return d;
}
/**
 * Translate a week day represented by 2 letters into the week day number
 * @param {String} s the 2 letters representing of the week day
 * @returns {Number} the week day number
 */
function parseWeekDay(s) {
	switch (s) {
	case "SU": return 0;
	case "MO": return 1;
	case "TU": return 2;
	case "WE": return 3;
	case "TH": return 4;
	case "FR": return 5;
	case "SA": return 6;
	}
	return -1;
}
/**
 * Parse a 'by day' string from a recurring event, which is composed of an optional number, and 2-letters week day.
 * Exemple: 2SU means the second sunday of the month.
 * @param {String} s the string to parse
 * @returns {Array} [number,week day] where number may be null of it is not indicated
 */
function parseByDay(s) {
	var num = parseInt(s);
	var day = parseWeekDay(s.substring(s.length-2));
	return [isNaN(num) ? null : num, day];
}
/**
 * Calculate the date of the <code>num</code>th <code>week_day</code> of the <code>month</code> in the given <code>year</code>
 * @param {Number} year year
 * @param {Number} month month
 * @param {Number} num number
 * @param {Number} week_day week day
 * @returns {Date} the calculated date, or null if it does not exist
 */
function getDateByDay(year, month, num, week_day) {
	if (num > 0) {
		var d = new Date(year, month, 1, 0, 0, 0, 0);
		var i = 1;
		while (d.getMonth() == month) {
			if (week_day == d.getDay()) {
				if (i == num) return d;
				i++;
			}
			d.setTime(d.getTime()+24*60*60*1000);
		}
	} else {
		var d = new Date(year, month, 31, 0, 0, 0, 0);
		while (d.getMonth() != month) d.setTime(d.getTime()-24*60*60*1000);
		var i = -1;
		while (d.getMonth() == month) {
			if (week_day == d.getDay()) {
				if (i == num) return d;
				i--;
			}
			d.setTime(d.getTime()-24*60*60*1000);
		}
	}
	return null;
}