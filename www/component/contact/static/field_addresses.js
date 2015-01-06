/* #depends[/static/widgets/typed_field/typed_field.js] */

if (typeof require != 'undefined') require("contact_objects.js");

/**
 * Display a list of addresses
 * @param {PostalAddressesData} data coming from a AddressDataDisplay, it specifies the list of addresses together with the owner (people or organization)
 * @param {Boolean} editable created as editable or not
 * @param {Object} config no configuration supported by this field
 */
function field_addresses(data,editable,config){
	typed_field.call(this, data, editable, config);
}
field_addresses.prototype = new typed_field_multiple();
field_addresses.prototype.constructor = field_addresses;
field_addresses.prototype.canBeNull = function() { return true; };
field_addresses.prototype._create = function(data) {
	if (typeof this.config.sub_data_index == 'undefined') {
		if (this.editable)
			this._createEditableTogether(data);
		else
			this._createReadOnlyTogether(data);
	} else {
		this.element.style.height = "100%";
		if (this.editable)
			this.element.onclick = function(event) { stopEventPropagation(event); return false; };
		if (this.config.sub_data_index == 0) {
			if (this.editable)
				this._createEditableAddressType(data);
			else
				this._createReadOnlyAddressType(data);
		} else
			this._createAddressDivision(data, this.config.sub_data_index-1);
	}
};
/** create, for an editable field, where we have the full address (not a sub-field)
 * @param {Object} data the data
 */
field_addresses.prototype._createEditableTogether = function(data) {
	var t=this;
	require("addresses.js",function() {
		t.control = new addresses(t.element, false, data.type, data.type_id, valueCopy(data.addresses,10), true, true, true);
		t.control.onchange.addListener(function() { t._data.addresses = t.originalData.addresses = valueCopy(t.control.getAddresses(),10); });
	});
	this.addData = function(new_data) {
		var finalize = function(address) {
			if (t.control)
				t.control.addAddress(address, true);
			else
				setTimeout(function() { finalize(address); },10);
		};
		if (typeof new_data == 'object')
			finalize(new_data);
		else if (typeof new_data == 'string')
			require("contact_objects.js", function() { parsePostalAddress(new_data, function(addr) { if(addr) finalize(addr);}); });
		else
			finalize(new_data);
	};
	this.getNbData = function() {
		if (!t.control) return 0;
		return t.control.getAddresses().length;
	};
	this.resetData = function() {
		var nb = t.control.getAddresses().length;
		for (var i = nb-1; i >= 0; --i)
			t.control.removeAddress(t.control.getAddresses()[i]);
	};
};
/** create, for an non-editable field, where we have the full address (not a sub-field)
 * @param {Object} data the data
 */
field_addresses.prototype._createReadOnlyTogether = function(data) {
	this.table = document.createElement("TABLE"); this.element.appendChild(this.table);
	this.table.style.borderSpacing = "0px";
	this.table.style.borderCollapse = "collapse";
	this.table.appendChild(this.tr = document.createElement("TR"));
	this._setData = function(data) {
		while (this.tr.childNodes.length > 0) this.tr.removeChild(this.tr.childNodes[0]);
		if (data == null) return null;
		var t=this;
		require("address_text.js",function() {
			for (var i = 0; i < data.addresses.length; ++i) {
				var text = new address_text(data.addresses[i]);
				var td = document.createElement("TD");
				td.style.padding = "0px 1px";
				td.style.whiteSpace = "nowrap";
				td.style.color = "#808080";
				td.appendChild(document.createTextNode(data.addresses[i].address_type));
				t.tr.appendChild(td);
				if (t.tr.childNodes.length > 1) td.style.borderLeft = "1px solid #808080";
				td = document.createElement("TD");
				td.style.padding = "0px 1px";
				td.style.whiteSpace = "nowrap";
				t.tr.appendChild(td);
				td.appendChild(text.element);
				td.style.verticalAlign = "top";
				layout.changed(t.element);
			}
		});
		return data;
	};
	this._setData(data);
};
/** create an editable sub-field 'address type'
 * @param {Object} data the data
 */
