function location_and_partners(popup, section_location, section_other_partners, center_type, center_id, geographic_area_text, partners, editable) {
	
	this.center_id = center_id;
	this.geographic_area_text = geographic_area_text;
	this.partners = partners;
	
	// Functionalities
	
	this.getHostPartner = function() {
		for (var i = 0; i < this.partners.length; ++i) {
			if (!this.partners[i].host) continue;
			return this.partners[i];
		}
		return null;
	};
	this.getHostAddress = function() {
		var host = this.getHostPartner();
		if (host != null) {
			for (var i = 0; i < host.organization.addresses.length; ++i)
				if (host.organization.addresses[i].id == host.host_address_id)
					return host.organization.addresses[i];
		}
		return null;
	};
	this.setHostPartner = function(host) {
		// unselect previous host
		for (var i = 0; i < this.partners.length; ++i)
			if (this.partners[i].host) {
				this.partners[i].host = false;
				this.partners[i].host_address = null;
				break;
			}
		this.geographic_area_text = null;
		if (host != null) {
			for (var i = 0; i < host.organization.addresses.length; ++i)
				if (host.organization.addresses[i].id == host.host_address_id) {
					this.geographic_area_text = host.organization.addresses[i].geographic_area;
					break;
				}
			// check if already present in the partners list
			var present = false;
			for (var i = 0; i < this.partners.length; ++i) {
				if (this.partners[i].organization.id == host.organization.id) {
					// it is present, update it
					this.partners[i].host = true;
					this.partners[i].host_address_id = host.host_address_id;
					present = true;
					break;
				}
			}
			// not yet a partner, add it in the list
			if (!present)
				this.partners.push(host);
		}
		window.pnapplication.dataUnsaved("SelectionLocationAndPartners");
		this._refreshAddress();
		this._refreshHost();
		this._refreshPartners();
		this._refreshMap();
	};
	
	this.dialogSelectLocation = function() {
		var t=this;
		var win=window;
		require("popup_select_area_and_partner.js", function() {
			var host = t.getHostPartner();
			new popup_select_area_and_partner(
				t.geographic_area_text ? t.geographic_area_text.id : null,
				host,
				function(selected) {
					if (selected.host) {
						// a host is selected
						if (selected.host.center_id == -1) {
							// the host changed
							popup.freeze();
							service.json("contact","get_organization",{id:selected.host.organization.id},function(org) {
								popup.unfreeze();
								if (!org) return;
								selected.host.center_id = t.center_id;
								selected.host.organization = org;
								t.setHostPartner(selected.host);
							});
							return;
						}
						return; // no change
					} else {
						// no host selected
						if (host != null) {
							// one was previously selected: remove it
							t.setHostPartner(null);
						}
						if (selected.geographic_area) {
							// an area is selected
							if (t.geographic_area_text == null || t.geographic_area_text.id != selected.geographic_area) {
								// it is a different one
								t.geographic_area_text = null; // temporary
								win.pnapplication.dataUnsaved("SelectionLocationAndPartners");
								popup.freeze();
								window.top.geography.getCountryData(window.top.default_country_id, function(country_data) {
									t.geographic_area_text = window.top.geography.getGeographicAreaTextFromId(country_data, selected.geographic_area);
									t._refreshAddress();
									t._refreshMap();
									popup.unfreeze();
								});
							}
						} else {
							if (t.geographic_area_text) {
								// there was one before: unselect it
								geographic_area_text = null;
								win.pnapplication.dataUnsaved("SelectionLocationAndPartners");
							}
						}
						t._refreshAddress();
						t._refreshMap();
					}
				},
				"<img style = 'vertical-align:bottom;'src = '"+theme.icons_16.info+"'/> <i>To be fully completed a host partner must be attached</i>",
				"<img style = 'vertical-align:bottom;'src = '"+theme.icons_16.info+"'/> <i>Location field is fully completed!</i>"
			);
		});
	};

	// Location
	
	/** Initialize the Location section with the address and the host partner */
	this._initLocation = function() {
		//section_location.content.style.display = "flex";
		//section_location.content.style.flexDirection = "row";
		
		var left = document.createElement("DIV");
		//var right = document.createElement("DIV");
		section_location.content.appendChild(left);
		//section_location.content.appendChild(right);
		
		// if this is a new center, mark it as not saved
		if (center_id == -1)
			window.pnapplication.dataUnsaved("SelectionLocationAndPartners");
		// Location section is composed of 2 elements: the address / geographic area, and the host partner
		var address_title = document.createElement("DIV");
		address_title.style.fontWeight = "bold";
		address_title.innerHTML = "Address";
		address_title.style.padding = "1px 2px";
		address_title.style.marginTop = "2px";
		address_title.style.fontStyle = "italic";
		//address_title.style.color = "#606060";
		address_title.style.textDecoration = "underline";
		left.appendChild(address_title);
		this._address_container = document.createElement("DIV");
		this._address_container.style.padding = "1px 2px";
		this._address_container.style.marginBottom = "4px";
		left.appendChild(this._address_container);
		this._host_container = document.createElement("DIV");
		left.appendChild(this._host_container);
		// buttons
		if (editable) {
			this._button_set_location = document.createElement("BUTTON");
			this._button_set_location.className = "action";
			this._button_set_location.innerHTML = "Select a location";
			this._button_set_location.t = this;
			this._button_set_location.onclick = function() { this.t.dialogSelectLocation(); };
			section_location.addToolBottom(this._button_set_location);
		}
		this._map_container = document.createElement("DIV");
		left.appendChild(this._map_container);
		this._map_container.style.visibility = "hidden";
		this._map_container.style.position = "absolute";
		this._map_container.style.top = "-1000px";
		//this._map_container.style.width = "100%";
		this._map_container.style.height = "100%";
		this._map_container.style.minHeight = "180px";
		this._map_container.style.width = "250px";
		// refresh with actual values
		this._refreshAddress();
		this._refreshHost();
		this._refreshMap();
	};
	/** Refresh the address part in the Location section */
	this._refreshAddress = function() {
		// reset content
		this._address_container.removeAllChildren();
		// 3 possibilities: complete address (from the host), only geographic area, or nothing
		var address = this.getHostAddress();
		if (address != null) {
			// we have an address
			var t=this;
			require("address_text.js",function() {
				var a = new address_text(address);
				t._address_container.appendChild(a.element);
				layout.changed(section_location.element);
			});
			if (this._warning_host) { this._warning_host.parentNode.removeChild(this._warning_host); this._warning_host = null; } 
		} else if (this.geographic_area_text != null) {
			// we only have a geographic area
			this._address_container.innerHTML = this.geographic_area_text.text;
			if (!this._warning_host) {
				this._warning_host = document.createElement("DIV");
				this._warning_host.style.display = "inline-block";
				this._warning_host.style.marginLeft = "4px";
				this._warning_host.style.marginRight = "4px";
				this._warning_host.innerHTML = "<img src='"+theme.icons_16.warning+"' style='vertical-align:bottom'/> <i style='color:#FF8000'>Please select a hosting partner</i>";
				section_location.addToolBottom(this._warning_host);
			}
		} else {
			// nothing
			this._address_container.innerHTML = "<center style='color:red'><img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> <i>Please select a location</i></center>";
			if (this._warning_host) { this._warning_host.parentNode.removeChild(this._warning_host); this._warning_host = null; }
		}
		layout.changed(section_location.element);
	};
	this._map = null;
	this._marker = null;
	this._refreshMap = function() {
		var address = this.getHostAddress();
		if (address == null && this.geographic_area_text == null) {
			// nothing => hide the map
			this._map_container.style.visibility = "hidden";
			this._map_container.style.position = "absolute";
			this._map_container.style.top = "-1000px";
		} else {
			// show the map
			this._map_container.style.visibility = "visible";
			this._map_container.style.position = "relative";
			this._map_container.style.top = "";
			var t=this;
			var update_map = function() {
				// fit to area
				var area_id;
				if (address != null)
					area_id = address.geographic_area.id;
				else
					area_id = t.geographic_area_text.id;
				window.top.geography.getCountry(window.top.default_country_id, function(country) {
					window.top.geography.getCountryData(window.top.default_country_id, function(country_data) {
						var area = window.top.geography.searchArea(country_data, area_id);
						while (!area.north && area.area_parent_id > 0)
							area = window.top.geography.getParentArea(country_data, area);
						if (!area.north)
							area = country;
						if (area.north)
							t._map.fitToBounds(area.south, area.west, area.north, area.east);
						if (address && address.lat != null) {
							if (t._marker) {
								t._marker.setPosition(new window.top.google.maps.LatLng(address.lat, address.lng));
							} else {
								t._marker = t._map.addMarker(address.lat, address.lng, 1);
							}
						} else {
							if (t._marker) {
								t._map.removeShape(t._marker);
								t._marker = null;
							}
						}
					});
				});
			};
			if (this._map != null)
				setTimeout(update_map,1);
			else {
				window.top.google.loadGoogleMap(t._map_container, function(m) {
					t._map = m;
					update_map();
				});
			}
		}
	};
	this._refreshHost = function() {
		this._host_container.removeAllChildren();
		var host = this.getHostPartner();
		if (!host) return;
		var table = document.createElement("TABLE");
		table.className = "selection_partners_table";
		this._host_container.appendChild(table);
		var tr = document.createElement("TR"); table.appendChild(tr);
		var th;
		tr.appendChild(th = document.createElement("TH"));
		th.style.fontStyle = "italic";
		//th.style.color = "#606060";
		th.style.textDecoration = "underline";
		th.appendChild(document.createTextNode("Hosting Partner"));
		tr.appendChild(th = document.createElement("TH"));
		th.style.fontStyle = "italic";
		//th.style.color = "#606060";
		th.style.textDecoration = "underline";
		th.appendChild(document.createTextNode("Contact Points"));
		new partnerRow(table, host, editable, function(org) {
			// remove any non-valid contact point
			for (var i = 0; i < host.selected_contact_points_id.length; ++i) {
				var found = false;
				for (var j = 0; j < org.contact_points.length; ++j)
					if (org.contact_points[j].people.id == host.selected_contact_points_id[i]) {
						found = true;
						break;
					}
				if (!found) {
					host.selected_contact_points_id.splice(i,1);
					i--;
				}
			}
			host.organization = org;
			window.pnapplication.dataUnsaved("SelectionLocationAndPartners");
		});
	};
	
	// Other Partners
	
	this._initPartners = function() {
		// table of partners
		this._partners_table = document.createElement("TABLE");
		this._partners_table.className = "selection_partners_table";
		section_other_partners.content.appendChild(this._partners_table);
		
		// button
		if (editable) {
			var button = document.createElement("BUTTON");
			button.className = "action";
			button.appendChild(document.createTextNode("Select Partners"));
			section_other_partners.addToolBottom(button);
			var t=this;
			var twin=window;
			button.onclick = function() {
				require("partners_objects.js");
				var data = {selected:[],selected_not_changeable:[]};
				for (var i = 0; i < t.partners.length; ++i) {
					if (t.partners[i].host)
						data.selected_not_changeable.push(t.partners[i].organization.id);
					else
						data.selected.push(t.partners[i].organization.id);
				}
				window.top.popup_frame("/static/contact/organization.png", "Partners", "/dynamic/contact/page/organizations?creator=Selection", data, 95, 95, function(frame,pop) {
					pop.addOkCancelButtons(function(){
						var win = getIFrameWindow(frame);
						var selected = win.selected;
						pop.close();
						popup.freeze();
						// remove partners not anymore selected
						for (var i = 0; i < t.partners.length; ++i) {
							var found = false;
							for (var j = 0; j < selected.length; ++j)
								if (selected[j] == t.partners[i].organization.id) {
									selected.splice(j,1);
									found = true; 
									break; 
								}
							if (t.partners[i].host) continue; // this is the host
							if (!found) {
								t.partners.splice(i,1);
								i--;
								twin.pnapplication.dataUnsaved("SelectionLocationAndPartners");
							}
						}
						if (selected.length > 0) {
							twin.pnapplication.dataUnsaved("SelectionLocationAndPartners");
							// add new partners
							service.json("contact","get_organizations",{ids:selected},function(list) {
								if (!list) { popup.unfreeze(); return; }
								require("partners_objects.js", function() {
									for (var i = 0; i < list.length; ++i) {
										var p = new SelectionPartner(t.center_id, list[i], false, null, []);
										t.partners.push(p);
									}
									t._refreshPartners();
									popup.unfreeze();
								});
							});
						} else {
							t._refreshPartners();
							popup.unfreeze();
						}
					});
				});
			};
		}
		
		this._refreshPartners();
	};
	
	this._refreshPartners = function() {
		this._partners_table.removeAllChildren();
		if (this.partners.length == 0 || (this.partners.length == 1) && this.partners[0].host) {
			this._partners_table.innerHTML = "<tr><td><i>No other partner</i></td></tr>";
			return;
		}
		var tr, th;
		this._partners_table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(th = document.createElement("TH"));
		th.appendChild(document.createTextNode("Partner"));
		tr.appendChild(th = document.createElement("TH"));
		th.appendChild(document.createTextNode("Contact Point(s)"));
		for (var i = 0; i < this.partners.length; ++i) {
			if (this.partners[i].host) continue;
			new partnerRow(this._partners_table, this.partners[i], editable, function(org, partner) {
				// remove any non-valid contact point
				for (var i = 0; i < partner.selected_contact_points_id.length; ++i) {
					var found = false;
					for (var j = 0; j < org.contact_points.length; ++j)
						if (org.contact_points[j].people.id == partner.selected_contact_points_id[i]) {
							found = true;
							break;
						}
					if (!found) {
						partner.selected_contact_points_id.splice(i,1);
						i--;
					}
				}
				partner.organization = org;
				window.pnapplication.dataUnsaved("SelectionLocationAndPartners");
			});
		}
	};
	
	// Initialization
	this._initLocation();
	this._initPartners();
}

