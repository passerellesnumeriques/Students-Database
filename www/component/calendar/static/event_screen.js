/**
 * Display a popup window with an event details
 * @param {Object} ev the event to display or null to create a new event
 * @param {Calendar} default_calendar default calendar for an event creation
 * @param {Date} new_datetime in case of an event creation, indicates the pre-filled date and time of the event
 * @param {Boolean} new_all_day in case of an event creation, indicates if the event is pre-filled as an <i>all day</i> event
 */
function event_screen(ev,default_calendar,new_datetime,new_all_day) {
	var t=this;
	/** Copy of the given event, that will be manipulated and updated according to the screen, without modifying directly the event (in case the user finally cancel) */
	this.event = copyCalendarEvent(ev);
	/** Calendar of the given event, or null if this is a new event */
	this.original_calendar = ev ? window.top.CalendarsProviders.getProvider(ev.calendar_provider_id).getCalendar(ev.calendar_id) : null;
	/** {Boolean} indicates if we can edit and save the event */
	this.editable = ev ? (typeof this.original_calendar.saveEvent) == 'function' : true;
	/** {Boolean} indicates if we can remove this event */
	this.removable = ev ? (typeof this.original_calendar.removeEvent) == 'function' : false;

	/**
	 * Populate information from the display into the given event
	 * @param {CalendarEvent} event the event to populate
	 * @returns {String|null} an error message, or null if everything was ok
	 */
	this.populateEvent = function(event) {
		var err = this.calendar.populate(event);
		if (err) return err;
		err = this.what.populate(event);
		if (err) return err;
		err = this.when.populate(event);
		if (err) return err;
		err = this.who.populate(event);
		if (err) return err;
		return null;
	};
	/**
	 * Save the event
	 * @param {CalendarEvent} event the event to save
	 */
	this.saveEvent = function(event) {
		var calendar = window.top.CalendarsProviders.getProvider(event.calendar_provider_id).getCalendar(event.calendar_id);
		if (ev && (ev.calendar_id != event.calendar_id || ev.calendar_provider_id != event.calendar_provider_id)) {
			if (!confirm("You are going to move the event to a different calendar. Are you sure you want to remove it from "+t.original_calendar.name+" and create it into "+calendar.name+" ?"))
				return;
			// TODO
			return;
		}
		calendar.saveEvent(event);
	};
	
	/** Initialize the display */
	this._init = function() {
		this._table = document.createElement("TABLE");
		this._table.style.borderCollapse = "collapse";
		this._table.style.borderSpacing = "0px";
		var tr,td;
		
		// Calendar
		this._table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "Calendar";
		this._styleLeftTitle(td);
		td.style.paddingTop = "3px";
		tr.appendChild(td = document.createElement("TD"));
		setBoxShadow(td, 0, 2, 2, -1, "#808080");
		td.style.paddingBottom = "5px";
		td.style.paddingTop = "3px";
		this.calendar = new event_screen_calendar_selector(td, ev ? window.top.CalendarsProviders.getProvider(ev.calendar_provider_id).getCalendar(ev.calendar_id) : default_calendar, this.editable, this.event == null);
		
		// What
		this._table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "What";
		this._styleLeftTitle(td);
		td.style.paddingTop = "3px";
		tr.appendChild(td = document.createElement("TD"));
		setBoxShadow(td, 0, 2, 2, -1, "#808080");
		td.style.paddingBottom = "5px";
		td.style.paddingTop = "3px";
		this.what = new event_screen_what(td, ev ? ev.title : null, ev ? ev.description : null, this.editable);
		
		// When
		this._table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "When";
		this._styleLeftTitle(td);
		td.style.paddingTop = "3px";
		tr.appendChild(td = document.createElement("TD"));
		setBoxShadow(td, 0, 2, 2, -1, "#808080");
		td.style.paddingBottom = "5px";
		td.style.paddingTop = "3px";
		this.when = new event_screen_when(td, ev ? ev.start : new_datetime, ev ? ev.end : new Date(new_datetime.getTime()+60*60*1000), ev ? ev.all_day : new_all_day, ev ? ev.frequency : null, this.editable);
		
		// Who
		this._table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "Who";
		this._styleLeftTitle(td);
		td.style.paddingTop = "3px";
		tr.appendChild(td = document.createElement("TD"));
		setBoxShadow(td, 0, 2, 2, -1, "#808080");
		td.style.paddingBottom = "5px";
		td.style.paddingTop = "3px";
		this.who = new event_screen_who(td, ev ? ev.attendees : [], this.editable);
		
		// app link
		if (ev && ev.app_link) {
			this._table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.style.paddingTop = "3px";
			td.colSpan = 2;
			var link = document.createElement("A");
			link.style.fontWeight = "bold";
			if (ev.app_link.startsWith("popup:")) {
				link.href = '#';
				link.onclick = function() {
					window.top.require("popup_window.js", function() {
						var popup = new window.top.popup_window(ev.title,null,"");
						popup.setContentFrame(ev.app_link.substring(6));
						popup.showPercent(95,95);
					});
					return false;
				};
			} else
				link.href = ev.app_link;
			link.innerHTML = ev.app_link_name;
			td.appendChild(link);
		}
		
		var popup = new popup_window("Event", "/static/calendar/event.png",this._table);
		if (!ev) {
			popup.addCreateButton(function() {
				var event = new CalendarEvent();
				var err = t.populateEvent(event);
				if (err) { alert(err); return; }
				t.saveEvent(event);
				popup.close();
			});
			popup.addCancelButton();
		} else {
			if (this.editable)
				popup.addSaveButton(function() {
					var event = new CalendarEvent();
					event.id = ev.id;
					event.uid = ev.uid;
					var err = t.populateEvent(event);
					if (err) { alert(err); return; }
					t.saveEvent(event);
					popup.close();
				});
			if (this.removable)
				popup.addIconTextButton(theme.icons_16.remove, "Remove this event", 'remove', function() {
					confirmDialog("Are you sure you want to remove this event ?", function(yes) {
						if (!yes) return;
						t.original_calendar.removeEvent(ev);
						popup.close();
					});
				});
			popup.addCloseButton();
		}
		popup.show();
	};
	/** Apply title style for left side
	 * @param {Element} td the TD
	 */
	this._styleLeftTitle = function(td) {
		td.style.color = "#606060";
		td.style.fontWeight = "bold";
		td.style.verticalAlign = "top";
		td.style.textAlign = "left";
	};
	require("popup_window.js",function() {
		t._init();
	})
}

