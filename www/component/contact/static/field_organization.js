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
	for (var i = 0; i < this.config.list.length; ++i)
		values.push(this.config.list[i].name);
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
					var areas = [];
					for (var i = 0; i < org.addresses.length; ++i)
						if (org.addresses[i].geographic_area && org.addresses[i].geographic_area.id)
							areas.push(org.addresses[i].geographic_area.id);
					service.json("contact", "add_organization", org, function(res) {
						if (!res) { popup.unfreeze(); return; }
						t._addPossibleValue(res.id, org.name, areas);
						var fields = window.top.pnapplication.getCustoms('field_organization');
						for (var i = 0; i < fields.length; ++i) {
							if (fields[i] == t) continue; // ourself
							if (arrayEquivalent(t.config.creators,fields[i].config.creators) &&
								arrayEquivalent(t.config.types,fields[i].config.types)
								) {
								fields[i]._addPossibleValue(res.id, org.name, areas);
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
field_organization.prototype.helpFillMultipleItems = function() {
	var s = new OrganizationSelectionPopupContent(this.config.list, false, null)
	var helper = {
		title: 'Set the same value for all',
		content: s.content,
		apply: function(field) {
			field.setData(s.selected_ids.length > 0 ? s.selected_ids[0] : null);
		}
	};
	return helper;
};
field_organization.prototype._addPossibleValue = function(org_id, org_name, areas) {
	this.config.list.push({id:org_id,name:org_name,areas:areas});
};
function getOrganizationIDFromData(data, list) {
	if (typeof data == "number") return data;
	if (data == null) return null;
	if (data == "") return null;
	if (typeof data == "string") {
		var id = data.parseNumber();
		if (!isNaN(id)) return id;
		data = data.trim().latinize().toLowerCase();
		for (var i = 0; i < list.length; ++i)
			if (list[i].name.isSame(data))
				return list[i].id;
	}
	return null;
}
field_organization.prototype._getOrgIDFromData = function(data) {
	return getOrganizationIDFromData(data, this.config.list);
};
function getOrganizationNameFromID(id, list) {
	if (id === null) return null;
	for (var i = 0; i < list.length; ++i)
		if (list[i].id == id)
			return list[i].name;
	return null;
}
field_organization.prototype._getOrgName = function(id) {
	return getOrganizationNameFromID(id, this.config.list);
};
field_organization.prototype._create = function(data) {
	if (this.editable) {
		data = this._getOrgIDFromData(data);
		this.select = new OrganizationSelectionField(this.config.list, data, this.config.name);
		this.element.appendChild(this.select.input);
		var t=this;
		this.select.closeOnBlur = function() {
			var f = t.select.input.onfocus;
			t.select.input.onfocus = null;
			t.select.input.focus();
			t.select.input.onfocus = f;
		};
		this.select.onchanged.add_listener(function() { t._datachange(); });
		this._getEditedData = function() {
			return this.select.selected_id;
		};
		this._setData = function(data) {
			var id = this._getOrgIDFromData(data);
			this.select.setId(null);
			return this.select.selected_id;
		};
		this.validate = function() {
			if (this.selected_id > 0 || this.config.can_be_null)
				this.signal_error(null);
			else
				this.signal_error("Please select an organization");
		};
		this.signal_error = function(error) {
			this.error = error;
			this.select.input.style.border = error ? "1px solid red" : "";
			this.select.input.title = error ? error : "";
		};
		this.fillWidth = function() {
			// calculate the minimum width of the select, to be able to see it...
			var included_in_body = false;
			if (this.element.parentNode == null) {
				included_in_body = true;
				document.body.appendChild(this.element);
			}
			this.element.style.width = "100%";
			this.select.input.style.width = "100%";
		};
	} else {
		this.element.style.whiteSpace = "nowrap";
		this._setData = function(data) {
			var id = this._getOrgIDFromData(data);
			this._data = id;
			var name = this._getOrgName(id);
			if (name == null) name = "";
			this.element.removeAllChildren();
			this.element.appendChild(document.createTextNode(name));
			return id;
		};
	}
	this._setData(data);
};
function OrganizationSelectionField(list, selected_id, name) {
	require("mini_popup.js");
	this.onchanged = new Custom_Event();
	this.input = document.createElement("INPUT");
	this.input.type = "text";
	this.selected_id = selected_id;
	if (selected_id)
		for (var i = 0; i < list.length; ++i)
			if (list[i].id == selected_id) { this.input.value = list[i].name; break; }
	var t=this;
	this.input.onfocus = function() {
		require("mini_popup.js",function() {
			var p = new mini_popup("Select "+name, true);
			var s = new OrganizationSelectionPopupContent(list, false, []);
			if (t.closeOnBlur) s.closeOnBlur = function() {
				p.close();
				t.closeOnBlur();
			}
			p.content.style.display = "flex";
			p.content.style.flexDirection = "column";
			s.content.style.flex = "1 1 auto";
			p.content.appendChild(s.content);
			s.onchange.add_listener(function() {
				for (var i = 0; i < list.length; ++i)
					if (list[i].id == s.selected_ids[0]) { t.input.value = list[i].name; break; }
				t.selected_id = s.selected_ids[0];
				t.onchanged.fire(t);
				p.close();
			});
			p.showBelowElement(t.input);
			s.focus();
		});
	};
	this.input.onkeydown = function(ev) {
		var e = getCompatibleKeyEvent(ev);
		if (e.isTab) return true;
		stopEventPropagation(ev);
		return false;
	}
	this.setId = function(id) {
		if (id == this.selected_id) return;
		this.selected_id = id;
		for (var i = 0; i < list.length; ++i)
			if (list[i].id == id) { this.input.value = list[i].name; return; }
		this.input.value = "";
		this.selected_id = null;
	};
}
function OrganizationSelectionPopupContent(list, multiple, selected_ids) {
	if (!multiple) {
		window.top.theme.css("context_menu.css");
		theme.css("context_menu.css");
	}
	this.selected_ids = selected_ids ? selected_ids : [];
	this.content = document.createElement("DIV");
	this.content.style.display = "flex";
	this.content.style.flexDirection = "column";
	this.onchange = new Custom_Event();
	this.checkboxes = [];
	var t=this;
	this._search = {
		build: function() {
			this.div = document.createElement("DIV");
			this.div.style.flex = "none";
			this.div.style.display = "flex";
			this.div.style.flexDirection = "row";
			this.div.innerHTML = "<img src='"+theme.icons_16.search+"' style='vertical-align:bottom;flex:none;'/> ";
			this.fakeInput1 = document.createElement("INPUT");
			this.fakeInput1.type = "text";
			this.fakeInput1.style.width = "1px";
			this.fakeInput1.style.height = "1px";
			this.fakeInput1.style.dlex = "none";
			this.fakeInput1.tabIndex = 2;
			setOpacity(this.fakeInput1, 0);
			this.fakeInput1.onfocus = function() {
				if (t.closeOnBlur) t.closeOnBlur();
			};
			this.div.appendChild(this.fakeInput2);
			this.input = document.createElement("INPUT");
			this.input.type = "text";
			this.input.style.flex = "1 1 auto";
			this.input.tabIndex = 1;
			this.div.appendChild(this.input);
			this.input.onkeyup = function() {
				setTimeout(function() {
					var name = t._search.input.value;
					name = name.trim().latinize().toLowerCase();
					if (name.length == 0) name = null;
					t._byname.filter(name);
					t._byarea.filter(name);
				},1);
			};
			this.fakeInput2 = document.createElement("INPUT");
			this.fakeInput2.type = "text";
			this.fakeInput2.style.width = "1px";
			this.fakeInput2.style.height = "1px";
			this.fakeInput2.style.dlex = "none";
			this.fakeInput2.tabIndex = 2;
			setOpacity(this.fakeInput2, 0);
			this.fakeInput2.onfocus = function() {
				if (t.closeOnBlur) t.closeOnBlur();
			};
			this.div.appendChild(this.fakeInput2);
			return this.div;
		},
		focus: function() {
			this.input.focus();
		}
	};
	this._byname = {
		build: function() {
			this.div = document.createElement("DIV");
			this.div.style.display = "flex";
			this.div.style.flexDirection = "column";
			this.header = document.createElement("DIV");
			this.header.innerHTML = "List by name";
			this.header.style.flex = "none";
			this.header.style.textAlign = "center";
			this.header.style.fontWeight = "bold";
			this.header.style.borderBottom = "1px solid #808080";
			this.div.appendChild(this.header);
			this.content = document.createElement("DIV");
			this.content.style.flex = "1 1 auto";
			this.content.style.overflowY = "auto";
			this.content.style.paddingRight = "20px";
			this.div.appendChild(this.content);
			for (var i = 0; i < list.length; ++i) {
				var item = document.createElement("DIV");
				item.style.whiteSpace = "nowrap";
				item._org = list[i];
				if (multiple) {
					var cb = document.createElement("INPUT");
					cb.type = "checkbox";
					cb.style.verticalAlign = "middle";
					cb.style.marginRight = "3px";
					cb.checked = t.selected_ids.indexOf(list[i].id) >= 0 ? "checked" : "";
					t.checkboxes.push(cb);
					item.appendChild(cb);
					cb.onchange = function() {
						if (this.checked)
							t.selected_ids.push(this.parentNode._org.id);
						else
							t.selected_ids.remove(this.parentNode._org.id);
						t.onchange.fire();
					};
				}
				t.addItemName(item, list[i].name);
				if (!multiple) {
					item.className = "context_menu_item";
					item.onclick = function() {
						t.selected_ids = [this._org.id];
						t.onchange.fire();
					};
				}
				this.content.appendChild(item);
			}
			return this.div;
		},
		filter: function(name) {
			for (var i = 0; i < this.content.childNodes.length; ++i) {
				var item = this.content.childNodes[i];
				if (!name || item._org.name.latinize().toLowerCase().indexOf(name) >= 0) {
					item.style.display = "";
					t.highlightFilteredItem(item, name);
				} else
					item.style.display = "none";
			}
		}
	};
	this._byarea = {
		build: function() {
			this.div = document.createElement("DIV");
			this.div.style.display = "flex";
			this.div.style.flexDirection = "column";
			this.header = document.createElement("DIV");
			this.header.innerHTML = "List by geographic area";
			this.header.style.flex = "none";
			this.header.style.textAlign = "center";
			this.header.style.fontWeight = "bold";
			this.header.style.borderBottom = "1px solid #808080";
			this.div.appendChild(this.header);
			this.content = document.createElement("DIV");
			this.content.style.flex = "1 1 auto";
			this.content.style.overflowY = "auto";
			this.content.style.paddingRight = "20px";
			this.div.appendChild(this.content);
			window.top.geography.getCountryData(window.top.default_country_id, function(country_data) {
				t._byarea.fillAreas(country_data, 0, null, t._byarea.content, 0);
				var missing = [];
				for (var i = 0; i < list.length; ++i) if (list[i].areas.length == 0) missing.push(list[i]);
				if (missing.length > 0) {
					var div = document.createElement("DIV");
					for (var j = 0; j < missing.length; ++j)
						t._byarea.createItem(missing[j], div, 15);
					var header = document.createElement("DIV");
					header.style.backgroundColor = "#FFE0C0";
					if (multiple) {
						var cb = document.createElement("INPUT");
						cb.type = "checkbox";
						cb.style.verticalAlign = "middle";
						cb.style.marginRight = "3px";
						header.appendChild(cb);
						cb.onchange = function() {
							for (var i = 0; i < missing.length; ++i)
								if (this.checked)
									t.selected_ids.push(missing[i].id);
								else
									t.selected_ids.remove(missing[i].id);
							t.onchange.fire();
						};
					}
					header.appendChild(document.createTextNode("Unknown Location"));
					div.insertBefore(header, div.firstChild);
					t._byarea.content.appendChild(div);
				}
				layout.changed(t._byarea.content);
			});
			return this.div;
		},
		fillAreas: function(country_data, division_index, parent_id, parent_div, indent) {
			for (var i = 0; i < country_data[division_index].areas.length; ++i) {
				var area = country_data[division_index].areas[i];
				if (area.area_parent_id != parent_id) continue;
				var div = document.createElement("DIV");
				div._area = area;
				div.style.paddingLeft = indent+"px";
				for (var j = 0; j < list.length; ++j)
					if (list[j].areas.contains(area.area_id))
						this.createItem(list[j], div, indent+15);
				if (division_index < country_data.length-1)
					this.fillAreas(country_data, division_index+1, area.area_id, div, indent+15);
				if (div.childNodes.length > 0) {
					var header = document.createElement("DIV");
					header.style.backgroundColor = "#FFE0C0";
					header.style.whiteSpace = "nowrap";
					if (multiple) {
						var cb = document.createElement("INPUT");
						cb.type = "checkbox";
						cb.style.verticalAlign = "middle";
						cb.style.marginRight = "3px";
						header.appendChild(cb);
						cb.onchange = function(ev,nofire) {
							var header = this.parentNode;
							var item = header.nextSibling;
							while (item) {
								if (item._area) {
									item.childNodes[0].childNodes[0].checked = this.checked ? "checked" : "";
									item.childNodes[0].childNodes[0].onchange(ev,true);
								} else if (item._org) {
									item.childNodes[0].checked = this.checked ? "checked" : "";
									item.childNodes[0].onchange(ev,true);
								}
								item = item.nextSibling;
							}
							if (!nofire) t.onchange.fire();
						};
					}
					header.appendChild(document.createTextNode(country_data[division_index].division_name+' '));
					t.addItemName(header, area.area_name);
					div.insertBefore(header, div.firstChild);
					parent_div.appendChild(div);
				}
			}
		},
		createItem: function(org, container, indent) {
			var item = document.createElement("DIV");
			item.style.paddingLeft = indent+"px";
			item.style.whiteSpace = "nowrap";
			item._org = org;
			if (multiple) {
				var cb = document.createElement("INPUT");
				cb.type = "checkbox";
				cb.style.verticalAlign = "middle";
				cb.style.marginRight = "3px";
				cb.checked = t.selected_ids.indexOf(org.id) >= 0 ? "checked" : "";
				t.checkboxes.push(cb);
				item.appendChild(cb);
				cb.onchange = function(ev,nofire) {
					if (this.checked)
						t.selected_ids.push(this.parentNode._org.id);
					else
						t.selected_ids.remove(this.parentNode._org.id);
					if (!nofire) t.onchange.fire();
				};
			}
			t.addItemName(item, org.name);
			if (!multiple) {
				item.className = "context_menu_item";
				item.onclick = function() {
					t.selected_ids = [this._org.id];
					t.onchange.fire();
				};
			}
			container.appendChild(item);
		},
		filter: function(name) {
			this.filterDiv(this.content, name);
		},
		filterDiv: function(div, name, show_anyway) {
			if (div._org) {
				t.highlightFilteredItem(div, name);
				if (show_anyway || !name) {
					div.style.display = "";
					return true;
				}
				var matching = div._org.name.latinize().toLowerCase().indexOf(name) >= 0;
				if (matching) {
					div.style.display = "";
					has_something = true;
					return true;
				}
				div.style.display = "none";
				return false;
			}
			var has_something = false;
			for (var i = 0; i < div.childNodes.length; ++i) {
				var item = div.childNodes[i];
				if (item._area) {
					var matching = !name || item._area.area_name.latinize().toLowerCase().indexOf(name) >= 0;
					t.highlightFilteredItem(item.childNodes[0], name);
					var ok = true;
					for (var j = 1; j < item.childNodes.length; ++j)
						ok &= this.filterDiv(item.childNodes[j], name, matching || show_anyway);
					if (matching || ok || show_anyway) {
						item.style.display = "";
						has_something = true;
					} else
						item.style.display = "none";
					continue;
				}
				has_something |= this.filterDiv(item, name, show_anyway);
			}
			return has_something;
		}
	};
	this.addItemName = function(div, name) {
		div.span_name = document.createElement("SPAN");
		div.span_name.appendChild(document.createTextNode(name));
		div.span_name._name = name;
		div.appendChild(div.span_name);
	};
	this.highlightFilteredItem = function(div, name) {
		div.span_name.removeAllChildren();
		if (!name) div.span_name.appendChild(document.createTextNode(div.span_name._name));
		else {
			var s = div.span_name._name;
			while ((i = s.latinize().toLowerCase().indexOf(name)) >= 0) {
				if (i > 0) {
					div.span_name.appendChild(document.createTextNode(s.substring(0,i)));
					s = s.substring(i);
				}
				var span = document.createElement("SPAN");
				span.style.fontWeight = "bold";
				span.appendChild(document.createTextNode(s.substring(0,name.length)));
				div.span_name.appendChild(span);
				s = s.substring(name.length);
			}
			if (s.length > 0)
				div.span_name.appendChild(document.createTextNode(s));
		}
	}
	this.focus = function() {
		this._search.focus();
	};
	this.checkAll = function(checked) {
		for (var i = 0; i < t.checkboxes.length; ++i)
			t.checkboxes[i].checked = checked ? "checked" : "";
		t.selected_ids = [];
		if (checked) for (var i = 0; i < list.length; ++i) t.selected_ids.push(list[i].id);
	};
	if (multiple) t.onchange.add_listener(function() {
		for (var i = 0; i < t.checkboxes.length; ++i)
			t.checkboxes[i].checked = t.selected_ids.indexOf(t.checkboxes[i].parentNode._org.id) >= 0 ? "checked" : "";
	});
	this.content.appendChild(this._search.build());
	var div = document.createElement("DIV");
	div.style.flex = "1 1 auto";
	div.style.display = "flex";
	div.style.flexDirection = "row";
	var d = this._byname.build();
	d.style.borderRight = "1px solid black";
	div.appendChild(d);
	div.appendChild(this._byarea.build());
	this.content.appendChild(div);
}