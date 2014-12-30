/* #depends[typed_field.js] */
/**
 * A field containing a list of fields.
 * Configuration must contain <code>element_type</code> which is the class of the typed_field to use in the list.
 * The data is an array of data, each being given to a typed_field.
 */
function field_list(data,editable,config) {
	require(config.element_type+".js");
	typed_field.call(this, data, editable, config);
}
field_list.prototype = new typed_field;
field_list.prototype.constructor = field_list;
field_list.prototype.canBeNull = function() { return false; };
field_list.prototype._create = function(data) {
	this._setData(data);
}
field_list.prototype._setData = function(data) {
	this.element.removeAllChildren();
	if (!data) data = [];
	var t=this;
	require(this.config.element_type+".js",function() {
		for (var i = 0; i < data.length; ++i) {
			var div = document.createElement("DIV");
			var f = new window[t.config.element_type](data[i],false,t.config.element_cfg);
			div.appendChild(f.getHTMLElement());
			t.element.appendChild(div);
		}
	});
	return data;
};