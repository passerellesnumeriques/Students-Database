/* #depends[/static/widgets/typed_field/typed_field.js] */

function field_geographic_area(data,editable,config) {
	typed_field.call(this, data, editable, config);
}
field_geographic_area.prototype = new typed_field();
field_geographic_area.prototype.constructor = field_geographic_area;		
field_geographic_area.prototype._create = function(data) {
	if (this.editable) {
		this._text = document.createElement("A");
		this._text.className = "black_link";
		this._text.href = "#";
		this._text.onclick = function(ev) {
			// TODO
			stopEventPropagation(ev);
			return false;
		};
	} else {
		this._text = document.createElement("SPAN");
	}
	this._setData = function(data) {
		this._text.removeAllChildren();
		if (data == null) {
			this._text.appendChild(document.createTextNode("Not specified"));
			this._text.style.fontStyle = "italic";
		} else {
			// TODO
			this._text.appendChild(document.createTextNode("TODO"));
			this._text.style.fontStyle = "normal";
		}
		return data;
	};
	this.element.appendChild(this._text);
	this._setData(data);
};
