/**
 * View of one month
 * @param {CalendarView} view the view manager
 * @param {Element} container where to display
 */
function calendar_view_month(view, container) {

	/** {Date} The first day of the month to display */
	this.month = view.cursor_date.getMonth()+1;
	this.start_date = new Date(view.cursor_date.getTime());
	this.start_date.setDate(1);
	this.start_date.setHours(0,0,0,0);
	this.end_date = new Date(this.start_date.getTime());
	this.end_date.setMonth(this.end_date.getMonth()+1);
	this.end_date.setDate(this.end_date.getDate()-1);
	this.end_date.setHours(23,59,59,999);
	// go back to a Monday
	while (this.start_date.getDay() != 1) this.start_date.setTime(this.start_date.getTime()-24*60*60*1000);
	/** {Date} The last day of the month to display */
	// go to next Sunday
	while (this.end_date.getDay() != 0) this.end_date.setTime(this.end_date.getTime()+24*60*60*1000);
	
	var t=this;

	/** Returns a text to describe the current position of the view
	 * @param {Number} shorter indicates an index of how small we should try to make the text
	 * @returns {String} the text
	 */
	this.getPositionText = function(shorter) {
		switch (shorter) {
		case 0: // normal
			return getMonthName(this.month);
		case 1: // short name
			return getMonthShortName(this.month);
		}
		return null;
	};
	
	/** Called by the CalendarView when a new event should be displayed.
	 * @param {Object} ev the event to display
	 */
	this.addEvent = function(ev) {
		var td = this._getTD(ev.start);
		if (!td) return;
		var div = this._createEventDiv(ev);
		td.childNodes[0].childNodes[1].appendChild(div);
		// TODO events on several days
	};
	
	/** Called by the CalendarView when an event needs to be removed from the dislpay.
	 * @param {String} uid the uid of the event to remove
	 */
	this.removeEvent = function(uid) {
		
	};
	
	this._createEventDiv = function(ev) {
		var cal = window.top.CalendarsProviders.getProvider(ev.calendar_provider_id).getCalendar(ev.calendar_id);
		var div = document.createElement("DIV");
		div.style.backgroundColor = "#"+cal.color;
		require("color.js", function() {
			div.style.border = "1px solid "+color_string(color_darker(parse_hex_color(cal.color), 0x60));
		});
		div.style.overflow = 'hidden';
		div.style.padding = "1px";
		div.style.fontSize = '8pt';
		div.style.marginBottom = "1px";
		if (!ev.all_day) {
			var time = document.createElement("SPAN");
			time.style.fontSize = "90%";
			time.style.color = "#404040";
			time.style.marginRight = "3px";
			var time_str = ev.start.getHours()+":"+_2digits(ev.start.getMinutes());
			time_str += "-"+ev.end.getHours()+":"+_2digits(ev.end.getMinutes());
			time.appendChild(document.createTextNode(time_str));
			div.appendChild(time);
		}
		div.appendChild(document.createTextNode(ev.title));
		div.title = cal.name+"\r\n"+ev.title+"\r\n"+ev.description;
		div.style.cursor = "pointer";
		div.event = ev;
		div.onclick = function() {
			var ev = this.event;
			require("event_screen.js",function() {
				event_screen(ev.original_event, cal);
			});
		};
		return div;
	};
	
	this._getTD = function(date) {
		var start = this.start_date.getTime()/(24*60*60*1000);
		var day = date.getTime()/(24*60*60*1000);
		if (day < start) return null;
		var end = this.end_date.getTime()/(24*60*60*1000);
		if (day > end) return null;
		var week = Math.floor((day-start)/7);
		day = Math.floor(day-start)-week*7;
		return this._tbody.childNodes[week].childNodes[day];
	};
	
	this._layout = function() {
		var total_width = container.clientWidth;
		var day_width = Math.floor(total_width/7);
		// update day name in THEAD
		for (var i = 0; i < 7; ++i)
			this._thead.childNodes[0].childNodes[i].childNodes[0].nodeValue =
				day_width >= 75 ? getDayName(i) :
				day_width >= 30 ? getDayShortName(i) :
				getDayLetter(i);
		// update width of days
		for (var week = 0; week < this._tbody.childNodes.length; ++week) {
			var tr = this._tbody.childNodes[week];
			for (var day = 0; day < 7; ++day) {
				var div = tr.childNodes[day].childNodes[0];
				var header = div.childNodes[0];
				var content = div.childNodes[1];
				div.style.width = (day_width-2)+'px';
			}
		}
	};
	
	this._init = function() {
		this._table = document.createElement("TABLE");
		this._table.style.borderCollapse = "collapse";
		this._table.style.borderSpacing = "0px";
		this._thead = document.createElement("THEAD"); this._table.appendChild(this._thead);
		this._tbody = document.createElement("TBODY"); this._table.appendChild(this._tbody);
		var tr = document.createElement("TR");
		this._thead.appendChild(tr);
		for (var i = 0; i < 7; ++i) {
			var td = document.createElement("TD");
			tr.appendChild(td);
			td.appendChild(document.createTextNode(""));
			td.style.textAlign = "center";
			td.style.fontWeight = "bold";
			td.style.backgroundColor = "#D0D0D0";
			td.style.border = "1px solid black";
		}
		var nb_weeks = Math.floor((this.end_date.getTime()+1-this.start_date.getTime())/(24*60*60*1000)/7);
		for (var i = 0; i < nb_weeks; ++i) {
			tr = document.createElement("TR");
			this._tbody.appendChild(tr);
			for (var j = 0; j < 7; ++j) {
				var td = document.createElement("TD");
				td.date = new Date(this.start_date.getTime()+(i*7+j)*24*60*60*1000);
				var same_month = td.date.getMonth()+1 == this.month;
				var today = new Date(); today.setHours(0,0,0,0);
				var is_today = td.date.getTime() == today.getTime();
				td.style.padding = "0px";
				td.style.border = "1px solid black";
				td.style.verticalAlign = "top";
				td.style.backgroundColor = same_month ? (is_today ? "#C0FFC0" : "#FFFFFF") : "#E0E0E0";
				if (!same_month) setOpacity(td, 0.8);
				tr.appendChild(td);
				var div = document.createElement("DIV");
				td.appendChild(div);
				var header = document.createElement("DIV");
				header.style.borderBottom = "1px solid black";
				header.style.backgroundColor = is_today ? "#D0FFD0" : same_month ? "#D0D0D0" : "#E8E8E8";
				header.style.textAlign = "center";
				header.style.fontWeight = is_today ? "bold" : "normal";
				header.style.color = is_today ? "#008000" : "black";
				header.appendChild(document.createTextNode(td.date.getDate()));
				var content = document.createElement("DIV");
				content.style.minHeight = "10px";
				content.style.padding = "1px";
				content.style.paddingBottom = "0px";
				div.appendChild(header);
				div.appendChild(content);
				if (is_today) {
					td.style.borderColor = "#008000";
					if (td.previousSibling) td.previousSibling.style.borderRightColor = "#008000";
				}
			}
		}
		container.appendChild(this._table);
		this._layout();
	};
	this._init();
	layout.listenElementSizeChanged(container, function() { t._layout(); });
}