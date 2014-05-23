/* #depends[typed_field.js] */
/** Time field: if editable, it will be a text input, else only a simple text node
 * @constructor
 * @param config can contain: <code>can_be_null</code>
 */
function field_time(data,editable,config) {
	if (data == null) data = "";
	if (typeof data == 'number') data = getMinutesTimeString(data);
	else data = getMinutesTimeString(parseTimeStringToMinutes(data));
	typed_field.call(this, data, editable, config);
}
field_time.prototype = new typed_field();
field_time.prototype.constructor = field_time;		
field_time.prototype.canBeNull = function() { return this.config && this.config.can_be_null; };		
field_time.prototype._create = function(data) {
	if (this.editable) {
		var t=this;
		var input = document.createElement("INPUT");
		input.type = "text";
		input.maxlength = 5;
		if (data) input.value = data;
		input.style.margin = "0px";
		input.style.padding = "0px";
		require("input_utils.js",function(){inputAutoresize(input);});
		var getTimeFromInput = function() {
			if (input.value.length == 0) {
				if (t.config && t.config.can_be_nyll) return null;
				return 0;
			}
			return parseTimeStringToMinutes(input.value);
		};
		input.onblur = function(ev) {
			input.value = getMinutesTimeString(getTimeFromInput());
			t._datachange();
		};
		this.element.appendChild(input);
		this._getEditedData = function() {
			return getMinutesTimeString(getTimeFromInput());
		};
		this.getCurrentMinutes = function() {
			return getTimeFromInput();
		};
		this._setData = function(data) {
			if (data == null) data = "";
			else if (typeof data == 'number') data = getMinutesTimeString(data);
			else data = getMinutesTimeString(parseTimeStringToMinutes(data));
			input.value = data;
		};
		this.signal_error = function(error) {
			this.error = error;
			input.style.border = error ? "1px solid red" : "";
		};
	} else {
		this.element.appendChild(this.text = document.createTextNode(data));
		this._setData = function(data) {
			var text;
			if (data == null) text = "";
			else if (typeof data == 'number') text = getMinutesTimeString(data);
			else text = getMinutesTimeString(parseTimeStringToMinutes(data));
			this.text.nodeValue = text;
		};
		this.getCurrentMinutes = function() {
			var s = this.text.nodeValue;
			if (s.length == 0) return this.config && this.config.can_be_null ? null : 0;
			return parseTimeStringToMinutes(s);
		};
		this.signal_error = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};