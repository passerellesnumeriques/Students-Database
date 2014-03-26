/**
 * Create a section contaning the date selection for the given Information Session
 * @param {HTMLElement|String} container
 * @param {Number | NULL} event_id the ID of the event linked to this information session, if any
 * @param {Number} IS_id the ID of the information session
 * @param {Number} calendar_id the ID of the calendar linked to this selection campaign
 * @param {String} default_duration the preselected duration of an Information Session, ginven by the config attribute
 * @param {Boolean} can_manage
 * @param {Array} all_durations containing all the possible durations for an information session
 */
function IS_date(container, event_id, IS_id, calendar_id, default_duration, can_manage, all_durations){
	var t = this;
	require(["popup_window.js",["typed_field.js","field_date.js","field_time.js"],"input_utils.js","section.js"],function(){
		t.container_of_section_content = document.createElement("div");
		t.section = new section(theme.icons_16.date_picker,"Date",t.container_of_section_content,false,false,"soft");
		container.appendChild(t.section.element);
		t._setEventAttribute();
	});
	
	t.table = document.createElement("table");
	t.table.style.width = "100%";
	
	/**
	 * Reset the section element content, removing all its children and launching the process
	 * @param {HTMLElement|NULL} the screen locker to remove, if any
	 */
	t.resetTable = function(locker){
		t.container_of_section_content.removeChild(t.table);
		t.section.resetToolBottom();
		delete t.table;
		t.table = document.createElement("table");
		// container.appendChild(t.table);
		t._init();
		if(typeof(locker) != "undefined" && locker != null) unlock_screen(locker);
	};	
	
	/**
	 * Set the event ID attribute
	 * @param {Number} id, the new event ID
	 */
	t.setEventId = function(id){
		t._event.id = id;
	};
	
	/**
	 * Get the event id
	 * @returns {Number} the event ID
	 */
	t.getEvent = function(){
		return t._event;
	};
	
	/**Private attributes and functionalities*/
	t._start_sql = null;
	t._event = null;
	t._custom_event_name = null;
	t._date = null;
	t._duration_selected = null;
	t._starting_time_selected = null;
	
	/**
	 * Set the event attribute.
	 * If the event already exists into the database (ID != -1), the event is retrieved callind the calendar#get_event service
	 * Else a new calendar object is created
	 */
	t._setEventAttribute = function(){
		if((IS_id != -1 && IS_id != "-1") && (event_id != null && event_id != "null") && t._event == null){
			service.json("calendar","get_event",{id:event_id, calendar_id:calendar_id},function(res){
				if(!res){
					t._event = null;
					return;
				}
				t._event = res;
				t._init();
			});
		} else {
			require("calendar_objects.js",function() {
				t._event = new CalendarEvent(-1, 'PN', calendar_id, null, null, null, false, 0, null, null, null, "Selection", "UNKNOWN", "OPTIONAL");
				t._init();
			});
		}
	};
	
	/**
	 * Launch the process, populate the table
	 */
	t._init = function(){
		if(t._event != null && typeof(t._event.id) != "undefined"){
			t._setTable();
			if(can_manage) t._setFooter();
		}
		t.container_of_section_content.appendChild(t.table);
	};
	
	/**
	 * Set the table content
	 * If a date is selected, the date, starting time and ending time are displayed
	 * If the default duration is all day, no starting nor ending time are displayed 
	 */
	t._setTable = function(){
		var tbody = document.createElement("tbody");
		var tr = document.createElement("tr");
		var td1 = document.createElement("td");
		var td2 = document.createElement("td");
		td1.innerHTML = "<font color='#808080'><b>Date selected: </b></font>";
		if(t._event.id != -1 || t._event.start != null){
			var start = null;
			if(typeof(t._event.start) == "string"){
				start = parseInt(t._event.start);
			} else {
				start = t._event.start;
			}
			var d = new Date(start*1000);
			t._start_sql = dateToSQL(d);
			t._date = new field_date(t._start_sql, false);
			td2.appendChild(t._date.getHTMLElement());
		} else {
			td2.innerHTML = "<i>No date selected</i>";
			t._date = new field_date(null, false);
			t._start_sql = null;
		}
		td2.style.paddingTop = "5px";
		td2.style.paddingBottom = "5px";
		tr.appendChild(td1);
		tr.appendChild(td2);
		tbody.appendChild(tr);
		
		if(default_duration != "All day"){
				var conf = {};
				conf.can_be_null = false;
			if(t._event.start != null && t._event.end != null){
				var tr_start = document.createElement("tr");
				var td2_start = document.createElement("td");
				var td1_start = document.createElement("td");
				if(t._starting_time_selected == null){
					t._starting_time_selected = "";
					var d = new Date(t._event.start * 1000);
					t._starting_time_selected += d.getHours()+":"+d.getMinutes();
				}
				if(t._duration_selected == null){
					var dif = parseInt(t._event.end) - parseInt(t._event.start);
					t._duration_selected = Math.floor(dif / (1000 * 60 * 60));
				}
				var temp_ending_date = new Date(t._event.end * 1000);
				var temp_ending_time = temp_ending_date.getHours() + ":" + temp_ending_date.getMinutes();
				var start_field_time = new field_time(t._starting_time_selected,false,conf);
				td1_start.innerHTML = "<font color='#808080'><b>Starting time: </b></font>";
				td2_start.appendChild(start_field_time.getHTMLElement());
				tr_start.appendChild(td1_start);
				tr_start.appendChild(td2_start);
				tbody.appendChild(tr_start);
			
				var tr_end = document.createElement("tr");
				var td2_end = document.createElement("td");
				var td1_end = document.createElement("td");
				// var date_end = new Date(t._event.end*1000);
				var end_field_time = new field_time(temp_ending_time,false,conf);

				td1_end.innerHTML = "<font color='#808080'><b>Ending time: </b></font>";
				td2_end.appendChild(end_field_time.getHTMLElement());
				
				tr_end.appendChild(td1_end);
				tr_end.appendChild(td2_end);
				tbody.appendChild(tr_end);
			}
		}
		t.table.appendChild(tbody);
	};
	
	/**
	 * Set the footer of the section
	 * Populate it with the buttons select date / remove date (if already set)
	 */
	t._setFooter = function(){
		var div_set_date = document.createElement("div");
		div_set_date.className = "button";
		div_set_date.innerHTML = "<img src = '/static/selection/IS/date_clock_picker.png' style='vertical-align:bottom'/> Set the date";
		t.section.addToolBottom(div_set_date);
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
		var remove_button = document.createElement("div");
		remove_button.className = "button";
		remove_button.onclick = function(){
			// var locker = lock_screen();
			t._resetTableAndEvent();
		};
		remove_button.innerHTML = "<img src = '"+theme.icons_16.remove+"' /> Unset date";
		if(t._event.start != null)
			t.section.addToolBottom(remove_button);
	};
	
	/**
	 * Set the select event popup content, with the content for all day (no starting / ending time)
	 * @param {HTMLElement} table the table to populate
	 * @param {Popup_window} the popup containing the table 
	 */
	t._popSelectAllDay = function(table,pop){
		table.appendChild(t._setTrSelectDate());
		// if(custom_event) table.appendChild(t._setTrSetCustomEventName());
		pop.addOkCancelButtons(function(){
			var day = t._date.getCurrentData();
			var locker = lock_screen();
			if(day != null){
				// t.setEventName();
				var start_timestamp = t._getStartTimestamp(day);
				t._event.start = start_timestamp;
				t._event.end = start_timestamp + 23*3600 + 59*60 + 59;
				t._event.all_day = true;
				pop.close();
				t.resetTable(locker);
			} else {
				t._resetTableAndEvent(locker);
				pop.close();
			}
		});
	};
	
	/**
	 * Get the start time stamp from the SQL date
	 * @returns {Number} the start timestamp, in seconds (PHP time)
	 */
	t._getStartTimestamp = function(start){
		var start_date = parseSQLDate(start);
		//we convert it into seconds (PHP time)
		return start_date.getTime()/1000;
	};
	
	/**
	 * Set the TR element select date, for the select date popup_window
	 * This method creates an editable field date
	 */
	t._setTrSelectDate = function(){
		var tr = document.createElement("tr");
		var td = document.createElement("td");
		td.innerHTML = "<font color='#808080'><b>Date: </b></font>";
		var td2 = document.createElement("td");
		t._date = new field_date(t._start_sql, true);
		td2.appendChild(t._date.getHTMLElement());
		tr.appendChild(td);
		tr.appendChild(td2);
		return tr;
	};
	
	
//	t._setTrSetCustomEventName = function(){
//		var tr_title = document.createElement("tr");
//		var td1_title = document.createElement("td");
//		var td2_title = document.createElement("td");
//		td1_title.innerHTML = "<font color='#808080'><b>Event title: </b></font><br/><i>For the selection<br/> calendar</i>";
//		var input = document.createElement("input");
//		if(t._event.title != null) input.value = t._event.title;
//		inputAutoresize(input);
//		input.onchange = function(){
//			t._custom_event_name = input.value.uniformFirstLetterCapitalized();
//		};
//		td2_title.appendChild(input);
//		tr_title.appendChild(td1_title);
//		tr_title.appendChild(td2_title);
//		return tr_title;
//	};
	
	/**
	 * Set the select event popup content, with starting / ending time
	 */
	t._popSelectHours = function(table, pop){
		table.appendChild(t._setTrSelectDate());
		table.appendChild(t._setTrSelectTime());
		// if(custom_event) table.appendChild(t._setTrSetCustomEventName());
		pop.addOkCancelButtons(function(){
			var locker = lock_screen();
			var day = t._date.getCurrentData();
			if(day != null){
				// t.setEventName();
				var day_timestamp = t._getStartTimestamp(day);
				//manage the starting time
				var string_start_time = t._starting_time_selected.split(":");
				var start_h = parseInt(string_start_time[0]);
				var start_m = parseInt(string_start_time[1]);
				t._event.start = day_timestamp + start_h * 60 * 60 + start_m * 60;
				//manage the ending time
				var duration_hours = parseInt(t._duration_selected);
				t._event.end = t._event.start + duration_hours * 60 * 60;
				t._event.all_day = false;
				pop.close();
				t.resetTable(locker);
			} else {
				t._resetTableAndEvent(locker);
				pop.close();
			}
		});
	};
	
	/**
	 * Set the TR element to select the starting time of the IS
	 * An editable field_time is created, and a select element containing all the durations available is added.
	 * The preselected time is the default_duration
	 */
	t._setTrSelectTime = function(){
		var tr = document.createElement("tr");
		var td_start = document.createElement("td");
		var td_duration = document.createElement("td");
		var conf = {};
		conf.can_be_null = false;
		var start = new field_time(t._starting_time_selected,true,conf);
		start.ondatachanged.add_listener(function(field){
			t._starting_time_selected = field.getCurrentData();
		});
		var div = document.createElement("div");
		div.innerHTML = "<font color='#808080'><b>Starting time: </b></font>";
		div.appendChild(start.getHTMLElement());
		t._starting_time_selected = start.getCurrentData();
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
				if(all_durations[i] == default_duration && t._duration_selected == null){
					option.selected = true;
					t._duration_selected = t._getHoursDuration(all_durations[i]);
				} else if(t._duration_selected != null && t._getHoursDuration(all_durations[i]) == t._duration_selected)
					option.selected = true;
				select.appendChild(option);
			}
		}
		select.onchange = function(){
			t._duration_selected = select.options[select.selectedIndex].value;
		};
		div_duration.appendChild(select);
		td_duration.appendChild(div_duration);
		tr.appendChild(td_start);
		tr.appendChild(td_duration);
		return tr;
	};
	
	/**
	 * Get the number of hours matching the all_duration attribute ("2 hours"...)
	 * @param {String} duration_string, a all_duration attribute
	 * @returns {Number} the number of hours
	 */
	t._getHoursDuration = function(duration_string){
		var tab = duration_string.split(" ");
		return(tab[0]);
	};
	
	/**
	 * Reset the table content and the event attribute
	 */
	t._resetTableAndEvent = function(locker){
		t.container_of_section_content.removeChild(t.table);
		t.section.resetToolBottom();
		delete t.table;
		t.table = document.createElement("table");
		t._setEventAttribute();
		if(typeof(locker) != "undefined" && locker != null) unlock_screen(locker);
	};
}