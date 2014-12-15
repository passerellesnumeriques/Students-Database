/**
 * View upcoming events of calendars
 * @param {CalendarView} view view manager
 * @param {Element} container where to display
 */
function calendar_view_upcoming(view, container) {
	/** Starting date to display */ 
	this.start_date = new Date();
	this.start_date.setHours(0,0,0,0);
	/** Maximum date to display */
	this.end_date = new Date(this.start_date.getTime()+view.zoom*24*60*60*1000-1);
	/** List of rows of the agenda */
	this._rows = [];
	
	/** returns an empty string 
	 * @returns {String} empty string
	 */
	this.getPositionText = function() { return ""; };
	
	/** Initialize the display */
	this._init = function() {
		this.div = document.createElement("DIV");
		container.appendChild(this.div);
	};
	/** Build the content of the display */
	this._reload = function() {
		return;
		this.div.removeAllChildren();
		this._rows = [];
		var date = new Date(this.start_date.getTime());
		while (date.getTime() <= this.end_date.getTime()) {
			var tr = document.createElement("TR"); this.table.appendChild(tr);
			var td = document.createElement("TD"); tr.appendChild(td);
			td.style.borderBottom = "1px solid #A0A0A0";
			td.innerHTML = date.toDateString();
			var td = document.createElement("TD"); tr.appendChild(td);
			td.style.borderBottom = "1px solid #A0A0A0";
			tr.date = new Date(date.getTime());
			var table = document.createElement("TABLE");
			td.appendChild(table);
			table.appendChild(tr.table = document.createElement("TBODY"));
			this._rows.push(tr);
			date.setTime(date.getTime()+24*60*60*1000);
		}
	};
	/** Get the text to display to describe the given date (yesterday, tomorrow...)
	 * @param {Date} date the date to display
	 * @returns {String} the text
	 */
	this._getDateText = function(date) {
		var today = new Date();
		today.setHours(0,0,0,0);
		var s = "";
		if (date.getTime() < today.getTime()) s += "Since ";
		if (date.getTime() >= today.getTime()-24*60*60*1000 && date.getTime() < today.getTime()) {
			s += "Yesterday ("+date.toDateString()+")";
		} else if (date.getTime() >= today.getTime() && date.getTime() < today.getTime()+24*60*60*1000) {
			s += "Today ("+date.toDateString()+")";
		} else if (date.getTime() >= today.getTime()+24*60*60*1000 && date.getTime() < today.getTime()+2*24*60*60*1000) {
			s += "Tomorrow ("+date.toDateString()+")";
		} else
			s += date.toDateString();
		return s;
	};
	
	/** Called by the CalendarView when a new event should be displayed.
	 * @param {Object} ev the event to display
	 */
	this.addEvent = function(ev) {
		if (ev.all_day) {
			// check this is not in fact in the past
			var utc = new Date(ev.end.getTime()-1);
			var end = new Date();
			end.setFullYear(utc.getUTCFullYear());
			end.setMonth(utc.getUTCMonth());
			end.setDate(utc.getUTCDate());
			end.setHours(0,0,0,0);
			var today = new Date();
			today.setHours(0,0,0,0);
			if (end.getTime() < today.getTime()) return;
		}
		var row = null;
		for (var i = 0; i < this._rows.length; ++i)
			if (ev.start.getTime() >= this._rows[i].date.getTime() && ev.start.getTime() < this._rows[i].date.getTime()+24*60*60*1000) {
				row = this._rows[i];
				break;
			}
		if (row == null) {
			var next_row = null;
			var next_row_index = -1;
			var row_date = new Date(ev.start.getTime());
			row_date.setHours(0,0,0,0);
			for (var i = 0; i < this._rows.length; ++i)
				if (this._rows[i].date.getTime() > row_date.getTime() && (next_row == null || next_row.date.getTime() > this._rows[i].date.getTime())) {
					next_row = this._rows[i];
					next_row_index = i;
				}
			row = document.createElement("DIV");
			row.style.borderBottom = "1px solid #808080";
			row.date = row_date;
			row.title_div = document.createElement("DIV");
			row.title_div.appendChild(document.createTextNode(this._getDateText(row_date)));
			row.title_div.style.fontWeight = "bold";
			row.title_div.style.color = "black";
			row.appendChild(row.title_div);
			row.ul = document.createElement("UL");
			row.appendChild(row.ul);
			if (next_row == null) {
				this._rows.push(row);
				this.div.appendChild(row);
			} else {
				this._rows.splice(next_row_index,0,row);
				this.div.insertBefore(row, next_row);
			}
		}

		var cal = window.top.CalendarsProviders.getProvider(ev.calendar_provider_id).getCalendar(ev.calendar_id);
		if (!cal) return;

		var li = document.createElement("LI");
		li.onmouseover = function() { this.style.textDecoration = "underline"; };
		li.onmouseout = function() { this.style.textDecoration = "none"; };
		li.title = "Calendar "+cal.name+(ev.description ? "\r\n"+ev.description : "");
		li.style.cursor = "pointer";
		li.style.fontSize = "9pt";
		li.event = ev;
		if (!ev.all_day) {
			var span = document.createElement("SPAN");
			span.appendChild(document.createTextNode(_2digits(ev.start.getHours())+":"+_2digits(ev.start.getMinutes())+" - "+_2digits(ev.end.getHours())+":"+_2digits(ev.end.getMinutes())));
			span.style.color = "#404040";
			span.style.fontSize = "8pt";
			li.appendChild(span);
			li.appendChild(document.createTextNode(" - "));
		}
		if (cal.icon) {
			var icon = document.createElement("IMG");
			icon.src = cal.icon;
			icon.style.verticalAlign = "bottom";
			icon.style.marginRight = "2px";
			li.appendChild(icon);
		}
		li.appendChild(document.createTextNode(ev.title));
		if (ev.end.getTime()-ev.start.getTime() >= 24*60*60*1000) {
			var span = document.createElement("SPAN");
			span.style.marginLeft = "5px";
			span.style.color = "#404040";
			span.style.fontSize = "8pt";
			span.appendChild(document.createTextNode("until "+this._getDateText(ev.end)));
			li.appendChild(document.createElement("BR"));
			li.appendChild(span);
		}
		li.onclick = function(e) {
			require("event_screen.js",function() {
				var cal = window.top.CalendarsProviders.getProvider(ev.calendar_provider_id).getCalendar(ev.calendar_id);
				event_screen(ev.original_event, cal);
			});
			stopEventPropagation(e);
			return false;
		};
		
		var added = false;
		for (var i = 0; i < row.ul.childNodes.length; ++i) {
			if (ev.all_day) {
				if (!row.ul.childNodes[i].event.all_day) {
					row.ul.insertBefore(li, row.ul.childNodes[i]);
					added = true;
					break;
				}
				continue;
			} else {
				if (row.ul.childNodes[i].event.all_day) continue;
				if (row.ul.childNodes[i].event.start.getTime() < ev.start.getTime()) continue;
				row.ul.insertBefore(li, row.ul.childNodes[i]);
				added = true;
				break;
			}
		}
		if (!added)
			row.ul.appendChild(li);
	};
	
	/** Called by the CalendarView when an event needs to be removed from the dislpay.
	 * @param {String} uid the uid of the event to remove
	 */
	this.removeEvent = function(uid) {
		for (var row_i = 0; row_i < this._rows.length; ++row_i) {
			var row = this._rows[row_i];
			for (var i = 0; i < row.ul.childNodes.length; ++i) {
				var li = row.ul.childNodes[i];
				if (li.event && li.event.uid == uid) {
					row.ul.removeChild(li);
					if (row.ul.childNodes.length == 0) {
						this._rows.remove(row);
						this.div.removeChild(row);
						break;
					}
					i--;
				}
			}
		}
	};

	this._init();
	
}