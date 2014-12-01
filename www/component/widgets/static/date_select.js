function date_select(container, date, minimum, maximum, not_null, date_picker_icon) {
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
	t.select_day.style.verticalAlign = "bottom";
	t.select_month.style.verticalAlign = "bottom";
	t.select_year.style.verticalAlign = "bottom";
	
	t.minimum = minimum;
	t.maximum = maximum;
	
	t.onchange = null;
	
	container.ondomremoved(function() {
		t.select_day = null;
		t.select_month = null;
		t.select_year = null;
		t.icon = null;
	});
	
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
		if (!not_null) add_option(0,"",t.select_month);
		for (var i = start; i <= end; ++i) add_option(i+1, months[i], t.select_month);
		if (month-1 < start) month = start+1;
		if (month-1 > end) month = end+1;
		select_option(t.select_month, year == 0 ? 0 : month);
		// update days
		if (!not_null && year == 0)
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
		if (!not_null) add_option(0,"",t.select_day);
		for (var i = start; i <= end; ++i) add_option(i, i, t.select_day);
		if (day < start) day = start;
		if (day > end) day = end;
		select_option(t.select_day, month == 0 ? 0 : day);
		if (!not_null && month == 0)
			select_option(t.select_year, 0);
		t.select_day.onchange();
	};
	t.select_day.onchange = function() {
		if (t.select_day.value == 0) {
			select_option(t.select_year, 0);
			select_option(t.select_month, 0);
		}
		if (t.onchange) t.onchange(t);
	};

	t.setLimits = function(min, max) {
		if (!min) min = new Date(2004,0,1);
		if (!max) max = new Date(new Date().getFullYear()+100,11,31);
		t.minimum = min;
		t.maximum = max;
		var prev_sel = t.select_year.value;
		if (prev_sel != 0 && prev_sel < min.getFullYear()) prev_sel = min.getFullYear(); 
		if (prev_sel != 0 && prev_sel > max.getFullYear()) prev_sel = max.getFullYear(); 
		clear_options(t.select_year);
		if (!not_null) add_option(0,"",t.select_year);
		for (var i = min.getFullYear(); i <= max.getFullYear(); ++i) add_option(i,i,t.select_year);
		select_option(t.select_year, prev_sel);
		t.select_year.onchange();
	};
	t.selectDate = function(date) {
		if (date == null && not_null) date = new Date();
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
	t.setDate = function(date) { this.selectDate(date); };
	
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
	if (date_picker_icon) {
		t.icon = document.createElement("BUTTON");
		t.icon.className = "flat small";
		t.icon.style.verticalAlign = "bottom";
		t.icon.innerHTML = "<img src='"+theme.icons_16.date_picker+"' onload='layout.changed(this);'/>";
		t.icon.onclick = function(ev) {
			require(["date_picker.js","context_menu.js"],function(){
				var menu = new context_menu();
				new date_picker(t.getDate(),t.minimum,t.maximum,function(picker){
					picker.onchange = function(picker, date) {
						t.selectDate(date);
					};
					picker.getElement().style.border = 'none';
					menu.addItem(picker.getElement());
					picker.getElement().onclick = null;
					menu.element.className = menu.element.className+" popup_date_picker";
					menu.showBelowElement(t.select_day);
				});
			});
			stopEventPropagation(ev);
			return false;
		};
		container.appendChild(t.icon);
	}
}