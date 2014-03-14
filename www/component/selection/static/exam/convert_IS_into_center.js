function convert_IS_into_center(container, can_add_EC, can_edit_EC, all_IS,all_IS_names, all_EC, db_locks) {
	var t = this;
	container = typeof container == "string" ? document.getElementById(container) : container;

	t._IS_checked = [];

	t._init = function() {
		var table = document.createElement("table");
		var tr = document.createElement("tr");
		t._convert_button_container = document.createElement("td");
		t._convert_button_container.style.verticalAlign = "middle";
		tr.appendChild(t._convert_button_container);
		table.appendChild(tr);
		table.style.display = "inline-block";
		table.style.height = "100%";
		t._refreshISSection();
		t._refreshECSection();
		container.appendChild(t._IS_section.element);
		t._IS_section.element.style.display = "inline-block";
		container.appendChild(table);
		
		container.appendChild(t._EC_section.element);
		t._EC_section.element.style.display = "inline-block";
		new fill_height_layout(t._IS_section.element);
		new fill_height_layout(t._EC_section.element);
		new fill_height_layout(tr);
	};

	t._refreshISSection = function() {
		t._IS_cb = []; // contains all the chekboxes of the IS list
		if (!t._IS_list_container) {
			t._IS_list_container = document.createElement("div");
			t._IS_list_container.style.overflowY = "scroll";// Anticipate
			// scrollbar
			t._IS_list_container.style.height = "100%";
		}
		if (!t._IS_section) {
			t._IS_section = new section("",
					"Non-assigned Information Sessions", t._IS_list_container,
					false, true);
			t._IS_section.element.style.display = "inline-block";
			t._IS_section.element.style.margin = "10px";
//			t._IS_section.element.style.height = "100%";			
			t._IS_section.element.style.overflowY = "hidden";
		}
		while (t._IS_list_container.firstChild)
			// Empty the section content
			t._IS_list_container.removeChild(t._IS_list_container.firstChild);
		// Add a right menu
		if (!t._convertButton) {
			t._actionRequested = null;
			t._convertButton = document.createElement("div");
			t._convertButton.style.visibility = "hidden";
			t._convertButton.className = 'button';
			t._convertButton.innerHTML = "<img src = '" + theme.icons_16.right+ "'/>";
			t._convertButton.title = "Convert the selected information sessions into exam centers, or link them to an existing center";
			t._convertButton.onclick = function() {
				// Disable the convert button
				this.disabled = true;
				require(
						"context_menu.js",
						function() {
							var menu = new context_menu();
							menu.onclose = function() {
								// If no item was clicked, reset
								if (t._actionRequested == null) {
									t._onCancel();
								}
							};
							if (all_EC.length > 0) {
								var add_to_existing = document.createElement('div');
								add_to_existing.appendChild(document.createTextNode("Add to an existing Exam center"));
								add_to_existing.onclick = function() {
									t._actionRequested = "add_to_existing";
									t._onActionRequired();
								};
								add_to_existing.className = "context_menu_item";
								menu.addItem(add_to_existing);
							}
							var create_new = document.createElement("div");
							create_new.appendChild(document.createTextNode("Create a new Exam Center"));
							create_new.onclick = function() {
								t._IS_host = null;
								t._actionRequested = "create_new";
								t._onActionRequired();
								// if several IS were selected, ask for the user
								// to pick one host
								if (t._IS_checked.length > 1) {
									require("popup_window.js",function() {
												var div = document.createElement("div");
												var pop = new popup_window("Select the host", "",div);
												div.innerHTML = "You have selected several information sessions,<br/>please pick one to be the host for the new exam center<br/>";
												for ( var i = 0; i < t._IS_checked.length; i++) {
													var b = document.createElement("div");
													b.className = "button";
													var index = t._findISIndex(t._IS_checked[i]);
													b.appendChild(document.createTextNode(all_IS[index].name));
													b.style.margin = "5px";
													b.style.textAlign = "center";
													b.IS_id = t._IS_checked[i];
													b.pop = pop;
													b.onclick = function() {
														t._IS_host = this.IS_id;
														if(t._anECAlreadyExistInThisArea(t._IS_host)){
															error_dialog("An exam center already exists in this geographic area");
															t._onCancel();
														} else {
															// Remove the IS_host from t._IS_host list
															var index = t._findISIndexInSelected(this.IS_id);
															t._IS_checked.splice(index, 1);
															this.pop.close();
															t._performAction(null,t._IS_checked,false,t._IS_host);
														}
													};
													div.appendChild(b);
												}
												pop.show();
											});
								} else {
									//Check that an exam center does not exist yet in the same area
									if(t._anECAlreadyExistInThisArea(t._IS_checked[0])){
										error_dialog("An exam center already exists in this geographic area");
										t._onCancel();
									} else										
									// Only one selected IS, the host
									t._performAction(null, [], false,t._IS_checked[0]);
								}
							};
							create_new.className = "context_menu_item";
							menu.addItem(create_new);
							menu.showBelowElement(t._convertButton);
						});
			};
//			t._IS_section.addToolRight(t._convertButton);
			 t._convert_button_container.appendChild(t._convertButton);
		}
		;

		if (all_IS.length == 0) {
			var div = document.createElement("div");
			div.appendChild(document.createTextNode("No information session remaining"));
			div.style.fontStyle = "italic";
			div.style.textAlign = "center";
			t._IS_list_container.appendChild(div);
		} else {
			var table = document.createElement("table");
			t._IS_list_container.appendChild(table);
			for ( var i = 0; i < all_IS.length; i++) {
				var tr = document.createElement("tr");
				var td1 = document.createElement("td");
				var td2 = document.createElement("td");
				// Add a check box on the left column
				var cb = document.createElement("input");
				cb.IS_id = all_IS[i].id;
				cb.type = "checkbox";
				cb.onchange = function() {
					// Update the t._IS_checked array
					if (!this.checked) {
						// Remove from t._IS_checked array
						var index = t._findISIndexInSelected(this.IS_id);
						if (index != null)
							t._IS_checked.splice(index, 1);
					} else {
						// Add in t._IS_checked array
						t._IS_checked.push(this.IS_id);
					}
					// Update the t._convertButton visibility
					t._convertButton.style.visibility = t._IS_checked.length == 0 ? "hidden": "visible";
				};
				td1.appendChild(cb);
				t._IS_cb.push(cb);
				td2.appendChild(t._createLinkProfile(all_IS[i].id, null,all_IS[i].name));
				tr.appendChild(td1);
				tr.appendChild(td2);
				table.appendChild(tr);
			}
		}
	};
	
	t._anECAlreadyExistInThisArea = function(IS_host_id){
		var index = t._findISIndex(IS_host_id);
		var area = all_IS[index].geographic_area;
		//Check in all_EC if there is already one in this area
		for(var i = 0; i < all_EC.length; i++){
			if(all_EC[i].geographic_area == area)
				return true;
		}
		return false;
	};
	
	t._refreshECSection = function() {
		t._EC_cb = [];//Contains all the exam centers checkboxes
		t._EC_checked = null; //Contains the selected exam center ID
		t._remove_buttons = []; // Contains the remove IS from centers buttons
		if (!t._EC_list_container) {
			t._EC_list_container = document.createElement("div");
			t._EC_list_container.style.overflowY = "scroll";// Anticipate scrollbar
			t._EC_list_container.style.height = "100%";
		}
		if (!t._EC_section) {
			t._EC_section = new section("", "Exam centers",t._EC_list_container, false, true);
			t._EC_section.element.style.margin = "10px";
			t._EC_section.element.style.display = "inline-block";
//			t._EC_section.element.style.height = "100%";
			t._EC_section.element.style.overflowY = "hidden";
		}
		while (t._EC_list_container.firstChild)
			// Empty the section content
			t._EC_list_container.removeChild(t._EC_list_container.firstChild);
		// Create the list of the exam centers		
		if (all_EC.length == 0) {
			var div = document.createElement("div");
			div.appendChild(document.createTextNode("No exam center yet"));
			div.style.fontStyle = "italic";
			div.style.textAlign = "center";
			t._EC_list_container.appendChild(div);
		} else {			
			var table = document.createElement("table");
			for ( var i = 0; i < all_EC.length; i++) {
				var tr = document.createElement("tr");
				var td1 = document.createElement("td");
				var td2 = document.createElement("td");
				var cb = document.createElement("input");
				cb.type = "checkbox";
				cb.EC_id = all_EC[i].id;
				// cb.style.visibility = "hidden";
				cb.disabled = true;
				cb.onclick = function() {
					// update t._EC_checked attribute
					t._EC_checked = this.EC_id;
					// Uncheck all the other checkboxes
					for ( var c = 0; c < t._EC_cb.length; c++) {
						if (t._EC_cb[c].EC_id != t._EC_checked)
							t._EC_cb[c].checked = "";
					}
				};
				t._EC_cb.push(cb);
				td1.appendChild(cb);
				td1.style.verticalAlign = "top";
				t._createECCell(td2, i);

				tr.appendChild(td1);
				tr.appendChild(td2);
				table.appendChild(tr);
			}
			t._EC_list_container.appendChild(table);
		}
		// Create the buttons in the section footer
		if (!t._okButton) {
			t._okButton = document.createElement("div");
			t._okButton.innerHTML = "<img src = '" + theme.icons_16.ok + "'/>";
			t._okButton.className = 'button';
			t._okButton.onclick = function() {
				// Check that an exam center has been selected
				if (t._EC_checked != null) {
					t._performAction(t._EC_checked, t._IS_checked,false, null);
				}
			};
			t._okButton.style.visibility = "hidden";
			t._EC_section.addToolBottom(t._okButton);
		}
		if (!t._cancelButton) {
			t._cancelButton = document.createElement("div");
			t._cancelButton.innerHTML = "<img src = '" + theme.icons_16.cancel+ "'/>";
			t._cancelButton.className = 'button';
			t._cancelButton.onclick = t._onCancel;
			t._cancelButton.style.visibility = "hidden";
			t._EC_section.addToolBottom(t._cancelButton);
		}
	};

	t._createECCell = function(td, index) {
		// Set the name
		var div_name = document.createElement("div");
		var link = t._createLinkProfile(null, all_EC[index].id,all_EC[index].name);
		div_name.appendChild(link);
		td.appendChild(div_name);
		// Set the linked IS list
		for ( var i = 0; i < all_EC[index].information_sessions.length; i++) {
			var div = document.createElement("div");
			div.appendChild(document.createTextNode(" - "));
			div.appendChild(t._createLinkProfile(all_EC[index].information_sessions[i],null,t._getISName(all_EC[index].information_sessions[i])));
			div.style.fontStyle = "italic";
			div.style.fontSize = "small";
			if (can_edit_EC) {
				var remove = document.createElement("img");
				remove.className = "button_verysoft";
				remove.style.verticalAlign = "bottom";
				remove.title = "Remove this information session from the exam center";
				remove.src = theme.icons_10.remove;
				remove.IS_id = all_EC[index].information_sessions[i];
				remove.EC_id = all_EC[index].id;
				remove.onclick = function() {
					// remove the IS from the exam center
					t._performAction(this.EC_id, [ this.IS_id ], true);
				};
				remove.style.marginLeft = "3px";
				div.appendChild(remove);
				t._remove_buttons.push(remove);
			}
			td.appendChild(div);
		}
	};

	t._createLinkProfile = function(IS_id, EC_id, name) {
		var link = document.createElement("a");
		if (IS_id != null){
			link.IS_id = IS_id;
			link.title = "See Information Session profile";
		}else if (EC_id != null) {
			link.EC_id = EC_id;
			link.title = "See Exam Center profile";
		}
		link.className = "black_link";		
		link.appendChild(document.createTextNode(name));
		link.onclick = function() {
			if (this.IS_id)
				var IS_id = this.IS_id;
			else if (this.EC_id)
				var EC_id = this.EC_id;
			require(
					"popup_window.js",
					function() {
						if (IS_id) {
							var pop = new popup_window("Information Session Profile");
							pop.setContentFrame("/dynamic/selection/page/IS/profile?id="+ IS_id+ "&readonly=true&hideback=true");
							pop.show();
						} else if (EC_id) {
							var pop = new popup_window("Exam Center Profile");
							pop.setContentFrame("/dynamic/selection/page/exam/center_profile?id="+ EC_id+ "&readonly=true&hideback=true");
							pop.show();
						}

					});
			return false;
		};
		return link;
	};

	t._performAction = function(EC_id, IS_ids, remove, IS_host) {
		//Lock the screen
		lock_screen();
		// Remove the script lock (not in DB)
		databaselock.removeLock(db_locks.EC);
		databaselock.removeLock(db_locks.ECIS);
		databaselock.removeLock(db_locks.IS);
		// Generate the url calling for the action
		// Add the locks
		var url = "/dynamic/selection/page/exam/convert_IS_into_center?lockec="+ db_locks.EC + "&lockis=" + db_locks.IS + "&lockecis="+ db_locks.ECIS;
		// Add the exam center id
		if (EC_id != null)// if null means the exam center must be created
			url += "&ec=" + EC_id;
		// Add the informations sessions ids
		for ( var i = 0; i < IS_ids.length; i++)
			url += "&is[" + i + "]=" + IS_ids[i];
		// add the required action
		if (remove) {
			url += "&remove=true";
		} else if (typeof IS_host != "undefined" && IS_host != null)
			url += "&ishost=" + IS_host;
//		alert(url);
		location.assign(url);
	};

	t._onCancel = function() {
		// reset EC the parameters
		t._actionRequested = null;
		t._EC_checked = null;
		for ( var i = 0; i < t._EC_cb.length; i++) {
			// uncheck
			t._EC_cb[i].checked = "";
			// t._EC_cb[i].style.visibility = "hidden";
			t._EC_cb[i].disabled = true;
		}
		// Enable the IS checkboxes
		for ( var i = 0; i < t._IS_cb.length; i++) {
			t._IS_cb[i].disabled = false;
		}
		// Hide the ok / cancel buttons
		t._okButton.style.visibility = "hidden";
		t._cancelButton.style.visibility = "hidden";
		// Show the convertButton if needed
		if(t._IS_checked.length > 0)
			t._convertButton.style.visibility = "visible";
		//Show the remove IS buttons
		for(var i = 0; i < t._remove_buttons.length; i++)
			t._remove_buttons[i].style.visibility = "visible";
	};

	t._onActionRequired = function() {
		// Disable the IS checkboxes
		for ( var i = 0; i < t._IS_cb.length; i++)
			t._IS_cb[i].disabled = true;
		// Enable the EC checkboxes
		for ( var i = 0; i < t._EC_cb.length; i++)
			t._EC_cb[i].disabled = false;
		// Show the ok / cancel buttons
		t._okButton.style.visibility = "visible";
		t._cancelButton.style.visibility = "visible";
		//Hide the remove IS buttons
		for(var i = 0; i < t._remove_buttons.length; i++)
			t._remove_buttons[i].style.visibility = "hidden";
		//Hide the convertButton
		t._convertButton.style.visibility = "hidden";
	};

	t._findISIndex = function(id) {
		for ( var i = 0; i < all_IS.length; i++) {
			if (all_IS[i].id == id)
				return i;
		}
		return null;
	};

	t._findISIndexInSelected = function(id) {
		for ( var i = 0; i < t._IS_checked.length; i++) {
			if (t._IS_checked[i] == id)
				return i;
		}
		return null;
	};

	t._findECIndex = function(id) {
		for ( var i = 0; i < all_EC.length; i++) {
			if (all_EC[i].id == id)
				return i;
		}
		return null;
	};

	t._getISName = function(id) {
		for ( var i = 0; i < all_IS_names.length; i++) {
			if (all_IS_names[i].id == id)
				return all_IS_names[i].name;
		}
		return null;
	};

	require([ "section.js", "context_menu.js","fill_height_layout.js"], function() {
		t._init();
	});
}