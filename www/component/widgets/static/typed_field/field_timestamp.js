/* #depends[typed_field.js] */
if (typeof require != 'undefined') require("autoresize_input.js");

function field_timestamp(data,editable,config) {
	typed_field.call(this, data, editable, config);
}
field_timestamp.prototype = new typed_field();
field_timestamp.prototype.constructor = field_timestamp;		
field_timestamp.prototype.canBeNull = function() {
	if (!this.config) return true;
	if (this.config.can_be_null) return true;
	return false;
};		
field_timestamp.prototype._create = function(data) {
	if (this.editable) {
		// TODO
	} else {
		if (this.config && this.config.style)
			for (var s in this.config.style)
				this.element.style[s] = this.config.style[s];
		this.setData = function(data, first) {
			if (data != null) data = parseInt(data);
			if (data == 0) data = null;
			this.data = data;
			while (this.element.childNodes.length > 0) this.element.removeNode(this.element.childNodes[0]);
			var text;
			if (data == null) text = "";
			else {
				if (this.config && this.config.data_is_seconds)
					data *= 1000;
				var d = new Date(data);
				text = d.toDateString();
				if (this.config && this.config.show_time)
					text += " "+d.toLocaleTimeString();
			}
			this.element.appendChild(document.createTextNode(text));
			if (!first) this._datachange();
		};
		this.setData(data, true);
		this.getCurrentData = function() {
			return this.data;
		};
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};