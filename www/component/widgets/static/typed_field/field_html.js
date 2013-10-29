function field_html(data,editable,config) {
	typed_field.call(this, data, editable, config);
}
field_html.prototype = new typed_field();
field_html.prototype.constructor = field_html;		
field_html.prototype._create = function(data) {
	this.element.innerHTML = data; 
	this.getCurrentData = function() { return this.element.innerHTML; };
	this.setData = function(data) { this.element.innerHTML = data; };
};
