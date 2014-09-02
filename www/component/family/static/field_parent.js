/* #depends[/static/widgets/typed_field/typed_field.js] */

function field_parent(data,editable,config){
	typed_field.call(this, data, editable, config);
}
field_parent.prototype = new typed_field();
field_parent.prototype.constructor = field_parent;	
field_parent.prototype.canBeNull = function() { return true; };
field_parent.prototype._create = function(data) {
	if (typeof this.config.sub_data_index == 'undefined') {
		// TODO
	} else {
		this._setData = function(data) {
			this.element.removeAllChildren();
			var value = null;
			if (data != null)
			switch (this.config.sub_data_index) {
			case 0: value = data.last_name; break;
			case 1: value = data.first_name; break;
			case 2:
				this.element.style.textAlign = "center";
				if (!data.birthdate) value = null;
				else {
					var birth = parseSQLDate(data.birthdate);
					var now = new Date();
					var age = now.getFullYear()-birth.getFullYear();
					if (now.getMonth() < birth.getMonth() || (now.getMonth() == birth.getMonth() && now.getDate() < birth.getDate()))
						age--;
					value = age;
				}
				break;
			case 3: value = data.occupation; break;
			case 4: value = data.education_level; break;
			}
			this.element.style.whiteSpace = "nowrap";
			this.element.appendChild(document.createTextNode(value ? value : ""));
			layout.invalidate(this.element);
		};
	}
	this._setData(data);
};
