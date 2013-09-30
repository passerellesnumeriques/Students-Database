function event_screen(ev,default_calendar) {
	var t=this;
	t.event = object_copy(ev);
	t._2digits = function(n) {
		var s = ""+n;
		while (s.length < 2) s = "0"+s;
		return s;
	};
	t.getDateString = function(date) {
		return date.getFullYear()+"-"+t._2digits(date.getMonth()+1)+"-"+t._2digits(date.getDate());
	};
	require([["typed_field.js","field_date.js"],"popup_window.js","calendar_view.js"],function() {
		var o;

		t.content = document.createElement("DIV");
		t.content.style.padding = "5px";
		// event title
		t.title_div = document.createElement("DIV"); t.content.appendChild(t.title_div);
		t.title_div.style.fontSize = "12pt";
		t.title_div.style.whiteSpace = 'nowrap';
		t.title_div.appendChild(document.createTextNode("Title "));
		t.title = document.createElement("INPUT");
		t.title.type = 'text';
		t.title.size = 80;
		t.title.style.fontSize = '12pt';
		t.title_div.appendChild(t.title);

		// event timing
		t.timing_div = document.createElement("DIV"); t.content.appendChild(t.timing_div);
		t.timing_div.style.marginTop = "3px";
		t.timing_div.style.marginBottom = "2px";
		t.timing_div.appendChild(document.createTextNode("From "));
		t.from_date = new field_date(null,true,null,null,{});
		t.timing_div.appendChild(t.from_date.getHTMLElement());
		t.from_time_span = document.createElement("SPAN");
		t.timing_div.appendChild(t.from_time_span);
		t.from_time_span.appendChild(document.createTextNode(" at "));
		t.timing_div.appendChild(document.createTextNode(" To "));
		t.to_date = new field_date(null,true,null,null,{});
		t.timing_div.appendChild(t.to_date.getHTMLElement());
		t.to_time_span = document.createElement("SPAN");
		t.timing_div.appendChild(t.to_time_span);
		t.to_time_span.appendChild(document.createTextNode(" at "));
		t.timing_div.appendChild(document.createTextNode(" "));
		t.all_day = document.createElement("INPUT");
		t.all_day.type = "checkbox";
		t.all_day.style.verticalAlign = 'bottom';
		t.timing_div.appendChild(t.all_day);
		t.timing_div.appendChild(document.createTextNode(" All day"));
		
		// calendar
		t.calendar_div = document.createElement("DIV"); t.content.appendChild(t.calendar_div);
		t.calendar_div.appendChild(document.createTextNode("Calendar: "));
		t.calendar_icon = document.createElement("IMG");
		t.calendar_icon.width = 16;
		t.calendar_icon.style.width = '16px';
		t.calendar_icon.src = ev ? ev.calendar.icon : default_calendar.icon;
		t.calendar_icon.style.marginRight = '3px';
		t.calendar_div.appendChild(t.calendar_icon);
		t.calendar_box = document.createElement("DIV"); t.calendar_div.appendChild(t.calendar_box);
		t.calendar_box.style.display = "inline-block";
		t.calendar_box.style.width = "10px";
		t.calendar_box.style.height = "10px";
		t.calendar_box.style.border = "1px solid #"+(ev ? ev.calendar.color : default_calendar.color);
		t.calendar_box.style.backgroundColor = "#"+(ev ? ev.calendar.color : default_calendar.color);
		t.calendar_box.style.marginRight = '3px';
		t.calendar_div.appendChild(document.createTextNode(ev ? ev.calendar.name : default_calendar.name));
		t.calendar_div.style.cursor = 'pointer';
		
		// recurrence
		t.repeat = document.createElement("INPUT");
		t.repeat.type = 'checkbox';
		t.repeat.style.verticalAlign = 'bottom';
		t.content.appendChild(t.repeat);
		t.content.appendChild(document.createTextNode("Repeat..."));
		t.content.appendChild(document.createElement("BR"));
		t.repeat_div = document.createElement("DIV");
		t.content.appendChild(t.repeat_div);
		t.repeat_div.style.marginLeft = '15px';
		t.repeat_div.style.visibility = 'hidden';
		t.repeat_div.style.position = 'absolute';
		t.frequency = document.createElement("SELECT");
		o = document.createElement("OPTION"); o.value = 'DAILY'; o.text = 'Daily'; t.frequency.add(o);
		o = document.createElement("OPTION"); o.value = 'WEEKLY'; o.text = 'Weekly'; t.frequency.add(o);
		o = document.createElement("OPTION"); o.value = 'MONTHLY'; o.text = 'Monthly'; t.frequency.add(o);
		o = document.createElement("OPTION"); o.value = 'YEARLY'; o.text = 'Yearly'; t.frequency.add(o);
		t.frequency.selectedIndex = 1; // by default, weekly
		t.repeat_div.appendChild(t.frequency);
		t.repeat_div.appendChild(document.createTextNode(" Every "));
		t.interval = document.createElement("SELECT");
		for (var i = 1; i <= 30; ++i) { o = document.createElement("OPTION"); o.value = i; o.text = i; t.interval.add(o); }
		t.repeat_div.appendChild(t.interval);
		t.repeat_div.appendChild(document.createTextNode(" "));
		t.interval_text = document.createTextNode("week(s)");
		t.repeat_div.appendChild(t.interval_text);
		t.repeat_on_div = document.createElement("DIV");
		t.repeat_div.appendChild(t.repeat_on_div);
		t.repeat_on_div.appendChild(document.createTextNode("Repeat on: "));
		t.repeat_on_MO = document.createElement("INPUT"); t.repeat_on_MO.type = 'checkbox'; t.repeat_on_MO.style.verticalAlign = 'bottom';
		t.repeat_on_div.appendChild(t.repeat_on_MO); t.repeat_on_div.appendChild(document.createTextNode(" Mon"));
		t.repeat_on_TU = document.createElement("INPUT"); t.repeat_on_TU.type = 'checkbox'; t.repeat_on_TU.style.verticalAlign = 'bottom';
		t.repeat_on_div.appendChild(t.repeat_on_TU); t.repeat_on_div.appendChild(document.createTextNode(" Tue"));
		t.repeat_on_WE = document.createElement("INPUT"); t.repeat_on_WE.type = 'checkbox'; t.repeat_on_WE.style.verticalAlign = 'bottom';
		t.repeat_on_div.appendChild(t.repeat_on_WE); t.repeat_on_div.appendChild(document.createTextNode(" Wed"));
		t.repeat_on_TH = document.createElement("INPUT"); t.repeat_on_TH.type = 'checkbox'; t.repeat_on_TH.style.verticalAlign = 'bottom';
		t.repeat_on_div.appendChild(t.repeat_on_TH); t.repeat_on_div.appendChild(document.createTextNode(" Thu"));
		t.repeat_on_FR = document.createElement("INPUT"); t.repeat_on_FR.type = 'checkbox'; t.repeat_on_FR.style.verticalAlign = 'bottom';
		t.repeat_on_div.appendChild(t.repeat_on_FR); t.repeat_on_div.appendChild(document.createTextNode(" Fri"));
		t.repeat_on_SA = document.createElement("INPUT"); t.repeat_on_SA.type = 'checkbox'; t.repeat_on_SA.style.verticalAlign = 'bottom';
		t.repeat_on_div.appendChild(t.repeat_on_SA); t.repeat_on_div.appendChild(document.createTextNode(" Sat"));
		t.repeat_on_SU = document.createElement("INPUT"); t.repeat_on_SU.type = 'checkbox'; t.repeat_on_SU.style.verticalAlign = 'bottom';
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
		t.repeat_by_div.appendChild(t.repeat_by_month_day);
		t.repeat_by_div.appendChild(document.createTextNode(" day of the month"));
		t.repeat_by_week_day = document.createElement("INPUT");
		t.repeat_by_week_day.type = 'radio';
		t.repeat_by_week_day.name = 'repeat_by';
		t.repeat_by_week_day.value = 'week_day';
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
		t.repeat_until_div.appendChild(t.repeat_until_never); t.repeat_until_div.appendChild(document.createTextNode(" Never "));
		t.repeat_until_count = document.createElement("INPUT");
		t.repeat_until_count.type = 'radio';
		t.repeat_until_count.name = 'repeat_until';
		t.repeat_until_count.value = 'count';
		t.repeat_until_div.appendChild(t.repeat_until_count);
		t.repeat_until_div.appendChild(document.createTextNode(" After "));
		t.repeat_count = document.createElement("INPUT"); t.repeat_until_div.appendChild(t.repeat_count);
		t.repeat_count.type = 'text';
		t.repeat_count.size = 5;
		t.repeat_until_div.appendChild(document.createTextNode(" occurences "));
		t.repeat_until_date = document.createElement("INPUT");
		t.repeat_until_date.type = 'radio';
		t.repeat_until_date.name = 'repeat_until';
		t.repeat_until_date.value = 'date';
		t.repeat_until_div.appendChild(t.repeat_until_date);
		t.repeat_until_div.appendChild(document.createTextNode(" On "));
		t.repeat_until = new field_date(null,true,null,null,{});
		t.repeat_until_div.appendChild(t.repeat_until.getHTMLElement());
		
		// event details
		t.content.appendChild(document.createTextNode("Description"));
		t.content.appendChild(document.createElement("BR"));
		t.description = document.createElement("TEXTAREA");
		t.description.rows = 4;
		t.description.cols = 80;
		if (ev) t.description.value = ev.description;
		t.content.appendChild(t.description);

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
		t.calendar_div.onclick = function() {
			var manager = ev ? ev.calendar.manager : default_calendar.manager;
			require("context_menu.js",function(){
				var menu = new context_menu();
				for (var i = 0; i < manager.calendars.length; ++i) {
					var item = document.createElement("DIV");
					item.className = 'context_menu_item';
					var icon = document.createElement("IMG");
					icon.src = manager.calendars[i].icon;
					icon.width = 16;
					icon.style.width = '16px';
					icon.style.paddingRight = '3px';
					item.appendChild(icon);
					var box = document.createElement("DIV");
					box.style.display = 'inline-block';
					box.style.width = "10px";
					box.style.height = "10px";
					box.style.border = "1px solid #"+manager.calendars[i].color;
					box.style.backgroundColor = "#"+manager.calendars[i].color;
					box.style.marginRight = "3px";
					item.appendChild(box);
					item.appendChild(document.createTextNode(manager.calendars[i].name));
					item.onclick = function() {
						// TODO
					};
					menu.addItem(item);
				}
				menu.showBelowElement(t.calendar_box);
			});
		};
				
		// initialize values
		if (ev) {
			t.title.value = ev.title;
			t.from_date.setData(t.getDateString(ev.start));
			t.to_date.setData(t.getDateString(ev.end));
			if (ev.all_day) {
				ev.all_day.checked = 'checked';
				t.from_time_span.style.visibility = 'hidden';
				t.from_time_span.style.position = 'absolute';
				t.to_time_span.style.visibility = 'hidden';
				t.to_time_span.style.position = 'absolute';
			}
			if (ev.frequency) {
				t.repeat.checked = 'checked';
				t.repeat.onchange();
				if (ev.frequency == "DAILY") {
					t.frequency.selectedIndex = 0;
				} else if (ev.frequency == "WEEKLY") {
					t.frequency.selectedIndex = 1;
					var days = ev.by_week_day.split(",");
					for (var i = 0; i < days.length; ++i) {
						days[i] = parse_by_day(days[i]);
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
				} else if (ev.frequency == "MONTHLY") {
					t.frequency.selectedIndex = 2;
					// TODO
				} else if (ev.frequency == "YEARLY") {
					t.frequency.selectedIndex = 3;
				}
				t.frequency.onchange();
				if (ev.count) {
					t.repeat_until_count.checked = 'checked';
					t.repeat_count.value = ev.count;
				} else if (ev.until) {
					t.repeat_until_date.checked = 'checked';
					t.repeat_until.setData(ev.until);
				} else {
					t.repeat_until_never.checked = 'checked';
				}
			}
		}
		
		
		t.popup = new popup_window("Event", "/static/calendar/event.png",content);
		t.popup.addButton("<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> "+(ev ? "Save" : "Create"), 'ok', function(){
			
		});
		t.popup.addButton("<img src='"+theme.icons_16.cancel+"' style='vertical-align:bottom'/> Cancel", 'cancel', function() { t.popup.close(); });
		t.popup.show();
	});
}