/**
 * Create a table containing partners list. A row is created for each partner, contaning the name of the organization, and the selected contact points (if any)
 * This table is created into a section object
 * @param {String | HTMLElement} container
 * @param {ISPartner} all_partners the partners that shall be displayed in the table
 * @param {Array} partners_contacts_points same as returned by contact#service#get_json_contact_points_no_address method
 * @param {Boolean} can_manage
 * @param {NULL | Number} host_id null if no host selected when instanciated, else, value of the host ID
 * @param {Function} update_host_custom_event custom event fired when an external function updates the host_id
 * @param {Function} oncontactpointchange_listener listener fired in the case of the contacts points of a partner are updated from the organization profile
 * @param {String} mode, the way this page is used. Can be "information_session","exam_center". this is used to use the appropriated services/partners objects
 */
function select_other_partners(container, all_partners, partners_contacts_points, can_manage, host_id, update_host_custom_event,oncontactpointchange_listener,mode){
	if(!mode) return;
	if(typeof container == "string")
		container = document.getElementById(container);
	var t = this;
	t.partners = [];
	t.host = host_id;
	
	/**
	 * Reset the table, and fire the layout event
	 * @param {HTMLElement | NULL} (optional) locker any screen locker to remove
	 * @param {Array}(optional) partners_contacts_points same as returned by contact#service#get_json_contact_points_no_address method
	 */
	t.reset = function(locker,new_partners_contact_points){
		if(new_partners_contact_points)
			partners_contacts_points = new_partners_contact_points;
		//Launch process
		t._init();
		if(locker)
			unlock_screen(locker);
		layout.invalidate(t.section.element);
	};
	
	/**
	 * Get the other partners attribute
	 * @returns {array} other_partners, the updated all_partners array, contaning all the partners but the host
	 */
	t.getOtherPartners = function(){
		return t.partners;
	};
	
	/** Private methods */
	
	/**
	 * Launch the process. The footer is set only if the user can manage this page
	 */
	t._init = function(){
		t._refreshBody();
		if(can_manage)
			t._refreshFooter();
	};
	
	/**
	 * Reset and populate the body of the table
	 * Each other partner row is made of create_partner_row objects
	 */
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
					display_header,
					null,
					oncontactpointchange_listener
				);
				//add the events listeners
				row.onupdatecontactpointsselection.add_listener(t._onContactPointSelected);
				tr.appendChild(td);
				body.appendChild(tr);
			}
		}
		t.container_of_section_content.appendChild(body);
	};
	
	/**
	 * Listener fired when the contact points of an organization are updated
	 * The partners attribute is updated and the table is reseted
	 * @param {object} containing two attributes: <ul><li><code>contact_points_selected</code> array containing the ids of the contacts points selected</li><li><code>index</code> the partner index in the other partners array</li></ul>
	 */
	t._onContactPointSelected = function(param){
		var new_contact_points_selected = param.contact_points_selected;
		var index = param.additional;
		//Update the t.partners array
		t.partners[index].contact_points_selected = new_contact_points_selected;
		//Reset
		t.reset();
	};
	
	/**
	 * Reset and populate the footer of the table
	 * This method creates a button in the section footer, button pushed to pick the partners into the selection partners list
	 */
	t._refreshFooter = function(){
		t.section.resetToolBottom();
		//Add the select partners button
		var div = document.createElement("div");
		var data_grid = document.createElement("div");
		div.appendChild(document.createTextNode("Pick partners"));
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
			var type_partner_selection;
			if(mode == "information_session")
				type_partner_selection = "is";
			else if (mode == "exam_center")
				type_partner_selection = "ec";
			var frame = pop.setContentFrame("/dynamic/selection/page/organizations_for_selection?"+type_partner_selection+"=true"+url_partners);
			pop.addOkCancelButtons(function(){
				pop.close();
				var locker = lock_screen();
				var win = getIFrameWindow(frame);
				var service_get_partners_array_url;
				if(mode == "information_session")
					service_get_partners_array_url = "IS/get_partners_array";
				else if (mode == "exam_center")
					service_get_partners_array_url = "exam/get_center_partners_array";
				//update the data object
				service.json("selection",service_get_partners_array_url,{partners_id:win.selected_partners},function(new_partners){
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
	
	/**
	 * Remove all the children of a given element
	 * @param {HTMLElement} e teh element to refresh
	 */
	t._refreshElement = function(e){
		while(e.firstChild)
			e.removeChild(e.firstChild);
	};
	
	/**
	 * Set the partners attribute, starting from the all_partners array.
	 * Create an array containing all the partners elements (organizations linked) but the host
	 */
	t._setPartnersArray = function(){
		//Get only the partners that are not host
		for(var i = 0; i < all_partners.length; i++){
			if(all_partners[i].host == false){
				if(mode == "information_session"){
					require("IS_objects.js",function(){
						var partner = new ISPartner(
								all_partners[i].organization,
								all_partners[i].organization_name,
								false,
								null,
								all_partners[i].contact_points_selected
						);
						t.partners.push(partner);
					});
				} else if (mode == "exam_center"){
					require("exam_objects.js",function(){
						var partner = new ExamCenterPartner(
								all_partners[i].organization,
								all_partners[i].organization_name,
								false,
								null,
								all_partners[i].contact_points_selected
						);
						t.partners.push(partner);
					});
				}
			}
		}
	};
	
	/**
	 * Method called to update the partners array attribute after selecting the partners
	 * The selection returns an array of organizations IDs, so need to update the partners attribute, keeping the contacts points selected previously for the organizations that were kept by the selection
	 * @param {Array} containing the selected organizations IDs
	 * @returns {ISPartners|ExamCenterPartners} updated partners attribute
	 */
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
	
	/**
	 * Find a partner index within the partners attribute, from its ID
	 * @param {Number} id the id of the seeked partner
	 * @returns {Number | NULL} null if not found, else the index of the partner
	 */
	t._findPartnerIndex = function(id){
		for(var i = 0; i < t.partners.length; i++){
			if(t.partners[i].organization == id)
				return i;
		}
		return null;
	};
	
	/**
	 * find a partner index within the partners_contacts_points array, from its ID
	 * @param {Number} id the id of the seeked partner
	 * @returns {Number | NULL} null if not found, else the index of the partner
	 */
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
	
	/**
	 * Update the host attribute from its ID
	 * If the new host was in the partners attribute, the matching partner is removed from the partners array and the table is reseted
	 * @param {Number} new_host_id
	 */
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
	
	require(["create_partner_row.js","popup_window.js","section.js"],function(){
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