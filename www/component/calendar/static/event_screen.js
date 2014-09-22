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
	t.event = copyCalendarEvent(ev);
	/** Add a 0 if the number is only 1 digit
	 * @param {Number} n the number
	 */
	t._2digits = function(n) {
		var s = ""+n;
		while (s.length < 2) s = "0"+s;
		return s;
	};
	/** Return a string representation of the given date
	 * @param {Date} date the date
	 * @returns {String} string representing the date
	 */
	t.getDateString = function(date) {
		return date.getFullYear()+"-"+t._2digits(date.getMonth()+1)+"-"+t._2digits(date.getDate());
	};
	require([["typed_field.js",["field_date.js","field_time.js"]],"popup_window.js","calendar_view.js"],function() {
		var o;
		var calendar = ev ? window.top.CalendarsProviders.getProvider(ev.calendar_provider_id).getCalendar(ev.calendar_id) : null;
		var ro = ev && !calendar.saveEvent;

		t.content = document.createElement("DIV");
		t.content.style.padding = "5px";
		t.content.appendChild(t.table = document.createElement("TABLE"));
		var tr, td;
		var createRowTitle = function(tr, title) {
			var td = document.createElement("TD"); tr.appendChild(td);
			td.style.fontWeight = "bold";
			td.style.color = "#404040";
			td.style.verticalAlign = "top";
			td.style.whiteSpace = 'nowrap';
			td.style.paddingTop = "4px";
			td.appendChild(document.createTextNode(title));
			return td;
		};
		
		// event title
		t.table.appendChild(tr = document.createElement("TR"));
		td = createRowTitle(tr, "Title");
		td.style.fontSize = "12pt";
		tr.appendChild(td = document.createElement("TD"));
		t.title = document.createElement("INPUT");
		t.title.type = 'text';
		t.title.size = 80;
		t.title.style.fontSize = '12pt';
		t.title.style.fontWeight = "bold";
		if (ro) t.title.disabled = 'disabled';
		td.appendChild(t.title);

		// calendar
		t.table.appendChild(tr = document.createElement("TR"));
		createRowTitle(tr, "Calendar");
		tr.appendChild(td = document.createElement("TD"));
		t.selected_calendar = ev ? calendar : default_calendar;
		t.calendar_provider_icon = document.createElement("IMG");
		t.calendar_provider_icon.width = 16;
		t.calendar_provider_icon.style.width = '16px';
		t.calendar_provider_icon.src = t.selected_calendar.provider.getProviderIcon();
		t.calendar_provider_icon.style.marginRight = '3px';
		td.appendChild(t.calendar_provider_icon);
		t.calendar_box = document.createElement("DIV"); td.appendChild(t.calendar_box);
		t.calendar_box.style.display = "inline-block";
		t.calendar_box.style.width = "10px";
		t.calendar_box.style.height = "10px";
		t.calendar_box.style.border = "1px solid #"+(ev ? calendar.color : default_calendar.color);
		t.calendar_box.style.backgroundColor = "#"+(ev ? calendar.color : default_calendar.color);
		t.calendar_box.style.marginRight = '3px';
		t.calendar_icon = document.createElement("IMG");
		t.calendar_icon.width = 16;
		t.calendar_icon.style.width = '16px';
		t.calendar_icon.style.marginRight = '3px';
		if (t.selected_calendar.icon)
			t.calendar_icon.src = t.selected_calendar.icon;
		else {
			t.calendar_icon.style.visibility = 'hidden';
			t.calendar_icon.style.position = 'absolute';
		}
		td.appendChild(t.calendar_icon);
		td.appendChild(t.calendar_name_node = document.createTextNode(ev ? calendar.name : default_calendar.name));
		td.style.cursor = 'pointer';
		var calendar_td = td;
		
		// event timing
		t.table.appendChild(tr = document.createElement("TR"));
		createRowTitle(tr, "When");
		tr.appendChild(td = document.createElement("TD"));
		td.appendChild(document.createTextNode("From "));
		t.from_date = new field_date(null,!ro,null,null,{});
		td.appendChild(t.from_date.getHTMLElement());
		t.from_time_span = document.createElement("SPAN");
		td.appendChild(t.from_time_span);
		t.from_time_span.appendChild(document.createTextNode(" at "));
		t.from_time = new field_time(null,!ro,null,null,{});
		t.from_time_span.appendChild(t.from_time.getHTMLElement());
		td.appendChild(document.createTextNode(" To "));
		t.to_date = new field_date(null,!ro,null,null,{});
		td.appendChild(t.to_date.getHTMLElement());
		t.to_time_span = document.createElement("SPAN");
		t.to_time_span.appendChild(document.createTextNode(" at "));
		t.to_time = new field_time(null,!ro,null,null,{});
		t.to_time_span.appendChild(t.to_time.getHTMLElement());
		td.appendChild(t.to_time_span);
		td.appendChild(document.createTextNode(" "));
		t.all_day = document.createElement("INPUT");
		t.all_day.type = "checkbox";
		t.all_day.style.verticalAlign = 'bottom';
		if (ro) t.all_day.disabled = 'disabled';
		td.appendChild(t.all_day);
		td.appendChild(document.createTextNode(" All day"));
		td.appendChild(document.createElement("BR"));
				
		// recurrence
		t.repeat = document.createElement("INPUT");
		t.repeat.type = 'checkbox';
		t.repeat.style.verticalAlign = 'bottom';
		if (ro) t.repeat.disabled = 'disabled';
		td.appendChild(t.repeat);
		td.appendChild(document.createTextNode("Repeat..."));
		td.appendChild(document.createElement("BR"));
		t.repeat_div = document.createElement("DIV");
		td.appendChild(t.repeat_div);
		t.repeat_div.style.marginLeft = '15px';
		t.repeat_div.style.visibility = 'hidden';
		t.repeat_div.style.position = 'absolute';
		t.frequency = document.createElement("SELECT");
		o = document.createElement("OPTION"); o.value = 'DAILY'; o.text = 'Daily'; t.frequency.add(o);
		o = document.createElement("OPTION"); o.value = 'WEEKLY'; o.text = 'Weekly'; t.frequency.add(o);
		o = document.createElement("OPTION"); o.value = 'MONTHLY'; o.text = 'Monthly'; t.frequency.add(o);
		o = document.createElement("OPTION"); o.value = 'YEARLY'; o.text = 'Yearly'; t.frequency.add(o);
		t.frequency.selectedIndex = 1; // by default, weekly
		if (ro) t.frequency.disabled = 'disabled';
		t.repeat_div.appendChild(t.frequency);
		t.repeat_div.appendChild(document.createTextNode(" Every "));
		t.interval = document.createElement("SELECT");
		for (var i = 1; i <= 30; ++i) { o = document.createElement("OPTION"); o.value = i; o.text = i; t.interval.add(o); }
		if (ro) t.interval.disabled = 'disabled';
		t.repeat_div.appendChild(t.interval);
		t.repeat_div.appendChild(document.createTextNode(" "));
		t.interval_text = document.createTextNode("week(s)");
		t.repeat_div.appendChild(t.interval_text);
		t.repeat_on_div = document.createElement("DIV");
		t.repeat_div.appendChild(t.repeat_on_div);
		t.repeat_on_div.appendChild(document.createTextNode("Repeat on: "));
		t.repeat_on_MO = document.createElement("INPUT"); t.repeat_on_MO.type = 'checkbox'; t.repeat_on_MO.style.verticalAlign = 'bottom';
		if (ro) t.repeat_on_MO.disabled = 'disabled';
		t.repeat_on_div.appendChild(t.repeat_on_MO); t.repeat_on_div.appendChild(document.createTextNode(" Mon"));
		t.repeat_on_TU = document.createElement("INPUT"); t.repeat_on_TU.type = 'checkbox'; t.repeat_on_TU.style.verticalAlign = 'bottom';
		if (ro) t.repeat_on_TU.disabled = 'disabled';
		t.repeat_on_div.appendChild(t.repeat_on_TU); t.repeat_on_div.appendChild(document.createTextNode(" Tue"));
		t.repeat_on_WE = document.createElement("INPUT"); t.repeat_on_WE.type = 'checkbox'; t.repeat_on_WE.style.verticalAlign = 'bottom';
		if (ro) t.repeat_on_WE.disabled = 'disabled';
		t.repeat_on_div.appendChild(t.repeat_on_WE); t.repeat_on_div.appendChild(document.createTextNode(" Wed"));
		t.repeat_on_TH = document.createElement("INPUT"); t.repeat_on_TH.type = 'checkbox'; t.repeat_on_TH.style.verticalAlign = 'bottom';
		if (ro) t.repeat_on_TH.disabled = 'disabled';
		t.repeat_on_div.appendChild(t.repeat_on_TH); t.repeat_on_div.appendChild(document.createTextNode(" Thu"));
		t.repeat_on_FR = document.createElement("INPUT"); t.repeat_on_FR.type = 'checkbox'; t.repeat_on_FR.style.verticalAlign = 'bottom';
		if (ro) t.repeat_on_FR.disabled = 'disabled';
		t.repeat_on_div.appendChild(t.repeat_on_FR); t.repeat_on_div.appendChild(document.createTextNode(" Fri"));
		t.repeat_on_SA = document.createElement("INPUT"); t.repeat_on_SA.type = 'checkbox'; t.repeat_on_SA.style.verticalAlign = 'bottom';
		if (ro) t.repeat_on_SA.disabled = 'disabled';
		t.repeat_on_div.appendChild(t.repeat_on_SA); t.repeat_on_div.appendChild(document.createTextNode(" Sat"));
		t.repeat_on_SU = document.createElement("INPUT"); t.repeat_on_SU.type = 'checkbox'; t.repeat_on_SU.style.verticalAlign = 'bottom';
		if (ro) t.repeat_on_SU.disabled = 'disabled';
		t.repeat_on_div.appendChild(t.repeat_on_SU); t.repeat_on_div.appendChild(document.createTextNode(" Sun"));
		t.repeat_by_div = document.createElement("DIV");
		t.repeat_div.appendChild(t.repeat_by_div);
		t.repeat_by_div.style.visibility = 'hidden';
		t.repeat_by_div.style.position = 'absolute';
		t.repeat_by_div.appendChild(document.createTextNode("By "));
		t.repeat_by_month_day = document.createElement("INPUT");
		t.repeat_by_month_day.type = 'radio';
		t.repeat_by_month_day.name = 'repeat_by';
		t.repeat_by_month_day.value = 'month_day';
		if (ro) t.repeat_by_month_day.disabled = 'disabled';
		t.repeat_by_div.appendChild(t.repeat_by_month_day);
		t.repeat_by_div.appendChild(document.createTextNode(" day of the month"));
		t.repeat_by_week_day = document.createElement("INPUT");
		t.repeat_by_week_day.type = 'radio';
		t.repeat_by_week_day.name = 'repeat_by';
		t.repeat_by_week_day.value = 'week_day';
		if (ro) t.repeat_by_week_day.disabled = 'disabled';
		t.repeat_by_div.appendChild(t.repeat_by_week_day);
		t.repeat_by_div.appendChild(document.createTextNode(" day of the week"));
		// TODO continue
		t.repeat_until_div = document.createElement("DIV");
		t.repeat_div.appendChild(t.repeat_until_div);
		t.repeat_until_div.appendChild(document.createTextNode("Ends: "));
		t.repeat_until_never = document.createElement("INPUT");
		t.repeat_until_never.type = 'radio';
		t.repeat_until_never.name = 'repeat_until';
		t.repeat_until_never.value = 'never';
		if (ro) t.repeat_until_never.disabled = 'disabled';
		t.repeat_until_div.appendChild(t.repeat_until_never); t.repeat_until_div.appendChild(document.createTextNode(" Never "));
		t.repeat_until_count = document.createElement("INPUT");
		t.repeat_until_count.type = 'radio';
		t.repeat_until_count.name = 'repeat_until';
		t.repeat_until_count.value = 'count';
		if (ro) t.repeat_until_count.disabled = 'disabled';
		t.repeat_until_div.appendChild(t.repeat_until_count);
		t.repeat_until_div.appendChild(document.createTextNode(" After "));
		t.repeat_count = document.createElement("INPUT"); t.repeat_until_div.appendChild(t.repeat_count);
		t.repeat_count.type = 'text';
		t.repeat_count.size = 5;
		if (ro) t.repeat_count.disabled = 'disabled';
		t.repeat_until_div.appendChild(document.createTextNode(" occurences "));
		t.repeat_until_date = document.createElement("INPUT");
		t.repeat_until_date.type = 'radio';
		t.repeat_until_date.name = 'repeat_until';
		t.repeat_until_date.value = 'date';
		if (ro) t.repeat_until_date.disabled = 'disabled';
		t.repeat_until_div.appendChild(t.repeat_until_date);
		t.repeat_until_div.appendChild(document.createTextNode(" On "));
		t.repeat_until = new field_date(null,!ro,null,null,{});
		t.repeat_until_div.appendChild(t.repeat_until.getHTMLElement());
		
		// event details
		t.table.appendChild(tr = document.createElement("TR"));
		createRowTitle(tr, "Description");
		tr.appendChild(td = document.createElement("TD"));
		t.description = document.createElement("TEXTAREA");
		t.description.rows = 4;
		t.description.cols = 80;
		if (ro) t.description.disabled = 'disabled';
		td.appendChild(t.description);

		
		if (ev && ev.app_link) {
			// link
			t.table.appendChild(tr = document.createElement("TR"));
			td = createRowTitle(tr, "");
			td.colSpan = 2;
			var link = document.createElement("A");
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
		
		// handle events
		t.all_day.onchange = function() {
			if (t.all_day.checked) {
				t.from_time_span.style.visibility = 'hidden';
				t.from_time_span.style.position = 'absolute';
				t.to_time_span.style.visibility = 'hidden';
				t.to_time_span.style.position = 'absolute';
			} else {
				t.from_time_span.style.visibility = 'visible';
				t.from_time_span.style.position = 'static';
				t.to_time_span.style.visibility = 'visible';
				t.to_time_span.style.position = 'static';
			}
		};
		t.repeat.onchange = function() {
			if (t.repeat.checked) {
				t.repeat_div.style.visibility = 'visible';
				t.repeat_div.style.position = 'static';
			} else {
				t.repeat_div.style.visibility = 'hidden';
				t.repeat_div.style.position = 'absolute';
			}
		};
		t.frequency.onchange = function() {
			if (this.value == 'DAILY') {
				t.interval_text.nodeValue = "day(s)";
				t.repeat_on_div.style.visibility = 'hidden';
				t.repeat_on_div.style.position = 'absolute';
				t.repeat_by_div.style.visibility = 'hidden';
				t.repeat_by_div.style.position = 'absolute';
			} else if (this.value == 'WEEKLY') {
				t.interval_text.nodeValue = "week(s)";
				t.repeat_on_div.style.visibility = 'visible';
				t.repeat_on_div.style.position = 'static';
				t.repeat_by_div.style.visibility = 'hidden';
				t.repeat_by_div.style.position = 'absolute';
			} else if (this.value == 'MONTHLY') {
				t.interval_text.nodeValue = "month(s)";
				t.repeat_on_div.style.visibility = 'hidden';
				t.repeat_on_div.style.position = 'absolute';
				t.repeat_by_div.style.visibility = 'visible';
				t.repeat_by_div.style.position = 'static';
			} else if (this.value == "YEARLY") {
				t.interval_text.nodeValue = "year(s)";
				t.repeat_on_div.style.visibility = 'hidden';
				t.repeat_on_div.style.position = 'absolute';
				t.repeat_by_div.style.visibility = 'hidden';
				t.repeat_by_div.style.position = 'absolute';
			}
		};
		calendar_td.onclick = function() {
			if (ev && !calendar.saveEvent) return; // we cannot move it
			var manager = window.top.calendar_manager;
			require("context_menu.js",function(){
				var menu = new context_menu();
				for (var i = 0; i < manager.calendars.length; ++i) {
					if (!manager.calendars[i].saveEvent) continue; // we cannot modify this calendar
					var item = document.createElement("DIV");
					item.calendar = manager.calendars[i]; 
					item.className = 'context_menu_item';
					var provider_icon = document.createElement("IMG");
					provider_icon.src = manager.calendars[i].provider.getProviderIcon();
					provider_icon.width = 16;
					provider_icon.style.width = '16px';
					provider_icon.style.paddingRight = '3px';
					item.appendChild(provider_icon);
					var box = document.createElement("DIV");
					box.style.display = 'inline-block';
					box.style.width = "10px";
					box.style.height = "10px";
					box.style.border = "1px solid #"+manager.calendars[i].color;
					box.style.backgroundColor = "#"+manager.calendars[i].color;
					box.style.marginRight = "3px";
					item.appendChild(box);
					if (manager.calendars[i].icon) {
						var icon = document.createElement("IMG");
						icon.src = manager.calendars[i].icon;
						icon.width = 16;
						icon.style.width = '16px';
						icon.style.paddingRight = '3px';
						item.appendChild(icon);
					}
					item.appendChild(document.createTextNode(manager.calendars[i].name));
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
				menu.showBelowElement(t.calendar_box);
			});
		};
				
		// initialize values
		if (ev) {
			t.title.value = ev.title;
			t.description.value = ev.description;
			t.from_date.setData(t.getDateString(ev.start));
			t.from_time.setData(ev.start.getHours()*60+ev.start.getMinutes());
			t.to_date.setData(t.getDateString(ev.end));
			t.to_time.setData(ev.end.getHours()*60+ev.end.getMinutes());
			if (ev.all_day) {
				t.all_day.checked = 'checked';
				t.from_time_span.style.visibility = 'hidden';
				t.from_time_span.style.position = 'absolute';
				t.to_time_span.style.visibility = 'hidden';
				t.to_time_span.style.position = 'absolute';
			}
			if (ev.frequency) {
				t.repeat.checked = 'checked';
				t.repeat.onchange();
				if (ev.frequency.frequency == "DAILY") {
					t.frequency.selectedIndex = 0;
				} else if (ev.frequency.frequency == "WEEKLY") {
					t.frequency.selectedIndex = 1;
					var days = ev.frequency.by_week_day.split(",");
					for (var i = 0; i < days.length; ++i) {
						days[i] = parseByDay(days[i]);
						switch (days[i][1]) {
							case 0: t.repeat_on_SU.checked = 'checked'; break;
							case 1: t.repeat_on_MO.checked = 'checked'; break;
							case 2: t.repeat_on_TU.checked = 'checked'; break;
							case 3: t.repeat_on_WE.checked = 'checked'; break;
							case 4: t.repeat_on_TH.checked = 'checked'; break;
							case 5: t.repeat_on_FR.checked = 'checked'; break;
							case 6: t.repeat_on_SA.checked = 'checked'; break;
						}
					}
				} else if (ev.frequency.frequency == "MONTHLY") {
					t.frequency.selectedIndex = 2;
					// TODO
				} else if (ev.frequency.frequency == "YEARLY") {
					t.frequency.selectedIndex = 3;
				}
				t.frequency.onchange();
				if (ev.frequency.count) {
					t.repeat_until_count.checked = 'checked';
					t.repeat_count.value = ev.frequency.count;
				} else if (ev.frequency.until) {
					t.repeat_until_date.checked = 'checked';
					t.repeat_until.setData(dateToSQL(ev.frequency.until));
				} else {
					t.repeat_until_never.checked = 'checked';
				}
			}
		} else {
			if (new_datetime) {
				t.from_date.setData(t.getDateString(new_datetime));
				t.from_time.setData(new_datetime.getHours()*60+new_datetime.getMinutes());
				var end = new Date(new_datetime.getTime()+60*60*1000);
				t.to_date.setData(t.getDateString(end));
				t.to_time.setData(end.getHours()*60+end.getMinutes());
				if (new_all_day) {
					ev.all_day.checked = 'checked';
					t.from_time_span.style.visibility = 'hidden';
					t.from_time_span.style.position = 'absolute';
					t.to_time_span.style.visibility = 'hidden';
					t.to_time_span.style.position = 'absolute';
				}
			}
		}
		
		
		t.popup = new popup_window("Event", "/static/calendar/event.png",content);
		if (!ev || t.selected_calendar.saveEvent)
		t.popup.addButton("<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> "+(ev ? "Save" : "Create"), 'ok', function(){
			var e = new CalendarEvent();
			e.title = t.title.value.trim();
			if (e.title.length == 0) {
				alert("Please enter a title for the event");
				return;
			}
			e.calendar_provider_id = t.selected_calendar.provider.id;
			e.calendar_id = t.selected_calendar.id;
			if (ev && (ev.calendar_id != t.selected_calendar.id || ev.calendar_provider_id != t.selected_calendar.provider.id)) {
				if (!confirm("You are going to move the event to a different calendar. Are you sure you want to remove it from "+ev.calendar.name+" and create it into "+e.calendar.name+" ?"))
					return;
			} else if (ev) {
				if (ev.id) e.id = ev.id;
				e.uid = ev.uid;
			}
			e.all_day = t.all_day.checked;
			e.start = parseSQLDate(t.from_date.getCurrentData());
			if (!e.all_day)
				e.start.setHours(0,t.from_time.getCurrentMinutes(),0,0);
			e.end = parseSQLDate(t.to_date.getCurrentData());
			if (!e.all_day)
				e.end.setHours(0,t.to_time.getCurrentMinutes(),0,0);
			else
				e.end.setHours(23,59,59,999);
			if (e.end.getTime() <= e.start.getTime()) {
				alert("The end of the event must be after its start ! Please correct the dates and times.");
				return;
			}
			e.description = t.description.value;
			e.participation = calendar_event_participation_yes;
			e.role = calendar_event_role_requested;
			// TODO get and validate the repeat
			t.selected_calendar.saveEvent(e);
			t.popup.close();
		});
		if (ev && t.selected_calendar.saveEvent)
			t.popup.addButton("<img src='"+theme.icons_16.remove+"' style='vertical-align:bottom'/> Remove", 'ok', function(){
				// TODO ask confirmation, then remove the event
			});
		t.popup.addButton("<img src='"+theme.icons_16.cancel+"' style='vertical-align:bottom'/> Cancel", 'cancel', function() { t.popup.close(); });
		t.popup.show();
	});
}