/**
 * Section to select the calendar
 * @param {Element} container where to put the section
 * @param {Calendar} calendar the current calendar
 * @param {Boolean} editable indicates if we can modify it
 * @param {Boolean} is_new indicates if this is a new event
 */
function event_screen_calendar_selector(container, calendar, editable, is_new) {
	this.selected_calendar = calendar;

	/** Icon representing the calendar provider */
	this.calendar_provider_icon = document.createElement("IMG");
	this.calendar_provider_icon.width = 16;
	this.calendar_provider_icon.style.width = '16px';
	this.calendar_provider_icon.src = calendar.provider.getProviderIcon();
	this.calendar_provider_icon.style.marginRight = '3px';
	this.calendar_provider_icon.style.verticalAlign = 'bottom';
	container.appendChild(this.calendar_provider_icon);
	/** Box with the color of the calendar */
	this.calendar_box = document.createElement("DIV");
	container.appendChild(this.calendar_box);
	this.calendar_box.style.display = "inline-block";
	this.calendar_box.style.width = "10px";
	this.calendar_box.style.height = "10px";
	this.calendar_box.style.border = "1px solid #"+calendar.color;
	this.calendar_box.style.backgroundColor = "#"+calendar.color;
	this.calendar_box.style.marginRight = '3px';
	/** Icon representing the calendar, if any */
	this.calendar_icon = document.createElement("IMG");
	this.calendar_icon.width = 16;
	this.calendar_icon.style.width = '16px';
	this.calendar_icon.style.marginRight = '3px';
	this.calendar_icon.style.verticalAlign = 'bottom';
	if (calendar.icon)
		this.calendar_icon.src = calendar.icon;
	else
		this.calendar_icon.style.display = "none";
	container.appendChild(this.calendar_icon);
	/** Name of the calendar */
	this.calendar_name_node = document.createTextNode(calendar.name)
	container.appendChild(this.calendar_name_node);
	if (editable) {
		container.style.cursor = "pointer";
		var t=this;
		container.onclick = function() {
			require("context_menu.js",function(){
				var menu = new context_menu();
				menu.addTitleItem(null,is_new ? "Create this event in the following calendar:" : "Move this event to the following calendar:");
				var providers = window.top.CalendarsProviders.getCurrentProviders();
				for (var provider_index = 0; provider_index < providers.length; ++provider_index) {
					var provider = providers[provider_index];
					for (var calendar_index = 0; calendar_index < provider.calendars.length; ++calendar_index) {
						var calendar = provider.calendars[calendar_index];
						if (!calendar.saveEvent) continue; // we cannot modify this calendar
						var item = document.createElement("DIV");
						item.calendar = calendar; 
						item.className = 'context_menu_item';
						var provider_icon = document.createElement("IMG");
						provider_icon.src = provider.getProviderIcon();
						provider_icon.width = 16;
						provider_icon.style.width = '16px';
						provider_icon.style.paddingRight = '3px';
						provider_icon.style.verticalAlign = 'bottom';
						item.appendChild(provider_icon);
						var box = document.createElement("DIV");
						box.style.display = 'inline-block';
						box.style.width = "10px";
						box.style.height = "10px";
						box.style.border = "1px solid #"+calendar.color;
						box.style.backgroundColor = "#"+calendar.color;
						box.style.marginRight = "3px";
						item.appendChild(box);
						if (calendar.icon) {
							var icon = document.createElement("IMG");
							icon.src = calendar.icon;
							icon.width = 16;
							icon.style.width = '16px';
							icon.style.paddingRight = '3px';
							icon.style.verticalAlign = 'bottom';
							item.appendChild(icon);
						}
						item.appendChild(document.createTextNode(calendar.name));
						item.onclick = function() {
							t.selected_calendar = this.calendar;
							t.calendar_provider_icon.src = this.calendar.provider.getProviderIcon();
							t.calendar_box.style.border = "1px solid #"+this.calendar.color;
							t.calendar_box.style.backgroundColor = "#"+this.calendar.color;
							if (this.calendar.icon) {
								t.calendar_icon.src = this.calendar.icon;
								t.calendar_icon.style.visibility = 'visible';
								t.calendar_icon.style.position = 'static';
							} else {
								t.calendar_icon.style.visibility = 'hidden';
								t.calendar_icon.style.position = 'absolute';
							}
							t.calendar_name_node.nodeValue = this.calendar.name;
						};
						menu.addItem(item);
					}
				}
				menu.showBelowElement(container);
			});
		};
	}
	/** Populate the given event
	 * @param {CalendarEvent} event the event into which to put the selected calendar
	 */
	this.populate = function(event) {
		event.calendar_provider_id = this.selected_calendar.provider.id;
		event.calendar_id = this.selected_calendar.id;
	}
}

