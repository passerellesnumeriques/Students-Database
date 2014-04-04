/**
 * Populate the exam center caracteristics section on the center_profile page
 * The section is populated by location section, name section(depends on the campaign config), rooms section, informations sessions linked section, and other partners section
 * All the data is updated into the database only when the user clicks on save button 
 * @param {Number} id exam center ID
 * @param {Boolean} config_name_center true if the campaign config is set in order to set custom name to an exam center
 * @param {Boolean} can_add
 * @param {Boolean} can_edit
 * @param {Boolean} can_remove
 * @param {String | HTMLElement}container
 * @param {ExamCenterData} data related to this center
 * @param {ExamCenterPartnersContactPoints} partners_contacts_points
 * @param {Number} campaign_id selection campaign ID
 * @param {String} save_button_id ID of the save button element to override
 * @param {String} remove_button_id ID of the remove button element to override
 * @param {Number} db_lock DataBaseLock for the applicant table
 * @param {Boolean} config_name_room true if the campaign config is set in order to set custom name to an exam center room
 * @param {Custom_Event} onupdateroom event to be fired when the rooms data is updated
 * @param {Custom_Event} onupdateapplicants event to be listened by the rooms section: when the applicants assignment to this center is updated, the room section must be refreshed to update the rights
 */
function exam_center_profile(id, config_name_center, can_add, can_edit, can_remove, container, data,partners_contacts_points,campaign_id,save_button_id,remove_button_id,db_lock,config_name_room,onupdateroom,onupdateapplicants){
	if(typeof(container) == "string") container = document.getElementById(container);
	var t = this;
	if(db_lock)
		t.db_lock = db_lock;
	else
		t.db_lock = null;
	t.table = document.createElement("table");
	t.div_address = document.createElement("div");
	t.div_partners = document.createElement("div");
	t.div_name = document.createElement("div");
	t.div_IS_linked = document.createElement("div");
	t.div_room = document.createElement("div");
	
	/**
	 * Reset all the subsections content
	 * The t._lockDatabase method is called at the end, in the case of this is an exam center creation and that the database was not locked yet
	 * @param {HTMLElement|NULL} locker screen locker to remove, if any
	 * @param {Boolean} update_partners_contact_points true if the partners_contacts_points array must be refreshed into the select_address and select_other_partners objects
	 * @param {ExamCenterRooms | NULL} new_rooms not null if rooms attribute must be updated
	 */
	t.resetAll = function(locker, update_partners_contact_points,new_rooms){
		container.removeChild(t.table);
		delete(t.table);
		t.table = document.createElement("table");
		t._setLayout();
		if(update_partners_contact_points){
			t.select_address.reset(partners_contacts_points);
			t.select_other_partners.reset(null,partners_contacts_points);
		} else {
			t.select_address.reset();
			t.select_other_partners.reset();
		}
		t.manage_rooms.reset(new_rooms);
		if(locker) unlock_screen(locker);
		t._lockDatabase();
	};
	
	/**
	 * Get if the data has been updated since the previous saving performed
	 * @returns {Boolean}
	 */
	t.dataMustBeSaved = function(){
		return t._updated;
	};
	
	/**Private methods and attributes*/
	
	t._rooms_updated = false;
	
	/**
	 * Listener fired when the data is updated
	 * Set the t._updated flag as true
	 */
	t._onDataUpdatedListener = function(){
		window.pnapplication.dataUnsaved("exam_center_profile_"+id);
	};
	
	/**
	 * Listener fired when the data is saved
	 * Set the t._updated flag as false
	 */
	t._onDataSavedListener = function(){
		window.pnapplication.dataSaved("exam_center_profile_"+id);
	};
	
	/**
	 * Update the t._updated flag as false even if the data was not saved
	 */
	t._bypassCheckDataSaved = function(){
		window.pnapplication.dataSaved("exam_center_profile_"+id);
	};
	
	/**
	 * Launch the process
	 */
	t._init = function(){
		t._setAddressField();
		t._setOtherPartnersField();
		if(config_name_center) t._setCustomNameField();
		t._setISLinkedField();
		t._setRooms();
		t._setLayout();
		t._lockDatabase();
		
	};
	
	/**
	 * Create the select_address object
	 */
	t._setAddressField = function(){
		t.select_address = new select_address(t.div_address, data, partners_contacts_points, can_edit,t._onContactsPointsUpdatedFromProfile,t._onAddressesUpdatedFromProfile,"exam center");
		t.select_address.onupdatehost.add_listener(t._onDataUpdatedListener);
	};
	
	/**
	 * Create the select_other_partners object
	 */
	t._setOtherPartnersField = function(){
		//Get host in data.partners array if set
		var host_id = null;
		for(var i = 0; i < data.partners.length; i++){
			if(data.partners[i].host == true){
				host_id = data.partners[i].organization;
				break;
			}
		}
		t.select_other_partners = new select_other_partners(t.div_partners, data.partners, partners_contacts_points, can_edit, host_id,t.select_address.onupdatehost, t._onContactsPointsUpdatedFromProfile,"exam_center");
		t.select_other_partners.onupdate.add_listener(t._onDataUpdatedListener);
	};
	
	/**
	 * Create the custom name field object
	 */
	t._setCustomNameField = function(){
		t.center_name = new IS_ExamCenter_name(t.div_name,data.name,can_edit,"Exam center name");
		t.center_name.onupdate.add_listener(t._onDataUpdatedListener);
	};
	
	/**
	 * Create the manage_exam_center_room object
	 * Add listeners on the rooms updated events
	 * A listener is also added on the onupdateapplicants custom_event to synchronize with the exam center applicants assignment section
	 */
	t._setRooms = function(){
		t.manage_rooms = new manage_exam_center_room(t.div_room,data.rooms,id,can_edit,config_name_room);
		t.manage_rooms.onupdate.add_listener(t._onDataUpdatedListener);
		t.manage_rooms.onupdate.add_listener(function(){t._rooms_updated = true;});
		if(onupdateapplicants)
			onupdateapplicants.add_listener(function(){t.manage_rooms.reset();});
	};
	
	/**
	 * Create the IS_linked field
	 */
	t._setISLinkedField = function(){
		t.IS_linked = new center_IS_link(t.div_IS_linked,data.information_sessions);
	};
	
	/**
	 * Set the layout of the section content
	 * Override the save and remove center buttons
	 */
	t._setLayout = function(){
		t.table.style.marginLeft = "15px";
		container.appendChild(t.table);
		var tr = document.createElement("tr");
		var td1 = document.createElement("td");
		var td2 = document.createElement("td");
		var td3 = document.createElement("td");
		tr.appendChild(td1);
		tr.appendChild(td2);
		tr.appendChild(td3);
		t.table.appendChild(tr);
		td1.appendChild(t.div_address);
		td1.appendChild(t.div_name);
		td1.appendChild(t.div_IS_linked);
		td2.appendChild(t.div_room);
		td3.appendChild(t.div_partners);
		td1.style.verticalAlign = "top";
		td2.style.verticalAlign = "top";
		td3.style.verticalAlign = "top";
		t.div_address.style.paddingBottom = "5px";
		t.div_name.style.paddingTop = "5px";
		t.div_name.style.paddingBottom = "5px";
		t.div_IS_linked.style.paddingTop = "5px";
		t.div_IS_linked.style.paddingBottom = "5px";
		var button_remove = document.getElementById(remove_button_id);
		if(can_remove && data.id != -1 && data.id != "-1"){
			button_remove.style.visibility = 'visible';
			button_remove.style.position = 'static';
			button_remove.onclick = function(){
				//Check that rooms can be removed
				service.json("selection","exam/can_rooms_be_removed_for_center",{EC_id:id},function(res){
					if(!res){
						error_dialog("An error occured, functionality not available");
						return;
					}
					var error_assigned = null;
					var error_capacity = null;
					if(res.rooms != null){
						res = res.rooms;
						for(var i = 0; i < res.length; i++){
							if(res[i].error_applicants != null){
								if(error_assigned == null)
									error_assigned = [];
								error_assigned.push({id:res[i].id, name:res[i].name, applicants:res[i].error_applicants});
							}
							if(res[i].error_capacity != null){
								if(error_capacity == null)
									error_capacity = [];
								error_capacity.push({id: res[i].id, name:res[i].name, sessions:res[i].error_capacity});
							}
						}
					}
					if(error_capacity == null && error_assigned == null)
						t._performRemoveCenter();
					else {
						error_dialog_html(t.manage_rooms.getInfoRow(error_assigned, error_capacity));
					}
				});
			};
		} else {
			button_remove.style.visibility = 'hidden';
			button_remove.style.position = 'absolute';
			button_remove.style.top = '-10000px';
		}
		var button_save = document.getElementById(save_button_id);
		if(can_edit){
			button_save.style.visibility = 'visible';
			button_save.style.position = 'static';
			button_save.onclick = t._launchSaving;
		} else {
			button_save.style.visibility = 'hidden';
			button_save.style.position = 'absolute';
			button_save.style.top = '-10000px';
		}
	};
	
	/**
	 * Calls the remove center service
	 * If well performed, the user is redirected to the center_main_page
	 */
	t._performRemoveCenter = function(){
		t._bypassCheckDataSaved();
		service.json("selection","exam/remove_center",{id:data.id},function(r){
			if(!r){
				error_dialog("An error occured, this center was not removed properly");
				return;
			}
			window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "Exam center succesfully removed!", [{action:"close"}], 5000));
			location.assign("/dynamic/selection/page/exam/center_main_page");
		});
	};
	
	/**
	 * Launch the saving of the exam center caracteristics
	 * Check that an area is set for the center, and then update all the attributes before saving
	 * The on data saved listener is fired after performing the saving
	 */
	t._launchSaving = function(){
		var locker = lock_screen();
		if(data.geographic_area == null){
			unlock_screen(locker);
			error_dialog("You must at least pick a geographic area before saving");
		} else {				
			/** 
			 * Get the data from each screen:
			 * select_address works with the reference of data
			 * so there is no need to update
			 */		
			//get from other_partners
			//remove all the old other partners
			var length = data.partners.length;
			var i = 0;
			var offset = 0;
			while(i < length){
				if(data.partners[offset].host == null || data.partners[offset].host == false){
					data.partners.splice(offset,1);
				} else
					offset++;
				i++;
			}
			//Update, keeping the host
			data.partners = data.partners.concat(t.select_other_partners.getOtherPartners());
			//get from rooms
			data.rooms = t.manage_rooms.getNewRoomsArray();
			// get from name
			var name = null;
			if(config_name_center)
				name = t.center_name.getName();
			if(name != null && typeof name == "string" && name.checkVisible())
				data.name = name;
			else 
				data.name = null;			
			service.json("selection","exam/save_center",{data:data},function(res){
				unlock_screen(locker);
				if(!res)
					error_dialog("An error occured, your informations were not saved");
				else {
					window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "Your informations have been successfuly saved!", [{action:"close"}], 5000));
					// Update the data on the page (some ids have been generated)
					data = res;
					if(t._rooms_updated && onupdateroom)
						onupdateroom.fire();
					t._rooms_updated = false;
					//reset the table (in case remove button must be added, and update the objects)
					t.resetAll(null,null,res.rooms);
					t._onDataSavedListener();
				}
			});
		}
	};
	
	/**
	 * Get a partner index into data.partners array, from its ID
	 * @param {Number} id
	 * @returns {Number|NULL} the index of the seeked partner, or NULL if not found
	 */
	t._findPartnerIndex = function(id){
		for(var i = 0; i < data.partners.length; i++){
			if(data.partners[i].organization == id)
				return i;
		}
		return null;
	};
	
	/**
	 * Find the host index within the data.partners array
	 * @returns {Number|NULL} the index of the host partner, or NULL if not found
	 */
	t._findHostIndex = function(){
		for(var i = 0; i < data.partners.length; i++){
			if(data.partners[i].host == true)
				return i;
		}
		return null;
	};
	
	/**
	 * Find the host address into the data.partners array
	 * @returns {Number|NULL} the address ID, or NULL if the host partner was not found
	 */
	t._getHostAddress = function(){
		var index = t._findHostIndex();
		if(index == null)
			return null;
		return data.partners[index].host_address;
	};
	
	/**
	 * Called after creating this object and saving
	 * If the id != -1 and db_lock attribute == null, this method would
	 * call the lock_row service, update the db_lock attribute and add the lock javascript
	 * Else nothing is done
	 */
	t._lockDatabase = function(){
		if(t.db_lock == null){
			if(data.id != -1 || data.id != "-1")
				service.json("data_model","lock_row",{table:"ExamCenter",row_key:data.id, sub_model:campaign_id},function(res){
					if(res){
						databaselock.addLock(res.lock);
						t.db_lock = res.lock;
					}
				});
		}
	};
	
	/**
	 * Listener fired when the contact points of any partner are updated from the popups showing the organization profile (especially removed, added)
	 */
	t._onContactsPointsUpdatedFromProfile = function(){
		var locker = lock_screen();
		/**This listener is fired only when any contact point is updated into the database
		 * so we just have to update data.partners array and allpartners_contacts_points array
		 */
		//Update all the values
		//remove all the old other partners
		var length = data.partners.length;
		var i = 0;
		var offset = 0;
		while(i < length){
			if(data.partners[offset].host == null || data.partners[offset].host == false){
				data.partners.splice(offset,1);
			} else
				offset++;
			i++;
		}
		//Update, keeping the host
		data.partners = data.partners.concat(t.select_other_partners.getOtherPartners());
		//Get the selected partners ids
		var selected_partners = [];
		for(var i = 0; i < data.partners.length; i++ )
			selected_partners.push(data.partners[i].organization);
		service.json("contact","get_json_contact_points_no_address",{organizations:selected_partners},function(r){
			if(!r){
				error_dialog("You have updated some data about the contact points of this organization, but an error occured and you will not be able to use them on this page<br/>You can save reload to see your updates");
				unlock_screen(locker);
				return;
			} else {
				partners_contacts_points = r;
				//Check that all contact points selected still exist
				var cp_to_check = [];
				for(var i = 0; i < data.partners.length; i++){
					for(var j = 0; j < data.partners[i].contact_points_selected.length; j++){
						cp_to_check.push({partner_index:i, cp:data.partners[i].contact_points_selected[j]});
					}
				}
				if(cp_to_check.length > 0){
					//Check if they still exist in partners_contacts_points
					for(var i = 0; i < cp_to_check.length; i++){
						if(!t._contactPointExist(cp_to_check[i].cp)){
							//remove from the contact point from selected partners
							var index = null;
							for(var j = 0; j < data.partners[cp_to_check[i].partner_index].contact_points_selected.length; j++){
								if(data.partners[cp_to_check[i].partner_index].contact_points_selected[j] == cp_to_check[i].cp){
									index = j;
									break;
								}									
							}
							if(index != null)
								data.partners[cp_to_check[i].partner_index].contact_points_selected.splice(index,1);
						}
					}
				}
				//reset tables
				t.resetAll(locker,true);
			}
		});
	};
	
	/**
	 * Check that the given contact point ID still exists into partners_contacts_points array
	 * @param {Number} cp the contact point ID to be checked
	 * @returns {Boolean} true if the cp is found into partners_contacts_points array
	 */
	t._contactPointExist = function(cp){
		for(var i = 0; i < partners_contacts_points.length; i++){
			for(var j = 0; j < partners_contacts_points[i].contact_points.length; j++){
				if(partners_contacts_points[i].contact_points[j].people_id == cp)
					return true;
			}
		}
		return false;
	};
	
	/**
	 * Listener fired when the addresses of any partner are updated from the popups showing the organization profile (especially removed, added)
	 */
	t._onAddressesUpdatedFromProfile = function(){
		var locker = lock_screen();
		//Check if an host was selected
		var host_address = t._getHostAddress();
		if(host_address == null)//nothing to do
			return;
		//Get the address set into the database
		service.json("contact","get_address",{id:host_address},function(res){
			unlock_screen(locker);
			if(res == false){
				error_dialog("You have updated some data about the addresses of this organization, but an error occured and you will not be able to use them on this page<br/>You can save reload to see your updates");
				return;
			}
			var index = t._findHostIndex();
			if(res == null){
				//address does not exist anymore, so reset the host
				data.partners[index].host = null;
				data.partners[index].host_address = null;
				//reset select_address
				t.select_address.reset();
			} else {
				//address still exist, just refresh in the case of any updates occured
				t.select_address.reset();
			}
		});
	};
	
	require(["popup_window.js",
	         "select_address.js",
	         "select_other_partners.js",
	         "IS_ExamCenter_name.js",
	         "center_IS_link.js",
	         "manage_exam_center_room.js"
	         ],function(){
		t._init();
	});
}