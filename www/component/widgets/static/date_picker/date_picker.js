if (typeof require != 'undefined') {
	require("small_calendar.js");
	require("date_select.js");
}
if (typeof theme != 'undefined') {
	theme.css("date_picker.css");
	theme.css("small_calendar.css");
}
/**
 * Small calendar to select a date
 * @param {Date} date the currently selected date. If not given, the date of today will be selected
 * @param {Date} minimum minimum date the user can select. If not given, 1 January 1900 will be the minimum
 * @param {Date} maximum maximum date the user can select. If not given, the maximum will be 200 years after today
 * @param {Function} onready called when everything is ready and we can display it
 */
function date_picker(date, minimum, maximum, onready) {
	if (!date) date = new Date();
	if (!minimum) minimum = new Date(1900,0,1,0,0,0,0);
	if (!maximum) maximum = new Date(new Date().getFullYear()+200,11,31,0,0,0,0);
	
	var t = this;
	/** {Function} callend when the selected date change */
	t.onchange = null;
	/** The top-level element of the screen */
	t.element = document.createElement("DIV");
	t.element.className = 'date_picker';
	t.element.appendChild(t.header = document.createElement("DIV"));
	/** Return the top-level element of the screen, to include it where we want */
	t.getElement = function() { return t.element; };

	require(["date_select.js","small_calendar.js"],function() {
		var back = document.createElement("IMG");
		back.src = getScriptPath("date_picker.js")+'back.png';
		back.className = "button_verysoft";
		back.style.margin = "0px 2px 0px 2px";
		back.style.padding = "0px 1px 0px 1px";
		t.header.appendChild(back);
		back.onclick = function() {
			var d = t.select.getDate();
			d.setMonth(d.getMonth()-1);
			t.select.selectDate(d);
			t.cal.setDate(d);
			if (t.onchange) t.onchange(t, t.cal.getDate());
		};
		// header: 3 selects for day, month and year 
		t.select = new date_select(t.header, date, minimum, maximum);
		var forward = document.createElement("IMG");
		forward.src = getScriptPath("date_picker.js")+'forward.png';
		forward.className = "button_verysoft";
		forward.style.margin = "0px 2px 0px 2px";
		forward.style.padding = "0px 1px 0px 1px";
		t.header.appendChild(forward);
		forward.onclick = function() {
			var d = t.select.getDate();
			var new_month = d.getMonth()+1;
			d.setMonth(new_month);
			if (new_month > 11) new_month = 0;
			while (d.getMonth() != new_month) d.setDate(d.getDate()-1);
			t.select.selectDate(d);
			t.cal.setDate(d);
			if (t.onchange) t.onchange(t, t.cal.getDate());
		};
		// small calendar
		t.cal = new small_calendar(minimum, maximum);
		t.cal.setDate(date);
		t.element.appendChild(t.cal.getElement());
		// change events
		t.select.onchange = function() {
			t.cal.setDate(t.select.getDate());
			if (t.onchange) t.onchange(t, t.cal.getDate());
		};
		t.cal.onchange = function() { 
			t.select.selectDate(t.cal.getDate());
			if (t.onchange) t.onchange(t, t.cal.getDate());
		};
		t.element.appendChild(t.footer = document.createElement("DIV"));
		t.footer.style.padding = "2px";
		t.footer.appendChild(document.createTextNode("Today: "));
		var today = document.createElement("A");
		t.footer.appendChild(today);
		var now = new Date();
		today.appendChild(document.createTextNode(now.getDate()+" "+getMonthName(now.getMonth()+1)+" "+now.getFullYear()));
		today.href ='#';
		today.onclick = function () {
			t.cal.setDate(new Date());
			t.select.selectDate(new Date());
			if (t.onchange) t.onchange(t, t.cal.getDate());
			return false;
		};

		t.setDate = function(date) { t.cal.setDate(date); t.select.selectDate(date); };
		t.getDate = function() { return t.cal.getDate(); };

		if (onready) onready(t);
		layout.changed(t.element);
	});
}