/**
 * Section containing the title and description of the event
 * @param {Element} container where to put the section
 * @param {String} title the current event title
 * @param {String} description the current event description
 * @param {Boolean} editable indicates if we can edit the information
 */
function event_screen_what(container, title, description, editable) {
	this.title = title ? title : "";
	this.description = description ? description : "";
	/** Populate the given event
	 * @param {CalendarEvent} event the event into which to put the title and description
	 * @returns {String|null} an error message or null if ok
	 */
	this.populate = function(event) {
		if (this.title.length == 0) return "Please enter a title for your event";
		event.title = this.title;
		event.description = this.description;
	};
	var t=this;
	/** Initialize the display */
	this._init = function() {
		// title
		var div = document.createElement("DIV");
		div.style.fontSize = "12pt";
		div.style.fontWeight = "bold";
		if (editable) {
			var io = new InputOver(title, "Enter a title for this event");
			io.onchange.addListener(function(io) { t.title = io.input.value; });
			div.appendChild(io.container);
		} else {
			div.appendChild(document.createTextNode(this.title));
		}
		container.appendChild(div);
		
		// description
		div = document.createElement("DIV");
		div.style.fontStyle = "italic";
		div.style.color = "#606060";
		div.style.marginTop = "3px";
		div.innerHTML = "Description";
		container.appendChild(div);
		var text = document.createElement("TEXTAREA");
		text.value = this.description;
		if (!editable) text.disabled = 'disabled';
		else text.onchange = function() { t.description = this.value; };
		text.style.minWidth = "300px";
		text.style.width = "90%";
		text.style.height = "30px";
		container.appendChild(text);
	};
	require("input_utils.js",function() { t._init(); });
}

