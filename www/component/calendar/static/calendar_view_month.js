/**
 * View of one month
 * @param {CalendarView} view the view manager
 * @param {Element} container where to display
 */
function calendar_view_month(view, container) {

	/**
	 * Adjust the start and end dates to start on a Monday, and to end on a Sunday
	 */
	this._adjustDates = function() {
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
	};
	this._adjustDates();
	
	var t=this;

	/** Returns a text to describe the current position of the view
	 * @param {Number} shorter indicates an index of how small we should try to make the text
	 * @returns {String} the text
	 */
	this.getPositionText = function(shorter) {
		switch (shorter) {
		case 0: // normal
			return getMonthName(this.month)+" "+view.cursor_date.getFullYear();
		case 1: // short name
			return getMonthShortName(this.month)+" "+view.cursor_date.getFullYear();
		case 2: // remove year
			return getMonthShortName(this.month);
		}
		return null;
	};
	
	/** Goes one month before */
	this.back = function() {
		view.cursor_date.setMonth(view.cursor_date.getMonth()-1);
		this._adjustDates();
		this._reloadEvents();
	};
	/** Goes one year before */
	this.backStep = function() {
		view.cursor_date.setFullYear(view.cursor_date.getFullYear()-1);
		this._adjustDates();
		this._reloadEvents();
	};
	/** Goes one month after */
	this.forward = function() {
		view.cursor_date.setMonth(view.cursor_date.getMonth()+1);
		this._adjustDates();
		this._reloadEvents();
	};
	/** Goes one year after */
	this.forwardStep = function() {
		view.cursor_date.setFullYear(view.cursor_date.getFullYear()+1);
		this._adjustDates();
		this._reloadEvents();
	};
	/** Refresh the view by reloading the events according to the start and end dates */
	this._reloadEvents = function() {
		container.removeAllChildren();
		this._init();
		view.loadEvents();
	};
	
	/** Called by the CalendarView when a new event should be displayed.
	 * @param {Object} ev the event to display
	 */
	this.addEvent = function(ev) {
		if (ev.all_day && ev.end.getTime()-ev.start.getTime()>24*60*60*1000) {
			// event on several days
			// calculate first and last days, of event, and of the view
			var first_day = Math.floor(ev.start.getTime()/(24*60*60*1000));
			var start = new Date(); start.setUTCFullYear(this.start_date.getFullYear()); start.setUTCMonth(this.start_date.getMonth()); start.setUTCDate(this.start_date.getDate()); start.setUTCHours(0,0,0,0);
			var first_day_of_view = Math.floor(start.getTime()/(24*60*60*1000));
			var first_day_visible = first_day > first_day_of_view ? first_day : first_day_of_view;
			var last_day = Math.floor(ev.end.getTime()/(24*60*60*1000));
			var end = new Date(); end.setUTCFullYear(this.end_date.getFullYear()); end.setUTCMonth(this.end_date.getMonth()); end.setUTCDate(this.end_date.getDate()); end.setUTCHours(23,59,59,999);
			var last_day_of_view = Math.floor(end.getTime()/(24*60*60*1000));
			var last_day_visible = last_day < last_day_of_view ? last_day : last_day_of_view;
			// calculate starting cell, and ending cell
			var start_day = first_day_visible-first_day_of_view;
			var start_week = Math.floor(start_day/7);
			start_day -= start_week*7;
			var end_day = last_day_visible-first_day_of_view;
			var end_week = Math.floor(end_day/7);
			end_day -= end_week*7;
			for (var week = start_week; week <= end_week; ++week) {
				// create a div
				var div = this._createEventDiv(ev);
				if (!div) return; // event does not exist anymore
				div.style.position = "relative"; // make it visible over table cells borders
				// calculate where it should be displayed
				var start_col = week > start_week ? 0 : start_day;
				var end_col = week < end_week ? 6 : end_day;
				div._start_col = start_col;
				div._end_col = end_col;
				div._insidePadding = 2;
				// add arrows if needed
				if (week > start_week || (week == 0 && first_day < first_day_visible)) {
					var img = document.createElement("IMG");
					img.src = theme.icons_10.arrow_left_black;
					img.style.position = "absolute";
					img.style.top = "2px";
					img.style.left = "0px";
					div.style.paddingLeft = "9px";
					div._insidePadding += 8;
					div.appendChild(img);
				}
				if (week < end_week || (week == this._tbody.childNodes.length && last_day > last_day_visible)) {
					var img = document.createElement("IMG");
					img.src = theme.icons_10.arrow_right_black;
					img.style.position = "absolute";
					img.style.top = "2px";
					img.style.right = "0px";
					div.style.paddingRight = "9px";
					div._insidePadding += 8;
					div.appendChild(img);					
				}
				// insert the div at the first day
				var content = this._tbody.childNodes[week].childNodes[start_col].childNodes[0].childNodes[1];
				content.insertBefore(div, content.firstChild);
				layout.changed(content);
				// indicates overriding space to other cells
				for (var i = start_col+1; i <= end_col; ++i) {
					var td = this._tbody.childNodes[week].childNodes[i];
					td._overriding_divs.push(div);
				}
			}
		} else {
			var td = this._getTD(ev.start);
			if (!td) return;
			var div = this._createEventDiv(ev);
			if (!div) return; // event does not exist anymore
			var content = td.childNodes[0].childNodes[1];
			if (ev.all_day) {
				// insert after last all_day event
				var next = content.firstChild;
				while (next != null && next.event.all_day) next = next.nextSibling;
				if (!next) content.appendChild(div);
				else content.insertBefore(div, next);
			} else {
				// insert after all_day events, and chronologically by time
				var next = content.firstChild;
				while (next != null && (next.event.all_day || next.event.start.getTime() <= ev.start.getTime())) next = next.nextSibling;
				if (!next) content.appendChild(div);
				else content.insertBefore(div, next);
			}
			layout.changed(td);
		}
	};
	
	/** Called by the CalendarView when an event needs to be removed from the dislpay.
	 * @param {String} uid the uid of the event to remove
	 */
	this.removeEvent = function(uid) {
		for (var week = 0; week < this._tbody.childNodes.length; ++week) {
			var tr = this._tbody.childNodes[week];
			for (var day = 0; day < tr.childNodes.length; ++day) {
				var td = tr.childNodes[day];
				for (var i = 0; i < td._overriding_divs.length; ++i)
					if (td._overriding_divs[i].event.uid == uid) {
						td._overriding_divs.splice(i,1);
						i--;
						layout.changed(td);
					}
				var content = td.childNodes[0].childNodes[1];
				for (var i = 0; i < content.childNodes.length; ++i) {
					var div = content.childNodes[i];
					if (div.event.uid == uid) {
						content.removeChild(div);
						i--;
						layout.changed(content);
					}
				}
			}
		}
	};
	
	/** Create the DIV element to display an event */
	this._createEventDiv = function(ev) {
		var cal = window.top.CalendarsProviders.getProvider(ev.calendar_provider_id).getCalendar(ev.calendar_id);
		if (!cal) return null; // calendar has been removed
		var div = createEventDiv(ev,cal);
		if (!div) return null;
		div.style.overflow = 'hidden';
		div.style.marginBottom = "1px";
		return div;
	};
	/** Retrieve the cell corresponding to the given date
	 * @param {Date} date the date we are looking for
	 * @returns {Element} the cell, or null if the date is not in the range
	 */
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
	/** Re-layout cells, to handle events which are on several days */
	t._refreshCellsLayout = function() {
		for (var week = 0; week < this._tbody.childNodes.length; ++week) {
			var tr = this._tbody.childNodes[week];
			for (var day = 0; day < tr.childNodes.length; ++day) {
				var td = tr.childNodes[day];
				var space = 0;
				for (var i = 0; i < td._overriding_divs.length; ++i) {
					var div = td._overriding_divs[i];
					var h = div.offsetTop+div.offsetHeight;
					if (space < h) space = h;
				}
				var content = td.childNodes[0].childNodes[1];
				content.style.paddingTop = (space+1)+"px";
				for (var i = 0; i < content.childNodes.length; ++i) {
					var div = content.childNodes[i];
					if (typeof div._start_col != 'undefined') {
						// re-calculate width
						var width = 0;
						for (var col = div._start_col; col <= div._end_col; ++col) {
							width += tr.childNodes[col].childNodes[0].offsetWidth+1;
							//if (col == 0 || col == 6) width -= 1;
						}
						width -= 5+div._insidePadding;
						div.style.width = width+"px";
					}
				}
			}
		}
	};
	/** Layout the view to adjust according to the available space */
	this._layout = function() {
		var total_width = container.clientWidth;
		var day_width = total_width/7;
		var widths = [];
		var w = total_width;
		var nb = 7;
		for (var i = 0; i < 7; i++) {
			widths[i] = Math.floor(w/nb--);
			w -= widths[i];
		}
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
				div.style.width = (widths[day]-1-(day==0||day==6?1:0))+'px';
			}
		}
		t._refreshCellsLayout();
	};
	/** Initialization of the view */
	this._init = function() {
		container.style.overflow = "auto";
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
				td.style.borderLeft = "1px solid black";
				td.style.borderRight = "1px solid black";
				td.style.borderTop = "2px solid black";
				td.style.borderBottom = "2px solid black";
				td.style.verticalAlign = "top";
				td._overriding_divs = [];
				//td.style.backgroundColor = same_month ? (is_today ? "#C0FFC0" : "#FFFFFF") : "#E0E0E0";
				//if (!same_month) setOpacity(td, 0.8);
				tr.appendChild(td);
				var div = document.createElement("DIV");
				td.appendChild(div);
				var header = document.createElement("DIV");
				header.style.borderBottom = "2px solid "+(is_today ? "#008000" : "black");
				header.style.backgroundColor = is_today ? "#D0FFD0" : same_month ? "#000000" : "#606060";
				header.style.textAlign = "center";
				header.style.fontWeight = "bold";
				header.style.color = is_today ? "#008000" : same_month ? "white" : "#C0C0C0";
				header.appendChild(document.createTextNode(td.date.getDate()));
				var content = document.createElement("DIV");
				content.style.minHeight = "10px";
				content.style.padding = "1px";
				content.style.paddingBottom = "0px";
				content.style.position = "relative"; // make offset of divs inside relative to this one
				div.appendChild(header);
				div.appendChild(content);
				if (is_today) {
					td.style.borderColor = "#008000";
					if (td.previousSibling) td.previousSibling.style.borderRightColor = "#008000";
					if (tr.previousSibling) tr.previousSibling.childNodes[j].style.borderBottomColor = "#008000";
				}
			}
		}
		container.appendChild(this._table);
		this._layout();
	};
	this._init();
	layout.listenElementSizeChanged(container, function() { t._layout(); });
	layout.listenInnerElementsChanged(container, function() { t._refreshCellsLayout(); });
}