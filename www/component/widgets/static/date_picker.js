if (typeof require != 'undefined') {
	require("small_calendar.js");
	require("date_select.js");
}
function date_picker(date, minimum, maximum, onready) {
	if (!date) date = new Date();
	if (!minimum) minimum = new Date(1900,0,1,0,0,0,0);
	if (!maximum) maximum = new Date(new Date().getFullYear()+200,11,31,0,0,0,0);
	
	var t = this;
	t.onchange = null;
	t.element = document.createElement("DIV");
	t.element.className = 'date_picker';
	t.element.appendChild(t.header = document.createElement("DIV"));
	t.getElement = function() { return t.element; };

	require(["date_select.js","small_calendar.js"],function() {
		// header: 3 selects for day, month and year 
		t.select = new date_select(t.header, date, minimum, maximum);
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
		if (onready) onready(t);

		t.setDate = function(date) { t.cal.setDate(date); t.select.selectDate(date); };
	});
}