/**
 * Section containing the dates, times, and frequency information
 * @param {Element} container where to put the section
 * @param {Date} start current starting date
 * @param {Date} end current ending date
 * @param {Boolean} all_day current all_day flag
 * @param {CalendarEventFrequency} frequency the current frequency information
 * @param {Boolean} editable indicates if we can modify
 */
function event_screen_when(container, start, end, all_day, frequency, editable) {
	/** Copy of the starting date */
	this.start = start ? new Date(start.getTime()) : new Date();
	/** Copy of the ending date */
	this.end = end ? new Date(end.getTime()) : new Date();
	this.all_day = all_day;
	/** Copy of the frequency */
	this.frequency = objectCopy(frequency);
	
	if (!editable) {
		var s = "";
		if (this.all_day) {
			if (this.end.getTime()-this.start.getTime() <= 24*60*60*1000) {
				// only one day, date is UTC
				var day = new Date();
				day.setFullYear(this.start.getUTCFullYear());
				day.setMonth(this.start.getUTCMonth());
				day.setDate(this.start.getUTCDate());
				day.setHours(0,0,0,0);
				s += "On "+getDateString(day);
			} else {
				// several days, dates are UTC
				var day = new Date();
				day.setFullYear(this.start.getUTCFullYear());
				day.setMonth(this.start.getUTCMonth());
				day.setDate(this.start.getUTCDate());
				day.setHours(0,0,0,0);
				s += "From "+getDateString(day);
				var end = new Date(this.end.getTime()-1); // end is exclusive
				day.setFullYear(end.getUTCFullYear());
				day.setMonth(end.getUTCMonth());
				day.setDate(end.getUTCDate());
				s += " To "+getDateString(day);
			}
		} else {
			// not all day
			if (this.start.getFullYear() == this.end.getFullYear() && this.start.getMonth() == this.end.getMonth() && this.start.getDate() == this.end.getDate()) {
				// same day
				s += "On "+getDateString(this.start);
				s += " from "+getTimeString(this.start)+" to "+getTimeString(this.end);
			} else {
				// different days
				s += "From "+getDateString(this.start);
				s += " at "+getTimeString(this.start);
				s += " to "+getDateString(this.end);
				s += " at "+getTimeString(this.end);
			}
		}
		var div = document.createElement("DIV");
		div.appendChild(document.createTextNode(s));
		container.appendChild(div);
		if (this.frequency) {
			s = "Repeated every ";
			if (frequency.interval && frequency.interval > 1)
				s += frequency.interval+" ";
			switch (frequency.frequency) {
			case "YEARLY": s += "year"; break;
			case "MONTHLY": s += "month"; break;
			case "WEEKLY": s += "week"; break;
			case "DAILY": s += "day"; break;
			}
			if (frequency.interval && frequency.interval > 1) s += "s";
			// TODO repeat...
			div = document.createElement("DIV");
			div.appendChild(document.createTextNode(s));
			container.appendChild(div);
		}
	} else {
		var t=this;
		require([["typed_field.js",["field_date.js","field_time.js"]]], function() {
			var table = document.createElement("TABLE");
			container.appendChild(table);
			var tr, td;
			// from
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.style.textAlign = "right";
			td.appendChild(document.createTextNode("From "));
			tr.appendChild(td = document.createElement("TD"));
			td.style.textAlign = "left";
			var from_date = new field_date(dateToSQL(t.start),true,{can_be_null:false});
			from_date.setMaximum(dateToSQL(t.end));
			td.appendChild(from_date.getHTMLElement());
			tr.appendChild(td = document.createElement("TD"));
			var from_time_span = document.createElement("SPAN");
			from_time_span.appendChild(document.createTextNode(" at "));
			var from_time = new field_time(t.start.getHours()*60+t.start.getMinutes(),true,{can_be_null:false});
			from_time_span.appendChild(from_time.getHTMLElement());
			if (t.all_day) from_time_span.style.display = "none";
			td.appendChild(from_time_span);
			// To
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.style.textAlign = "right";
			td.appendChild(document.createTextNode("To "));
			tr.appendChild(td = document.createElement("TD"));
			td.style.textAlign = "left";
			var to_date = new field_date(dateToSQL(t.end),true,{can_be_null:false});
			to_date.setMinimum(dateToSQL(t.start));
			td.appendChild(to_date.getHTMLElement());
			tr.appendChild(td = document.createElement("TD"));
			var to_time_span = document.createElement("SPAN");
			to_time_span.appendChild(document.createTextNode(" at "));
			var to_time = new field_time(t.end.getHours()*60+t.end.getMinutes(),true,{can_be_null:false});
			to_time_span.appendChild(to_time.getHTMLElement());
			if (t.all_day) to_time_span.style.display = "none";
			td.appendChild(to_time_span);
			// All day
			var div = document.createElement("DIV");
			var cb_all_day = document.createElement("INPUT");
			cb_all_day.type = "checkbox";
			if (t.all_day) cb_all_day.checked = 'checked';
			cb_all_day.style.marginRight = "5px";
			cb_all_day.style.verticalAlign = "middle";
			div.appendChild(cb_all_day);
			div.appendChild(document.createTextNode("This event doesn't have specific time (all day event)"));
			container.appendChild(div);
			// recurrence
			div = document.createElement("DIV");
			var repeat = document.createElement("INPUT");
			repeat.type = 'checkbox';
			repeat.style.marginRight = "5px";
			repeat.style.verticalAlign = 'middle';
			if (t.frequency) repeat.checked = 'checked';
			div.appendChild(repeat);
			div.appendChild(document.createTextNode("Repeat every..."));
			container.appendChild(div);
			var repeat_div = document.createElement("DIV");
			container.appendChild(repeat_div);
			repeat_div.style.marginLeft = '25px';
			repeat_div.style.whiteSpace = 'nowrap';
			var frequency = document.createElement("SELECT");
			var o;
			o = document.createElement("OPTION"); o.value = 'DAILY'; o.text = 'Daily'; frequency.add(o);
			o = document.createElement("OPTION"); o.value = 'WEEKLY'; o.text = 'Weekly'; frequency.add(o);
			o = document.createElement("OPTION"); o.value = 'MONTHLY'; o.text = 'Monthly'; frequency.add(o);
			o = document.createElement("OPTION"); o.value = 'YEARLY'; o.text = 'Yearly'; frequency.add(o);
			frequency.selectedIndex = 1; // weekly by default
			repeat_div.appendChild(frequency);
			repeat_div.appendChild(document.createTextNode(" Every "));
			var interval = document.createElement("SELECT");
			for (var i = 1; i <= 30; ++i) { o = document.createElement("OPTION"); o.value = i; o.text = i; interval.add(o); }
			repeat_div.appendChild(interval);
			repeat_div.appendChild(document.createTextNode(" "));
			var interval_text = document.createTextNode("week(s)");
			repeat_div.appendChild(interval_text);
			var repeat_on_div = document.createElement("DIV");
			repeat_div.appendChild(repeat_on_div);
			repeat_on_div.appendChild(document.createTextNode("Repeat on: "));
			var repeat_on_MO = document.createElement("INPUT"); repeat_on_MO.type = 'checkbox'; repeat_on_MO.style.verticalAlign = 'middle';
			repeat_on_div.appendChild(repeat_on_MO); repeat_on_div.appendChild(document.createTextNode("Mon"));
			var repeat_on_TU = document.createElement("INPUT"); repeat_on_TU.type = 'checkbox'; repeat_on_TU.style.verticalAlign = 'middle';
			repeat_on_div.appendChild(repeat_on_TU); repeat_on_div.appendChild(document.createTextNode("Tue"));
			var repeat_on_WE = document.createElement("INPUT"); repeat_on_WE.type = 'checkbox'; repeat_on_WE.style.verticalAlign = 'middle';
			repeat_on_div.appendChild(repeat_on_WE); repeat_on_div.appendChild(document.createTextNode("Wed"));
			var repeat_on_TH = document.createElement("INPUT"); repeat_on_TH.type = 'checkbox'; repeat_on_TH.style.verticalAlign = 'middle';
			repeat_on_div.appendChild(repeat_on_TH); repeat_on_div.appendChild(document.createTextNode("Thu"));
			var repeat_on_FR = document.createElement("INPUT"); repeat_on_FR.type = 'checkbox'; repeat_on_FR.style.verticalAlign = 'middle';
			repeat_on_div.appendChild(repeat_on_FR); repeat_on_div.appendChild(document.createTextNode("Fri"));
			var repeat_on_SA = document.createElement("INPUT"); repeat_on_SA.type = 'checkbox'; repeat_on_SA.style.verticalAlign = 'middle';
			repeat_on_div.appendChild(repeat_on_SA); repeat_on_div.appendChild(document.createTextNode("Sat"));
			var repeat_on_SU = document.createElement("INPUT"); repeat_on_SU.type = 'checkbox'; repeat_on_SU.style.verticalAlign = 'middle';
			repeat_on_div.appendChild(repeat_on_SU); repeat_on_div.appendChild(document.createTextNode("Sun"));
			var repeat_by_div = document.createElement("DIV");
			repeat_div.appendChild(repeat_by_div);
			repeat_by_div.style.display = "none";
			repeat_by_div.appendChild(document.createTextNode("By "));
			var repeat_by_month_day = document.createElement("INPUT");
			repeat_by_month_day.type = 'radio';
			repeat_by_month_day.name = 'repeat_by';
			repeat_by_month_day.value = 'month_day';
			repeat_by_month_day.style.verticalAlign = 'middle';
			repeat_by_div.appendChild(repeat_by_month_day);
			repeat_by_div.appendChild(document.createTextNode(" day of the month"));
			var repeat_by_week_day = document.createElement("INPUT");
			repeat_by_week_day.type = 'radio';
			repeat_by_week_day.name = 'repeat_by';
			repeat_by_week_day.value = 'week_day';
			repeat_by_week_day.style.verticalAlign = 'middle';
			repeat_by_div.appendChild(repeat_by_week_day);
			repeat_by_div.appendChild(document.createTextNode(" day of the week"));
			// TODO continue
			var repeat_until_table = document.createElement("TABLE");
			repeat_div.appendChild(repeat_until_table);
			repeat_until_table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.rowSpan = 3;
			td.style.verticalAlign = "top";
			td.appendChild(document.createTextNode("Stop "));
			tr.appendChild(td = document.createElement("TD"));
			var repeat_until_never = document.createElement("INPUT");
			repeat_until_never.type = 'radio';
			repeat_until_never.name = 'repeat_until';
			repeat_until_never.value = 'never';
			repeat_until_never.style.verticalAlign = 'bottom';
			repeat_until_never.checked = 'checked';
			td.appendChild(repeat_until_never); td.appendChild(document.createTextNode(" Never "));
			repeat_until_table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			var repeat_until_count = document.createElement("INPUT");
			repeat_until_count.type = 'radio';
			repeat_until_count.name = 'repeat_until';
			repeat_until_count.value = 'count';
			repeat_until_count.style.verticalAlign = 'bottom';
			td.appendChild(repeat_until_count);
			td.appendChild(document.createTextNode(" After "));
			var repeat_count = document.createElement("INPUT"); td.appendChild(repeat_count);
			repeat_count.type = 'text';
			repeat_count.size = 5;
			td.appendChild(document.createTextNode(" occurences "));
			repeat_until_table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			var repeat_until_date = document.createElement("INPUT");
			repeat_until_date.type = 'radio';
			repeat_until_date.name = 'repeat_until';
			repeat_until_date.value = 'date';
			repeat_until_date.style.verticalAlign = 'bottom';
			td.appendChild(repeat_until_date);
			td.appendChild(document.createTextNode(" On "));
			var repeat_until = new field_date(null,true,null,null,{});
			td.appendChild(repeat_until.getHTMLElement());
			// fill frequency
			if (!t.frequency) repeat_div.style.display = "none";
			else {
				frequency.value = t.frequency.frequency;
				if (t.frequency.until) {
					repeat_until_date.checked = 'checked';
					repeat_until.setData(dateToSQL(t.frequency.until));
				} else if (t.frequency.count) {
					repeat_until_count.checked = 'checked';
					repeat_count.value = t.frequency.count;
				}
				interval.value = t.frequency.interval ? t.frequency.interval : "1";
				if (t.frequency.frequency == "WEEKLY" && t.frequency.by_week_day) {
					repeat_on_MO.checked = t.frequency.by_week_day.indexOf("MO") >= 0 ? "checked" : "";
					repeat_on_TU.checked = t.frequency.by_week_day.indexOf("TU") >= 0 ? "checked" : "";
					repeat_on_WE.checked = t.frequency.by_week_day.indexOf("WE") >= 0 ? "checked" : "";
					repeat_on_TH.checked = t.frequency.by_week_day.indexOf("TH") >= 0 ? "checked" : "";
					repeat_on_FR.checked = t.frequency.by_week_day.indexOf("FR") >= 0 ? "checked" : "";
					repeat_on_SA.checked = t.frequency.by_week_day.indexOf("SA") >= 0 ? "checked" : "";
					repeat_on_SU.checked = t.frequency.by_week_day.indexOf("SU") >= 0 ? "checked" : "";
				}
				// TODO
			}
			
			// events
			cb_all_day.onchange = function() {
				if (this.checked) {
					t.all_day = true;
					from_time_span.style.display = "none";
					to_time_span.style.display = "none";
				} else {
					t.all_day = true;
					from_time_span.style.display = "";
					to_time_span.style.display = "";
				}
				layout.changed(from_time_span.parentNode);
			};
			from_date.onchange.addListener(function() {
				var d = from_date.getCurrentData();
				if (!d) return;
				to_date.setMinimum(d);
				repeat_until.setMinimum(d);
				d = parseSQLDate(d);
				t.start.setFullYear(d.getFullYear(), d.getMonth(), d.getDate());
			});
			to_date.onchange.addListener(function() {
				var d = to_date.getCurrentData();
				if (!d) return;
				from_date.setMaximum(d);
				d = parseSQLDate(d);
				t.end.setFullYear(d.getFullYear(), d.getMonth(), d.getDate());
			});
			from_time.onchange.addListener(function() {
				t.start.setHours(0,from_time.getCurrentMinutes(),0,0);
			});
			to_time.onchange.addListener(function() {
				t.end.setHours(0,to_time.getCurrentMinutes(),0,0);
			});
			var getFrequency = function() {
				if (!t.frequency) t.frequency = {};
				t.frequency.frequency = frequency.value;
				t.frequency.until = repeat_until_date.checked ? parseSQLDate(repeat_until.getCurrentData()) : null;
				t.frequency.count = repeat_until_count.checked ? parseInt(repeat_count.value) : null;
				if (isNaN(t.frequency.count)) t.frequency.count = null;
				t.frequency.interval = interval.value;
				if (frequency.value == "WEEKLY") {
					var days = [];
					if (repeat_on_MO.checked) days.push("MO");
					if (repeat_on_TU.checked) days.push("TU");
					if (repeat_on_WE.checked) days.push("WE");
					if (repeat_on_TH.checked) days.push("TH");
					if (repeat_on_FR.checked) days.push("FR");
					if (repeat_on_SA.checked) days.push("SA");
					if (repeat_on_SU.checked) days.push("SU");
					if (days.length == 0) t.frequency.by_week_day = null;
					else {
						t.frequency.by_week_day = "";
						for (var i = 0; i < days.length; ++i) {
							if (i>0) t.frequency.by_week_day += ",";
							t.frequency.by_week_day += days[i];
						}
					}
				}
				// TODO t.frequency.by_month = ;
				// TODO t.frequency.by_week_no = ;
				// TODO t.frequency.by_year_day = ;
				// TODO t.frequency.by_month_day = ;
				// TODO t.frequency.by_week_day = ;
				// TODO t.frequency.by_hour = ;
				// TODO t.frequency.by_setpos = ;
				// TODO t.frequency.week_start = ;
			};
			repeat.onchange = function() {
				if (this.checked) {
					repeat_div.style.display = "";
					getFrequency();
				} else {
					repeat_div.style.display = "none";
					t.frequency = null;
				}
				layout.changed(repeat_div.parentNode);
			};
			frequency.onchange = function() {
				if (this.value == 'DAILY') {
					interval_text.nodeValue = "day(s)";
					repeat_on_div.style.display = "none";
					repeat_by_div.style.display = 'none';
				} else if (this.value == 'WEEKLY') {
					interval_text.nodeValue = "week(s)";
					repeat_on_div.style.display = '';
					repeat_by_div.style.display = 'none';
				} else if (this.value == 'MONTHLY') {
					interval_text.nodeValue = "month(s)";
					repeat_on_div.style.display = 'none';
					repeat_by_div.style.display = '';
				} else if (this.value == "YEARLY") {
					interval_text.nodeValue = "year(s)";
					repeat_on_div.style.display = "none";
					repeat_by_div.style.display = 'none';
				}
				getFrequency();
				layout.changed(repeat_div);
			};
			interval.onchange = function() { getFrequency(); };
			repeat_on_MO.onchange = function() { getFrequency(); };
			repeat_on_TU.onchange = function() { getFrequency(); };
			repeat_on_WE.onchange = function() { getFrequency(); };
			repeat_on_TH.onchange = function() { getFrequency(); };
			repeat_on_FR.onchange = function() { getFrequency(); };
			repeat_on_SA.onchange = function() { getFrequency(); };
			repeat_on_SU.onchange = function() { getFrequency(); };
			repeat_by_month_day.onchange = function() { getFrequency(); };
			repeat_by_week_day.onchange = function() { getFrequency(); };
			repeat_until_never.onchange = function() { getFrequency(); };
			repeat_until_count.onchange = function() { getFrequency(); };
			repeat_count.onchange = function() { getFrequency(); };
			repeat_until_date.onchange = function() { getFrequency(); };
			repeat_until.onchange.addListener(function() { getFrequency(); });
			
			t.populate = function(event) {
				var from = parseSQLDate(from_date.getCurrentData());
				if (!from) return "You must enter a starting date";
				var to = parseSQLDate(to_date.getCurrentData());
				if (!to) return "Please enter valid dates";
				if (cb_all_day.checked) {
					event.all_day = true;
					// make the dates as UTC
					event.start = new Date();
					event.start.setUTCFullYear(from.getFullYear());
					event.start.setUTCMonth(from.getMonth());
					event.start.setUTCDate(from.getDate());
					event.end = new Date();
					event.end.setUTCFullYear(to.getFullYear());
					event.end.setUTCMonth(to.getMonth());
					event.end.setUTCDate(to.getDate());
				} else {
					event.all_day = false;
					// get the time
					event.start = from;
					event.start.setHours(0,from_time.getCurrentMinutes(),0,0);
					event.end = to;
					event.end.setHours(0,to_time.getCurrentMinutes(),0,0);
				}
				if (repeat.checked) {
					getFrequency();
					event.frequency = t.frequency;
				} else
					event.frequency = null;
			};
		});
	}
}

