function applicant_manually_assign_to_entity(container, campaign_id,mode,EC_id) {
	var t = this;
	container = typeof container == "string" ? document.getElementById(container) : container;
	
	t.reset = function(){
		t._refreshLeftSection();
		t._refreshRightSection();
		//Update convert button visibility
		t._assign_b.style.visibility = (t._applicants_selected.length == 0 || t._target_checked == null)? "hidden": "visible";
	};
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
		t._assign_b.className = "button";
		t._assign_b.title = "Assign the selected applicants";
		t._assign_b.style.visibility = "hidden";
		t._assign_b.onclick = t._performAction;
		arrow_container.appendChild(t._assign_b);
		table.appendChild(tr);
		table.style.display = "inline-block";
		table.style.height = "100%";
		t._refreshLeftSection();
		t._refreshRightSection();
//		container.appendChild(t._left_section.element);
//		t._left_section.element.style.display = "inline-block";
		container.appendChild(table);
		container.appendChild(t._right_section.element);
		t._right_section.element.style.display = "inline-block";
//		new fill_height_layout(t._left_section.element);
//		new fill_height_layout(t._right_section.element);
//		new fill_height_layout(tr);
	};

	t._refreshLeftSection = function() {
		t._applicants_selected = [];
		if (!t._applicants_list_container) {
			t._applicants_list_container = document.createElement("div");
			t._applicants_list_container.style.height = "100%";
			t._applicants_list_container.style.display = "inline-block";
			t._applicants_list_container.style.margin = "10px";
			t._applicants_list_container.style.width = "60%";
			t._applicants_list_container.className = "section";
			container.appendChild(t._applicants_list_container);
		}
//		if (!t._left_section) {
//			t._left_section = new section("","Applicants", t._applicants_list_container,false, true);
//			t._left_section.element.style.display = "inline-block";
//			t._left_section.element.style.margin = "10px";
//			t._left_section.element.style.width = "60%";			
//			t._left_section.element.style.overflowY = "hidden";
//			t._left_section.element.style.height = "100%";
//		}
		//Set the filters
		var filters = [];
		if(mode == "center"){
			//filter on the students with no center yet
			filters.push({category:"Selection", name:"Exam Center", data:{value:"NULL"}, force:true});
		}
		if(mode == "session"){
			//filter on the students with no session yet
			filters.push({category:"Selection", name:"Exam Session", data:{value:"NULL"}, force:true});
			//filter on the students assigned to this center
			filters.push({category:"Selection", name:"Exam Center", data:{value:EC_id}, force:true});
		}
		if(!t._dl){
			t._dl = new data_list(
				t._applicants_list_container,
				'Applicant', campaign_id,
				[
					'Selection.Applicant ID',
					'Personal Information.First Name',
					'Personal Information.Last Name',
					'Personal Information.Gender',
					'Personal Information.Birth Date'
				],
				filters,
				500,
				function (list) {
					list.addTitle("/static/selection/applicant/applicants_16.png", "Applicants");
					list.makeRowsClickable(function(row){
						window.top.popup_frame('/static/selection/applicant/applicant_16.png', 'Applicant', "/dynamic/people/page/profile?people="+list.getTableKeyForRow("People",row.row_id), {sub_models:{SelectionCampaign:campaign_id}}, 95, 95); 
					});
					list.grid.setSelectable(true);
					list.grid.onrowselectionchange = t._applicants_selection_changed;
				}
			);
			t._dl.ondataloaded.add_listener(function(){
				t._dl.grid.onrowselectionchange = t._applicants_selection_changed;
			});
		} else {
			t._dl.grid.onrowselectionchange = null;
			t._dl.reloadData();
		}
	};
	
	t._applicants_selection_changed = function (row_id, selected){
		var people_id = t._dl.getTableKeyForRow("Applicant", row_id);
		if (selected)
			t._applicants_selected.push(people_id);
		else
			t._applicants_selected.remove(people_id);
		//Update convert button visibility
		t._assign_b.style.visibility = (t._applicants_selected.length == 0 || t._target_checked == null)? "hidden": "visible";
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
				name = "Exam Sessions and Rooms";
			t._right_section = new section("", name,t._targets_list_container, false, true);
			t._right_section.element.style.margin = "10px";
			t._right_section.element.style.display = "inline-block";
			t._right_section.element.style.overflowY = "hidden";
			t._right_section.element.style.height = "100%";
		}
		while (t._targets_list_container.firstChild)
			// Empty the section content
			t._targets_list_container.removeChild(t._targets_list_container.firstChild);
		var loading = document.createElement("img");
		loading.src = theme.icons_16.loading;
		t._targets_list_container.appendChild(loading);
		// Retrieve the data
		service.json("selection","applicant/manually_assign_to_exam_entity_provider",{mode:mode,EC_id:EC_id},function(res){
			if(!res){
				error_dialog("An error occured");
				return;
			}
			t._targets = res.targets;
			t._targets_list_container.removeChild(loading);
			if(mode == "session"){
				t._populateRightSectionForSessionMode();
			} else
				t._populateRightSection();
		});
		
	};
	
	t._populateRightSection = function(){
		if (t._targets.length == 0) {
			var div = document.createElement("div");
			if(mode == "center")
				div.appendChild(document.createTextNode("No exam center yet"));
			div.style.fontStyle = "italic";
			div.style.textAlign = "center";
			t._targets_list_container.appendChild(div);
		} else {			
			var table = document.createElement("table");
			for ( var i = 0; i < t._targets.length; i++) {
				var tr = document.createElement("tr");
				var td1 = document.createElement("td");
				var td2 = document.createElement("td");
				var cb = document.createElement("input");
				cb.type = "radio";
				cb.name = "target_element";
				cb.id = t._targets[i].id;
				cb.onclick = function() {
					// update t._target_checked attribute
					t._target_checked = this.id;					
					//Update convert button visibility
					t._assign_b.style.visibility = (t._applicants_selected.length == 0 || t._target_checked == null)? "hidden": "visible";
				};
				td1.appendChild(cb);
				td1.style.verticalAlign = "top";
				if(mode == "center")
					td2.appendChild(t._createLinkToCenterProfile(i));
				else
					td2.appendChild(document.createTextNode(t._targets[i].name));
				tr.appendChild(td1);
				tr.appendChild(td2);
				table.appendChild(tr);
			}
			t._targets_list_container.appendChild(table);
		}
	};
	
	t._populateRightSectionForSessionMode = function(){
		if (t._targets.length == 0) {
			var div = document.createElement("div");
			div.appendChild(document.createTextNode("No exam session yet"));
			div.style.fontStyle = "italic";
			div.style.textAlign = "center";
			t._targets_list_container.appendChild(div);
		} else {
			var ul = document.createElement("ul");
			t._targets_list_container.appendChild(ul);
			for ( var i = 0; i < t._targets.length; i++) {
				var li = document.createElement("li");
				var cont = document.createElement("div");
				li.appendChild(cont);
				var header = document.createElement("div");
				header.appendChild(document.createTextNode(getExamSessionNameFromEvent(t._targets[i].session.event)));
				cont.appendChild(header);
				ul.appendChild(li);
				var body = document.createElement("div");
				cont.appendChild(body);
				if(t._targets[i].rooms.length == 0){
					body.appendChild(document.createTextNode("No room"));
					body.style.fontStyle = "italic";
				} else {
					for(var j = 0; j < t._targets[i].rooms.length; j++){
						var row = document.createElement('div');
						body.appendChild(row);
						if(t._targets[i].rooms[j].remaining > 0){
							var cb = document.createElement("input");
							cb.type = "radio";
							cb.name = "target_element";
							cb.session_id = t._targets[i].session.event.id;
							cb.room_id = t._targets[i].rooms[j].id;
							cb.onclick = function(){
								t._target_checked = {session_id:this.session_id,room_id:this.room_id};					
								//Update convert button visibility
								t._assign_b.style.visibility = (t._applicants_selected.length == 0 || t._target_checked == null)? "hidden": "visible";
							};
							cb.style.display = "inline-block";
							cb.style.marginRight = "3px";
							row.appendChild(cb);
						}
						row.appendChild(document.createTextNode(t._targets[i].rooms[j].name+" ("+t._targets[i].rooms[j].remaining+" remaining "+getGoodSpelling("slot",t._targets[i].rooms[j].remaining)+")"));
					}
				}
			}
		}
	};
	
	t._createLinkToCenterProfile = function(index_in_targets) {
		var link = document.createElement("a");
		var name = t._targets[index_in_targets].name;
		link.EC_id = t._targets[index_in_targets].id;
		link.title = "See Exam Center profile";
		link.className = "black_link";
		link.appendChild(document.createTextNode(name));
		link.onclick = function() {
			var EC_id = this.EC_id;
			require(
					"popup_window.js",
					function() {
						var pop = new popup_window("Exam Center Profile");
						pop.setContentFrame("/dynamic/selection/page/exam/center_profile?id="+ EC_id+ "&readonly=true&hideback=true");
						pop.show();
					});
			return false;
		};
		return link;
	};
	
	t._performAction = function() {
		//Lock the screen
		var locker = lock_screen();
		service.json("selection","applicant/manually_assign_to_exam_entity",{mode:mode,applicants:t._applicants_selected,target:t._target_checked},function(res){
			unlock_screen(locker);
			if(!res){
				error_dialog("An error occured, applicants were not assigned");
				return;
			}
			window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK,"The applicants have been succesfully assigned!", [{action:"close"}], 5000));
			t.reset();
		});
	};

	require([ "section.js", "context_menu.js","fill_height_layout.js"], function() {
		t._init();
	});
}