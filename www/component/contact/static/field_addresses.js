/* #depends[/static/widgets/typed_field/typed_field.js] */

if (!window.top.field_addresses_registry) {
	// allow to register field_addresses having sub data, so that we can synchronize the sub datas
	window.top.field_addresses_registry = {
		_fields: [],
		register: function(win, field) {
			this._fields.push({field:field,win:win});
		},
		_in_change: false,
		changed: function(win, field) {
			if (this._in_change) return;
			this._in_change = true;
			for (var i = 0; i < this._fields.length; ++i) {
				var f = this._fields[i];
				if (f.win == win && f.field._data == field._data) {
					// same window, same data
					if (f.field.config.sub_data_index == field.config.sub_data_index) continue; // same
					f.field.setData(field._data, true);
				}
			}
			this._in_change = false;
		},
		_clean: function(win) {
			for (var i = 0; i < this._fields.length; ++i)
				if (this._fields[i].win == win) {
					this._fields.splice(i,1);
					i--;
				}
		}
	};
	window.top.pnapplication.onwindowclosed.add_listener(function(win) { window.top.field_addresses_registry._clean(win); });
}

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
field_addresses.prototype._create = function(data) {
	if (typeof this.config.sub_data_index == 'undefined') {
		if (this.editable) {
			this.table = document.createElement("TABLE"); this.element.appendChild(this.table);
			var t=this;
			require("addresses.js",function() {
				t.control = new addresses(t.table, false, data.type, data.type_id, data.addresses, true, true, true);
			});
			this.addData = function(new_data) {
				var address;
				if (typeof new_data == 'object')
					address = new_data;
				else if (typeof new_data == 'string')
					address = parsePostalAddress(new_data);
				var finalize = function() {
					if (t.control)
						t.control.addAddress(address, true);
					else
						setTimeout(finalize,10);
				};
				finalize();
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
		} else {
			this.table = document.createElement("TABLE"); this.element.appendChild(this.table);
			this.table.appendChild(this.tr = document.createElement("TR"));
			this._setData = function(data) {
				while (this.tr.childNodes.length > 0) this.tr.removeChild(this.tr.childNodes[0]);
				if (data == null) return;
				var t=this;
				require("address_text.js",function() {
					for (var i = 0; i < data.addresses.length; ++i) {
						var text = new address_text(data.addresses[i]);
						var td = document.createElement("TD");
						t.tr.appendChild(td);
						td.appendChild(text.element);
						td.style.verticalAlign = "top";
						if (t.tr.childNodes.length > 1) td.style.borderLeft = "1px solid #808080";
					}
				});
			};
			this._setData(data);
		}
	} else {
		window.top.field_addresses_registry.register(window, this);
		this.onchange.add_listener(function(f){
			window.top.field_addresses_registry.changed(window, f);
		});
		this._setData = function(data) {
			while (this.element.childNodes.length > 0) this.element.removeChild(this.element.childNodes[0]);
			if (data == null) return;
			var t=this;
			for (var i = 0; i < data.addresses.length; ++i) {
				var addr = data.addresses[i];
				var area = addr.geographic_area;
				var div = document.createElement("DIV"); this.element.appendChild(div);
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
						var closure = {
							div:div,
							area:area,
							get:function() {
								var tc=this;
								window.top.require("geography.js", function() {
									window.top.geography.getCountryData(window.top.default_country_id, function(cdata) {
										if (!t.editable && area.division_id == null) {
											div.style.fontStyle = "italic";
											div.style.color = "#808080";
											div.innerHTML = "Not set";
											return;
										}
										var div_index, levels_areas, ar_id, index;
										if (area.division_id) {
											// get the division level of the area set
											div_index = 0;
											while (div_index < cdata.length && cdata[div_index].division_id != tc.area.division_id) div_index++;
											if (div_index >= cdata.length) {
												div.style.fontStyle = "italic";
												div.style.color = "red";
												div.innerHTML = "Invalid division";
												return;
											}
											// get the selected area at each level
											levels_areas = [];
											ar_id = tc.area.id;
											index = div_index;
											do {
												var ar = null;
												for (var i = 0; i < cdata[index].areas.length; ++i) {
													if (cdata[index].areas[i].area_id == ar_id) {
														ar = cdata[index].areas[i];
														break;
													}
												}
												if (ar == null) {
													div.style.fontStyle = "italic";
													div.style.color = "red";
													div.innerHTML = "Invalid area";
													return;
												}
												levels_areas.splice(0,0,ar);
												index--;
												ar_id = ar.area_parent_id;
											} while (index >= 0 && ar_id != null);
										} else {
											div_index = -1;
											levels_areas = null;
											ar_id = -1;
											index = -1;
										}
										
										var select = null;
										if (t.editable) {
											select = document.createElement("SELECT");
											var o;
											o = document.createElement("OPTION");
											o.text = "";
											o.value = -1;
											select.add(o);
											for (var i = 0; i < cdata[t.config.sub_data_index].areas.length; ++i) {
												var ar = cdata[t.config.sub_data_index].areas[i];
												if (t.config.sub_data_index > 0 && div_index != -1) {
													if (levels_areas.length > t.config.sub_data_index-1) {
														// the previous level is defined: restrict to this one
														if (levels_areas[t.config.sub_data_index-1].area_id != ar.area_parent_id) continue;
													} else {
														// the previous level is not defined
														// get the latest defined level
														var latest = levels_areas[levels_areas.length-1];
														if (!window.top.geography.isAreaIncludedIn(cdata, ar, t.config.sub_data_index, latest.area_id)) continue;
													}
												}
												o = document.createElement("OPTION");
												o.text = ar.area_name;
												o.value = ar.area_id;
												select.add(o);
											}
											select.onchange = function() {
												if (this.selectedIndex > 0) {
													area.id = this.options[this.selectedIndex].value;
													area.division_id = cdata[t.config.sub_data_index].division_id;
												} else {
													if (t.config.sub_data_index == 0) {
														// first level, everything is reset
														area.id = null;
														area.division_id = null;
													} else {
														// reset this level: parent level is selected
														if (levels_areas.length < t.config.sub_data_index-1) return; // we don't have a level
														area.id = levels_areas[t.config.sub_data_index].area_parent_id;
														area.division_id = cdata[t.config.sub_data_index-1].division_id;
													}
												}
												t._datachange(true);
											};
										}
										if (t.config.sub_data_index > div_index) {
											if (t.editable) {
												div.appendChild(select);
												select.selectedIndex = 0;
											} else {
												div.style.fontStyle = "italic";
												div.style.color = "#808080";
												div.innerHTML = "Not set";
											}
											return;
										}

										if (t.editable) {
											div.appendChild(select);
											if (div_index != -1) {
												var ar = levels_areas[t.config.sub_data_index];
												var index = -1;
												for (var i = 0; i < select.options.length; ++i)
													if (select.options[i].value == ar.area_id) { index = i; break; }
												if (index != -1)
													select.selectedIndex = index;
											}
										} else
											div.appendChild(document.createTextNode(levels_areas[t.config.sub_data_index].area_name));
									});								
								});
							}
						};
						closure.get();
					}
				}
			}
			if (t.editable) {
				this.getNbData = function() {
					return t._data.addresses.length;
				};
				this.resetData = function() {
					t._data.addresses = [];
					t.setData(t._data, true);
				};
				this.addData = function(new_data) {
					var division_index = this.config.sub_data_index;
					var address = new PostalAddress(-1, window.top.default_country_id, null, "", "", "", "", "", "Work");
					if (typeof new_data == 'string') {
						window.top.require("geography.js", function() {
							window.top.geography.searchAreaByNameInDivision(window.top.default_country_id, division_index, new_data, function(area) {
								if (area) {
									// found
									address.geographic_area.country_id = window.top.default_country_id;
									address.geographic_area.id = area.area_id;
									window.top.geography.getDivisionIdFromIndex(window.top.default_country_id, division_index, function(division_id) {
										address.geographic_area.division_id = division_id;
										t._data.addresses.push(address);
										t.setData(t._data, true);
									});
								} else {
									// not found
									// TODO ?
								}
							});
						});
					} else {
						// TODO ?
					}
				};

				var add_button = document.createElement("BUTTON");
				add_button.className = "flat small_icon";
				add_button.innerHTML = "<img src='"+theme.icons_10.add+"'/>";
				add_button.title = "Add new address";
				this.element.appendChild(add_button);
				add_button.onclick = function() {
					var address = new PostalAddress(-1, window.top.default_country_id, null, null, null, null, null, null, "Work");
					t._data.addresses.push(address);
					t.setData(t._data, true);
					return false;
				};
			}
		};
		this._setData(data);
	}
};