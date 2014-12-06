/* #depends[typed_field.js] */
/** Enum field: if editable, it will be a combo box (select element), else only a simple text node
 * @constructor
 * @param config must contain:<code>possible_values</code>: an array of element, each element can be (1) a string, which will be displayed and used as key, (2) an array of 2 elements: the key and the string to display
 */
function field_multiple_choice(data,editable,config) {
	if (data == null) data = [];
	typed_field.call(this, data, editable, config);
}
field_multiple_choice.prototype = new typed_field_multiple();
field_multiple_choice.prototype.constructor = field_multiple_choice;
field_multiple_choice.prototype.canBeNull = function() { return true; };
field_multiple_choice.prototype.getPossibleValues = function() {
	var values = [];
	for (var i = 0; i < this.config.possible_values.length; ++i) {
		if (this.config.possible_values[i] instanceof Array)
			values.push(this.config.possible_values[i][1]);
		else
			values.push(this.config.possible_values[i]);
	}
	return values;
};
field_multiple_choice.prototype._create = function(data) {
	this.checkboxes = [];
	if (this.config.wrap == 'yes') this.element.style.whiteSpace = 'nowrap';
	for (var i = 0; i < this.config.possible_values.length; ++i) {
		var span = document.createElement(this.config.wrap == 'always' ? "DIV" : "SPAN");
		span.style.whiteSpace = "nowrap";
		span.style.verticalAlign = "middle";
		var cb = document.createElement("INPUT");
		cb.type = 'checkbox';
		cb.disabled = this.editable ? "" : "disabled";
		var val, text;
		if (this.config.possible_values[i] instanceof Array) {
			val = this.config.possible_values[i][0];
			text = this.config.possible_values[i][1];
		} else
			val = text = this.config.possible_values[i];
		cb.checked = data.indexOf(val) >= 0 ? "checked" : "";
		span.appendChild(cb);
		cb.style.marginRight = "3px";
		cb.style.marginBottom = "4px";
		cb.style.verticalAlign = "middle";
		span.appendChild(document.createTextNode(text));
		this.element.appendChild(span);
	}
};