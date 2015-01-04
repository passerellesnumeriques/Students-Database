/* #depends[typed_filter.js] */
/**
 * Filter on a text. It will be display with an operator (contains, starts with...) and an input to enter text to search.
 * The data is an object with 2 attributes:<ul>
 * <li>type: the operator</li>
 * <li>value: the text</li>
 * </ul>
 * Configuration is not used.
 */
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
	input.type = 'text';
	input.value = data.value;
	input.last_value = data.value;
	if (!editable) {
		select.disabled = 'disabled';
		input.disabled = 'disabled';
	}
	
	select.onchange = function() {
		data.type = select.value;
		t.onchange.fire(t);
	};
	input.onchange = function() {
		if (data.value == input.value) return;
		data.value = input.value;
		t.onchange.fire(t);
	};
	input.onkeyup = function() {
		setTimeout(function() {
			if (input.value == input.last_value) return;
			input.last_value = input.value;
			data.value = input.value;
			t.onchange.fire(t);
		},1);
	};
	
	this.isActive = function() {
		return input.value.trim().length > 0;
	};
	
	this.focus = function() {
		input.focus();
	};
}
filter_text.prototype = new typed_filter;
filter_text.prototype.constructor = filter_text;
filter_text.prototype.can_multiple = true;
