/** UI control for a DataDisplay, where the user can switch between editable and non-editable, and save a new value
 * @param {Element} container where to put it
 * @param {DataDisplay} data_display the data
 * @param {String} come_from to column from which we come to access to the table containing the data (to use the right DataDisplayHandler)
 * @param {Number} key the key identifying the row in the table
 * @param {Number} sub_model sub model or null
 * @param {Object} data the initial value
 * @param {Function} onchange (optional) called when the user is editing the value
 * @constructor
 */
function editable_datadisplay(container, data_display, come_from, key, sub_model, data, onchange) {
	
	var t=this;
	if (key == -1) {
		// new data
		// TODO
	} else {
		// existing data
		require("editable_field.js",function() {
			t.editable_field = new editable_field(container, data_display.field_classname, data_display.field_config, data, function(data, handler) {
				service.json("data_model", "lock_datadisplay", {table:data_display.table,name:data_display.name,come_from:come_from,key:key,sub_model:sub_model}, function(result) {
					if (!result) handler(null);
					else handler(result.locks, result.data);
				});
			}, function(data, handler) {
				service.json("data_model", "save_datadisplay", {table:data_display.table,name:data_display.name,come_from:come_from,key:key,sub_model:sub_model,data:data}, function(result) {
					handler(data);
				});
			});
			if (onchange) t.editable_field.field.onchange.addListener(onchange);
			t.editable_field.field.registerDataModelDataDisplay(data_display, key);
		});
	}
	
}