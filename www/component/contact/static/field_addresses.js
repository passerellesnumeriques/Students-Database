/* #depends[/static/widgets/typed_field/typed_field.js] */
/**
 * Display a list of addresses
 * @param {PostalAddressesData} data coming from a AddressDataDisplay, it specifies the list of addresses together with the owner (people or organization)
 * @param {Boolean} editable created as editable or not
 * @param {Object} config no configuration supported by this field
 */
function field_addresses(data,editable,config){
	typed_field.call(this, data, editable, config);
}
field_addresses.prototype = new typed_field();
field_addresses.prototype.constructor = field_addresses;		
field_addresses.prototype._create = function(data) {
	if (typeof this.config.sub_data_index == 'undefined') {
		if (this.editable) {
			this.table = document.createElement("TABLE"); this.element.appendChild(this.table);
			var t=this;
			require("addresses.js",function() {
				new addresses(t.table, false, data.type, data.type_id, data.addresses, true, true, true);
			});
		} else {
			this.table = document.createElement("TABLE"); this.element.appendChild(this.table);
			this.table.appendChild(this.tr = document.createElement("TR"));
			this.setData = function(data) {
				this.data = data;
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
			this.setData(data);
			this.getCurrentData = function(){
				return this.data;
			};
		}
	} else {
		this.setData = function(data) {
			this.data = objectCopy(data);
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
									window.top.geography.getCountryData(area.country_id, function(cdata) {
										if (area.division_id == null) {
											div.style.fontStyle = "italic";
											div.style.color = "#808080";
											div.innerHTML = "Not set";
											return;
										}
										// get the division level of the area set
										var div_index = 0;
										while (div_index < cdata.length && cdata[div_index].division_id != tc.area.division_id) div_index++;
										if (div_index >= cdata.length) {
											div.style.fontStyle = "italic";
											div.style.color = "red";
											div.innerHTML = "Invalid division";
											return;
										}
										
										// get the selected area at each level
										var levels_areas = [];
										var ar_id = tc.area.id;
										var index = div_index;
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
												if (t.config.sub_data_index > 0) {
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
													t._datachange();
												} else {
													if (t.config.sub_data_index == 0) {
														// first level, everything is reset
														area.id = null;
														area.division_id = null;
														t._datachange();
													} else {
														// reset this level: parent level is selected
														if (levels_areas.length < t.config.sub_data_index-1) return; // we don't have a level
														area.id = levels_areas[t.config.sub_data_index].area_parent_id;
														area.division_id = cdata[t.config.sub_data_index-1].division_id;
														t._datachange();
													}
												}
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

										var ar = levels_areas[t.config.sub_data_index];
										if (t.editable) {
											div.appendChild(select);
											var index = -1;
											for (var i = 0; i < select.options.length; ++i)
												if (select.options[i].value == ar.area_id) { index = i; break; }
											if (index != -1)
												select.selectedIndex = index;
										} else
											div.appendChild(document.createTextNode(ar.area_name));
									});								
								});
							}
						};
						closure.get();
					}
				}
			}
		};
		this.setData(data);
		this.getCurrentData = function(){
			return this.data;
		};
	}
};