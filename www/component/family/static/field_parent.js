/* #depends[/static/widgets/typed_field/typed_field.js] */

function field_parent(data,editable,config){
	typed_field.call(this, data, editable, config);
}
field_parent.prototype = new typed_field();
field_parent.prototype.constructor = field_parent;	
field_parent.prototype.canBeNull = function() { return true; };
field_parent.prototype._create = function(data) {
	if (!data) return; // must be set
	if (typeof this.config.sub_data_index == 'undefined') {
		this._setData = function(data) {
			var text = "";
			if (data) {
				if (data.first_name)
					text = data.last_name + " " + data.first_name;
				if (data.birthdate) {
					var birth = parseSQLDate(data.birthdate);
					var now = new Date();
					var age = now.getFullYear()-birth.getFullYear();
					if (now.getMonth() < birth.getMonth() || (now.getMonth() == birth.getMonth() && now.getDate() < birth.getDate()))
						age--;
					if (text.length > 0) text += ", ";
					text += age;
				}
				if (data.occupation) {
					if (text.length > 0) text += ", ";
					text += data.occupation;
				}
				if (data.education_level) {
					if (text.length > 0) text += ", ";
					text += data.education_level;
				}
			}
			this.element.removeAllChildren();
			var span = document.createElement("SPAN");
			span.style.whiteSpace = "nowrap";
			span.appendChild(document.createTextNode(text));
			this.element.appendChild(span);
			if (data && data.comment) {
				var com = document.createElement("SPAN");
				com.style.fontStyle = "italic";
				com.style.color = "#606060";
				com.style.whiteSpace = "nowrap";
				com.style.marginLeft = "2px";
				com.appendChild(document.createTextNode("("+data.comment+")"));
				this.element.appendChild(com);
			}
		};
	} else {
		if (!this.editable) {
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
				case 5: value = data.comment; break;
				}
				this.element.style.whiteSpace = "nowrap";
				this.element.appendChild(document.createTextNode(value ? value : ""));
				layout.changed(this.element);
			};
		} else {
			var t=this;
			var input = document.createElement("INPUT");
			this.input = input;
			input.type = "text";
			input.onclick = function(ev) { this.focus(); stopEventPropagation(ev); return false; };
			input.style.margin = "0px";
			input.style.padding = "0px";
			var updater = null;
			switch (this.config.sub_data_index) {
			case 0: input.maxLength = 100; updater = function(value) { data.last_name = value; }; break;
			case 1: input.maxLength = 100; updater = function(value) { data.first_name = value; }; break;
			case 3: input.maxLength = 100; updater = function(value) { data.occupation = value; }; break;
			case 4: input.maxLength = 100; updater = function(value) { data.education_level = value; }; break;
			case 5: input.maxLength = 250; updater = function(value) { data.comment = value; }; break;
			}
			this._getInputValue = function() {
				var data = input.value;
				if (data.length == 0) data = null;
				return data;
			};
			input.onkeyup = function() { setTimeout(function() { updater(t._getInputValue()); t._datachange(true); },1); };
			input.onblur = function() { updater(t._getInputValue()); t._datachange(true); };
			input.onchange = function() { updater(t._getInputValue()); t._datachange(true); };
			listenEvent(input, 'focus', function() { t.onfocus.fire(); });
			this.element.appendChild(input);
			var _fw = false;
			require("input_utils.js",function(){inputAutoresize(input,_fw ? -1 : 10);});
			
			this._getEditedData = function() {
				return data;
			};
			this._setData = function(d,from_input) {
				if (from_input) { input.value = d != null ? d : ""; updater(d); }
				return data;
			};
			this._fillWidth = this.fillWidth;
			this.fillWidth = function() {
				this._fillWidth();
				if (input.autoresize) input.setMinimumSize(-1);
				else _fw = true;
			};
			this.validate = function() {
				if (this.config.sub_data_index > 1) return;
				var fields = window.top.sub_field_registry.getFields(window, data);
				var has_other = false;
				for (var i = 0; i < fields.length; ++i) {
					if (fields[i] == this) continue;
					if (fields[i].config.sub_data_index > 1) continue;
					if (fields[i].config.sub_data_index == this.config.sub_data_index) continue;
					if (fields[i].input.value != "")
						has_other = true;
				}
				if (input.value == "" && has_other)
					this.signal_error("Cannot be empty");
				else
					this.signal_error(null);
			};
			this.signal_error = function(error) {
				this.error = error;
				input.style.border = error ? "1px solid red" : "";
				input.title = error ? error : "";
			};
			window.top.sub_field_registry.register(window, this);
			this.onchange.add_listener(function(f){
				window.top.sub_field_registry.changed(window, f);
			});
		}
	}
	this._setData(data);
};