/**
 * Section containing attendees information
 * @param {Element} container where to put the section
 * @param {Array} attendees list of CalendarEventAttendee
 * @param {Boolean} editable indicates if we can modify
 */
function event_screen_who(container, attendees, editable) {
	this.attendees = attendees;
	container.appendChild(this._table = document.createElement("TABLE"));
	/**
	 * Create the display for an attendee
	 * @param {CalendarEventAttendee} a the attendee
	 */
	this.createAttendee = function(a) {
		var tr, td;
		this._table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.rowSpan = 2;
		var picture_url = null;
		if (a.people > 0) picture_url = "/dynamic/people/service/picture?people="+a.people+"&redirect=1";
		else if (a.email) picture_url = "/dynamic/contact/service/picture_from_email?email="+a.email;
		if (picture_url) {
			var img = document.createElement("IMG");
			img.style.height = "100%";
			img.style.maxHeight = "40px";
			img.src = picture_url;
			td.appendChild(img);
		}
		tr.appendChild(td = document.createElement("TD"));
		td.style.verticalAlign = "bottom";
		if (a.name)	td.appendChild(document.createTextNode(a.name));
		this._table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.style.verticalAlign = "top";
		td.style.color = "#606060";
		td.style.fontStyle = "italic";
		td.style.fontSize = "9pt";
		if (a.email) td.appendChild(document.createTextNode(a.email));
	};
	/** Populate the event with attendees
	 * @param {CalendarEvent} event the event to populate
	 */
	this.populate = function(event) {
		event.attendees = arrayCopy(this.attendees, copyCalendarEventAttendee);
	};
	// created by
	for (var i = 0; i < attendees.length; ++i) {
		if (attendees[i].creator) {
			var tr, td;
			this._table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.colSpan = 2;
			td.style.fontWeight = "bold";
			td.innerHTML = "Created by";
			this.createAttendee(attendees[i]);
			break;
		}
	}
	// organized by
	for (var i = 0; i < attendees.length; ++i) {
		if (attendees[i].organizer) {
			var tr, td;
			this._table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.colSpan = 2;
			td.style.fontWeight = "bold";
			td.innerHTML = "Organized by";
			this.createAttendee(attendees[i]);
			break;
		}
	}
	// attendees
	var first = true;
	for (var i = 0; i < attendees.length; ++i) {
		if (attendees[i].role == calendar_event_role_none && (attendees[i].creator || attendees[i].organizer)) continue;
		if (first) {
			var tr, td;
			this._table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.colSpan = 2;
			td.style.fontWeight = "bold";
			td.innerHTML = "Attendees";
			first = false;
		}
		this.createAttendee(attendees[i]);
	}
}