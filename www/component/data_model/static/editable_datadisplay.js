function editable_datadisplay(container, data_display, come_from, key, data, onchange) {
	
	if (key == -1) {
		// new data
		// TODO
	} else {
		// existing data
		var t=this;
		require("editable_field.js",function() {
			t.editable_field = new editable_field(container, data_display.field_classname, data_display.field_config, data, function(data, handler) {
				service.json("data_model", "lock_datadisplay", {table:data_display.table,name:data_display.name,come_from:come_from,key:key}, function(result) {
					if (!result) handler(null);
					else handler(result.locks, result.data);
				});
			}, function(data, handler) {
				service.json("data_model", "save_datadisplay", {table:data_display.table,name:data_display.name,come_from:come_from,key:key,data:data}, function(result) {
					handler(data);
				});
			});
			if (onchange) t.editable_field.field.onchange.add_listener(onchange);
			t.editable_field.field.register_datamodel_datadisplay(data_display, key);
		});
	}
	
}