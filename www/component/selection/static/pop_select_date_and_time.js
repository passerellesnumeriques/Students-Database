/**
 * Create a popup window containing a day, a starting time, and a duration selections
 * This object handles a new CalendarEvent object
 * Note: Only the start, end, all_day attributes of the CalendarEvent object are set by this class
 * @param {String} pop_title title of the popup window
 * @param {String} pop_icon icon of the popup window
 * @param {Array} duration_choice containing the options displayed in the select duration element. Each option must be an object with two attributes:<ul><li><code>name</code> {String} the name content of the option</li><li><code>value</code> {Number} duration value, in SECONDS</li></ul>
 * @param {String} preselected_duration value (in SECONDS) of the option to preselect
 * @param {Function} onok function called with the new CalendarEvent object when ok button is pressed
 * @param {Function} oncancel function called when cancel button is pressed
 * @param {CalendarEvent} event if not starting from scratch but from an existing event
 */
function pop_select_date_and_time(pop_title,pop_icon, duration_choice, preselected_duration, onok, oncancel, event){
	var t = this;
	
	/**
	 * Launch the process, create the popup, add the buttons, show
	 */
	t._init = function(){
		t._container = document.createElement("div");
		t.pop = new popup_window(pop_title,pop_icon,t._container);
		t._event = (typeof event != "undefined" && event != null) ? event : new CalendarEvent();
		t._event.all_day = false;
		t._setPopContent();
		t.pop.addOkCancelButtons(t._onok,t._oncancel);
		t.pop.show();
	};
	
	/**
	 * Create two rows into the popup: one with the date picker, other one with the field time (starting time) and the duration select
	 */
	t._setPopContent = function(){
		var div_date = document.createElement("div");
		var div_time = document.createElement("div");
		t._container.appendChild(div_date);
		t._container.appendChild(div_time);
		//Set the date row
		div_date.appendChild(document.createTextNode("Date:"));
		var date_sql = null;
		if(t._event.start != null){
			var date = new Date(parseInt(t._event.start) * 1000);
			date_sql = dateToSQL(date);
		}
		t._field_date = new field_date(date_sql,true,{can_be_null:false});
		var date_picker = t._field_date.getHTMLElement();
		date_picker.style.marginLeft = "3px";
		div_date.appendChild(date_picker);
		//Set the time row
		var div1 = document.createElement("div");
		div1.style.display = "inline-block";
		div1.appendChild(document.createTextNode("Starting time:"));//contains the field time
		var _start = null;
		if(t._event.start != null){
			var d = new Date(parseInt(t._event.start) * 1000);
			_start = d.getHours()+":"+d.getMinutes();
		}
		t._field_time = new field_time(_start,true,{can_be_null:false});
		var time_picker = t._field_time.getHTMLElement();
		time_picker.style.marginLeft = "3px";
		div1.appendChild(time_picker);
		var div2 = document.createElement("div");//contains the duration select
		div2.style.display = "inline-block";
		div2.appendChild(document.createTextNode("Duration:"));
		div2.style.marginLeft = "30px";
		t._duration_select = document.createElement("select");
		for(var i = 0; i < duration_choice.length; i++){
			var option = document.createElement("option");
			option.appendChild(document.createTextNode(duration_choice[i].name));
			option.value = duration_choice[i].value;
			if(duration_choice[i].value == preselected_duration)
				option.selected = true;
			t._duration_select.appendChild(option);
		}
		div2.appendChild(t._duration_select);
		div_time.appendChild(div1);
		div_time.appendChild(div2);
	};
	
	/**
	 * Method called when the ok button is pressed
	 * Check that a date is selected and then processes the event before calling onok function, then close the popup
	 */
	t._onok = function(){
		if(t._field_date.error){
			errorDialog("You must set a date");
			return;
		}
		if(onok){
			//Update the event attribute
			//manage the starting time
			var day = t._field_date.getCurrentData();
			var day_timestamp = parseSQLDate(day);
			day_timestamp = day_timestamp.getTime()/1000;//we convert it into seconds (PHP time)
			var string_start_time = t._field_time.getCurrentData().split(":");
			var start_h = parseInt(string_start_time[0]);
			var start_m = parseInt(string_start_time[1]);
			t._event.start = day_timestamp + start_h * 60 * 60 + start_m * 60;
			//manage the ending time
			var duration = t._duration_select.options[t._duration_select.selectedIndex].value;
			t._event.end = t._event.start + parseInt(duration);
			onok(t._event);
		}
		t.pop.close();
	};
	
	/**
	 * Method called when the cancel button is pressed
	 * Oncancel function is called and then the popup is closed
	 */
	t._oncancel = function(){
		if(oncancel)
			oncancel();
		t.pop.close();
	};
	
	
	require([["popup_window.js","typed_field.js","calendar_objects.js"],["field_time.js","field_date.js"]],function(){
		t._init();
	});
}