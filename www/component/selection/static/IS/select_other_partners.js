function select_other_partners(container, all_partners, partners_contacts_points, can_manage, host_id, update_host_custom_event){
	if(typeof container == "string")
		container = document.getElementById(container);
	var t = this;
	t.partners = [];
	t.host = host_id;
	t.reset = function(locker){
		//Launch process
		t._init();
		if(locker)
			unlock_screen(locker);
		layout.invalidate(t.section.element);
	};
	
	t.getOtherPartners = function(){
		return t.partners;
	};
	
	t._init = function(){
		t._refreshBody();
		if(can_manage)
			t._refreshFooter();
	};
	
	t._refreshBody = function(){
		t._refreshElement(t.container_of_section_content);
		var body = document.createElement("table");
		if(t.partners.length == 0){
			//Create an information row
			var tr = document.createElement("tr");
			var td = document.createElement("td");
			td.appendChild(document.createTextNode("No additional partner"));
			td.style.fontStyle = 'italic';
			tr.appendChild(td);
			body.appendChild(tr);
		} else {
			//Create a row for each partner but the host
			for(var i = 0; i < t.partners.length; i++){
				var tr = document.createElement("tr");
				var td = document.createElement("td");
				var display_header = i == 0 ? true: false; // The header "contact point selected" is only displayed on the first row
				//Set a partner row within the td
				var index_in_contact_points = t._findPartnerIndexInPartners_contacts_points(t.partners[i].organization);
				var row = new create_partner_row(
					td,
					{id:t.partners[i].organization , name:t.partners[i].organization_name},
					t.partners[i].contact_points_selected,
					partners_contacts_points[index_in_contact_points].contact_points,
					can_manage,
					//Give the index in t.partners array to get it when the event listener is called
					i,
					display_header
				);
				//add the events listeners
				row.onupdatecontactpointsselection.add_listener(t._onContactPointSelected);
				tr.appendChild(td);
				body.appendChild(tr);
			}
		}
		t.container_of_section_content.appendChild(body);
	};
	
	t._onContactPointSelected = function(param){
		var new_contact_points_selected = param.contact_points_selected;
		var index = param.additional;
		//Update the t.partners array
		t.partners[index].contact_points_selected = new_contact_points_selected;
		//Reset
		t.reset();
	};
	
	t._refreshFooter = function(){
		t.section.resetToolBottom();
		//Add the select partners button
		var div = document.createElement("div");
		var data_grid = document.createElement("div");
		div.appendChild(document.createTextNode("Manage partners"));
		div.className = "button";		
		div.onclick = function(){
			var pop = new popup_window("Select the partners",theme.icons_16.question,data_grid);
			var url_partners = "";
			if(t.partners.length == 0) url_partners = "&partners=0";
			else{
				var j = 0;
				for(var i = 0; i < t.partners.length; i++){
					url_partners += "&partners["+j+"]="+t.partners[i].organization;
					j++;
				}
			}
			if(t.host != null)
				url_partners += "&host="+t.host;
			var frame = pop.setContentFrame("/dynamic/selection/page/organizations_for_selection?is=true"+url_partners);
			pop.addOkCancelButtons(function(){
				pop.close();
				var locker = lock_screen();
				var win = getIFrameWindow(frame);
				//update the data object
				service.json("selection","IS/get_partners_array",{partners_id:win.selected_partners},function(new_partners){
					if(!new_partners){
						error_dialog("An error occured");
						unlock_screen(locker);
						return;
					} else {
						//match the two partners arrays (in case some contact points were already selected but not saved into the database)
						t.partners = t._matchPartnersArraysAndRemoveHost(new_partners);
						//refresh the partners_contacts_points
						service.json("contact","get_json_contact_points_no_address",{organizations:win.selected_partners},function(r){
							if(!r){
								error_dialog("An error occured");
								unlock_screen(locker);
								return;
							} else {
								partners_contacts_points = r;
								//reset tables
								t.reset(locker);
							}
						});
					}
				});
			});
			pop.show();
		};
		t.section.addToolBottom(div);
	};
	
	t._refreshElement = function(e){
		while(e.firstChild)
			e.removeChild(e.firstChild);
	};
	
	t._setPartnersArray = function(){
		//Get only the partners that are not host
		for(var i = 0; i < all_partners.length; i++){
			if(all_partners[i].host == false){
				var partner = new ISPartner(
						all_partners[i].organization,
						all_partners[i].organization_name,
						false,
						null,
						all_partners[i].contact_points_selected
				);
				t.partners.push(partner);
			}
		}
	};
	
	t._matchPartnersArraysAndRemoveHost = function(new_array){
		//Match the contact points
		for(var i = 0; i < new_array.length; i++){
			var index = t._findPartnerIndex(new_array[i].organization);
			if(index != null){
				new_array[i].contact_points_selected = t.partners[index].contact_points_selected;
			}
		}
		if(t.host != null){
			//Remove the host if it exists in new_array
			for(var i = 0; i < new_array.length; i++){
				if(new_array[i].organization == t.host){
					new_array.splice(i,1);
				}
			}
		}
		return new_array;
	};
	
	t._findPartnerIndex = function(id){
		for(var i = 0; i < t.partners.length; i++){
			if(t.partners[i].organization == id)
				return i;
		}
		return null;
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
	
	t._updateHostAttribute = function(new_host_id){
		t.host = new_host_id;
		//If the host was in t.partners array, remove it
		var index = t._findPartnerIndex(t.host);
		if(index != null){
			//Remove from t.partners
			t.partners.splice(index,1);
			//Reset
			t.reset();
		}
	};
	
	require(["create_partner_row.js","popup_window.js","section.js","IS_objects.js"],function(){
		//Set the section
		t.container_of_section_content = document.createElement("div");
		t.section = new section("/static/contact/directory_16.png","Other partners",t.container_of_section_content,true);
		container.appendChild(t.section.element);
		//Set partners array
		t._setPartnersArray();
		//Set the listener for the host updates
		if(update_host_custom_event)
			update_host_custom_event.add_listener(t._updateHostAttribute);
		//Launch process
		t._init();
	});
}