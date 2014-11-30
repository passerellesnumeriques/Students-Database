// #depends[/static/widgets/typed_field/typed_field.js]
/** Selection of an organization
 * @param {Number} data the data is the organization ID, but it accepts a string (the name of the organization) which will be converted into its associated id
 * @param {Boolean} editable
 * @param {Object} config can_be_null, possible_values: array of array[id,name]
 */
function field_organization(data,editable,config) {
	window.top.pnapplication.registerCustom(window, 'field_organization', this);
	typed_field.call(this, data, editable, config);
}
field_organization.prototype = new typed_field();
field_organization.prototype.constructor = field_organization;		
field_organization.prototype.canBeNull = function() { return this.config.can_be_null; };
field_organization.prototype.getPossibleValues = function() {
	var values = [];
	for (var i = 0; i < this.config.possible_values.length; ++i)
		values.push(this.config.possible_values[i][1]);
	return values;
};
field_organization.prototype.createValue = function(value, name, oncreated) {
	var url_params = "";
	if (this.config.creators && this.config.creators.length > 0) {
		url_params += "&creator="+this.config.creators[0];
	}
	if (this.config.types && this.config.types.length > 0) {
		url_params += "&types_names=";
		var s = "";
		for (var i = 0; i < this.config.types.length; ++i)
			s += (i > 0 ? ";" : "")+this.config.types[i];
		url_params += encodeURIComponent(s);
	}
	if (value)
		url_params += "&name="+encodeURIComponent(value);
	var t=this;
	window.top.popup_frame(
		theme.build_icon("/static/contact/organization.png",theme.icons_10.add),
		"New "+(name ? name : "Organization"), 
		"/dynamic/contact/page/organization_profile?organization=-1"+url_params,
		null, null, null,
		function(frame,popup) {
			waitFrameContentReady(frame, function(win) {
				return win.organization;
			}, function(win) {
				popup.addOkCancelButtons(function(){
					popup.freeze();
					var org = win.organization.getStructure();
					service.json("contact", "add_organization", org, function(res) {
						if (!res) { popup.unfreeze(); return; }
						t._addPossibleValue(res.id, org.name);
						var fields = window.top.pnapplication.getCustoms('field_organization');
						for (var i = 0; i < fields.length; ++i) {
							if (arrayEquivalent(t.config.creators,fields[i].config.creators) &&
								arrayEquivalent(t.config.types,fields[i].config.types)
								) {
								fields[i]._addPossibleValue(res.id, org.name);
							}
						}
						popup.close();
						if (oncreated) oncreated(org.name);
					});
				});
			});
		}
	);
};
field_organization.prototype._addPossibleValue = function(org_id, org_name) {
	this.config.possible_values.push([org_id,org_name]);
	if (this.editable) {
		var o = document.createElement("OPTION");
		o.value = org_id;
		o.text = org_name;
		this.select.add(o);
	}
};
field_organization.prototype._getOrgIDFromData = function(data) {
	if (typeof data == "number") return data;
	if (data == null) return null;
	if (data == "") return null;
	if (typeof data == "string") {
		var id = data.parseNumber();
		if (!isNaN(id)) return id;
		data = data.trim().latinize().toLowerCase();
		for (var i = 0; i < this.config.possible_values.length; ++i)
			if (this.config.possible_values[i][1].trim().latinize().toLowerCase() == data)
				return this.config.possible_values[i][0];
	}
	return null;
};
field_organization.prototype._getOrgName = function(id) {
	if (id === null) return null;
	for (var i = 0; i < this.config.possible_values.length; ++i)
		if (this.config.possible_values[i][0] == id)
			return this.config.possible_values[i][1];
	return null;
};
field_organization.prototype._create = function(data) {
	if (this.editable) {
		this.select = document.createElement("SELECT");
		this.element.appendChild(this.select);
		var o;
		o = document.createElement("OPTION");
		o.value = -1; o.text = '';
		this.select.add(o);
		for (var i = 0; i < this.config.possible_values.length; ++i) {
			o = document.createElement("OPTION");
			o.value = this.config.possible_values[i][0];
			o.text = this.config.possible_values[i][1];
			this.select.add(o);
		}
		this.select.style.margin = "0px";
		this.select.style.padding = "0px";
		var t=this;
		listenEvent(this.select, 'focus', function() { t.onfocus.fire(); });
		this.select.onchange = function() {
			t._datachange();
		};
		this._getEditedData = function() {
			return this.select.value;
		};
		this._setData = function(data) {
			var id = this._getOrgIDFromData(data);
			if (id == null)
				this.select.selectedIndex = 0;
			else {
				var found = false;
				for (var i = 0; i < this.config.possible_values.length; ++i)
					if (this.config.possible_values[i][0] == id) {
						this.select.selectedIndex = i+1;
						found = true;
						break;
					}
				if (!found) {
					this.select.selectedIndex = 0;
					return null;
				}
			}
			return data;
		};
		this.validate = function() {
			if (this.selectedIndex == 0 && !this.config.can_be_null)
				this.signal_error("Please select an organization");
			else
				this.signal_error(null);
		};
		this.signal_error = function(error) {
			this.error = error;
			this.select.style.border = error ? "1px solid red" : "";
			this.select.title = error ? error : "";
		};
		this.fillWidth = function() {
			// calculate the minimum width of the select, to be able to see it...
			var included_in_body = false;
			if (this.element.parentNode == null) {
				included_in_body = true;
				document.body.appendChild(this.element);
			}
			this.select.style.width = "";
			this.select.style.minWidth = this.select.offsetWidth+"px";
			if (included_in_body) document.body.removeChild(this.element);
			
			this.element.style.width = "100%";
			this.select.style.width = "100%";
		};
	} else {
		this._setData = function(data) {
			var id = this._getOrgIDFromData(data);
			this._data = id;
			var name = this._getOrgName(id);
			if (name == null) name = "";
			this.element.removeAllChildren();
			this.element.appendChild(document.createTextNode(name));
			return data;
		};
	}
	this._setData(data);
};