if (typeof require != 'undefined') {
	require("editable_cell.js");
	require([["typed_field.js","field_text.js"]]);
}

/**
 * UI Control for an organization
 * @param {Element} container where to display
 * @param {Organization} org organization to display
 * @param {Array} existing_types list of {id:...,name:...} listing all existing organization types in database that can be used
 * @param {Boolean} can_edit indicates if the user can modify the organization
 */
function organization(container, org, existing_types, can_edit) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	/** Return the Organization structure
	 * @returns Organization the structure updated with actual values on the screen 
	 */
	this.getStructure = function() {
		return org;
	};
	
	this.onchange = new Custom_Event();
	this.onaddresschange = new Custom_Event();
	this.oncontactpointchange = new Custom_Event();
	
	/** Create the display */
	this._init = function() {
		// title: name of organization
		container.appendChild(t.title_container = document.createElement("DIV"));
		t.title_container.style.backgroundColor = "white";
		if (org.id != -1) {
			require("editable_cell.js", function() {
				t.title = new editable_cell(t.title_container, "Organization", "name", org.id, "field_text", {min_length:1,max_length:100,can_be_null:false,style:{fontSize:"x-large"}}, org.name, null, function(field){
					org.name = field.getCurrentData();
					t.onchange.fire();
				}, function(edit){
					if (!can_edit) edit.cancelEditable();
				});
			});
		} else {
			require([["typed_field.js","field_text.js"]],function(){
				t.title = new field_text(org.name, true, {min_length:1,max_length:100,can_be_null:false,style:{fontSize:"x-large"}});
				t.title_container.appendChild(t.title.getHTMLElement());
				t.title.onchange.add_listener(function() {
					org.name = t.title.getCurrentData();
					if (t.google_search_input)
						t.google_search_input.value = org.name;
					t.onchange.fire();
				});
			});
		}
		
		// list of organization types
		container.appendChild(t.types_container = document.createElement("DIV"));
		t.types_container.style.backgroundColor = "white";
		var span = document.createElement("SPAN");
		span.style.fontStyle = "italic";
		span.innerHTML = "Types: ";
		t.types_container.appendChild(span);
		require("labels.js", function() {
			var types = [];
			for (var i = 0; i < org.types_ids.length; ++i) {
				for (var j = 0; j < existing_types.length; ++j)
					if (existing_types[j].id == org.types_ids[i]) {
						types.push(existing_types[j]);
						break;
					}
			}
			t.types = new labels("#90D090", types, function(id) {
				// onedit
				alert('Edit not yet implemented');
				// TODO
			}, function(id, handler) {
				// onremove
				var ok = function() {
					for (var i = 0; i < org.types_ids.length; ++i)
						if (org.types_ids[i] == id) {
							org.types_ids.splice(i,1);
							t.onchange.fire();
							handler();
							break;
						}
				};
				if (org.id != -1) {
					service.json("contact", "unassign_organization_type", {organization:org.id,type:id}, function(res) {
						if (res) ok();
					});
				} else
					ok();
			}, function() {
				// add_list_provider
				var items = [];
				for (var i = 0; i < existing_types.length; ++i) {
					var found = false;
					for (var j = 0; j < org.types_ids.length; ++j)
						if (org.types_ids[j] == existing_types[i].id) { found = true; break; }
					if (!found) {
						var item = document.createElement("DIV");
						item.className = "context_menu_item";
						item.innerHTML = existing_types[i].name;
						item.org_type = existing_types[i];
						item.style.fontSize = "8pt";
						item.onclick = function() {
							if (org.id != -1) {
								var tt=this;
								service.json("contact", "assign_organization_type", {organization:org.id,type:this.org_type.id}, function(res) {
									if (res) {
										org.types_ids.push(tt.org_type.id);
										t.types.addItem(tt.org_type.id, tt.org_type.name);
										t.onchange.fire();
									}
								});
							} else {
								org.types_ids.push(this.org_type.id);
								t.types.addItem(this.org_type.id, this.org_type.name);
								t.onchange.fire();
							}
						};
						items.push(item);
					}
				}
				var item = document.createElement("DIV");
				item.className = "context_menu_item";
				item.innerHTML = "<img src='"+theme.icons_16.add+"' style='vertical-align:bottom;padding-right:3px'/> Create a new type";
				item.style.fontSize = "8pt";
				item.onclick = function() {
					input_dialog(theme.icons_16.add,"New Organization Type","Enter the name of the organization type","",100,function(name){
						if (name.length == 0) return "Please enter a name";
						for (var i = 0; i < existing_types.length; ++i)
							if (existing_types[i].name.toLowerCase().trim() == name.toLowerCase().trim())
								return "This organization type already exists";
						return null;
					},function(name){
						if (!name) return;
						service.json("contact","new_organization_type",{creator:org.creator,name:name},function(res){
							if (!res) return;
							if (org.id != -1) {
								service.json("contact", "assign_organization_type", {organization:org.id,type:res.id}, function(res2) {
									if (res2) {
										org.types_ids.push(res.id);
										existing_types.push({id:res.id,name:name});
										t.types.addItem(res.id, name);
										t.onchange.fire();
									}
								});
							} else {
								org.types_ids.push(res.id);
								existing_types.push({id:res.id,name:name});
								t.types.addItem(res.id, name);
								t.onchange.fire();
							}
						});
					});
				};
				items.push(item);
				return items;
			});
			t.types_container.appendChild(t.types.element);
		});

		t.types_container.style.paddingTop = "3px";
		t.types_container.style.paddingBottom = "5px";
		t.types_container.style.marginBottom = "5px";
		t.types_container.style.borderBottom = "1px solid #A0A0A0";
		
		// content: addresses, contacts, contact points, map
		container.appendChild(t.content_container = document.createElement("TABLE"));
		var tr, td_contacts, td_addresses, td_points, td_map;
		t.content_container.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td_contacts = document.createElement("TD"));
		tr.appendChild(td_addresses = document.createElement("TD"));
		tr.appendChild(td_points = document.createElement("TD"));
		tr.appendChild(td_map = document.createElement("TD"));
			// contacts
		td_contacts.style.verticalAlign = "top";
		require("contacts.js", function() {
			t._contacts_widget = new contacts(td_contacts, "organization", org.id, org.contacts, can_edit, can_edit, can_edit);
			t._contacts_widget.onchange.add_listener(function(c){
				org.contacts = c.getContacts();
				t.onchange.fire();
			});
		});
			// addresses
		td_addresses.style.verticalAlign = "top";
		require("addresses.js", function() {
			t._addresses_widget = new addresses(td_addresses, true, "organization", org.id, org.addresses, can_edit, can_edit, can_edit);
			t._addresses_widget.onchange.add_listener(function(a){
				org.addresses = a.getAddresses();
				t.onchange.fire();
				t.onaddresschange.fire();
			});
		});
			// contact points
		t._contact_points_rows = [];
		td_points.style.verticalAlign = "top";
		var table = document.createElement("TABLE");
		table.style.backgroundColor = "white";
		td_points.appendChild(table);
		var thead = document.createElement("THEAD");
		table.appendChild(thead);
		thead.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "<img src='/static/contact/contact_point.png' style='vertical-align:bottom'/> Contact Points";
		
		table.style.border = "1px solid #808080";
		table.style.borderSpacing = "0";
		table.style.marginBottom = "3px";
		setBorderRadius(table, 5, 5, 5, 5, 5, 5, 5, 5);
		td.style.padding = "2px 5px 2px 5px";
		td.style.backgroundColor = "#E0E0E0";
		td.style.fontWeight = "bold";
		td.colSpan = 2;
		setBorderRadius(td, 5, 5, 5, 5, 0, 0, 0, 0);
		
		var tbody = document.createElement("TBODY");
		table.appendChild(tbody);
		if (can_edit) {
			var tfoot = document.createElement("TFOOT");
			table.appendChild(tfoot);
			tfoot.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = "Add Contact Point";
			td.style.cursor = 'pointer';
			td.style.fontStyle ='italic';
			td.style.color = "#808080";
			td.colSpan = 2;
			td.onclick = function() {
				window.top.require("popup_window.js",function() {
					var p = new window.top.popup_window('New Contact Point', theme.build_icon("/static/contact/contact_point.png",theme.icons_10.add), "");
					var frame;
					if (org.id == -1) {
						frame = p.setContentFrame("/dynamic/people/page/popup_create_people?types=contact_"+org.creator+"&donotcreate=contact_point_created");
					} else {
						frame = p.setContentFrame(
							"/dynamic/people/page/popup_create_people?types=contact_"+org.creator+"&ondone=contact_point_created",
							null,
							{
								fixed_columns: [
								  {table:"ContactPoint",column:"organization",value:org.id}
								]
							}
						);
					}
					frame.contact_point_created = function(peoples) {
						var paths = peoples[0];
						var people_path = null;
						var contact_path = null;
						for (var i = 0; i < paths.length; ++i)
							if (paths[i].path == "People") people_path = paths[i];
							else if (paths[i].path == "People<<ContactPoint(people)") contact_path = paths[i];
						
						var people_id = people_path.key;
						var first_name = "", last_name = "";
						for (var i = 0; i < people_path.value.length; ++i)
							if (people_path.value[i].name == "First Name") first_name = people_path.value[i].value;
							else if (people_path.value[i].name == "Last Name") last_name = people_path.value[i].value;
						var designation = contact_path.value;
						var point = new ContactPoint(people_id, first_name, last_name, designation);
						if (org.id == -1)
							point.create_people = paths;
						org.contact_points.push(point);
						t._addContactPointRow(point, tbody);
						t.onchange.fire();
						t.oncontactpointchange.fire();
						p.close();
						layout.invalidate(tbody);
					};
					p.show();
				});
			};
		}
		
		for (var i = 0; i < org.contact_points.length; ++i)
			t._addContactPointRow(org.contact_points[i], tbody);
		
		// map
		td_map.style.verticalAlign = "top";
		var map_container = document.createElement("DIV");
		map_container.style.width = "350px";
		map_container.style.height = "250px";
		//map_container.style.position = "absolute";
		//map_container.style.visibility = "hidden";
		//map_container.style.top = "-1000px";
		td_map.appendChild(map_container);
		t.map = null;
		var markers = [];
		var update_map = function() {
			for (var i = 0; i < markers.length; ++i)
				t.map.removeShape(markers[i]);
			markers = [];
			var list = t._addresses_widget.getAddresses();
			if (list.length > 0) {
				for (var i = 0; i < list.length; ++i) {
					if (list[i].lat && list[i].lng)
						markers.push(t.map.addMarker(parseFloat(list[i].lat), parseFloat(list[i].lng), 1, list[i].address_type));
				}
				t.map.fitToShapes();
				//map_container.style.position = "static";
				//map_container.style.visibility = "visible";
			} else {
				//map_container.style.position = "absolute";
				//map_container.style.visibility = "hidden";
				//map_container.style.top = "-1000px";
			}
		};
		var link_map_to_addresses = function() {
			if (!t._addresses_widget) {
				setTimeout(link_map_to_addresses, 100);
				return;
			}
			update_map();
			t._addresses_widget.onchange.add_listener(update_map);
		};
		window.top.google.loadGoogleMap(map_container, function(m) {
			t.map = m;
			window.top.geography.getCountry(window.top.default_country_id, function(country) {
				if (country && country.north)
					t.map.fitToBounds(parseFloat(country.south), parseFloat(country.west), parseFloat(country.north), parseFloat(country.east));
				link_map_to_addresses();
			});
		});
		
		// google
		var google_title = document.createElement("DIV");
		container.appendChild(google_title);
		google_title.innerHTML = "<img src='/static/google/google.png' style='vertical-align:middle'/> Search and Import from Google: ";
		t.google_search_input = document.createElement("INPUT");
		t.google_search_input.type = "text";
		t.google_search_input.value = org.name;
		require("input_utils.js", function() {
			inputAutoresize(t.google_search_input, 15);
		});
		google_title.appendChild(t.google_search_input);
		google_title.style.borderTop = "1px solid #808080";
		google_title.style.borderBottom = "1px solid #808080";
		google_title.style.padding = "3px";
		google_title.style.backgroundColor = "#D0F0D0";
		var search_button = document.createElement("BUTTON");
		search_button.innerHTML = "Search";
		search_button.marginLeft = "10px";
		google_title.appendChild(search_button);
		search_button.onclick = function() {
			this.disabled = "disabled";
			var b=this;
			t.searchGoogle(function(){
				b.disabled = "";
			});
		};
		google_title.appendChild(document.createElement("BR"));
		var note = document.createElement("I");
		note.innerHTML = "Tip: if you don't find in Google just with the name of the organization, try to add the location (i.e. xxx, Paris)";
		google_title.appendChild(note);
		t._google_results_container = document.createElement("DIV");
		container.appendChild(t._google_results_container);
		t._google_results_container.style.backgroundColor = "white";
		t._google_results_container.style.textAlign = "left";
		t._google_results_container.style.maxWidth = "600px";
		t._google_results_container.style.maxHeight = "250px";
		t._google_results_container.style.overflowY = "auto";
		
		layout.invalidate(container);
	};
	/**
	 * Add a contact point to the table
	 * @param {ContactPoint} point the contact point to add
	 * @param {Element} tbody the table where to put it
	 */
	this._addContactPointRow = function(point, tbody) {
		var tr, td_design, td;
		tbody.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td_design = document.createElement("TD"));
		t._contact_points_rows.push(tr);
		tr.people_id = point.people_id;
		if (org.id != -1) {
			require("editable_cell.js",function() {
				new editable_cell(td_design, "ContactPoint", "designation", {organization:org.id,people:point.people_id}, "field_text", {min_length:1,max_length:100,can_be_null:false}, point.designation, function(new_data){
					point.designation = new_data;
					t.onchange.fire();
					return new_data;
				}, null, null);
			});
		} else {
			require([["typed_field.js","field_text.js"]], function() {
				var f = new field_text(point.designation, true, {min_length:1,max_length:100,can_be_null:false});
				f.onchange.add_listener(function() {
					point.designation = f.getCurrentData();
					t.onchange.fire();
				});
			});
		}
		tr.appendChild(td = document.createElement("TD"));
		td.style.whiteSpace = 'nowrap';
		var link = document.createElement("BUTTON");
		link.className = "flat";
		link.innerHTML = "<img src='/static/people/profile_16.png'/>";
		link.style.verticalAlign = "center";
		link.title = "See profile";
		link.people_id = point.people_id;
		link.onclick = function(){
			window.top.popup_frame("/static/people/people_16.png", "People Profile", "/dynamic/people/page/profile?plugin=people&people="+this.people_id);
		};
		var first_name = document.createTextNode(point.first_name);
		var last_name = document.createTextNode(point.last_name);
		td.appendChild(first_name);
		td.appendChild(document.createTextNode(" "));
		td.appendChild(last_name);
		td.appendChild(link);
		window.top.datamodel.registerCellText(window, "People", "first_name", point.people_id, first_name);
		window.top.datamodel.registerCellText(window, "People", "last_name", point.people_id, last_name);
		if(can_edit){
			var remove_button = document.createElement("BUTTON");
			td.appendChild(remove_button);
			remove_button.className = "flat";
			remove_button.innerHTML = "<img src = '"+theme.icons_16.remove+"' style = 'vertical-align:center;'/>";
			remove_button.people_id = point.people_id;
			remove_button.onclick = function(){
				var people_id = this.people_id;
				if(org.id != -1){
					//Remove from db
					service.json("contact","unassign_contact_point",{organization:org.id, people:people_id},function(res){
						if(res){
							//Remove from table
							var index = t._findRowIndexInContactPointsRows(people_id);
							if(index != null){
								//Remove from DOM
								tbody.removeChild(t._contact_points_rows[index]);
								//Remove from t._contact_points_rows
								t._contact_points_rows.splice(index,1);
								t.onchange.fire();
								t.oncontactpointchange.fire();
							}
						}
					});
				} else {
					//Remove from table
					var index = t._findRowIndexInContactPointsRows(people_id);
					if(index != null){
						//Remove from DOM
						tbody.removeChild(t._contact_points_rows[index]);
						//Remove from t._contact_points_rows
						t._contact_points_rows.splice(index,1);
						t.onchange.fire();
						t.oncontactpointchange.fire();
					}
				}
				
			};
		}
	};
	
	t._findRowIndexInContactPointsRows = function(people_id){
		for(var i = 0; i < t._contact_points_rows.length; i++){
			if(t._contact_points_rows[i].people_id == people_id)
				return i;
		}
		return null;
	};
	
	t.searchGoogle = function(ondone) {
		if (org.name.length < 3) return;
		t._google_results_container.innerHTML = "<img src='"+theme.icons_16.loading+"'/>";
		layout.invalidate(container);
		require("google_places.js", function() {
			getGooglePlaces(t.google_search_input.value, function(results,error) {
				if (error != null) {
					t._google_results_container.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+error;
					layout.invalidate(container);
				} else {
					var ul = document.createElement("UL");
					for (var i = 0; i < results.length; ++i) {
						var li = document.createElement("LI");
						ul.appendChild(li);
						var link = document.createElement("A");
						link.appendChild(document.createTextNode(results[i].name));
						link.href = '#';
						li.appendChild(link);
						link._google_ref = results[i].reference;
						link._google_pos = results[i].geometry && results[i].geometry.location ? results[i].geometry.location : null;
						link.marker = null;
						link.onmouseover = function() {
							if (!t.map) return;
							if (this.marker) return;
							if (!this._google_pos) return;
							this.marker = t.map.addMarker(this._google_pos.lat(), this._google_pos.lng(), 1);
							t.map.zoomOnShape(this.marker);
						};
						link.onmouseout = function () {
							if (!this.marker) return;
							t.map.removeShape(this.marker);
							t.map.fitToShapes();
							this.marker = null;
						};
						link.onclick = function() {
							window.top.geography.getCountry(window.top.default_country_id, function(){});
							window.top.geography.getCountryData(window.top.default_country_id, function(){});
							require("contact_objects.js");
							var locker = lock_screen(null, "Importing data from Google...");
							getGooglePlaceDetails(this._google_ref, function(place,error) {
								if (place == null) { unlock_screen(locker); return; }
								require("contact_objects.js", function() {
									// add phone
									if (place.formatted_phone_number && place.formatted_phone_number.length > 0) {
										var onlyDigits = function(s) {
											var r = "";
											for (var i = 0; i < s.length; ++i) {
												var c = s.charCodeAt(i);
												if (c >= "0".charCodeAt(0) && c <= "9".charCodeAt(0))
													r += s.charAt(i);
											}
											return r;
										};
										var num = onlyDigits(place.formatted_phone_number);
										if (num.length > 0) {
											var found = false;
											var existing = t._contacts_widget.phones.getContacts();
											for (var i = 0; i < existing.length; ++i)
												if (onlyDigits(existing[i].contact) == num) { found = true; break; }
											if (!found) {
												var phone = new Contact(-1, "phone", "Office", place.formatted_phone_number);
												t._contacts_widget.phones.addContact(phone);
											}
										}
									}

									// add address
									window.top.geography.getCountry(window.top.default_country_id, function(country){
										window.top.geography.getCountryData(window.top.default_country_id, function(country_data){
											var the_end = function() {
												unlock_screen(locker);
												layout.invalidate(container);
											};
											
											var a = new PostalAddress(-1,window.top.default_country_id, null, null, null, null, null, null, "Office");
											// google information are not often very accurate, except geographic coordinates
											// we will do it in several step
											if (!place.geometry || !place.geometry.location) { the_end(); return; }
											a.lat = place.geometry.location.lat();
											a.lng = place.geometry.location.lng();
											// 1- get the components from the formatted address
											var components = place.formatted_address.split(",");
											for (var i = 0; i < components.length; ++i)
												components[i] = components[i].trim();
											if (components.length > 0) {
												// remove the country name
												if (components[components.length-1].isSame(country.country_name))
													components.splice(components.length-1,1);
											}
											// go division by division
											var areas = [country];
											var parents_ids = null;
											var components_found = [];
											var last_matching = null;
											var last_matching_division = -1;
											for (var division_index = 0; division_index < country_data.length; division_index++) {
												// get the sub areas containing the position
												sub_areas = window.top.geography.searchAreasByCoordinates(country_data, division_index, a.lat, a.lng, parents_ids);
												// check if we have those sub areas in the components
												var matching = [];
												for (var i = components.length-1; i >= 0; --i) {
													var match = [];
													for (var j = 0; j < sub_areas.length; ++j)
														if (components[i].isSame(sub_areas[j].area_name))
															match.push(sub_areas[j]);
													if (match.length == 0)
														for (var j = 0; j < sub_areas.length; ++j)
															if (almostMatching(components[i], sub_areas[j].area_name))
																match.push(sub_areas[j]);
													if (match.length > 0) {
														// ok, found it, remove the components and reduce the scope of the areas
														last_matching = [].concat(match);
														last_matching_division = division_index;
														while (components.length > i) {
															components_found.push(components[components.length-1]);
															components.splice(components.length-1,1);
														}
														matching = match;
														break;
													}
												}
												if (matching.length > 0) {
													// scope reduces to matching components
													areas = matching;
													parents_ids = [];
													for (var i = 0; i < areas.length; ++i) parents_ids.push(areas[i].area_id);
												} else {
													// scope is all the areas matching the coordinates so far
													var list = [];
													if (parents_ids === null)
														for (var i = 0; i < country_data[0].areas.length; ++i) list.push(country_data[0].areas[i]);
													else
														for (var i = 0; i < country_data[division_index].areas.length; ++i) if (parents_ids.contains(country_data[division_index].areas[i].area_parent_id)) list.push(country_data[division_index].areas[i]);
													areas = list;
													parents_ids = [];
													for (var i = 0; i < areas.length; ++i) parents_ids.push(areas[i].area_id);
												}
											}
											// we have a final list of areas matching the geographic coordinates, together with the components names
											if (areas.length == 0) {
												// nothing matching...
												if (last_matching != null && last_matching.length == 1)
													a.geographic_area = window.top.geography.getGeographicAreaText(country_data, last_matching[0]);
											} else if (areas.length == 1) {
												// we have a single area matching, this is the one !
												a.geographic_area = window.top.geography.getGeographicAreaText(country_data, areas[0]);
											} else {
												// try to finalize only with the components' names
												var division_index = country_data.length-2;
												while (areas.length > 1) {
													for (var i = components.length-1; i >= 0; --i) {
														var match = [];
														for (var j = 0; j < areas.length; ++j)
															if (components[i].isSame(areas[j].area_name))
																match.push(areas[j]);
														if (match.length == 0)
															for (var j = 0; j < areas.length; ++j)
																if (almostMatching(components[i], areas[j].area_name))
																	match.push(areas[j]);
														if (match.length > 0) {
															// ok, found it, remove the components and reduce the scope of the areas
															while (components.length > i) {
																components_found.push(components[components.length-1]);
																components.splice(components.length-1,1);
															}
															areas = match;
															break;
														}
													}
													if (areas.length == 1) {
														a.geographic_area = window.top.geography.getGeographicAreaText(country_data, areas[0]);
														break;
													}
													if (division_index < 0) break;
													var parents = [];
													for (var i = 0; i < areas.length; ++i) {
														var p = window.top.geography.getParentArea(country_data, areas[i]);
														var found = false;
														for (var j = 0; j < parents.length; ++j) if (parents[j] == p) { found = true; break; }
														if (!found) parents.push(p);
													}
													areas = parents;
													if (areas.length == 1) {
														a.geographic_area = window.top.geography.getGeographicAreaText(country_data, areas[0]);
														break;
													}
												}
												if (areas.length == 1) {
													a.geographic_area = window.top.geography.getGeographicAreaText(country_data, areas[0]);
												}
											}
											var areas_names = [];
											if (a.geographic_area.id > 0) {
												areas_names = a.geographic_area.text.split(",");
												for (var i = 0; i < areas_names.length; ++i)
													areas_names[i] = areas_names[i].trim();
											}
											// check in the address components given by google
											var clean_google_info = function(s) {
												var list = s.split(",");
												for (var i = 0; i < list.length; ++i) list[i] = list[i].trim();
												for (var i = 0; i < list.length; ++i) {
													var found = false;
													for (var j = 0; j < areas_names.length; ++j)
														if (areas_names[j].isSame(list[i])) { found = true; break; }
													if (!found)
														for (var j = 0; j < components_found.length; ++j)
															if (components_found[j].isSame(list[i])) { found = true; break; }
													if (found) {
														while (list.length > i) list.splice(list.length-1,1);
														break;
													}
												}
												if (list.length == 0) return "";
												return list.join(", ");
											};
											for (var i = 0; i < place.address_components.length; ++i) {
												var ac = place.address_components[i];
												if (ac.types.contains("street_number")) {
													var s = clean_google_info(ac.long_name);
													if (s != "")
														a.street_number = s;
												}
												if (ac.types.contains("route")) {
													var s = clean_google_info(ac.long_name);
													if (s != "")
														a.street = s;
												}
											}
											// put the remaining components in additional info
											for (var i = 0; i < components.length; ++i)
												if ((a.street_number && components[i].isSame(a.street_number)) || (a.street && components[i].isSame(a.street))) {
													components.splice(i,1);
													i--;
												}
											if (components.length > 0)
												a.additional = components.join(", ");
											// finally, add the address
											// TODO first check it does not exist yet
											if (a.geographic_area.id > 0 || a.street_number != null || a.street != null) {
												if (a.geographic_area.id > 0) {
													var existings = t._addresses_widget.getAddresses();
													var same = [];
													for (var i = 0; i < existings.length; ++i) {
														if (existings[i].geographic_area.id > 0) {
															if (existings[i].geographic_area.id == a.geographic_area.id)
																same.push(existings[i]);
															else {
																var path = [];
																var p = window.top.geography.searchArea(country_data, existings[i].geographic_area.id);
																p = window.top.geography.getParentArea(country_data, p);
																while (p != null) {
																	path.push(p.area_id);
																	p = window.top.geography.getParentArea(country_data, p);
																}
																if (path.length > 0) {
																	var path2 = [];
																	p = window.top.geography.searchArea(country_data, a.geographic_area.id);
																	p = window.top.geography.getParentArea(country_data, p);
																	while (p != null) {
																		path2.push(p.area_id);
																		p = window.top.geography.getParentArea(country_data, p);
																	}
																	if (path2.length > 0) {
																		for (var j = 0; j < path.length; ++j)
																			if (path2.contains(path[j])) { same.push(existings[i]); break; }
																	}
																}
															}
														}
													}
													if (same.length > 0) {
														var msg = "The new address from Google in "+a.geographic_area.text+" is similar to ";
														var choices = ["Yes, create a new address","No, do not import this address"];
														if (same.length == 1) {
															msg += "the existing address in "+same[0].geographic_area.text+"<br/>";
															choices.push("Replace the existing one");
														} else {
															msg += "the following existing addresses:<ul>";
															for (var i = 0; i < same.length; ++i)
																msg += "<li>"+same[i].geographic_area.text+"</li>";
															msg += "</ul>";
														}
														msg += "Do you want to import it ?";
														choice_buttons_dialog(msg, choices, function(choice_index) {
															if (choice_index == 1) { the_end(); return; } // No
															if (choice_index == 0) {
																// Yes
																t._addresses_widget.addAddress(a,false);
																the_end();
																return;
															}
															// Replace
															t._addresses_widget.removeAddress(same[0]);
															t._addresses_widget.addAddress(a,false);
															the_end();
														});
														return;
													}
												}
												t._addresses_widget.addAddress(a,false);
											}
											the_end();
										});
									});
								});
							});
							return false;
						};
						li.appendChild(document.createTextNode(" ("+results[i].formatted_address+")"));
					}
					t._google_results_container.removeAllChildren();
					t._google_results_container.appendChild(ul);
				}
				layout.invalidate(t._google_results_container);
				ondone();
			});
		});
	};
	
	this._init();
}