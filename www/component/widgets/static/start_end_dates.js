function StartEndDates(start, end) {
	start.onchange.addListener(function() {
		end.setMinimum(start.getDate());
	});
	end.onchange.addListener(function() {
		start.setMaximum(end.getDate());
	});
}
function ConfigurableDate() {
	this.onchange = new Custom_Event();
}
ConfigurableDate.prototype = {
	onchange: null,
	getDate: function() {},
	setDate: function(date) {},
	setMinimum: function(min) {},
	setMaximum: function(max) {}
};
function SelectDay(container, current, min, max) {
	ConfigurableDate.call(this);
	var t=this;
	this.select = null;
	this.min = min;
	this.max = max;
	require("date_select.js", function() {
		t.select = new date_select(container, current, t.min, t.max, true, true);
	});
	this.getDate = function() {
		if (!this.select) return null;
		return this.select.getDate();
	};
	this.setDate = function(date) {
		if (!this.select) current = date;
		else this.select.setDate(date);
	};
	this.setMinimum = function(min) {
		this.min = min;
		if (this.select) this.select.setLimits(this.min, this.max);
	};
	this.setMaximum = function(max) {
		this.max = max;
		if (this.select) this.select.setLimits(this.min, this.max);		
	};
}
SelectDay.prototype = new ConfigurableDate;
SelectDay.prototype.constructor = SelectDay;

function SelectMonth(container, current, min, max) {
	ConfigurableDate.call(this);
	this.select = document.createElement("SELECT");
	container.appendChild(this.select);
	this.min = new Date(min.getTime());
	this.max = new Date(max.getTime());
	this.date = new Date(current.getTime());
	this.min.setDate(1);
	this.min.setHours(0,0,0,0);
	this.max.setDate(1);
	this.max.setHours(0,0,0,0);
	this.date.setHours(0,0,0,0);
	this.date.setDate(1);
	this.getDate = function() {
		return this.date;
	};
	this.setDate = function(date) {
		this.select.value = date.getTime();
	};
	this.setMinimum = function(min) {
		this.min = new Date(min.getTime());
		this.min.setDate(1);
		this.min.setHours(0,0,0,0);
		if (this.date.getTime() < min.getTime()) this.date = new Date(min.getTime());
		this.update();
	};
	this.setMaximum = function(max) {
		this.max = new Date(max.getTime());
		this.max.setDate(1);
		this.max.setHours(0,0,0,0);
		if (this.date.getTime() > max.getTime()) this.date = new Date(max.getTime());
		this.update();		
	};
	this.update = function() {
		while (this.select.options.length > 0) this.select.remove(0);
		var d = new Date(this.min.getTime());
		var selected = -1;
		while (d.getTime() <= this.max.getTime()) {
			var o = document.createElement("OPTION");
			o.value = d.getTime();
			o.text = getMonthName(d.getMonth()+1)+" "+d.getFullYear();
			this.select.add(o);
			if (d.getMonth() == this.date.getMonth() && d.getFullYear() == this.date.getFullYear())
				selected = this.select.options.length-1;
			d.setMonth(d.getMonth()+1);
		}
		this.select.selectedIndex = selected;
	};
	this.update();
	var t=this;
	this.select.onchange = function() {
		t.date = new Date(parseInt(this.value));
		t.onchange.fire();
	};
}
SelectMonth.prototype = new ConfigurableDate;
SelectMonth.prototype.constructor = SelectMonth;
