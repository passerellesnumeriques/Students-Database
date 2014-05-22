if (typeof require != 'undefined') {
	require("date_select.js");
	require([["typed_field.js","field_time.js"]]);
}

/**
 * 
 * @param container
 * @param start
 * @param duration -1 for all day
 * @param minimum_date
 * @param maximum_date
 * @param can_be_null
 * @param horizontal
 * @returns
 */
function event_date_time_duration(container, start, duration, minimum_date, maximum_date, can_be_null, horizontal) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	this.date = start ? new Date(start.getTime()) : null;
	this.time = start ? start.getHours()*60+start.getMinutes() : 0;
	this.duration = duration;
	
	this._init = function() {
		container.appendChild(document.createTextNode("Date: "));
		this._date = new date_select(container, this.start, minimum_date, maximum_date, !can_be_null, true);
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
		this._duration = new field_time(null,true,{can_be_null:can_be_null});
		if (this.duration) this._duration.setData(this.duration);
		this._duration.onchange.add_listener(function() { t.duration = t._duration.getCurrentMinutes(); });
		container.appendChild(this._duration.getHTMLElement());
	};

	require(["date_select.js",["typed_field.js","field_time.js"]], function() {
		t._init();
	});
}