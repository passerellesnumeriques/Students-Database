/**
 * Create a section contaning the date selection for the given Information Session
 * @param {HTMLElement|String} container
 * @param {Number | NULL} event_id the ID of the event linked to this information session, if any
 * @param {Number} IS_id the ID of the information session
 * @param {Number} calendar_id the ID of the calendar linked to this selection campaign
 * @param {String} default_duration the preselected duration of an Information Session, given by the config attribute
 * @param {Boolean} can_manage
 * @param {Array} all_durations containing all the possible durations for an information session
 */
function is_date(container, event_id, IS_id, calendar_id, default_duration, can_manage, all_durations){
	var t = this;
	if (typeof container == 'string') container = document.getElementById(container);
	
	require("section.js");
	
	this.onchange = new Custom_Event();
	
	this._update = function() {
		if (!this._event || !this._event.start) {
			this._link.innerHTML = "Not yet specified";
			this._link.style.color = "darkorange";
			this._remove_button.style.display = "none";
		} else {
			this._link.style.color = "";
			var d = this._event.start;
			var s = getDayShortName(d.getDay(),true)+" "+_2digits(d.getDate())+" "+getMonthShortName(d.getMonth()+1)+" "+d.getFullYear();
			if (default_duration != "All day") {
				s += "<br/>";
				s += " from ";
				s += getTimeString(d);
				s += " to ";
				s += getTimeString(this._event.end);
			}
			this._link.innerHTML = s;
			this._remove_button.style.display = can_manage ? "" : "none";
		}
	};
	
	this._popupDate = function() {
		var javascripts = ["mini_popup.js","date_picker.js"];
		if (default_duration != "All day") javascripts.push(["typed_field.js","field_time.js"]);
		require(javascripts,function() {
			var p = new mini_popup("When will occurs this Information Session ?");
			// Date
			var div_date = document.createElement("DIV");
			p.content.appendChild(div_date);
			div_date.style.display = "inline-block";
			div_date.style.verticalAlign = "middle";
			var date = new date_picker(t._event.start, null, null);
			div_date.appendChild(date.getElement());
			// Time and duration
			var start = null;
			var select_duration = null;
			if(default_duration != "All day") {
				var div_time = document.createElement("DIV");
				p.content.appendChild(div_time);
				div_time.style.display = "inline-block";
				div_time.style.verticalAlign = "middle";
				div_time.style.lineHeight = "20px";
				div_time.style.marginLeft = "5px";
				// starting time
				div_time.appendChild(document.createTextNode("Starting time: "));
				start = new field_time(t._event.start ? t._event.start.getHours()*60+t._event.start.getMinutes() : null,true,{can_be_null:false});
				div_time.appendChild(start.getHTMLElement());
				div_time.appendChild(document.createElement("BR"));
				// duration
				div_time.appendChild(document.createTextNode(" Duration: "));
				select_duration = document.createElement("SELECT");
				div_time.appendChild(select_duration);
				var current_duration = t._event.start ? Math.floor((t._event.end.getTime()-t._event.start.getTime())/(60*60*1000)) : default_duration;
				for(var i = 0; i < all_durations.length; i++){
					if(all_durations[i] != "All day"){
						var option = document.createElement("OPTION");
						option.value = t._getHoursDuration(all_durations[i]);
						option.innerHTML = all_durations[i];
						if(all_durations[i] == current_duration)
							option.selected = true;
						select_duration.add(option);
					}
				}
			}
			// ok button
			p.addOkButton(function() {
				if (!date.getDate) return false;
				var d = date.getDate();
				if (!d) return false;
				if (start && start.getCurrentData() === null) return false;
				d.setHours(0,start.getCurrentMinutes(),0,0);
				t._event.start = d;
				t._event.end = new Date(t._event.start.getTime()+select_duration.value*60*60*1000);
				t._update();
				window.pnapplication.dataUnsaved("SelectionISDate");
				t.onchange.fire();
				return true;
			});
			// show
			p.showBelowElement(t._link);
		});
	};
	
	this._reset = function() {
		if (this._event.start == null) return;
		if (event_id == null || event_id <= 0)
			window.pnapplication.dataSaved("SelectionISDate");
		else
			window.pnapplication.dataUnsaved("SelectionISDate");
		this._event.start = this._event.end = null;
		this._update();
		this.onchange.fire();
	};
	
	require("section.js",function() {
		// initialize section content
		t._content = document.createElement("DIV");
		t._link = document.createElement("A");
		t._link.href = "#";
		t._link.className = "black_link";
		t._content.style.margin = "2px 3px 0px 3px";
		t._link.onclick = function() { if (can_manage) t._popupDate(); return false; };
		t._content.appendChild(t._link);
		t._remove_button = document.createElement("BUTTON");
		t._remove_button.className = "flat icon";
		t._remove_button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
		t._remove_button.style.display = "none";
		t._remove_button.onclick = function() { t._reset(); return false; };
		t._content.appendChild(t._remove_button);
		t.section = new section("/static/selection/date_clock_picker.png","Date",t._content,false,false,"soft");
		container.appendChild(t.section.element);
		// load event, then update
		if (event_id != null && typeof event_id == 'object') {
			t._event = event_id;
			event_id = t._event.id;
			t._update();
		} else {
			if((IS_id != -1 && IS_id != "-1") && (event_id != null && event_id != "null") && t._event == null){
				service.json("calendar","get_event",{id:event_id, calendar_id:calendar_id},function(res){
					t._event = res;
					t._update();
				});
			} else {
				require("calendar_objects.js",function() {
					t._event = new CalendarEvent(-1, 'PN', calendar_id, null, null, null, false, 0, null, null, null, null);
					t._update();
				});
			}
		}
		require(["mini_popup.js",["typed_field.js",["field_date.js","field_time.js"]]]);
	});
	
	/**
	 * Set the event ID attribute
	 * @param {Number} id, the new event ID
	 */
	t.setEventId = function(id){
		t._event.id = id;
	};
	
	/**
	 * Get the event
	 * @returns {CalendarEvent} the event
	 */
	t.getEvent = function(){
		return t._event;
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
	
}