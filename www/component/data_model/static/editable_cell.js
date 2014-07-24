if (typeof require != 'undefined') {
	require("typed_field.js");
	require("editable_field.js");
}
	
/** UI control to display a cell, which can be edited by the user. The user can switch between editable and non-editable. When editable, a lock is first obtain.
 * @param {Element} container where to put it
 * @param {String} table the table to which the data belongs to
 * @param {String} column the column to which the data belongs to
 * @param {Number} row_key key identifying the row
 * @param {String} field_classname the typed filed of the data
 * @param {Object} field_arguments (optional) in case this typed_filed needs arguments
 * @param {Object} data the data that initiates the editable_cell
 * @param {Function} onsave (optional) called when the user save a value. As parameter, the new data is given, and the function must return the new data, eventually modified. This allows to <i>intercept</i> the new value being saved, just before it is send to the server
 * @param {Function} onchange (optional) called each time the value is changed in the UI (not on the server), meaning while the user is editing the value
 * @param {Function} onready (optional) called when this control is ready to be used
 */
function editable_cell(container, table, column, row_key, field_classname, field_arguments, data, onsave, onchange, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	container.editable_cell = this;
	var t=this;
	require("editable_field.js",function() {
		t.editable_field = new editable_field(container, field_classname, field_arguments, data, function(data, handler) {
			service.json("data_model", "lock_cell", {table:table,row_key:row_key,column:column}, function(result) {
				if (!result) handler(null);
				else handler([result.lock], result.value);
			});
		}, function(data, handler) {
			var new_data = data;
			if (onsave)
				new_data = onsave(new_data,t);
			service.json("data_model", "save_cell", {lock:t.lock,table:table,row_key:row_key,column:column,value:new_data},function(result) {
				handler(new_data);
			});
		},function(ef) {
			if (onchange) ef.field.onchange.add_listener(function(f) { onchange(f.getCurrentData(), t); });
			ef.field.register_datamodel_cell(table,column,row_key);
			if (onready) onready(t);
		});
	});
	
	t.fillContainer = function() {
		t.editable_field.fillContainer();
	};
	
	/** Cancel any change, and goes to non-editable mode */
	t.cancelEditable = function() {
		t.editable_field.cancelEditable();
	};
}
