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
	window.top.popupFrame(
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
		},
		validated: new Custom_Event()
	};
	s.onchange.addListener(function() {
		helper.validated.fire();
	});
	return helper;
};
/** Called when a new organization has been created
 * @param {Number} org_id id of the new organization
 * @param {String} org_name name of the new organization
 * @param {Array} areas areas' id containing the new organization
 */
field_organization.prototype._addPossibleValue = function(org_id, org_name, areas) {
	for (var i = 0; i < this.config.list.length; ++i)
		if (this.config.list[i].id == org_id) return; // we already have it ?
	this.config.list.push({id:org_id,name:org_name,areas:areas});
};
/** Retrieve the ID of the organization using a data coming from input or service which can be the name, the id...
 * @param {String|Number|null} data the data
 * @param {Array} list the list of known organizations
 * @returns {Number} the ID, or null if we cannot find it
 */
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
/** Get the ID of the organization corresponding to the given data
 * @param {String|Number|null} data the data
 * @returns {Number} the ID, or null if we cannot find it
 */
field_organization.prototype._getOrgIDFromData = function(data) {
	return getOrganizationIDFromData(data, this.config.list);
};
/** Get the name of an organization by its ID
 * @param {Number} id the organization id
 * @param {Array} list the list where to search
 * @returns {String} the name, or null if not found
 */
function getOrganizationNameFromID(id, list) {
	if (id === null) return null;
	for (var i = 0; i < list.length; ++i)
		if (list[i].id == id)
			return list[i].name;
	return null;
}
/** Get the name using the id
 * @param {Number} id organization ID
 * @returns {String} the name, or null if not found
 */
