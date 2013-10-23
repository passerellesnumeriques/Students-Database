function small_calendar() {
	var t = this;
	t.table = document.createElement("TABLE");
	t.table.className = 'small_calendar';
	t.table.appendChild(t.tbody = document.createElement("TBODY"));
	
	var days = ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"];
	
	t.getElement = function() { return t.table; };
	t.getDate = function() { return t.date; };
	t.setDate = function(date) {
		t.date = date;
		while (t.tbody.childNodes.length > 0) t.tbody.removeChild(t.tbody.childNodes[0]);
		var tr, td;
		t.tbody.appendChild(tr = document.createElement("TR"));
		for (var i = 0; i < days.length; ++i) {
			tr.appendChild(td = document.createElement("TH"));
			td.innerHTML = days[i];
		}
		// first day of the month
		var c = new Date();
		c.setMonth(date.getMonth());
		c.setFullYear(date.getFullYear());
		c.setDate(1);
		// last day of the month
		var end = new Date();
		end.setFullYear(date.getFullYear());
		end.setMonth(date.getMonth()+1);
		end.setDate(1);
		// go back to Monday
		while (c.getDay() != 1) c.setDate(c.getDate()-1);
		// go until we changed month
		while (c.getTime() < end.getTime()) {
			t.tbody.appendChild(tr = document.createElement("TR"));
			for (var i = 0; i < 7; ++i) {
				tr.appendChild(td = document.createElement("TD"));
				td.innerHTML = c.getDate();
				if (c.getMonth() != date.getMonth()) td.className = "disabled";
				else if (c.getTime() == date.getTime()) td.className = "selected";
				td.onclick = function() {
					if (this.className == "disabled") return false;
					t.date.setDate(this.innerHTML);
					t.setDate(t.date);
				};
				c.setDate(c.getDate()+1);
			}
		}
		if (t.onchange) t.onchange(t);
	};
	
	t.setDate(new Date());
}