if (typeof require != 'undefined') {
	require("typed_field.js");
	require("editable_field.js");
}
	
/**
 * @param container
 * @param table the table to which the data belongs to
 * @param column the column to which the data belongs to
 * @param row_key
 * @param field_classname the typed filed of the data
 * @param field_arguments (optional) in case this typed_filed needs arguments
 * @param data the data that initiates the editable_cell
 */
function editable_cell(container, table, column, row_key, field_classname, field_arguments, data, onsave, onchange) {
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
				new_data = onsave(new_data);
			service.json("data_model", "save_cell", {lock:t.lock,table:table,row_key:row_key,column:column,value:new_data},function(result) {
				handler(new_data);
			});
		});
		if (onchange) t.editable_field.field.onchange.add_listener(onchange);
		t.editable_field.field.register_datamodel_cell(table,column,row_key);
	});
}
