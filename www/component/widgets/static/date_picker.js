if (typeof require != 'undefined')
	require("small_calendar.js");
function date_picker(max_year, onready) {
	if (!max_year) max_year = new Date().getFullYear();
	var t = this;
	t.element = document.createElement("DIV");
	t.element.className = 'date_picker';
	t.element.appendChild(t.header = document.createElement("DIV"));
	t.header.appendChild(t.daySelect = document.createElement("SELECT"));
	t.header.appendChild(t.monthSelect = document.createElement("SELECT"));
	t.header.appendChild(t.yearSelect = document.createElement("SELECT"));

	for (var i = 0; i < 12; ++i) {
		var o = document.createElement("OPTION");
		o.value = (i+1);
		switch (i) {
		case 0: o.text = "January"; break;
		case 1: o.text = "February"; break;
		case 2: o.text = "March"; break;
		case 3: o.text = "April"; break;
		case 4: o.text = "May"; break;
		case 5: o.text = "June"; break;
		case 6: o.text = "July"; break;
		case 7: o.text = "August"; break;
		case 8: o.text = "September"; break;
		case 9: o.text = "October"; break;
		case 10: o.text = "November"; break;
		case 11: o.text = "December"; break;
		}
		t.monthSelect.add(o);
	}
	for (var year = 1900; year <= max_year; ++year) {
		var o = document.createElement("OPTION");
		o.value = year;
		o.text = year;
		t.yearSelect.add(o);
	}
	for (var day = 1; day <= 28; ++day) {
		var o = document.createElement("OPTION");
		o.value = day;
		o.text = day;
		t.daySelect.add(o);
	}
	
	t.getElement = function() { return t.element; };
	t.setDate = function(date) { if (t.cal) t.cal.setDate(date); else t.date = date; };
	
	require("small_calendar.js",function() {
		t.cal = new small_calendar();
		if (t.date != null) t.cal.setDate(t.date);
		t.element.appendChild(t.cal.getElement());
		t.daySelect.onchange = function() {
			var date = t.cal.getDate();
			date.setDate(t.daySelect.selectedIndex+1);
			t.cal.setDate(date);
		};
		t.monthSelect.onchange = function() {
			var date = t.cal.getDate();
			date.setMonth(t.monthSelect.selectedIndex);
			t.cal.setDate(date);
		};
		t.yearSelect.onchange = function() {
			var date = t.cal.getDate();
			date.setFullYear(t.yearSelect.options[t.yearSelect.selectedIndex].value);
			t.cal.setDate(date);
		};

		t._date_changed = function(date) {
			for (var i = 0; i < t.yearSelect.options.length; ++i)
				if (t.yearSelect.options[i].value == date.getFullYear()) {
					t.yearSelect.selectedIndex = i;
					break;
				}
			t.monthSelect.selectedIndex = date.getMonth();
			var max_day = 28;
			do {
				var c = new Date();
				c.setFullYear(date.getFullYear());
				c.setMonth(date.getMonth());
				c.setDate(max_day+1);
				if (c.getDate() != max_day+1) break;
				max_day++;
			} while (true);
			while (t.daySelect.options.length > max_day)
				t.daySelect.remove(max_day);
			while (t.daySelect.options.length < max_day) {
				var o = document.createElement("OPTION");
				o.value = (t.daySelect.options.length+1);
				o.test = (t.daySelect.options.length+1);
				t.daySelect.add(o);
			}
			t.daySelect.selectedIndex = date.getDate()-1;
		};
		t.cal.onchange = function(cal) { 
			t._date_changed(cal.getDate());
			if (t.onchange)
				t.onchange(this, cal.getDate());
		};
		t._date_changed(new Date());
		if (onready) onready(t);
	});
}