function date_select(container, date, minimum, maximum) {
	if (typeof container == 'string') container = document.getElementById(container);
	container.style.whiteSpace = 'nowrap';
	
	var t=this;
	
	var add_option = function(value, text, select) { var o = document.createElement("OPTION"); o.value = value; o.text = text; select.add(o); };
	var select_option = function (select, value) { for (var i = 0; i < select.options.length; ++i) if (select.options[i].value == value) { select.selectedIndex = i; break; } };
	var clear_options = function(select) { while (select.options.length > 0) select.options.remove(0); };

	t.select_day = document.createElement("SELECT");
	t.select_month = document.createElement("SELECT");
	t.select_year = document.createElement("SELECT");
	t.select_day.onclick = function(ev) { stopEventPropagation(ev); return false; };
	t.select_month.onclick = function(ev) { stopEventPropagation(ev); return false; };
	t.select_year.onclick = function(ev) { stopEventPropagation(ev); return false; };
	var months = ["January","February","March","April","May","June","July","August","September","October","November","December"];

	t.select_day.style.margin = "0px";
	t.select_month.style.margin = "0px";
	t.select_year.style.margin = "0px";
	t.select_day.style.padding = "0px";
	t.select_month.style.padding = "0px";
	t.select_year.style.padding = "0px";
	
	t.minimum = minimum;
	t.maximum = maximum;
	
	t.onchange = null;
	t.select_year.onchange = function() {
		var year = t.select_year.value;
		var month = t.select_month.value;
		// update months
		var start = 0, end = 11;
		if (year != 0) {
			if (t.minimum && year == t.minimum.getFullYear()) start = t.minimum.getMonth();
			if (t.maximum && year == t.maximum.getFullYear()) end = t.maximum.getMonth();
		}
		clear_options(t.select_month);
		add_option(0,"",t.select_month);
		for (var i = start; i <= end; ++i) add_option(i+1, months[i], t.select_month);
		if (month-1 < start) month = start+1;
		if (month-1 > end) month = end+1;
		select_option(t.select_month, year == 0 ? 0 : month);
		// update days
		if (year == 0)
			select_option(t.select_day, 0);
		t.select_month.onchange();
	};
	t.select_month.onchange = function() {
		var year = t.select_year.value;
		var month = t.select_month.value;
		var day = t.select_day.value;
		var start = 1, end = 31;
		if (month != 0) {
			var d = new Date(year, month, 0);
			end = d.getDate();
			if (t.minimum && year == t.minimum.getFullYear() && month == t.minimum.getMonth()+1) start = t.minimum.getDate();
			if (t.maximum && year == t.maximum.getFullYear() && month == t.maximum.getMonth()+1) end = t.maximum.getDate();
		}
		clear_options(t.select_day);
		add_option(0,"",t.select_day);
		for (var i = start; i <= end; ++i) add_option(i, i, t.select_day);
		if (day < start) day = start;
		if (day > end) day = end;
		select_option(t.select_day, month == 0 ? 0 : day);
		if (month == 0)
			select_option(t.select_year, 0);
		t.select_day.onchange();
	};
	t.select_day.onchange = function() {
		if (t.select_day.value == 0) {
			select_option(t.select_year, 0);
			select_option(t.select_month, 0);
		}
		if (t.onchange) t.onchange();
	};

	t.setLimits = function(min, max) {
		t.minimum = min;
		t.maximum = max;
		var prev_sel = t.select_year.value;
		clear_options(t.select_year);
		add_option(0,"",t.select_year);
		for (var i = min.getFullYear(); i <= max.getFullYear(); ++i) add_option(i,i,t.select_year);
		select_option(t.select_year, prev_sel);
		t.select_year.onchange();
	};
	t.selectDate = function(date) {
		var cur_date = t.getDate();
		if (cur_date == null) {
			if (date == null) return;
		} else {
			if (date != null && cur_date.getFullYear() == date.getFullYear() && cur_date.getMonth() == date.getMonth() && cur_date.getDate() == date.getDate()) return;
		}
		if (date == null) { 
			select_option(t.select_year, 0);
			t.select_year.onchange();
		} else {
			select_option(t.select_year, date.getFullYear());
			t.select_year.onchange();
			select_option(t.select_month, date.getMonth()+1);
			t.select_month.onchange();
			select_option(t.select_day, date.getDate());
			t.select_day.onchange();
		}
	};
	
	t.getDate = function() {
		var year = t.select_year.value;
		var month = t.select_month.value;
		var day = t.select_day.value;
		if (year == 0 || month == 0 || day == 0) return null;
		return new Date(year, month-1, day,0,0,0,0);
	};

	t.setLimits(minimum, maximum);
	t.selectDate(date);
	
	container.appendChild(t.select_day);
	container.appendChild(t.select_month);
	container.appendChild(t.select_year);
}