field_addresses.prototype._createEditableAddressType = function(data) {
	window.top.sub_field_registry.register(window, this);
	this.onchange.addListener(function(f){
		window.top.sub_field_registry.changed(window, f);
	});
	this._setData = function(data) {
		this.element.removeAllChildren();
		if (data == null) return null;
		for (var i = 0; i < data.addresses.length; ++i) {
			var addr = data.addresses[i];
			var div = document.createElement("DIV");
			var input = document.createElement("INPUT")
			input.type = 'text';
			input.maxLength = 100;
			input.size = 10;
			input.style.color = "#606060";
			input.value = addr.address_type;
			div.appendChild(input);
			this.element.appendChild(div);
			input._addr = addr;
			var t=this;
			input.onchange = function() {
				this._addr.address_type = this.value;
				t._datachange(true);
			};
			input.onclick = function() {
				var input = this;
				require("contact_objects.js", function() {
					showAddressTypeMenu(input,t._data.type,input.value,false,function(new_type) { input.value = new_type; input.onchange(); });
				});
			};
		}
		return data;
	};
	this._setData(data);
	
	this.getNbData = function() {
		return this._data.addresses.length;
	};
	this.resetData = function() {
		this._data.addresses = [];
		t.setData(this._data, true);
	};
	this.addData = function(new_data) {
		var t=this;
		require("contact_objects.js", function() {
			var address = new PostalAddress(-1, window.top.default_country_id, null, "", "", "", "", "", new_data);
			t._data.addresses.push(address);
			t.setData(t._data, true);
		});
	};
	this.getDataIndex = function(index) {
		return this._data.addresses[index].address_type;
	};
	this.setDataIndex = function(index, new_data) {
		this._data.addresses[index].address_type = new_data;
		this.setData(this._data, true);
	};
};
/** create a read-only sub-field 'address type'
 * @param {Object} data the data
 */
field_addresses.prototype._createReadOnlyAddressType = function(data) {
	window.top.sub_field_registry.register(window, this);
	this.onchange.addListener(function(f){
		window.top.sub_field_registry.changed(window, f);
	});
	this._setData = function(data) {
		this.element.removeAllChildren();
		if (data)
		for (var i = 0; i < data.addresses.length; ++i) {
			var addr = data.addresses[i];
			var div = document.createElement("DIV");
			div.style.color = "#606060";
			div.style.whiteSpace = 'nowrap';
			div.appendChild(document.createTextNode(addr.address_type));
			this.element.appendChild(div);
		}
		return data;
	};
	this._setData(data);
};
/** create sub-field for a country division
 * @param {Object} data the data
 * @param {Number} sub_division_index the country division index
 */
