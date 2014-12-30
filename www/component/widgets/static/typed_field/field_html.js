/* #depends[typed_field.js] */
/**
 * Field containing HTML code.
 * No configuration.
 */
function field_html(data,editable,config) {
	typed_field.call(this, data, editable, config);
}
field_html.prototype = new typed_field();
field_html.prototype.constructor = field_html;		
field_html.prototype._create = function(data) {
	if (typeof data == 'string')
		this.element.innerHTML = data;
	else if (data != null) {
		this.element.appendChild(data);
		this._data = data.outerHTML;
	}
	this._setData = function(data) {
		this.element.removeAllChildren();
		if (typeof data == 'string') {
			this.element.innerHTML = data;
			return data;
		}
		if (data == null) return data;
		this.element.appendChild(data);
		return data.outerHTML;
	};
};
