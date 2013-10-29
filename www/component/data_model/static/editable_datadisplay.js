function editable_datadisplay(container, data_display, come_from, key, data) {
	
	if (key == -1) {
		// new data
		// TODO
	} else {
		// existing data
		require("editable_field.js",function() {
			var f = new editable_field(container, data_display.field_classname, data_display.field_config, data, function(data, handler) {
				service.json("data_model", "lock_datadisplay", {table:data_display.table,name:data_display.name,come_from:come_from,key:key}, function(result) {
					if (!result) handler(null);
					else handler(result.locks, result.data);
				});
			}, function(data, handler) {
				service.json("data_model", "save_datadisplay", {table:data_display.table,name:data_display.name,come_from:come_from,key:key,data:data}, function(result) {
					handler(data);
				});
			});
		});
	}
	
}