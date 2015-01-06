/**
 * Display 3 SELECT: day, month and year. And optionnaly an icon to display a date picker.
 * @param {Element} container where to put it
 * @param {Date} date initial date
 * @param {Date} minimum minimum date the user can select
 * @param {Date} maximum maximum date the user can select
 * @param {Boolean} not_null if true, the user will be forced to enter a date. If no initial date is given, the today's date is set.
 * @param {Boolean} date_picker_icon if true, an icon will be displayed to allow the user to select a date using a date_picker
 */
function date_select(container, date, minimum, maximum, not_null, date_picker_icon) {
	if (typeof container == 'string') container = document.getElementById(container);
	container.style.whiteSpace = 'nowrap';
	
	var t=this;
	
	/** Add an option to a select
	 * @param {String} value value
	 * @param {String} text text
	 * @param {Element} select SELECT
	 */
	var addOption = function(value, text, select) { var o = document.createElement("OPTION"); o.value = value; o.text = text; select.add(o); };
	/** Select an OPTION in a SELECT
	 * @param {Element} select the SELECT
	 * @param {String} value the value of the OPTION to select
	 */
	var selectOption = function (select, value) { for (var i = 0; i < select.options.length; ++i) if (select.options[i].value == value) { select.selectedIndex = i; break; } };
	/** Remove all options in a select
	 * @param {Element} select the SELECT to clear
	 */
	var clearOptions = function(select) { while (select.options.length > 0) select.options.remove(0); };

	/** SELECT for the month day */
	t.select_day = document.createElement("SELECT");
	/** SELECT for the month */
	t.select_month = document.createElement("SELECT");
	/** SELECT for the year */
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
	
	/** {Function} if specified, called each time the user change the date */
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
		clearOptions(t.select_month);
		if (!not_null) addOption(0,"",t.select_month);
		for (var i = start; i <= end; ++i) addOption(i+1, months[i], t.select_month);
		if (month-1 < start) month = start+1;
		if (month-1 > end) month = end+1;
		selectOption(t.select_month, year == 0 ? 0 : month);
		// update days
		if (!not_null && year == 0)
			selectOption(t.select_day, 0);
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
		clearOptions(t.select_day);
		if (!not_null) addOption(0,"",t.select_day);
		for (var i = start; i <= end; ++i) addOption(i, i, t.select_day);
		if (day < start) day = start;
		if (day > end) day = end;
		selectOption(t.select_day, month == 0 ? 0 : day);
		if (!not_null && month == 0)
			selectOption(t.select_year, 0);
		t.select_day.onchange();
	};
	t.select_day.onchange = function() {
		if (t.select_day.value == 0) {
			selectOption(t.select_year, 0);
			selectOption(t.select_month, 0);
		}
		if (t.onchange) t.onchange(t);
	};

	/** Set the minimum and maximum dates
	 * @param {Date} min minimum date
	 * @param {Date} max maximum date
	 */
	t.setLimits = function(min, max) {
		if (!min) min = new Date(2004,0,1);
		if (!max) max = new Date(new Date().getFullYear()+100,11,31);
		t.minimum = min;
		t.maximum = max;
		var prev_sel = t.select_year.value;
		if (prev_sel != 0 && prev_sel < min.getFullYear()) prev_sel = min.getFullYear(); 
		if (prev_sel != 0 && prev_sel > max.getFullYear()) prev_sel = max.getFullYear(); 
		clearOptions(t.select_year);
		if (!not_null) addOption(0,"",t.select_year);
		for (var i = min.getFullYear(); i <= max.getFullYear(); ++i) addOption(i,i,t.select_year);
		selectOption(t.select_year, prev_sel);
		t.select_year.onchange();
	};
	/** Set the date
	 * @param {Date} date new selected date
	 */
	t.selectDate = function(date) {
		if (date == null && not_null) date = new Date();
		var cur_date = t.getDate();
		if (cur_date == null) {
			if (date == null) return;
		} else {
			if (date != null && cur_date.getFullYear() == date.getFullYear() && cur_date.getMonth() == date.getMonth() && cur_date.getDate() == date.getDate()) return;
		}
		if (date == null) { 
			selectOption(t.select_year, 0);
			t.select_year.onchange();
		} else {
			selectOption(t.select_year, date.getFullYear());
			t.select_year.onchange();
			selectOption(t.select_month, date.getMonth()+1);
			t.select_month.onchange();
			selectOption(t.select_day, date.getDate());
			t.select_day.onchange();
		}
	};
	/** Set the date
	 * @param {Date} date new selected date
	 */
	t.setDate = function(date) { this.selectDate(date); };
	
	/** Get the currently selected date
	 * @returns {Date} selected date
	 */
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