/* #depends[typed_filter.js] */
function filter_enum(data, config) {
	if (data == null) data = {value:''};
	typed_filter.call(this, data, config);
	
	var t=this;
	var select = document.createElement("SELECT"); this.element.appendChild(select);
	var o;
	o = document.createElement("OPTION"); o.value = ""; o.text = ""; if (data.value == '') o.selected = true; select.add(o);
	if (config.can_be_empty) {
		o = document.createElement("OPTION"); o.value = "NULL"; o.text = "<All not specified>"; if (data.value == 'NULL') o.selected = true; select.add(o);
		o = document.createElement("OPTION"); o.value = "NOT_NULL"; o.text = "<All specified>"; if (data.value == 'NOT_NULL') o.selected = true; select.add(o);
	}
	for (var i = 0; i < config.possible_values.length; ++i) {
		o = document.createElement("OPTION");
		o.value = config.possible_values[i][0];
		o.text = config.possible_values[i][1];
		if (data.value == config.possible_values[i][0]) o.selected = true;
		select.add(o);
	}
	
	select.onchange = function() {
		t.onchange.fire(t);
	};
	
	this.getCurrentData = function() {
		this.currentData = {value:select.value};
		return this.currentData;
	};
}
filter_enum.prototype = new typed_filter;
filter_enum.prototype.constructor = filter_enum;
