/* #depends[typed_filter.js] */
function filter_text(data, config, editable) {
	if (data == null) data = {type:'contains',value:''};
	typed_filter.call(this, data, config, editable);
	
	var t=this;
	var select = document.createElement("SELECT"); this.element.appendChild(select);
	var input = document.createElement("INPUT"); this.element.appendChild(input);
	var o;
	o = document.createElement("OPTION"); o.value = "contains"; o.text = "Contains"; if (data.type == 'contains') o.selected = true; select.add(o);
	o = document.createElement("OPTION"); o.value = "starts"; o.text = "Starts with"; if (data.type == 'starts') o.selected = true; select.add(o);
	o = document.createElement("OPTION"); o.value = "ends"; o.text = "Ends with"; if (data.type == 'ends') o.selected = true; select.add(o);
	o = document.createElement("OPTION"); o.value = "exact"; o.text = "Exactly"; if (data.type == 'exact') o.selected = true; select.add(o);
	input.value = data.value;
	if (!editable) {
		select.disabled = 'disabled';
		input.disabled = 'disabled';
	}
	
	select.onchange = function() {
		t.onchange.fire(t);
	};
	input.onchange = function() {
		t.onchange.fire(t);
	};
	
	this.getCurrentData = function() {
		this.currentData = {type:select.value,value:input.value};
		return this.currentData;
	};
}
filter_text.prototype = new typed_filter;
filter_text.prototype.constructor = filter_text;
