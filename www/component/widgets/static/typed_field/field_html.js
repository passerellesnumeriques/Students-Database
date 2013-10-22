function field_html(data,editable,onchanged,onunchanged,config) {
	typed_field.call(this, data, editable, onchanged, onunchanged);
	this.element = document.createElement("DIV");
	this.element.innerHTML = data; 
	this.element.typed_field = this;
	this.getCurrentData = function() { return this.element.innerHTML; };
	this.setData = function(data) { this.element.innerHTML = data; };
}
if (typeof typed_field != 'undefined') {
	field_html.prototype = new typed_field();
	field_html.prototype.constructor = field_html;		
}
