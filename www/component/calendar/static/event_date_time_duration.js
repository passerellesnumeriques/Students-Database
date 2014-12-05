if (typeof require != 'undefined') {
	require("date_select.js");
	require([["typed_field.js","field_time.js"]]);
}

/** Displays an editable date_select, time field, and duration
 * @param {Element|String} container where to put it
 * @param {Date} start starting date/time
 * @param {Number} duration in minutes
 * @param {Date} minimum_date minimum date the user can select, or null
 * @param {Date} maximum_date maximum date the user can select, or null
 * @param {Boolean} can_be_null if false, the user must select a date, time and duration
 * @param {Boolean} horizontal if true, all fields are displayed horizontally, else the date is on one line, time and duration on a second line
 */
function event_date_time_duration(container, start, duration, minimum_date, maximum_date, can_be_null, horizontal, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	/** {Date} selected date */
	this.date = start ? new Date(start.getTime()) : null;
	/** {Number} selected time in minutes */
	this.time = start ? start.getHours()*60+start.getMinutes() : 0;
	this.duration = duration;
	
	/** Initialize the display */
	this._init = function() {
		container.appendChild(document.createTextNode("Date: "));
		this._date = new date_select(container, this.date, minimum_date, maximum_date, !can_be_null, true);
		this._date.onchange = function(ds) { t.date = ds.getDate(); };
		this.date = this._date.getDate();
		
		if (!horizontal) {
			container.appendChild(document.createElement("BR"));
			var br = document.createElement("BR");
			container.appendChild(br);
			br.style.fontSize = "2px";
		}
		
		container.appendChild(document.createTextNode("Time: "));
		this._start_time = new field_time(null,true,{can_be_null:can_be_null});
		this._start_time.setData(this.time);
		this._start_time.onchange.add_listener(function() { t.time = t._start_time.getCurrentMinutes(); });
		container.appendChild(this._start_time.getHTMLElement());
		container.appendChild(document.createTextNode(" Duration: "));
		this._duration = new field_time(null,true,{can_be_null:can_be_null,is_duration:true});
		if (this.duration) this._duration.setData(this.duration);
		this._duration.onchange.add_listener(function() { t.duration = t._duration.getCurrentMinutes(); });
		container.appendChild(this._duration.getHTMLElement());
		if (onready) onready(this);
	};

	require(["date_select.js",["typed_field.js","field_time.js"]], function() {
		t._init();
	});
}