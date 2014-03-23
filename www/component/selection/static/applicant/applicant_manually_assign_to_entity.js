
function applicant_manually_assign_to_entity(container, applicants,targets, mode,db_lock,EC_id,session_id, targets_additional) {
	var t = this;
	container = typeof container == "string" ? document.getElementById(container) : container;
	/** Private methods and attributes */
	
	t._applicants_checked = [];//Contains the ids of the applicants selected by the user
	

	t._init = function() {
		var table = document.createElement("table");
		var tr = document.createElement("tr");
		var arrow_container = document.createElement("td");
		arrow_container.style.verticalAlign = "middle";
		tr.appendChild(arrow_container);
		t._assign_b = document.createElement("div");
		t._assign_b.innerHTML = "<img src = '"+theme.icons_16.right+"'/> Assign";
		t._assign_b.className = "button_verysoft";
		t._assign_b.title = "Assign the selected applicants";
		t._assign_b.style.visibility = "hidden";
		t._assign_b.onclick = t._performAction;
		arrow_container.appendChild(t._assign_b);
		table.appendChild(tr);
		table.style.display = "inline-block";
		table.style.height = "100%";
		t._refreshLeftSection();
		t._refreshRightSection();
		container.appendChild(t._left_section.element);
		t._left_section.element.style.display = "inline-block";
		container.appendChild(table);
		
		container.appendChild(t._right_section.element);
		t._right_section.element.style.display = "inline-block";
		new fill_height_layout(t._left_section.element);
		new fill_height_layout(t._right_section.element);
		new fill_height_layout(tr);
	};
	

	t._refreshLeftSection = function() {
		if (!t._applicants_list_container) {
			t._applicants_list_container = document.createElement("div");
			t._applicants_list_container.style.overflowY = "scroll";// Anticipate
			// scrollbar
			t._applicants_list_container.style.height = "100%";
		}
		if (!t._left_section) {
			t._left_section = new section("","Applicants", t._applicants_list_container,false, true);
			t._left_section.element.style.display = "inline-block";
			t._left_section.element.style.margin = "10px";
//			t._left_section.element.style.height = "100%";			
			t._left_section.element.style.overflowY = "hidden";
		}
		while (t._applicants_list_container.firstChild)
			// Empty the section content
			t._applicants_list_container.removeChild(t._applicants_list_container.firstChild);		

		if (applicants.length == 0) {
			var div = document.createElement("div");
			div.appendChild(document.createTextNode("No applicant remaining"));
			div.style.fontStyle = "italic";
			div.style.textAlign = "center";
			t._applicants_list_container.appendChild(div);
		} else {
			var table = document.createElement("table");
			t._applicants_list_container.appendChild(table);
			for ( var i = 0; i < applicants.length; i++) {
				var tr = document.createElement("tr");
				var td1 = document.createElement("td");
				var td2 = document.createElement("td");
				// Add a check box on the left column
				var cb = document.createElement("input");
				cb.people_id = applicants[i].people_id;
				cb.type = "checkbox";
				cb.onchange = function() {
					// Update the t._applicants_checked array
					if (!this.checked) {
						// Remove from t._applicants_checked array
						var index = t._findApplicantIndexInSelected(this.IS_id);
						if (index != null)
							t._applicants_checked.splice(index, 1);
					} else {
						// Add in t._applicants_checked array
						t._applicants_checked.push(this.people_id);
					}
					// Update the buttons visibility
					t._assign_b.style.visibility = (t._applicants_checked.length == 0 || t._target_checked == null)? "hidden": "visible";
				};
				td1.appendChild(cb);
				td2.appendChild(t._createLinkProfile(i, null));
				tr.appendChild(td1);
				tr.appendChild(td2);
				table.appendChild(tr);
			}
		}
	};
	
	t._refreshRightSection = function() {
		t._target_checked = null; //Contains the selected targets elements
		if (!t._targets_list_container) {
			t._targets_list_container = document.createElement("div");
			t._targets_list_container.style.overflowY = "scroll";// Anticipate scrollbar
			t._targets_list_container.style.height = "100%";
		}
		if (!t._right_section) {
			var name = null;
			if(mode == "center")
				name = "Exam Centers";
			else if (mode == "session")
				name = "Exam Session";
			t._right_section = new section("", name,t._targets_list_container, false, true);
			t._right_section.element.style.margin = "10px";
			t._right_section.element.style.display = "inline-block";
//			t._right_section.element.style.height = "100%";
			t._right_section.element.style.overflowY = "hidden";
		}
		while (t._targets_list_container.firstChild)
			// Empty the section content
			t._targets_list_container.removeChild(t._targets_list_container.firstChild);
		// Create the list of the targets elements	
		if (targets.length == 0) {
			var div = document.createElement("div");
			if(mode == "center")
				div.appendChild(document.createTextNode("No exam center yet"));
			else if(mode == "session")
				div.appendChild(document.createTextNode("No exam session yet"));
			div.style.fontStyle = "italic";
			div.style.textAlign = "center";
			t._targets_list_container.appendChild(div);
		} else {			
			var table = document.createElement("table");
			for ( var i = 0; i < targets.length; i++) {
				var tr = document.createElement("tr");
				var td1 = document.createElement("td");
				var td2 = document.createElement("td");
				var cb = document.createElement("input");
				cb.type = "radio";
				cb.name = "target_element";
				cb.id = targets[i].id;
				cb.onclick = function() {
					// update t._target_checked attribute
					t._target_checked = this.id;					
					//Update convert button visibility
					t._assign_b.style.visibility = (t._applicants_checked.length == 0 || t._target_checked == null)? "hidden": "visible";
				};
				td1.appendChild(cb);
				td1.style.verticalAlign = "top";
				if(targets_additional && t._findTargetAdditionalDataIndex(targets[i].id) != null)
					t._createRowAdditionalInfoTargetElement(td2,t._createLinkProfile(null,i),t._findTargetAdditionalDataIndex(targets[i].id));
				else
					td2.appendChild(t._createLinkProfile(null,i));
				tr.appendChild(td1);
				tr.appendChild(td2);
				table.appendChild(tr);
			}
			t._targets_list_container.appendChild(table);
		}
	};
	
	t._createLinkProfile = function(index_in_applicants, index_in_targets) {
		var link = document.createElement("a");
		var name = null;
		if (index_in_applicants != null){
			link.people_id = applicants[index_in_applicants].people_id;
			link.title = "See applicant profile";
			name = applicants[index_in_applicants].applicant_id+', '+applicants[index_in_applicants].last_name+", "+applicants[index_in_applicants].middle_name+", "+applicants[index_in_applicants].first_name+", "+applicants[index_in_applicants].sex+", "+applicants[index_in_applicants].birthdate;
		}else if (index_in_targets != null) {
			name = targets[index_in_targets].name;
			if(mode == "center"){
				link.EC_id = targets[index_in_targets].id;
				link.title = "See Exam Center profile";				
			} else if (mode == "session"){
				link.session_id = targets[index_in_targets].id;
				link.title = "See Exam Session profile";		
			}			
		}
		link.className = "black_link";		
		link.appendChild(document.createTextNode(name));
		link.onclick = function() {
			if (this.people_id)
				var people_id = this.people_id;
			else if (this.EC_id)
				var EC_id = this.EC_id;
			else if(this.session_id)
				var session_id = this.session_id;
			require(
					"popup_window.js",
					function() {
						if (people_id) {
							var pop = new popup_window("Applicant Profile");
							pop.setContentFrame("/dynamic/people/page/profile?people="+ people_id);
							pop.show();
						} else if (EC_id) {
							var pop = new popup_window("Exam Center Profile");
							pop.setContentFrame("/dynamic/selection/page/exam/center_profile?id="+ EC_id+ "&readonly=true&hideback=true");
							pop.show();
						} else if (session_id){
							//TODO
						}

					});
			return false;
		};
		return link;
	};
	
	t._findTargetAdditionalDataIndex = function(target_id){
		for(var i = 0; i < targets_additional.length; i++){
			if(targets_additional[i].id == target_id)
				return i;
		}
		return null;
	};
	
	t._createRowAdditionalInfoTargetElement = function(td, link, index_in_additional){
		var div_link = document.createElement("div");
		var div_additional = document.createElement("div");
		div_link.style.display = "inline-block";
		div_additional.style.display = "inline-block";
		td.appendChild(div_link);
		div_link.appendChild(link);
		td.appendChild(div_additional);		
		div_additional.style.textAlign = "right";
		div_additional.style.fontStyle = "italic";
		div_additional.style.marginLeft = "3px";
		if(mode == "session"){
			div_additional.appendChild(document.createTextNode("("+targets_additional[index_in_additional].additional+" "+getGoodSpelling("slot",targets_additional[index_in_additional].additional)+" remaining)"));
		}
	};
	
	t._performAction = function() {
		//Lock the screen
		lock_screen();
		// Remove the script lock (not in DB)
		databaselock.removeLock(db_lock);
		// Generate the url calling for the action
		// Add the locks and the mode
		var url = "/dynamic/selection/page/applicant/manually_assign_to_exam_entity?lock="+ db_lock+"&mode="+mode;
		if(session_id)
			url += "&session="+session_id;
		if(EC_id)
			url += "&center="+EC_id;
		//Add the applicants ids
		for(var i = 0; i < t._applicants_checked.length; i++)
			url += "&a["+i+"]="+t._applicants_checked[i];
		//Add the target id
			url += "&target="+t._target_checked;
		location.assign(url);
	};

	/**
	 * Find an information session index into the t._applicants_checked array
	 * @param {Number} id the information session ID
	 * @returns {Number | NULL} NULL if not found, else the index
	 */
	t._findApplicantIndexInSelected = function(id) {
		for ( var i = 0; i < t._applicants_checked.length; i++) {
			if (t._applicants_checked[i].people_id == id)
				return i;
		}
		return null;
	};

	require([ "section.js", "context_menu.js","fill_height_layout.js"], function() {
		t._init();
	});
}