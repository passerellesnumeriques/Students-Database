function select_address_2(container, data, partners_contacts_points, can_manage){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	
	t.reset = function(){
		t._refreshTableHeader();
		t._refreshTableBody();
		t._refreshTableFooter();
		layout.invalidate(container);
	};
	
	t._init = function(){
		t._table = document.createElement("table");//The table is splitted into three parts, independants, to be able to seperate the styles
		t._tbody = document.createElement("tbody");//Contains the host detail
		t._thead = document.createElement("thead");//Contains the address / geographic area
		t._tfoot = document.createElement("tfoot");//Contains the buttons
		t._table.appendChild(t._thead);
		t._table.appendChild(t._tbody);
		t._table.appendChild(t._tfoot);
		t.container_of_section_content.appendChild(t._table);
		t._refreshTableHeader();
		t._refreshTableBody();
		t._refreshTableFooter();
	};
	
	t._refreshTableHeader = function(){
		t._refreshElement(t._thead);
		var tr = document.createElement("tr");
		var td = document.createElement("td");
		tr.appendChild(td);
		t._thead.appendChild(tr);
		if(t._getInternStep() == "area"){
			//Only an area is set, so no host
			/*Get the current domain country id */
			window.top.require("geography.js", function() {
				window.top.geography.getCountries(function(countries) {
					var country_id = countries[0].country_id;
					service.json("geography","get_area_parents_names",{country:country_id, area_id:data.geographic_area},function(res){
						if(!res)
							td.innerHTML = "<i>This functionality is not available</i>";
						else {
							var div_area = document.createElement("div");
							var div_country = document.createElement("div");
							td.appendChild(div_area);
							td.appendChild(div_country);
							var first = true;
							var text = "";
							for(var i = 0; i < res.area_parents_names.length; i++){
								if(!first)
									text += ", ";
								first = false;
								text += res.area_parents_names[i].uniformFirstLetterCapitalized();
							}
							div_area.appendChild(document.createTextNode(text));
							div_country.appendChild(document.createTextNode(res.country_name));
						}
						layout.invalidate(container);
					});
					
				});
			});		
			
		} else if (t._getInternStep() == "host"){
			//The host and the address is set
			/* get the address object */
			service.json("contact","get_address",{id:t._getHostAddressInPartners()},function(res){
				if(!res)
					td.innerHTML = "<i>This functionality is not available</i>";
				else {
					var text = new address_text(res);
					td.appendChild(text.element);
				}
				layout.invalidate(container);
			});

		} else {
			//Nothing is set
			td.innerHTML = "<i><center>No location is set for this Information Session</i></center>";
		}
	};
	
	t._refreshTableBody = function(){
		t._refreshElement(t._tbody);
		var tr = document.createElement("tr");
		var td = document.createElement("td");
		tr.appendChild(td);
		t._tbody.appendChild(tr);
		if(t._getInternStep() == "area"){
			//Only an area is set, so no host
			td.innerHTML = '<center><i>No host selected</i></center>';
		} else if (t._getInternStep() == "host"){
			//The host and the address is set
			var index = t._getHostIndexInPartners();
			var index_in_contact_points = t._findPartnerIndexInPartners_contacts_points(data.partners[index].organization);
			var row = new create_partner_row(
				td,
				{id:data.partners[index].organization , name:data.partners[index].organization_name},
				data.partners[index].contact_points_selected,
				partners_contacts_points[index_in_contact_points].contact_points,
				can_manage
			);
			//Add the listener for the contact points selection
			row.onupdatecontactpointsselection.add_listener(function(contact_points_selected){
				//Update the data object
				data.partners[t._getHostIndexInPartners()].contact_points_selected = contact_points_selected;
				//Reset the body
				t._refreshTableBody();
			});
		}//Else nothing is set
	};
	
	t._refreshTableFooter = function(){
		if(can_manage){
			t._refreshElement(t._tfoot);
			var tr = document.createElement("tr");
			var td = document.createElement("td");
			td.style.textAlign = "right";
			tr.appendChild(td);
			t._tfoot.appendChild(tr);
			if(t._getInternStep() == "area"){
				//Only an area is set, so no host
				//Create two buttons, one to remove the location, an other one to continue (select a host)
				var continue_button = document.createElement("div");
				continue_button.className = "button";
				continue_button.appendChild(document.createTextNode("Continue"));
				continue_button.title = "Select a host partner";
				continue_button.onclick = function(){
					new pop_select_area_and_partner(
						data.geographic_area,
						null,
						null,
						null,
						function(area, host_id, host_address, host_name){
							//update geographic area
							data.geographic_area = area;
							if(host_id != null){
								//Update the new host data
								var index = t._findPartnerIndexInPartners(host_id);
								if(index == null){
									//The partner doesn't exist yet in data.partners array
									index = data.partners.length;
									data.partners.push({
										organization:host_id,
										organization_name:host_name,
										contact_points_selected:[]
									});
								}
								data.partners[index].host = true;
								data.partners[index].host_address = host_address;
								//update the contact points array, and reset once it is done
								t._updateAllContactsPointsIfNeeded(host_id, t.reset, t.reset);
							} else
								t.reset();
							//Else nothing to do because there were no host before
							//No need to update the area because a reference of data is given to the pop_select_area_and_partner object so will be automatically updated
							//Reset (even if no host is set, geographic area may have been updated
							
						}
					);
				};
				var remove_button = document.createElement("div");
				remove_button.className = "button";
				remove_button.innerHTML = "<img src = '"+theme.icons_16.remove+"'/> Reset location";
				remove_button.title = "Reset the location and restart from scratch";
				remove_button.onclick = function(){
					//Reset the geographic area attribute
					data.geographic_area = null;
					//No need to reset the host because there were no before
					//Reset
					t.reset();
				};
				td.appendChild(continue_button);
				td.appendChild(remove_button);
			} else if (t._getInternStep() == "host"){
				//The host and the address is set
				//Add a remove host button and an update host button
				var remove_host = document.createElement("div");
				remove_host.className = "button";
				remove_host.innerHTML = "<img src = '"+theme.icons_16.remove+"'/> Remove this host";
				remove_host.title = "Remove the host, but keep the geographic area";
				remove_host.onclick = function(){
					//Reset the host
					t._resetHost();
					//The geographic_area attribute is not reseted, this way the user doesn t restart from scratch
					//Reset
					t.reset();
				};
				
				var update_host = document.createElement("div");
				update_host.className = 'button';
				update_host.appendChild(document.createTextNode("Update host"));
				update_host.title = "Update the host partner";
				update_host.onclick = function(){
					var index = t._getHostIndexInPartners();
					new pop_select_area_and_partner(
						data.geographic_area,
						data.partners[index].organization,
						data.partners[index].host_address,
						data.partners[index].organization_name,
						function(area, host_id,host_address, host_name){
							//update geographic area
							data.geographic_area = area;
							if(host_id != null){
								//Reset the host attribute
								t._resetHost();
								//Set the new one
								var index = t._findPartnerIndexInPartners(host_id);
								if(index == null){
									//The partner doesn't exist yet in data.partners array
									index = data.partners.length;
									data.partners.push({
										organization:host_id,
										organization_name:host_name,
										contact_points_selected:[]
									});
								}
								data.partners[index].host = true;
								data.partners[index].host_address = host_address;
								//update the contact points array, and reset once it is done
								t._updateAllContactsPointsIfNeeded(host_id, t.reset, t.reset);
							} else {
								//Reset the host attribute because it was already set
								t._resetHost();
								//Reset
								t.reset();
							}
						}
					);
				};
				td.appendChild(remove_host);
				td.appendChild(update_host);
			} else {
				//Nothing is set, so just add a set location button
				var set_button = document.createElement("div");
				set_button.className = "button";
				set_button.appendChild(document.createTextNode("Set a location"));
				set_button.title = "Set a location for this information session";
				set_button.onclick = function(){
					new pop_select_area_and_partner(
						data.geographic_area,
						null,
						null,
						null,
						function(area, host_id, host_address, host_name){
							//update geographic area
							data.geographic_area = area;
							if(host_id != null){
								var index = t._findPartnerIndexInPartners(host_id);
								if(index == null){
									//The partner doesn't exist yet in data.partners array
									index = data.partners.length;
									data.partners.push({
										organization:host_id,
										organization_name:host_name,
										contact_points_selected:[]
									});
								}
								data.partners[index].host = true;
								data.partners[index].host_address = host_address;
								//update the contact points array, and reset once it is done
								t._updateAllContactsPointsIfNeeded(host_id, t.reset, t.reset);
							} else
							//Reset
							t.reset();
						}
					);
				};
				td.appendChild(set_button);
			}
		}
	};
	
	t._updateAllContactsPointsIfNeeded = function(new_host, onupdate, onnoupdate){
		//The host set may not be in the AllContactsPoints Array
		if(t._findPartnerIndexInPartners_contacts_points(new_host) != null){
			if(onnoupdate)
				onnoupdate();
			return;
		}
		//Not found, so need to update the partners_contact_points array
		var partners = [];
		for(var i = 0; i < data.partners.length; i++){
			partners.push(data.partners[i].organization);
		}
		service.json("contact","get_json_contact_points_no_address",{organizations:partners, contacts_details:false},function(res){
			if(!res)
				error_dialog("An error occured");
			else
				partners_contacts_points = res;
			if(onupdate)
				onupdate();
		});
	};
	
	t._resetHost = function(){
		var index = t._getHostIndexInPartners();
		if(index == null)
			return;
		//Remove the host from partners array
		data.partners.splice(index,1);
//		data.partners[index].host = false;
//		data.partners[index].host_address = null;
	};
	
	t._refreshElement = function(e){
		while(e.firstChild)
			e.removeChild(e.firstChild);
	};
	
	t._findPartnerIndexInPartners_contacts_points = function(partner_id){
		var index = null;
		for(var i = 0; i <  partners_contacts_points.length; i++){
			if(partner_id ==  partners_contacts_points[i].organization){
				index = i;
				break;
			}
		}
		return index;
	};
	
	t._findPartnerIndexInPartners = function(id){
		for(var i = 0; i < data.partners.length; i++){
			if(data.partners[i].organization == id)
				return i;
		}
		return null;
	};
	
	/**
	 * Get the host_address from data.partners array
	 * @returns {Number | Null} null if the host is not set yet, else host_address (address ID selected)
	 */
	t._getHostAddressInPartners = function(){
		var i = t._getHostIndexInPartners();
		var to_return = i == null ? null : data.partners[i].host_address;
		return to_return;
	};
	
	t._getHostIndexInPartners = function(){
		for(var i = 0; i < data.partners.length; i++){
			if(data.partners[i].host ==  true)
				return i;
		}
		return null;
	}
	
	t._getInternStep = function(){
		if(t._getHostAddressInPartners() != null)
			return "host";
		else if(t._getHostAddressInPartners() == null && data.geographic_area != null)
			return "area";
		else
			return null;
	};
	
	require(["popup_window.js","address_text.js","edit_address.js","section.js","contact_objects.js","create_partner_row.js","pop_select_area_and_partner.js"],function(){
		t.address = new PostalAddress(null, null, null, null, null, null, null, null);
		t.container_of_section_content = document.createElement("div");
		t.section = new section("/static/contact/address_16.png","Location",t.container_of_section_content,false);
		container.appendChild(t.section.element);
		t._init();
	});
}