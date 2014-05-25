/* #depends[typed_filter.js] */
function filter_enum(data, config, editable) {
	if (data == null) data = {};
	typed_filter.call(this, data, config, editable);

	if (typeof data.values == 'undefined') {
		data.values = [];
		// if not specified, by default everything is selected
		for (var i = 0; i < config.possible_values.length; ++i) {
			if (config.possible_values[i] instanceof Array)
				data.values.push(config.possible_values[i][0]);
			else
				data.values.push(config.possible_values[i]);
		}
		if (config.can_be_empty) {
			data.values.push("NULL");
			data.values.push("NOT_NULL");
		}
	}
	
	var t=this;
	require("select_checkboxes.js", function() {
		t.select = new select_checkboxes(t.element);
		if (config.can_be_empty) {
			t.select.add("NULL", "(<i>blank / not specified</i>)");
			t.select.add("NOT_NULL", "(<i>not blank / specified</i>)");
		}
		for (var i = 0; i < config.possible_values.length; ++i) {
			if (config.possible_values[i] instanceof Array)
				t.select.add(config.possible_values[i][0], document.createTextNode(config.possible_values[i][1]));
			else
				t.select.add(config.possible_values[i], document.createTextNode(config.possible_values[i]));
		}
		t.select.setSelection(data.values);
		t.select.onchange = function() {
			data.values = t.select.getSelection();
			t.onchange.fire(t);
		};
		if (!editable) t.select.disable();
	});
}
filter_enum.prototype = new typed_filter;
filter_enum.prototype.constructor = filter_enum;