field_addresses.prototype._createAddressDivision = function(data, sub_division_index) {
	var t=this;
	t.country_data = null;
	window.top.require("geography.js", function() {
		window.top.geography.getCountryData(window.top.default_country_id, function(country_data) {
			t.country_data = country_data;
		});
	});
	window.top.sub_field_registry.register(window, this);
	this.onchange.addListener(function(f){
		window.top.sub_field_registry.changed(window, f);
	});
	this._setData = function(data) {
		this.element.removeAllChildren();
		if (data == null) return null;
		for (var i = 0; i < data.addresses.length; ++i) {
			var addr = data.addresses[i];
			var div = document.createElement("DIV"); this.element.appendChild(div);
			div.style.whiteSpace = "nowrap";
			if (this.editable) {
				// by default, take enough space compared to the select
				div.style.paddingTop = "2px";
				div.style.paddingBottom = "2px";
			}
			var area = addr.geographic_area;
			layout.changed(this.element);
			if (area == null) {
				div.style.fontStyle = "italic";
				div.style.color = "#808080";
				div.innerHTML = "Not set";
			} else {
				if (area.country_id != window.top.default_country_id) {
					div.style.fontStyle = "italic";
					div.style.color = "#808080";
					div.innerHTML = "Not in "+window.top.default_country_name;
				} else {
					div.innerHTML = "<img src='"+theme.icons_10.loading+"'/>";
					var closure = {
						div:div,
						area:area,
						get:function() {
							var tc=this;
							window.top.require("geography.js", function() {
								window.top.geography.getCountryData(window.top.default_country_id, function(country_data) {
									if (!t.editable && tc.area.division_id == null) {
										tc.div.style.fontStyle = "italic";
										tc.div.style.color = "#808080";
										tc.div.innerHTML = "Not set";
										return;
									}
									var div_index, this_level_ar, this_level_parent;
									if (tc.area.division_id) {
										// get the division level of the area set
										div_index = 0;
										while (div_index < t.country_data.length && t.country_data[div_index].division_id != tc.area.division_id) div_index++;
										if (div_index >= t.country_data.length) {
											tc.div.style.fontStyle = "italic";
											tc.div.style.color = "red";
											tc.div.innerHTML = "Invalid division";
											return;
										}
										if (div_index < sub_division_index-1) {
											// do not allow to select
											tc.div.style.fontStyle = "italic";
											tc.div.style.color = "#808080";
											tc.div.innerHTML = "Select "+t.country_data[sub_division_index-1].division_name;
											return;
										}
										// get the selected area
										var ar = window.top.geography.searchArea(t.country_data, tc.area.id);
										if (ar == null) {
											tc.div.style.fontStyle = "italic";
											tc.div.style.color = "red";
											tc.div.innerHTML = "Invalid area";
											return;
										}
										if (div_index >= sub_division_index) {
											// get the one of this level
											this_level_ar = ar;
											var i = div_index;
											while (i > sub_division_index) {
												this_level_ar = window.top.geography.getParentArea(t.country_data, this_level_ar);
												if (this_level_ar == null) {
													tc.div.style.fontStyle = "italic";
													tc.div.style.color = "red";
													tc.div.innerHTML = "Invalid area";
													return;
												}
												i--;
											}
											if (sub_division_index > 0)
												this_level_parent = window.top.geography.getParentArea(t.country_data, this_level_ar);
											else
												this_level_parent = null;
										} else {
											this_level_ar = null;
											this_level_parent = ar;
										}
									} else if (sub_division_index > 0) {
										// do not allow to select
										tc.div.style.fontStyle = "italic";
										tc.div.style.color = "#808080";
										tc.div.innerHTML = "Select "+t.country_data[sub_division_index-1].division_name;
										return;
									} else {
										div_index = -1;
										ar = null;
										this_level_ar = null;
										this_level_parent = null;
									}
									
									var select = null;
									var selected = 0;
									if (t.editable) {
										select = document.createElement("SELECT");
										var o;
										o = document.createElement("OPTION");
										o.text = "";
										o.value = -1;
										select.add(o);
										for (var i = 0; i < t.country_data[sub_division_index].areas.length; ++i) {
											var a = t.country_data[sub_division_index].areas[i];
											if (sub_division_index > 0 && t.country_data[sub_division_index].areas[i].area_parent_id != this_level_parent.area_id) continue;
											o = document.createElement("OPTION");
											o.text = a.area_name;
											o.value = a.area_id;
											if (this_level_ar == a) selected = select.options.length;
											select.add(o);
										}
										select.selectedIndex = selected;
										select.onchange = function() {
											if (this.selectedIndex > 0) {
												tc.area.id = this.options[this.selectedIndex].value;
												tc.area.division_id = t.country_data[sub_division_index].division_id;
											} else {
												if (sub_division_index == 0) {
													// first level, everything is reset
													tc.area.id = null;
													tc.area.division_id = null;
												} else {
													// reset this level: parent level is selected
													tc.area.id = this_level_parent.area_id;
													tc.area.division_id = t.country_data[sub_division_index-1].division_id;
												}
											}
											t._datachange(true);
										};
									}
									if (t.editable) {
										tc.div.removeAllChildren();
										tc.div.appendChild(select);
										// remove the padding
										tc.div.style.paddingTop = "0px";
										tc.div.style.paddingBottom = "0px";
									} else {
										if (sub_division_index > div_index) {
											tc.div.style.fontStyle = "italic";
											tc.div.style.color = "#808080";
											tc.div.innerHTML = "Not set";
										} else {
											tc.div.removeAllChildren();
											tc.div.appendChild(document.createTextNode(this_level_ar.area_name));
										}
									}
								});
							});
						}
					};
					closure.get();
				}
			}
		}
		if (t.editable) {
			var add_button = document.createElement("BUTTON");
			add_button.className = "flat small_icon";
			add_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
			add_button.title = "Add new address";
			this.element.appendChild(add_button);
			add_button.onclick = function(event) {
				require("contact_objects.js", function() {
					var address = new PostalAddress(-1, window.top.default_country_id, null, null, null, null, null, null, "");
					t._data.addresses.push(address);
					t.setData(t._data, true);
				});
				stopEventPropagation(event);
				return false;
			};
		}
		return data;
	};
	if (t.editable) {
		this.getNbData = function() {
			return t._data.addresses.length;
		};
		this.resetData = function() {
			t._data.addresses = [];
			t.setData(t._data, true);
		};
		this.addData = function(new_data) {
			var division_index = sub_division_index;
			require("contact_objects.js", function() {
				var address = new PostalAddress(-1, window.top.default_country_id, null, "", "", "", "", "", "");
				if (typeof new_data == 'string') {
					var area = window.top.geography.searchAreaByNameInDivision(t.country_data, division_index, new_data);
					if (!area)
						area = window.top.geography.searchAreaByNameInDivisionBestMatch(t.country_data, division_index, new_data);
					if (area) {
						// found
						address.geographic_area.country_id = window.top.default_country_id;
						address.geographic_area.id = area.area_id;
						address.geographic_area.division_id = window.top.geography.getAreaDivisionId(t.country_data, area);
						t._data.addresses.push(address);
						t.setData(t._data, true);
					} else {
						// not found
						// TODO ?
					}
				} else {
					// TODO ?
				}
			});
		};
		this.getDataIndex = function(index) {
			var addr = t._data.addresses[index];
			var area_id = addr.geographic_area.id;
			if (area_id == null || area_id <= 0) return null;
			var area = window.top.geography.searchArea(t.country_data, area_id);
			var division_index = window.top.geography.getAreaDivisionIndex(t.country_data, area);
			if (division_index < sub_division_index) return null;
			while (division_index > sub_division_index) {
				area = window.top.geography.getParentArea(t.country_data, area);
				division_index--;
			}
			return area;
		};
		this.setDataIndex = function(index, new_data) {
			var division_index = sub_division_index;
			var address = t._data.addresses[index];
			if (typeof new_data == 'string') { // by name
				if (division_index > 0 && address.geographic_area.id && address.geographic_area.id > 0) {
					// restrict search in parent
					var parent = window.top.geography.searchArea(t.country_data, address.geographic_area.id);
					var div = window.top.geography.getAreaDivisionIndex(t.country_data, parent);
					while (div > division_index-1) {
						parent = window.top.geography.getParentArea(t.country_data, parent);
						div--;
					}
					var a = window.top.geography.searchAreaByNameInParent(t.country_data, parent.area_id, division_index, new_data);
					if (!a)
						a = window.top.geography.searchAreaByNameInParentBestMatch(t.country_data, parent.area_id, division_index, new_data);
					new_data = a;
				} else {
					var a = window.top.geography.searchAreaByNameInDivision(t.country_data, division_index, new_data);
					if (!a)
						a = window.top.geography.searchAreaByNameInDivisionBestMatch(t.country_data, division_index, new_data);
					new_data = a;
				}
			} else if (typeof new_data == 'number') // by id
				new_data = window.top.geography.searchArea(t.country_data, new_data);
			else if (new_data && typeof new_data.area_id == 'undefined') // invalid data
				return; // error ?
			if (new_data) {
				address.geographic_area.country_id = window.top.default_country_id;
				address.geographic_area.id = new_data.area_id;
				address.geographic_area.division_id = window.top.geography.getAreaDivisionId(t.country_data, new_data);
				t.setData(t._data, true);
			} else {
				// TODO ?
			}
		};
	}
	this._setData(data);
};
field_addresses.prototype.helpFillMultipleItemsForAllSubData = function() {
	var helper = {
		title: 'Prefilled an address for all',
		content: document.createElement("DIV"),
		apply: function(fields) {
			var t=this;
			require("contact_objects.js", function() {
				var area = null;
				if (t.geo.getSelectedArea() > 0)
					area = window.top.geography.getGeographicAreaTextFromId(t.geo.country_data,t.geo.getSelectedArea());
				var address = new PostalAddress(-1, window.top.default_country_id, area, "", "", "", "", "", t.input_type.value);
				fields[0]._data.addresses.push(address);
				fields[0].setData(fields[0]._data, true);
			});
		}
	};
	helper.content.appendChild(document.createTextNode("Address Type:"));
	helper.input_type = document.createElement("INPUT");
	helper.input_type.type = "text";
	helper.input_type.maxLength = 100;
	helper.input_type.size = 15;
	helper.input_type.style.marginLeft = "5px";
	var t=this;
	helper.input_type.onclick = function() {
		require("contact_objects.js", function() {
			showAddressTypeMenu(helper.input_type,t._data.type,helper.input_type.value,false,function(new_type) { helper.input_type.value = new_type; });
		});
	};
	helper.content.appendChild(helper.input_type);
	require("geographic_area_selection.js", function() {
		helper.geo = new geographic_area_selection(helper.content, window.top.default_country_id, null, 'vertical', true, function() {
			layout.changed(helper.content);
		});
	});
	return helper;
};