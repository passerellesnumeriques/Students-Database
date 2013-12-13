/**
 * @param container
 * @param data the object given by the get_json_IS_data method (selection.inc)
 * @param default_duration the duration that must be pre_selected when the event is created
 * @param can_read
 * @param can_manage: if true, the user can modify/remove the date
 */


function IS_date(container, event_id, IS_id, calendar_id, default_duration, can_read, can_manage, all_durations){
	var t = this;
	require(["popup_window.js",["typed_field.js","field_date.js","field_time.js"],"autoresize_input.js"],function(){t._setEventAttribute();});
	
	t.table = document.createElement("table");
	t.event = null;
	t.custom_event_name = null;
	t.date = null;
	t.start_sql = null;
	t.duration_selected = null;
	t.starting_time_selected = null;
		
	t._setEventAttribute = function(){
		if((IS_id != -1 && IS_id != "-1") && (event_id != null && event_id != "null") && t.event == null){
			service.json("calendar","get_event",{id:event_id, calendar_id:calendar_id},function(res){
				if(!res){
					t.event = null;
					return;
				}
				t.event = res;
				t._init();
			});
		} else {
			t.event = {};
			t.event.id = -1;
			t.event.calendar = calendar_id;
			t.event.start = null;
			t.event.end = null;
			t.event.all_day = null;
			t.event.title = null;
			t.event.description = null;
			t._init();
		}
	}
	
	t._init = function(){
		if(t.event != null && typeof(t.event.id) != "undefined"){
			t._setThead();
			t._setTable();
			if(can_manage) t._setFooter();
		}
		container.appendChild(t.table);
	}
	
	t._setThead = function(){
		var thead  = document.createElement("thead");
		var tr_head = document.createElement("tr");
		var th_head = document.createElement("th");
		th_head.colSpan = 2;
		th_head.innerHTML = "<img src = '"+theme.icons_16.date_picker+"' /> Date";
		tr_head.appendChild(th_head);
		thead.appendChild(tr_head);
		setCommonStyleTable(t.table, th_head, "#34A0FF");
		t.table.appendChild(thead);
	}
	
	t._setTable = function(){
		var tbody = document.createElement("tbody");
		var tr = document.createElement("tr");
		var td1 = document.createElement("td");
		var td2 = document.createElement("td");
		td1.innerHTML = "<font color='#808080'><b>Date selected: </b></font>";
		// td1.style.fontColor = "#959595";
		if(t.event.id != -1 || t.event.start != null){
			var start = null;
			if(typeof(t.event.start) == "string"){
				start = parseInt(t.event.start);
			} else {
				start = t.event.start;
			}
			var d = new Date(start*1000);
			t.start_sql = dateToSQL(d);
			t.date = new field_date(t.start_sql, false);
			td2.appendChild(t.date.getHTMLElement());
		} else {
			td2.innerHTML = "<i>No date selected</i>";
			t.date = new field_date(null, false);
			t.start_sql = null;
		}
		td2.style.paddingTop = "5px";
		td2.style.paddingBottom = "5px";
		tr.appendChild(td1)
		tr.appendChild(td2)
		tbody.appendChild(tr);
		
		if(default_duration != "All day"){
				var start_m = null;
				var start_h = null;
				var end_h = null;
				var end_m = null;
				var conf = {};
				conf.can_be_null = false;
			if(t.event.start != null && t.event.end != null){
				var tr_start = document.createElement("tr");
				var td2_start = document.createElement("td");
				var td1_start = document.createElement("td");
				if(t.starting_time_selected == null){
					t.starting_time_selected = "";
					var d = new Date(t.event.start * 1000);
					t.starting_time_selected += d.getHours()+":"+d.getMinutes();
				}
				if(t.duration_selected == null){
					var dif = parseInt(t.event.end) - parseInt(t.event.start);
					t.duration_selected = Math.floor(dif / (1000 * 60 * 60));
				}
				var temp_ending_date = new Date(t.event.end * 1000);
				var temp_ending_time = temp_ending_date.getHours() + ":" + temp_ending_date.getMinutes();
				var start_field_time = new field_time(t.starting_time_selected,false,conf);
				td1_start.innerHTML = "<font color='#808080'><b>Starting time: </b></font>";
				td2_start.appendChild(start_field_time.getHTMLElement());
				tr_start.appendChild(td1_start);
				tr_start.appendChild(td2_start);
				tbody.appendChild(tr_start);
			
				var tr_end = document.createElement("tr");
				var td2_end = document.createElement("td");
				var td1_end = document.createElement("td");
				// var date_end = new Date(t.event.end*1000);
				var end_field_time = new field_time(temp_ending_time,false,conf);

				td1_end.innerHTML = "<font color='#808080'><b>Ending time: </b></font>";
				td2_end.appendChild(end_field_time.getHTMLElement());
				
				tr_end.appendChild(td1_end);
				tr_end.appendChild(td2_end);
				tbody.appendChild(tr_end);
			}
		}
		t.table.appendChild(tbody);
	}
	
	t._setFooter = function(){
		var tfoot = document.createElement("tfoot");
		var tr = document.createElement("tr");
		var td = document.createElement("td");
		var div_set_date = document.createElement("div");
		div_set_date.className = "button";
		div_set_date.innerHTML = "<img src = '/static/selection/date_clock_picker.png' style='vertical-align:bottom'/> Set the date";
		td.style.borderTop = "1px solid #959595";
		td.appendChild(div_set_date);
		td.colSpan = 2;
		tr.appendChild(td);
		tfoot.appendChild(tr);
		t.table.appendChild(tfoot);
		div_set_date.onclick = function(){
			var table = document.createElement("table");
			var pop = new popup_window("Set the date",theme.icons_16.date_picker,table);
			if(default_duration != "All day"){
				t._popSelectHours(table, pop);
			} else {
				t._popSelectAllDay(table, pop);
			}
			pop.show();
		};
	}
	
	t._popSelectAllDay = function(table,pop){
		table.appendChild(t._setTrSelectDate());
		// if(custom_event) table.appendChild(t._setTrSetCustomEventName());
		pop.addOkCancelButtons(function(){
			var day = t.date.getCurrentData();
			if(day != null){
				// t.setEventName();
				var start_timestamp = t._getStartTimestamp(day);
				t.event.start = start_timestamp;
				t.event.end = start_timestamp + 23*3600 + 59*60 + 59;
				t.event.all_day = true;
				pop.close();
				var locker = lock_screen();
				t.resetTable(locker);
			} else error_dialog("You must select a date");
		});
	}
	
	// t.setEventName = function(){
		// if(custom_event && t.custom_event_name != null && t.custom_event_name.checkVisible()){
			// t.event.title = t.custom_event_name;
		// } else {
			// t.event.title = default_event_name;
		// }
	// }
	
	t._getStartTimestamp = function(start){
		var start_date = parseSQLDate(start);
		//we convert it into seconds (php time)
		return start_date.getTime()/1000;
	}
	
	t._setTrSelectDate = function(){
		var tr = document.createElement("tr");
		var td = document.createElement("td");
		td.innerHTML = "<font color='#808080'><b>Date: </b></font>";
		var td2 = document.createElement("td");
		t.date = new field_date(t.start_sql, true);
		td2.appendChild(t.date.getHTMLElement());
		tr.appendChild(td);
		tr.appendChild(td2);
		return tr;
	}
	
	t._setTrSetCustomEventName = function(){
		var tr_title = document.createElement("tr");
		var td1_title = document.createElement("td");
		var td2_title = document.createElement("td");
		td1_title.innerHTML = "<font color='#808080'><b>Event title: </b></font><br/><i>For the selection<br/> calendar</i>";
		var input = document.createElement("input");
		if(t.event.title != null) input.value = t.event.title;
		autoresize_input(input);
		input.onchange = function(){
			t.custom_event_name = input.value.uniformFirstLetterCapitalized();
		};
		td2_title.appendChild(input);
		tr_title.appendChild(td1_title);
		tr_title.appendChild(td2_title);
		return tr_title;
	}
	
	t._popSelectHours = function(table, pop){
		table.appendChild(t._setTrSelectDate());
		table.appendChild(t._setTrSelectTime());
		// if(custom_event) table.appendChild(t._setTrSetCustomEventName());
		pop.addOkCancelButtons(function(){
			var day = t.date.getCurrentData();
			if(day != null){
				// t.setEventName();
				var day_timestamp = t._getStartTimestamp(day);
				//manage the starting time
				var string_start_time = t.starting_time_selected.split(":");
				var start_h = parseInt(string_start_time[0]);
				var start_m = parseInt(string_start_time[1]);
				t.event.start = day_timestamp + start_h * 60 * 60 + start_m * 60;
				//manage the ending time
				var duration_hours = parseInt(t.duration_selected);
				t.event.end = t.event.start + duration_hours * 60 * 60;
				t.event.all_day = false;
				pop.close();
				var locker = lock_screen();
				t.resetTable(locker);
			} else error_dialog("You must select a date");
		});
	}
	
	t._setTrSelectTime = function(){
		var tr = document.createElement("tr");
		var td_start = document.createElement("td");
		var td_duration = document.createElement("td");
		var conf = {};
		conf.can_be_null = false;
		var start = new field_time(t.starting_time_selected,true,conf);
		start.ondatachanged.add_listener(function(field){
			t.starting_time_selected = field.getCurrentData();
		});
		var div = document.createElement("div");
		div.innerHTML = "<font color='#808080'><b>Starting time: </b></font>";
		div.appendChild(start.getHTMLElement());
		t.starting_time_selected = start.getCurrentData();
		td_start.appendChild(div);
		var div_duration = document.createElement("div");
		div_duration.innerHTML = "<font color = '#808080'><b>Duration: </b></font>";
		div.style.paddingTop = "10px";
		div.style.paddingBottom = "10px";
		var select = document.createElement("select");
		for(var i = 0; i < all_durations.length; i++){
			if(all_durations[i] != "All day"){
				var option = document.createElement("option");
				option.value = t._getHoursDuration(all_durations[i]);
				option.innerHTML = all_durations[i];
				if(all_durations[i] == default_duration && t.duration_selected == null){
					option.selected = true;
					t.duration_selected = t._getHoursDuration(all_durations[i]);
				} else if(t.duration_selected != null && t._getHoursDuration(all_durations[i]) == t.duration_selected)
					option.selected = true;
				select.appendChild(option);
			}
		}
		select.onchange = function(){
			t.duration_selected = select.options[select.selectedIndex].value;
		};
		div_duration.appendChild(select);
		td_duration.appendChild(div_duration);
		tr.appendChild(td_start);
		tr.appendChild(td_duration);
		return tr;
	}
	
	t._getHoursDuration = function(duration_string){
		var tab = duration_string.split(" ");
		return(tab[0]);
	}
	
	t.resetTable = function(locker){
		container.removeChild(t.table);
		t.table = document.createElement("table");
		// container.appendChild(t.table);
		t._init();
		if(typeof(locker) != "undefined" && locker != null) unlock_screen(locker);
	}
	
	t.getEvent = function(){
		return t.event;
	}
}