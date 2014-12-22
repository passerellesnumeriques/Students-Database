/* #depends[typed_field.js] */
/**
 * Typed field to display an age. The data is the birth date, which can be in SQL date format, or a timestamp.
 */
function field_age(data,editable,config) {
	typed_field.call(this, data, false, config);
}
field_age.prototype = new typed_field();
field_age.prototype.constructor = field_age;		
field_age.prototype.canBeNull = function() { return true; };
field_age.prototype.compare = function(v1,v2) {
	if (v1 == null) return v2 == null ? 0 : -1;
	if (v2 == null) return 1;
	v1 = parseInt(v1);
	if (isNaN(v1)) return 1;
	v2 = parseInt(v2);
	if (isNaN(v2)) return -1;
	if (v1 < v2) return -1;
	if (v1 > v2) return 1;
	return 0;
};
field_age.prototype.exportCell = function(cell) {
	var val = this.getCurrentData();
	if (val == null)
		cell.value = "";
	else {
		cell.value = val;
		cell.format = "number:0";
	}
};
field_age.prototype._setData = function(data) {
	this.element.removeAllChildren();
	if (typeof data == 'string') {
		var birth = parseSQLDate(data);
		if (birth != null) {
			// we get a birth date in input
			var now = new Date();
			data = now.getFullYear()-birth.getFullYear();
			if (now.getMonth() < birth.getMonth() || (now.getMonth() == birth.getMonth() && now.getDate() < birth.getDate()))
				data--;
		} else {
			// try to convert string to integer
			data = parseInt(data);
			if (isNaN(data)) data = null;
		}
	}
	if (data === null) return null;
	this.element.appendChild(document.createTextNode(""+data));
	return data;
};
field_age.prototype._create = function(data) {
	this._setData(data);
};
field_age.prototype.setDataDisplay = function(data_display, data_key) {
	if (this._dm_listener) return;
	var t=this;
	this._dm_listener = function(value) {
		t.setData(value);
	};
	window.top.datamodel.addCellChangeListener(window, this.config.table, this.config.column, data_key, this._dm_listener);
	this.element.ondomremoved(function() {
		window.top.datamodel.removeCellChangeListener(t._dm_listener);
		t._dm_listener = null;
	});
};