function field_blank(html, value) {
	typed_field.call(this, null, false, null, null);
	if (typeof html == 'string') {
		this.element = document.createElement("DIV");
		this.element.innerHTML = html;
	} else
		this.element = html;
	this.element.typed_field = this;
	this.setData = function(data) {
	};
	this.getCurrentData = function() {
		return value;
	};
	this.signal_error = function(error) {
	};
}
if (typeof require != 'undefined')
	require("typed_field.js",function(){
		field_blank.prototype = new typed_field();
		field_blank.prototype.constructor = field_blank;		
	});
