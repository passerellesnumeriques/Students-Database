/* #depends[typed_field.js] */
/**
 * Boolean field: check box
 * @param {Boolean} data initial value
 * @param {Boolean} editable editable
 * @param {Object} config not used
 */
function field_boolean(data,editable,config) {
	if (data == null) data = false;
	if (typeof data == 'string') data = (data == "true" || data == "1" || data.trim().toLowerCase() == "yes" || data.trim().toLowerCase() == "y");
	else if (typeof data == 'number') data = (data == 1);
	typed_field.call(this, data, editable, config);
}
field_boolean.prototype = new typed_field();
field_boolean.prototype.constructor = field_text;
field_boolean.prototype.exportCell = function(cell) { cell.value = this.getCurrentData() ? "Yes" : "No"; cell.format = "boolean"; };
field_boolean.prototype.canBeNull = function() { return false; };
field_boolean.prototype._create = function(data) {
	var t=this;
	t.input = document.createElement("INPUT");
	t.input.type = "checkbox";
	t.input.style.margin = "1px";
	t.input.onclick = function(ev) { stopEventPropagation(ev, true); };
	if (data) t.input.checked = "checked";
	if (!this.editable) t.input.disabled = "disabled";

	t.input.onchange = function() { t._datachange(); };
	listenEvent(t.input, 'focus', function() { t.onfocus.fire(); });
	this.element.appendChild(t.input);
	
	this._getEditedData = function() {
		return t.input.checked;
	};
	this._setData = function(data) {
		if (data == null) data = false;
		if (typeof data == 'string') data = (data == "true" || data == "1" || data.trim().toLowerCase() == "yes" || data.trim().toLowerCase() == "y");
		else if (typeof data == 'number') data = (data == 1);
		t.input.checked = data ? "checked" : "";
		return data;
	};
	this.focus = function() { t.input.focus(); };
};