function partnerRow(table, partner, editable, onchange) {
	var tr = document.createElement("TR");
	tr.className = "selection_partner_row";
	table.appendChild(tr);
	this._refresh = function() {
		tr.removeAllChildren();
		var td;
		// name
		tr.appendChild(td = document.createElement("TD"));
		td.className = "partner_name black_link";
		td.title = "Click to see partner details";
		var name_node = document.createTextNode(partner.organization.name);
		td.appendChild(name_node);
		window.top.datamodel.registerCellText(window, "Organization", "name", partner.organization.id, name_node);
		var t=this;
		td.onclick = function(){
			window.top.popup_frame("/static/contact/organization.png","Organization Profile","/dynamic/contact/page/organization_profile?organization="+partner.organization.id+"&onready=orgready", null, null, null, function(frame) {
				frame.orgready = function(org) {
					org.onchange.add_listener(function () {
						if (onchange) onchange(org.getStructure(), partner);
						t._refresh();
					});
				};
			});
		};

		// contacts
		tr.appendChild(td = document.createElement("TD"));
		td.className = "partner_contacts";
		if (partner.selected_contact_points_id == null || partner.selected_contact_points_id.length == 0) {
			td.innerHTML = "<i>No contact selected</i>";
		} else {
			for (var i = 0; i < partner.selected_contact_points_id.length; ++i) {
				var contact = null;
				for (var j = 0; j < partner.organization.contact_points.length; ++j)
					if (partner.organization.contact_points[j].people.id == partner.selected_contact_points_id[i]) {
						contact = partner.organization.contact_points[j];
						break;
					}
				var div = document.createElement("DIV"); td.appendChild(div);
				div.className = "black_link";
				div.people_id = contact.people.id;
				div.title = "Click to see details of this contact";
				div.onclick = function() {
					window.top.popup_frame("/static/people/profile_16.png", "Profile", "/dynamic/people/page/profile?people="+this.people_id, null, 95, 95);
				};
				var span_fn = document.createElement("SPAN"); div.appendChild(span_fn);
				span_fn.className = "contact_point_name";
				span_fn.appendChild(document.createTextNode(contact.people.first_name));
				window.top.datamodel.registerCellSpan(window, "People", "first_name", contact.people.id, span_fn);
				div.appendChild(document.createTextNode(" "));
				var span_ln = document.createElement("SPAN"); div.appendChild(span_ln);
				span_ln.className = "contact_point_name";
				span_ln.appendChild(document.createTextNode(contact.people.last_name));
				window.top.datamodel.registerCellSpan(window, "People", "last_name", contact.people.id, span_ln);
				div.appendChild(document.createTextNode(" "));
				var span = document.createElement("SPAN"); div.appendChild(span);
				span.className = "contact_point_designation";
				span.appendChild(document.createTextNode("("));
				var span_design = document.createElement("SPAN"); span.appendChild(span_design);
				span_design.appendChild(document.createTextNode(contact.designation));
				window.top.datamodel.registerCellSpan(window, "ContactPoint", "designation", contact.people.id, span_design);
				span.appendChild(document.createTextNode(")"));
			}
		}
		
		// actions
		if (editable) {
			tr.appendChild(td = document.createElement("TD"));
			var button_select_contacts = document.createElement("BUTTON");
			td.appendChild(button_select_contacts);
			button_select_contacts.innerHTML = "<img src='/static/people/people_list_16.png'/>";
			button_select_contacts.title = "Select contacts from this partner";
			button_select_contacts.className = "flat";
			button_select_contacts.onclick = function() {
				var button = this;
				var twin = window;
				require("context_menu.js", function() {
					var menu = new context_menu();
					for (var i = 0; i < partner.organization.contact_points.length; ++i) {
						var cp = partner.organization.contact_points[i];
						var div = document.createElement("DIV");
						var cb = document.createElement("INPUT");
						cb.type = "checkbox";
						var found = false;
						for (var j = 0; j < partner.selected_contact_points_id.length; ++j)
							if (partner.selected_contact_points_id[j] == cp.people.id) { found = true; break; }
						if (found) cb.checked = "checked";
						div.appendChild(cb);
						div.appendChild(document.createTextNode(" "+cp.people.first_name+" "+cp.people.last_name+" ("+cp.designation+")"));
						menu.addItem(div, true);
						cb.contact_point_id = cp.people.id;
						cb.onchange = function() {
							if (this.checked) {
								partner.selected_contact_points_id.push(this.contact_point_id);
							} else {
								partner.selected_contact_points_id.remove(this.contact_point_id);
							}
							twin.pnapplication.dataUnsaved("SelectionLocationAndPartners");
							if (onchange) onchange(partner.organization, partner);
							t._refresh();
						};
					}
					if (partner.organization.contact_points.length == 0) {
						var div = document.createElement("DIV");
						div.style.padding = "5px";
						div.innerHTML = "<img src='"+theme.icons_16.warning+"' style='vertical-align:bottom'/> This partner does not have any contact point<br/>Please edit the partner organization";
						menu.addItem(div);
					}
					menu.showBelowElement(button);
				});
			};
		}
		layout.changed(table);
	};
	this._refresh();
}