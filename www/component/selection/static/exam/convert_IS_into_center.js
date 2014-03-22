/**
 * Create the screen to convert / link informations sessions into / and exam centers
 * @param {HTMLElement | string}container
 * @param {Boolean} can_add_EC true if an exam center can be added by the user
 * @param {Boolean} can_edit_EC true if the user can edit an exam center (link, unlink IS)
 * @param {Array} all_IS array containing all the ISData objects (partner attribute can be null) that can be assigned to any center (so not already assigned)
 * @param {Array} all_IS_names array containing all the information sessions names in objects {id:, name:}
 * @param {Array} all_EC array containing all the ExamCenterData objects (partner attribute can be null)
 * @param {Object} db_locks containing the three locks required for this page:<ul><li><code>EC</code> ExamCenter table lock</li><li><code>IS</code> InformationSession table lock</li><li><code>ECIS</code> InformationSessionExamCenter table lock</li></ul>
 */
function convert_IS_into_center(container, can_add_EC, can_edit_EC, all_IS,all_IS_names, all_EC, db_locks) {
	var t = this;
	container = typeof container == "string" ? document.getElementById(container) : container;

	/** Private methods and attributes */
	
	t._IS_checked = [];//Contains the ids of the informations sessions selected by the user
	
	/**
	 * Launch the process
	 * Create two sections: the left one contains the not selected information sessions list
	 * The right one contains the exam centers list
	 */
	t._init = function() {
		var table = document.createElement("table");
		var tr = document.createElement("tr");
		var arrow_container = document.createElement("td");
		arrow_container.style.verticalAlign = "middle";
		tr.appendChild(arrow_container);
		arrow_container.innerHTML = "<img src = '"+theme.icons_16.right+"'/>";
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
	
	/**
	 * Create the section containing the IS list
	 * A check box is added on each row
	 * A button is added at the bottom to convert the selected IS into center (if several are picked, the user must pick the host)
	 */
	t._refreshISSection = function() {
		t._IS_cb = []; // contains all the chekboxes of the IS list
		if (!t._IS_list_container) {
			t._IS_list_container = document.createElement("div");
			t._IS_list_container.style.overflowY = "scroll";// Anticipate
			// scrollbar
			t._IS_list_container.style.height = "100%";
		}
		if (!t._IS_section) {
			t._IS_section = new section("","Non-assigned Information Sessions", t._IS_list_container,false, true);
			t._IS_section.element.style.display = "inline-block";
			t._IS_section.element.style.margin = "10px";
//			t._IS_section.element.style.height = "100%";			
			t._IS_section.element.style.overflowY = "hidden";
		}
		while (t._IS_list_container.firstChild)
			// Empty the section content
			t._IS_list_container.removeChild(t._IS_list_container.firstChild);
		//Add a bottom menu
		t._create_center_button = document.createElement("div");
		t._create_center_button.className = "button";
		t._create_center_button.innerHTML = "<img src = '"+theme.build_icon("/static/selection/exam/exam_center_16.png",theme.icons_10.add)+"'/> Convert into center";
		t._create_center_button.style.visibility = "hidden";
		t._create_center_button.onclick = function(){
			t._IS_host = null;
			t._actionRequested = "create_new";
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
				} else										
				// Only one selected IS, the host
				t._performAction(null, [], false,t._IS_checked[0]);
			}
		};
		t._IS_section.addToolBottom(t._create_center_button);
		//Add an arrow in the middle
		

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
					// Update the buttons visibility
					t._create_center_button.style.visibility = t._IS_checked.length == 0 ? "hidden": "visible";
					t._okButton.style.visibility = (t._IS_checked.length > 0 && t._EC_checked != null) ? "visible" : "hidden";
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
	
	/**
	 * Get if an exam center already exists in the geographic area of a given information session
	 * @param {Number} IS_host_id the information session ID
	 * @returns {Boolean}
	 */
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
	
	/**
	 * Create the exam center section
	 * A list of the exam center is created
	 * For each exam center, the list of the informations sessions already linked is added, with a remove button
	 * At the begining of an exam center row, a radio button is added
	 * A 'link IS to center' button is added at the bottom of the section
	 */
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
				cb.type = "radio";
				cb.name = "exam_center";
				cb.EC_id = all_EC[i].id;
				// cb.style.visibility = "hidden";
				cb.onclick = function() {
					// update t._EC_checked attribute
					t._EC_checked = this.EC_id;
					//Update the ok button visibility
					t._okButton.style.visibility = (t._EC_checked != null && t._IS_checked.length > 0)? "visible" : "hidden";
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
			t._okButton.innerHTML = "<img src = '/static/selection/exam/link_16.png'/> Link to center";
			t._okButton.className = 'button';
			t._okButton.onclick = function() {
				// Check that an exam center has been selected
				if (t._EC_checked != null) {
					t._performAction(t._EC_checked, t._IS_checked,false, null);
				}
			};
			t._okButton.style.visibility = "hidden";
			t._okButton.title = "Link the selected informations sessions to this exam center";
			t._EC_section.addToolBottom(t._okButton);
		}
	};
	
	/**
	 * Create an exam center cell
	 * The first row contains the exam center name, and the information sessions linked list is added below
	 * @param {HTMLElement} td the container of the exam center cell
	 * @param {Number} index of the exam center into the all_EC array
	 */
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
	
	/**
	 * Create a link to an exam center or an information session profile,
	 * with the read only attribute
	 * @param {Number | NULL} the information session ID, or NULL if link to EC profile
	 * @param {Number | NULL} the exam center ID, or NULL if link to IS profile
	 * @param {String} name the content of the link
	 * @returns {HTMLElement} link 
	 */
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
	
	/**
	 * Perform a required action by reloading the page with the adapted url
	 * @param {Number | NULL} EC_id the exam center ID to which the IS must be attached, or to which the IS must be removed, or NULL if none of these actions is required
	 * @param {Array} IS_ids array of the selected information sessions ids, to be attached, removed, or converted into host
	 * @param {Boolean} remove true if the action required is to remove an information session from an exam center
	 * @param {Number | NULL} IS_host the ID of the information session to convert into center, if any
	 */
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
		location.assign(url);
	};
	
	/**
	 * Find an information session index in all_IS array
	 * @param {Number} id the information session ID
	 * @returns {Number | NULL} NULL if not found, else the index
	 */
	t._findISIndex = function(id) {
		for ( var i = 0; i < all_IS.length; i++) {
			if (all_IS[i].id == id)
				return i;
		}
		return null;
	};

	/**
	 * Find an information session index into the t._IS_checked array
	 * @param {Number} id the information session ID
	 * @returns {Number | NULL} NULL if not found, else the index
	 */
	t._findISIndexInSelected = function(id) {
		for ( var i = 0; i < t._IS_checked.length; i++) {
			if (t._IS_checked[i] == id)
				return i;
		}
		return null;
	};
	
	/**
	 * Find an exam center index in all_IS array
	 * @param {Number} id the exam center ID
	 * @returns {Number | NULL} NULL if not found, else the index
	 */
	t._findECIndex = function(id) {
		for ( var i = 0; i < all_EC.length; i++) {
			if (all_EC[i].id == id)
				return i;
		}
		return null;
	};
	
	/**
	 * Get an information session name from its ID
	 * @param {Number} id
	 * @returns {String | NULL} name if found
	 */
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