field_organization.prototype._getOrgName = function(id) {
	return getOrganizationNameFromID(id, this.config.list);
};
field_organization.prototype._create = function(data) {
	if (this.editable) {
		data = this._getOrgIDFromData(data);
		var t=this;
		this.select = new OrganizationSelectionField(this.config.list, data, this.config.name, function(new_name) {
			t.createValue(new_name, t.config.name, function(final_name) {
				t.select.setId(t._getOrgIDFromData(final_name));
			});
		});
		this.element.appendChild(this.select.input);
		this.select.closeOnBlur = function() {
			var f = t.select.input.onfocus;
			t.select.input.onfocus = null;
			t.select.input.focus();
			t.select.input.onfocus = f;
		};
		this.select.onchanged.addListener(function() { t._datachange(); });
		this._getEditedData = function() {
			return this.select.selected_id;
		};
		this._setData = function(data) {
			var id = this._getOrgIDFromData(data);
			this.select.setId(id);
			return this.select.selected_id;
		};
		this.validate = function() {
			if (this.selected_id > 0 || this.config.can_be_null)
				this.signalError(null);
			else
				this.signalError("Please select an organization");
		};
		this.signalError = function(error) {
			this.error = error;
			this.select.input.style.border = error ? "1px solid red" : "";
			this.select.input.title = error ? error : "";
		};
		this._fillWidth = function() {
			this.select.fillWidth();
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
/**
 * Create a field for an organization, using the given list. It will be an INPUT, which displays a mini-popup when getting the focus.
 * @param {Array} list list of known organizations
 * @param {Number} selected_id the ID of the currently selected organization, or null
 * @param {String} name the type of organization, like 'High School', 'NGO'...
 * @param {Function} create if given, if the user enter a non-existing name, we will propose to create a new organization with this name. In this case, this function is called to handle the creation.
 */
function OrganizationSelectionField(list, selected_id, name, create) {
	require("mini_popup.js");
	/** Event fired when the selected organization changed */
	this.onchanged = new Custom_Event();
	/** The input */
	this.input = document.createElement("INPUT");
	this.input.type = "text";
	this.input.onkeydown = function(ev) {
		var e = getCompatibleKeyEvent(ev);
		if (e.isTab) return true;
		stopEventPropagation(ev);
		return false;
	}
	var t=this;
	this.selected_id = selected_id;
	if (selected_id)
		for (var i = 0; i < list.length; ++i)
			if (list[i].id == selected_id) { this.input.value = list[i].name; break; }
	this.input.onfocus = function() {
		require("mini_popup.js",function() {
			var p = new mini_popup("Select "+name, true);
			var s = new OrganizationSelectionPopupContent(list, false, [], create ? function(new_name) {
				confirmDialog("Do you want to create a new "+name+" <b>"+new_name+"</b> ?", function(yes) {
					if (!yes) return;
					p.close();
					create(new_name);
				});
			} : null, function() {
				p.close();
			});
			if (t.closeOnBlur) s.closeOnBlur = function() {
				p.close();
				t.closeOnBlur();
			}
			p.content.style.display = "flex";
			p.content.style.flexDirection = "column";
			s.content.style.flex = "1 1 auto";
			p.content.appendChild(s.content);
			s.onchange.addListener(function() {
				for (var i = 0; i < list.length; ++i)
					if (list[i].id == s.selected_ids[0]) { t.input.value = list[i].name; if (t.input.autoresize) t.input.autoresize(); break; }
				t.selected_id = s.selected_ids[0];
				t.onchanged.fire(t);
				p.close();
			});
			p.showBelowElement(t.input);
			s.focus();
		});
	};
	var _fw = false;
	require("input_utils.js", function() { inputAutoresize(t.input, _fw ? -1 : 10); });
	/** Fill the width of its container */
	this.fillWidth = function() {
		_fw = true;
		this.input.style.minWidth = "100%";
		if (typeof this.input.setMinimumSize == 'function')
			this.input.setMinimumSize(-1);
	};
	/** Set the selected organization
	 * @param {Number} id the id of the new selected organization
	 */
	this.setId = function(id) {
		if (id == this.selected_id) return;
		this.selected_id = id;
		for (var i = 0; i < list.length; ++i)
			if (list[i].id == id) { this.input.value = list[i].name; if (this.input.autoresize) this.input.autoresize(); return; }
		this.input.value = "";
		this.selected_id = null;
		if (this.input.autoresize) t.input.autoresize();
	};
}
/** Create and handle the content of the popup to select one or several organizations, among a list of known organizations.
 * @param {Array} list the list of known organizations
 * @param {Boolean} multiple if true, checkboxes will be displayed for the user to select which organizations, if false only one can be selected, and it will behave like a context_menu: when the user click on an organization, it becomes selected.
 * @param {Array} selected_ids the list of currently selected organizations
 * @param {Function} create if given, the user will have the opportunity to create a new organization, and this function will be called to handle the creation
 * @param {Function} cancel if given, will be called in case the user press the Escape key to cancel the selection and close the popup
 */
function OrganizationSelectionPopupContent(list, multiple, selected_ids, create, cancel) {
	if (!multiple) {
		window.top.theme.css("context_menu.css");
		theme.css("context_menu.css");
	}
	this.selected_ids = selected_ids ? selected_ids : [];
	/** Content of the popup */
	this.content = document.createElement("DIV");
	this.content.style.display = "flex";
	this.content.style.flexDirection = "column";
	/** Event fired when selection changed */
	this.onchange = new Custom_Event();
	/** List of checkboxes, in case multiple is true */
	this.checkboxes = [];
	var t=this;
	/** Section with the input for the search */
	this._search = {
		/** Build the section */
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
			this.div.appendChild(this.fakeInput1);
			this.input = document.createElement("INPUT");
			this.input.type = "text";
			this.input.style.flex = "1 1 auto";
			this.input.tabIndex = 1;
			this.div.appendChild(this.input);
			this.input.onkeyup = function(ev) {
				if (!multiple) {
					// can select using keyboard
					var e = getCompatibleKeyEvent(ev);
					if (e.isArrowDown) {
						t._byname.keyboardSelectionDown();
						return;
					}
					if (e.isArrowUp) {
						t._byname.keyboardSelectionUp();
						return;
					}
					if (e.isEnter) {
						stopEventPropagation(ev);
						var sel = t._byname.getKeyboardSelection();
						if (sel) {
							t.selected_ids = [sel.id];
							t.onchange.fire();
							return false;
						}
						if (this.value.trim().length > 0 && create) {
							create(this.value.trim());
							return false;
						}
						return false;
					}
					if (e.isEscape) {
						if (cancel) {
							stopEventPropagation(ev);
							cancel();
							return false;
						}
					}
				}
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
		/** Give the focus to the input */
		focus: function() {
			this.input.focus();
		}
	};
	/** Section to display the matching organizations by name */
	this._byname = {
		/** Build the section */
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
		/** Filter items with given name
		 * @param {String} name the name to be matched
		 */
		filter: function(name) {
			for (var i = 0; i < this.content.childNodes.length; ++i) {
				var item = this.content.childNodes[i];
				if (!name || item._org.name.latinize().toLowerCase().indexOf(name) >= 0) {
					item.style.display = "";
					t.highlightFilteredItem(item, name);
				} else
					item.style.display = "none";
			}
			if (this._keyboard_selected) {
				var item = null;
				for (var i = 0; i < this.content.childNodes.length; ++i)
					if (this.content.childNodes[i]._org == this._keyboard_selected) { item = this.content.childNodes[i]; break; }
				removeClassName(item, "selected");
				this._keyboard_selected = null;
			}
		},
		/** Selected organization when the user is using keyboard arrows */
		_keyboard_selected: null,
		/** The user pressed the down arrow key */
		keyboardSelectionDown: function() {
			if (this._keyboard_selected == null) {
				if (this.content.childNodes.length == 0) return;
				var item = this.content.childNodes[0];
				while (item && item.style.display == "none") item = item.nextSibling;
				if (!item) return;
				this._keyboard_selected = item._org;
				addClassName(item, "selected");
				return;
			}
			var item = null;
			for (var i = 0; i < this.content.childNodes.length; ++i)
				if (this.content.childNodes[i]._org == this._keyboard_selected) { item = this.content.childNodes[i]; break; }
			var next = item.nextSibling;
			while (next && next.style.display == "none") next = next.nextSibling;
			if (!next) return;
			removeClassName(item, "selected");
			this._keyboard_selected = next._org;
			addClassName(next, "selected");
		},
		/** The user pressed the up arrow key */
		keyboardSelectionUp: function() {
			if (this._keyboard_selected == null) return;
			var item = null;
			for (var i = 0; i < this.content.childNodes.length; ++i)
				if (this.content.childNodes[i]._org == this._keyboard_selected) { item = this.content.childNodes[i]; break; }
			var prev = item.previousSibling;
			while (prev && prev.style.display == "none") prev = prev.previousSibling;
			if (!prev) return;
			removeClassName(item, "selected");
			this._keyboard_selected = prev._org;
			addClassName(prev, "selected");
		},
		/** Returns the currently selected organization if the user is using keyboard arrow keys
		 * @returns {Number} the id
		 */
		getKeyboardSelection: function() {
			return this._keyboard_selected;
		}
	};
	/** Section to display matching organizations by geographic area */
	this._byarea = {
		/** Build the section */
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
				
				var roots_div = [];
				var areas_div = {};
				var missing = [];
				for (var i = 0; i < list.length; ++i) {
					if (list[i].areas.length == 0) {
						missing.push(list[i]);
						continue;
					}
					var area_id = list[i].areas[list[i].areas.length-1];
					if (typeof areas_div[area_id] == 'undefined') {
						var area = window.top.geography.searchArea(country_data, area_id);
						var division = window.top.geography.getAreaDivisionIndex(country_data, area);
						areas_div[area_id] = t._byarea.createAreaDiv(country_data[division].division_name, area);
						areas_div[area_id]._division = division;
						if (division > 0) areas_div[area_id].style.marginLeft = "15px";
						if (area.area_parent_id > 0) {
							var child_div = areas_div[area_id];
							while (area.area_parent_id > 0) {
								area = window.top.geography.getParentArea(country_data, area);
								division--;
								if (typeof areas_div[area.area_id] == 'undefined') {
									var div = t._byarea.createAreaDiv(country_data[division].division_name, area);
									areas_div[area.area_id] = div;
									div._division = division;
									if (division > 0) div.style.marginLeft = "15px";
									if (child_div)
										div.appendChild(child_div);
									if (area.area_parent_id > 0) { child_div = div; } else roots_div.push(areas_div[area.area_id]);
								} else {
									if (child_div)
										areas_div[area.area_id].appendChild(child_div);
									child_div = null;
								}
							}
						} else
							roots_div.push(areas_div[area_id]);
					}
					t._byarea.createItem(list[i], areas_div[area_id], 15);
				}
				for (var i = 0; i < roots_div.length; ++i)
					t._byarea.content.appendChild(roots_div[i]);
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
		/** Create a DIV for the given area
		 * @param {String} division_name name of the division the area belongs to
		 * @param {GeographicArea} area the area
		 * @returns {Element} the DIV
		 */
		createAreaDiv: function(division_name, area) {
			var div = document.createElement("DIV");
			div._area = area;
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
			header.appendChild(document.createTextNode(division_name+' '));
			t.addItemName(header, area.area_name);
			div.appendChild(header);
			return div;
		},
		/** Create a DIV for the given organization
		 * @param {Organization} org the organization
		 * @param {Element} container where to put the div
		 * @param {Number} indent pixels to indent the div
		 * @returns {Element} the DIV
		 */
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
			return item;
		},
		/** Filter organizations to match the given name
		 * @param {String} name name to be matched
		 */
		filter: function(name) {
			this.filterDiv(this.content, name);
		},
		/** Filter the DIV
		 * @param {Element} div the div to be filtered, or in which to look for sub divs
		 * @param {String} name the name to be matched
		 * @param {Boolean} show_anyway if true and the organization is not matching, it won't be hidden (if this is the name of the containing geographic area which is matching)
		 * @returns {Boolean} true if something matched inside, or false if everything is hidden
		 */
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
	/** Add a SPAN with the name
	 * @param {Element} div where to put the name
	 * @param {String} name the name
	 */
	this.addItemName = function(div, name) {
		div.span_name = document.createElement("SPAN");
		div.span_name.appendChild(document.createTextNode(name));
		div.span_name._name = name;
		div.appendChild(div.span_name);
	};
	/** Highlight item if matching, by making the matching part in bold
	 * @param {Element} div the div containing the name to be matched
	 * @param {String} name the name to be matched
	 */
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
	/** Give the focus */
	this.focus = function() {
		this._search.focus();
	};
	/** Tick/Untick all checkboxes
	 * @param {Boolean} checked to tick or untick
	 */
	this.checkAll = function(checked) {
		for (var i = 0; i < t.checkboxes.length; ++i)
			t.checkboxes[i].checked = checked ? "checked" : "";
		t.selected_ids = [];
		if (checked) for (var i = 0; i < list.length; ++i) t.selected_ids.push(list[i].id);
	};
	if (multiple) t.onchange.addListener(function() {
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