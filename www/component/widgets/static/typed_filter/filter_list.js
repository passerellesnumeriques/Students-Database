/* #depends[typed_filter.js] */
function filter_list(data, config, editable) {
	if (!data) data = {type:">0",element_data:null};
	typed_filter.call(this, data, config, editable);
	this.element.style.whiteSpace = "nowrap";
	var select = document.createElement("SELECT");
	this.element.appendChild(select);
	select.style.verticalAlign = "bottom";
	var o;
	o = document.createElement("OPTION");
	o.value = ">0";
	o.text = "At least one comply with:";
	select.add(o);
	o = document.createElement("OPTION");
	o.value = "none";
	o.text = "None comply with";
	select.add(o);
	o = document.createElement("OPTION");
	o.value = "null";
	o.text = "Has no value";
	select.add(o);
	o = document.createElement("OPTION");
	o.value = "not_null";
	o.text = "Has one or more value";
	select.add(o);
	if (data.type) select.value = data.type;
	if (!editable) select.disabled = "disabled";
	var t=this;
	var update = function() {
		if (!t.sub_filter) {
			setTimeout(update, 25);
			return;
		}
		t.data.type = select.value;
		if (t.data.type != 'null' && t.data.type != 'not_null') {
			t.data.element_data = t.sub_filter.data;
			t.sub_filter.element.style.display = "inline-block";
		} else {
			t.sub_filter.element.style.display = "none";
			t.data.element_data = null;
		}
		layout.changed(t.element);
	};
	select.onchange = function() {
		update();
		t.onchange.fire(t);
	};
	require(config.element_type+".js", function() {
		t.sub_filter = new window[config.element_type](t.data.element_data, config.element_cfg, editable);
		t.sub_filter.element.style.display = "inline-block";
		t.element.appendChild(t.sub_filter.element);
		t.sub_filter.onchange.addListener(function() { t.onchange.fire(t); });
		update();
	});
	this.isActive = function() {
		if (select.value == "null") return true;
		if (select.value == "not_null") return true;
		if (!t.sub_filter) return false;
		return t.sub_filter.isActive();
	};
}
filter_list.prototype = new typed_filter;
filter_list.prototype.constructor = filter_list;
filter_list.prototype.can_multiple = true;
