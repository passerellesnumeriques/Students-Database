function small_calendar() {
	var t = this;
	t.table = document.createElement("TABLE");
	t.table.className = 'small_calendar';
	t.table.appendChild(t.tbody = document.createElement("TBODY"));
	for (var i = 0; i < 5; ++i) {
		var tr = document.createElement("TR");
		for (var j = 0; j < 7; ++j) {
			var td = document.createElement("TD");
			td.innerHTML = (i*7)+j+1;
			td.onclick = function() {
				if (this.className == "disabled") return false;
				t.date.setDate(this.innerHTML);
				t.setDate(t.date);
			};
			tr.appendChild(td);
		}
		t.tbody.appendChild(tr);
	}
	
	t.getElement = function() { return t.table; };
	t.getDate = function() { return t.date; };
	t.setDate = function(date) {
		t.date = date;
		var c = new Date();
		c.setMonth(date.getMonth());
		c.setFullYear(date.getFullYear());
		var tr = t.tbody.childNodes[4];
		for (var i = 0; i < 7; ++i) {
			var td = tr.childNodes[i];
			c.setMonth(date.getMonth());
			c.setFullYear(date.getFullYear());
			c.setDate(4*7+i+1);
			if (c.getDate() == 4*7+i+1) {
				td.innerHTML = 4*7+i+1;
				td.className = "";
			} else {
				td.innerHTML = "";
				td.className = "disabled";
			}
		}
		for (var i = 0; i < t.tbody.childNodes.length-1; ++i)
			for (var j = 0; j < t.tbody.childNodes[i].childNodes.length; ++j)
				t.tbody.childNodes[i].childNodes[j].className = "";
		tr = t.tbody.childNodes[Math.floor((date.getDate()-1)/7)];
		var td = tr.childNodes[(date.getDate()-1)%7];
		td.className = "selected";
		if (t.onchange) t.onchange(t);
	};
	
	t.setDate(new Date());
}