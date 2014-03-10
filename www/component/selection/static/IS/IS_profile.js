/** * Create the profile page of an Information session * If the user can edit, manage, remove, this page is generated as editable (not possible to switch to uneditable mode) * This page contains a select_address, a select_other_partners, a IS_date, and a IS_statistics object. A IS_name is added according to the selection campaign config * @param {Number} id if -1, means this is a new Information Session * @param {Object} config the selection process config object [{attribute:value},...] * @param {Number} calendar_id the ID of the calendar linked to the selection process * @param {Boolean} can_add * @param {Boolean} can_edit * @param {Boolean} can_remove * @param {String|HTMLElement}container * @param {ISData} data the information session data * @param {ISPartnersContactPoints} partners_contacts_points * @param {Array} all_durations containing all the possible durations for an information session * @param {Number} campaign_id the ID of the current campaign * @param {String} save_button_id ID of the save button to handle * @param {String} remove_button_id ID of the save button to handle * @param {Number} db_lock the database lock, if this IS is not new */function IS_profile(id, config, calendar_id, can_add, can_edit, can_remove, container, data ,partners_contacts_points,all_duration,campaign_id, save_button_id, remove_button_id, db_lock){	if(typeof(container) == "string") container = document.getElementById(container);	var t = this;	if(db_lock)		t.db_lock = db_lock;	else		t.db_lock = null;	t.table = document.createElement("table");	t.div_header = document.createElement("div");	t.div_address = document.createElement("div");	t.div_partners = document.createElement("div");	t.div_date = document.createElement("div");	t.div_name = document.createElement("div");	t.div_statistics = document.createElement("div");		/**	 * Reset the page, removeing all the children and restarting the process	 * Method _lockDatabase is called at the end of the process	 * @param {HTMLElement|NULL} locker the screen locker to remove if any	 * @param {Boolean} update_partners_contact_points if true, the partners_contacts_points array is given to the select_address and select_other_partners reseters, to update them	 */	t.resetAll = function(locker, update_partners_contact_points){		container.removeChild(t.table);		delete(t.table);		t.table = document.createElement("table");		t._setLayout();		if(update_partners_contact_points){			t.select_address.reset(partners_contacts_points);			t.select_other_partners.reset(null,partners_contacts_points);		} else {			t.select_address.reset();			t.select_other_partners.reset();		}		if(data.date != null)			t.IS_date.setEventId(data.date);		t.IS_date.resetTable();		if(typeof(locker) != "undefined" && locker != null) unlock_screen(locker);		t._lockDatabase();	};		/**Private attributes and functionalities*/		/**	 * Launch the process	 */	t._init = function(){		if((id == -1 && !can_add) || (id == "-1" && !can_add)) error_dialog("You are not allowed to add an Information Session");		else{			t._setAddressField();			t._setOtherPartnersField();			t._setDateField();			var index_name = findIndexInConfig(config, "give_name_to_IS");			if(config[index_name].value) t._setCustomNameField();			t._setStatisticsField();			t._setLayout();			t._lockDatabase();		}	};		/**	 * Set the header of the page	 */	t._setHeader = function(){		t.div_header.innerHTML = "<center>Information Session Profile</center>";		t.div_header.style.fontWeight = "bold";		t.div_header.style.fontSize = "x-large";		t.div_header.style.paddingTop = "20px";		t.div_header.style.paddingBottom = "40px";	};		/**	 * Create the select_address object	 */	t._setAddressField = function(){		t.select_address = new select_address(t.div_address, data, partners_contacts_points, can_edit,t._onContactsPointsUpdatedFromProfile,t._onAddressesUpdatedFromProfile);	};		/**	 * Create the select_other_partners object	 */	t._setOtherPartnersField = function(){		//Get host in data.partners array if set		var host_id = null;		for(var i = 0; i < data.partners.length; i++){			if(data.partners[i].host == true){				host_id = data.partners[i].organization;				break;			}		}		t.select_other_partners = new select_other_partners(t.div_partners, data.partners, partners_contacts_points, can_edit, host_id,t.select_address.onupdatehost, t._onContactsPointsUpdatedFromProfile);	};		/**	 * Create the IS_date object	 */	t._setDateField = function(){		var index = findIndexInConfig(config, "default_duration_IS");		// var index2 = findIndexInConfig(config, "give_name_to_IS_event");		t.IS_date = new IS_date(t.div_date, data.date, data.id, calendar_id, config[index].value, can_edit, all_duration);	};		/**	 * Create the IS_name object	 */	t._setCustomNameField = function(){		t.IS_name = new IS_name(t.div_name,data.name,can_edit);	};		/**	 * Create the IS_statistics object	 */	t._setStatisticsField = function(){		var index_statistics = findIndexInConfig(config, "separate_boys_girls_IS"); 		t.IS_statistics = new IS_statistics(t.div_statistics, config[index_statistics].value, can_edit, data.number_boys_expected, data.number_boys_real, data.number_girls_expected, data.number_girls_real);	};		/**	 * Set the layout of the page	 * The page is componed of a table with two columns. First contains the select_address, IS_date, IS_name, IS_statistics objects	 * Second column contains the select_other_partners object	 * The buttons are set into the header_bar element	 */	t._setLayout = function(){		var tr1 = document.createElement("tr");		var tr2 = document.createElement("tr");		var tr3 = document.createElement("tr");		var tr4 = document.createElement("tr");		var tr5 = document.createElement("tr");		var tr6 = document.createElement("tr");		var td1 = document.createElement("td");				td1.appendChild(t.div_header);		td1.colSpan = 2;		tr1.appendChild(td1);				var td21 = document.createElement("td");		var td22 = document.createElement("td");		td21.appendChild(t.div_address);		td22.appendChild(t.div_partners);		td22.rowSpan = 5;		td22.style.verticalAlign = "top";		tr2.appendChild(td21);		tr2.appendChild(td22);				var td31 = document.createElement("td");		var td32 = document.createElement("td");		td31.appendChild(t.div_name);		// td31.style.textAlign = "center";		tr3.appendChild(td31);		tr3.appendChild(td32);				var td41 = document.createElement("td");		var td42 = document.createElement("td");		td41.appendChild(t.div_date);		tr4.appendChild(td41);		tr4.appendChild(td42);				var td51 = document.createElement("td");		var td52 = document.createElement("td");		td51.appendChild(t.div_statistics);		td51.style.verticalAlign = "top";		tr5.appendChild(td51);		tr5.appendChild(td52);				var td61 = document.createElement("td");		// var td62 = document.createElement("td");				var button_remove = document.getElementById(remove_button_id);		if(can_remove && data.id != -1 && data.id != "-1"){			// td61.appendChild(button_remove);			button_remove.style.visibility = 'visible';			button_remove.style.position = 'static';			button_remove.onclick = function(){				confirm_dialog("Remove this information session and all the linked data?",function(res){					if(res){						var locker = lock_screen();						service.json("selection","IS/remove",{id:data.id},function(r){							unlock_screen(locker);							if(r)								location.assign("/dynamic/selection/page/IS/main_page");							else								error_dialog("An error occured");						});					}				});			};		} else {			button_remove.style.visibility = 'hidden';			button_remove.style.position = 'absolute';			button_remove.style.top = '-10000px';		}		var button_save = document.getElementById(save_button_id);		if(can_edit){			button_save.style.visibility = 'visible';			button_save.style.position = 'static';			button_save.onclick = function(){				t._launchSaving();			};		} else {			button_save.style.visibility = 'hidden';			button_save.style.position = 'absolute';			button_save.style.top = '-10000px';		}		tr6.appendChild(td61);		// tr6.appendChild(td62);				t.table.appendChild(tr1);		t.table.appendChild(tr2);		t.table.appendChild(tr3);		t.table.appendChild(tr4);		t.table.appendChild(tr5);		t.table.appendChild(tr6);				t.table.style.marginLeft = "15px";				container.appendChild(t.table);	};		/**	 * Method called by the save button	 * User cannot save the IS without having set at least a geographic area, since this geographic area is used to set the name of the event linked to this IS	 * All the data are retrieved from each screen and then the selection#IS#Save service is called	 * The IDs created by inserting the IS, and the event into the database are retrieved before reseting	 * reset method would lock the database if needed	 */	t._launchSaving = function(){		var locker = lock_screen();		if(data.geographic_area == null){			unlock_screen(locker);			error_dialog("You must at least pick a geographic area before saving");		} else {							/** 			 * Get the data from each screen:			 * select_address works with the reference of data			 * so there is no need to update			 */					//get from other_partners			//remove all the old other partners			var length = data.partners.length;			var i = 0;			var offset = 0;			while(i < length){				if(data.partners[offset].host == null || data.partners[offset].host == false){					data.partners.splice(offset,1);				} else					offset++;				i++;			}			//Update, keeping the host			data.partners = data.partners.concat(t.select_other_partners.getOtherPartners());			//get from date			var event = t.IS_date.getEvent();			event.title = null;						// get from statistics			var figures = t.IS_statistics.getFigures();			data.number_boys_expected = figures.boys_expected;			data.number_girls_expected = figures.girls_expected;			data.number_boys_real = figures.boys_real;			data.number_girls_real = figures.girls_real;						// get from name			var index_name = findIndexInConfig(config, "give_name_to_IS");			var name = null;			if(config[index_name].value)				name = t.IS_name.getName();			if(name != null && typeof name == "string" && name.checkVisible())				data.name = name;			else 				data.name = null;						service.json("selection","IS/save",{event:event, data:data},function(res){				unlock_screen(locker);				if(!res)					error_dialog("An error occured, your informations were not saved");				else {					window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "Your informations have been successfuly saved!", [{action:"close"}], 5000));					// Update the data on the page (some ids have been generated)					data.id = res.id;					if(res.date != null)						data.date = res.date;					//reset the table (in case remove button must be added, and update the objects)					t.resetAll();				}			});		}	};		/**	 * Get a partner index into data.partners array, from its ID	 * @param {Number} id	 * @returns {Number|NULL} the index of the seeked partner, or NULL if not found	 */	t._findPartnerIndex = function(id){		for(var i = 0; i < data.partners.length; i++){			if(data.partners[i].organization == id)				return i;		}		return null;	};		/**	 * Find the host index within the data.partners array	 * @returns {Number|NULL} the index of the host partner, or NULL if not found	 */	t._findHostIndex = function(){		for(var i = 0; i < data.partners.length; i++){			if(data.partners[i].host == true)				return i;		}		return null;	};		/**	 * Find the host address into the data.partners array	 * @returns {Number|NULL} the address ID, or NULL if the host partner was not found	 */	t._getHostAddress = function(){		var index = t._findHostIndex();		if(index == null)			return null;		return data.partners[index].host_address;	};		/**	 * Called after creating this object and saving	 * If the id != -1 and db_lock attribute == null, this method would	 * call the lock_row service, update the db_lock attribute and add the lock javascript	 * Else nothing is done	 */	t._lockDatabase = function(){		if(t.db_lock == null){			if(data.id != -1 || data.id != "-1")				service.json("data_model","lock_row",{table:"InformationSession",row_key:data.id, sub_model:campaign_id},function(res){					if(res){						databaselock.addLock(res.lock);						t.db_lock = res.lock;					}				});		}	};		/**	 * Listener fired when the contact points of any partner are updated from the popups showing the organization profile (especially removed, added)	 */	t._onContactsPointsUpdatedFromProfile = function(){		var locker = lock_screen();		/**This listener is fired only when any contact point is updated into the database		 * so we just have to update data.partners array and allpartners_contacts_points array		 */		//Update all the values		//remove all the old other partners		var length = data.partners.length;		var i = 0;		var offset = 0;		while(i < length){			if(data.partners[offset].host == null || data.partners[offset].host == false){				data.partners.splice(offset,1);			} else				offset++;			i++;		}		//Update, keeping the host		data.partners = data.partners.concat(t.select_other_partners.getOtherPartners());		//Get the selected partners ids		var selected_partners = [];		for(var i = 0; i < data.partners.length; i++ )			selected_partners.push(data.partners[i].organization);		service.json("contact","get_json_contact_points_no_address",{organizations:selected_partners},function(r){			if(!r){				error_dialog("You have updated some data about the contact points of this organization, but an error occured and you will not be able to use them on this page<br/>You can save reload to see your updates");				unlock_screen(locker);				return;			} else {				partners_contacts_points = r;				//Check that all contact points selected still exist				var cp_to_check = [];				for(var i = 0; i < data.partners.length; i++){					for(var j = 0; j < data.partners[i].contact_points_selected.length; j++){						cp_to_check.push({partner_index:i, cp:data.partners[i].contact_points_selected[j]});					}				}				if(cp_to_check.length > 0){					//Check if they still exist in partners_contacts_points					for(var i = 0; i < cp_to_check.length; i++){						if(!t._contactPointExist(cp_to_check[i].cp)){							//remove from the contact point from selected partners							var index = null;							for(var j = 0; j < data.partners[cp_to_check[i].partner_index].contact_points_selected.length; j++){								if(data.partners[cp_to_check[i].partner_index].contact_points_selected[j] == cp_to_check[i].cp){									index = j;									break;								}																}							if(index != null)								data.partners[cp_to_check[i].partner_index].contact_points_selected.splice(index,1);						}					}				}				//reset tables				t.resetAll(locker,true);			}		});	};		/**	 * Check that the given contact point ID still exists into partners_contacts_points array	 * @param {Number} cp the contact point ID to be checked	 * @returns {Boolean} true if the cp is found into partners_contacts_points array	 */	t._contactPointExist = function(cp){		for(var i = 0; i < partners_contacts_points.length; i++){			for(var j = 0; j < partners_contacts_points[i].contact_points.length; j++){				if(partners_contacts_points[i].contact_points[j].people_id == cp)					return true;			}		}		return false;	};		/**	 * Listener fired when the addresses of any partner are updated from the popups showing the organization profile (especially removed, added)	 */	t._onAddressesUpdatedFromProfile = function(){		var locker = lock_screen();		//Check if an host was selected		var host_address = t._getHostAddress();		if(host_address == null)//nothing to do			return;		//Get the address set into the database		service.json("contact","get_address",{id:host_address},function(res){			unlock_screen(locker);			if(res == false){				error_dialog("You have updated some data about the addresses of this organization, but an error occured and you will not be able to use them on this page<br/>You can save reload to see your updates");				return;			}			var index = t._findHostIndex();			if(res == null){				//address does not exist anymore, so reset the host				data.partners[index].host = null;				data.partners[index].host_address = null;				//reset select_address				t.select_address.reset();			} else {				//address still exist, just refresh in the case of any updates occured				t.select_address.reset();			}		});	};}require(["popup_window.js","select_address.js","select_other_partners.js","IS_date.js","IS_name.js","IS_statistics.js"],function(){t._init();});