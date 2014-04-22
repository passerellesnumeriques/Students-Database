/**
 * Create the section containing the data related to an exam session
 * @param {String|HTMLElement} container
 * @param {String|HTMLElement} supervisor_container container of the supervisors sub section
 * @param {String|HTMLElement} list_container container of the rooms list sub section
 * @param {ExamSession} session
 * @param {Boolean} can_manage
 * @param {Function|NULL} onreset called when reset method is called
 */
function exam_session_profile(container, supervisor_container, list_container, session, can_manage, onreset){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	if(typeof supervisor_container == "string")
		supervisor_container = document.getElementById(supervisor_container);
	if(typeof list_container == "string")
		list_container = document.getElementById(list_container);
	
	/**
	 * Reset the content
	 */
	t.reset = function(){
		t._refreshListContent();
		if(onreset)
			onreset();
	};
	
	/** Private methods and attributes */
	
	t._init = function(){
		t._refreshListContent();
		t._refreshSupervisorsContent();
	};
	
	/**
	 * Refresh the list of rooms assignment content
	 * Calls the service applicant/get_assigned_to_rooms_for_session to populate the list
	 */
	t._refreshListContent = function(){
		while(list_container.firstChild)
			list_container.removeChild(list_container.firstChild);
		list_container.appendChild(t._getLoading());
		service.json("selection","applicant/get_assigned_to_rooms_for_session",{session_id:session.event.id,count:true},function(res){
			if(!res){
				error_dialog("An error occured");
				return;
			}
			if(list_container.firstChild)
				list_container.removeChild(list_container.firstChild);
			if(res.rooms == null){
				var div = document.createElement("div");
				div.appendChild(document.createTextNode("No exam room"));
				div.style.fontStyle = "italic";
				list_container.appendChild(div);
			} else {
				t._populateList(res.rooms, res.count_session);
			}
		});
		layout.invalidate(list_container);
	};
	
	/**
	 * Populate the rooms assignment list
	 * @param {Array} rooms array of rooms objects with an additional attribute: <code>applicants</code> number of applicants assigned to this room during this exam session
	 * @param {Number} total_applicants_in_session number of applicants assigned to this exam session (assigned to a room or not)
	 */
	t._populateList = function(rooms, total_applicants_in_session){
		t._total_assigned = 0;
		var table = document.createElement("table");
		//Set the column headers
		var tr_head = document.createElement("tr");
		var th_rooms = document.createElement("th");
		var th_applicants = document.createElement("th");
		th_rooms.appendChild(document.createTextNode('Rooms'));
		th_applicants.appendChild(document.createTextNode("Applicants"));
		tr_head.appendChild(th_rooms);
		th_rooms.style.paddingRight = "20px";
		tr_head.appendChild(th_applicants);
		table.appendChild(tr_head);
		//Set the body
		for(var i = 0; i < rooms.length; i++){
			var tr = document.createElement("tr");
			var td1 = document.createElement("td");
			var td2 = document.createElement("td");
			td1.style.textAlign = "center";
			td2.style.textAlign = "center";
			td1.style.paddingRight = "20px";
			tr.appendChild(td1);
			tr.appendChild(td2);
			table.appendChild(tr);
			//Set the room name in td1
			td1.appendChild(document.createTextNode(rooms[i].name));
			//Set the number of applicants in the td2
			td2.appendChild(t._createFigureElement(rooms[i].applicants, rooms[i].id, false, rooms[i].name));
			td2.appendChild(document.createTextNode(" / "+rooms[i].capacity));
			td2.style.textAlign = "left";
			if(can_manage && rooms[i].applicants && rooms[i].applicants > 0){
				var b_empty_room = document.createElement("img");
				b_empty_room.src = theme.icons_10.remove;
				b_empty_room.className = "button_verysoft";
				b_empty_room.title = "Unassign all the applicants from this room";
				b_empty_room.room_id = rooms[i].id;
				b_empty_room.onclick = function(){
					t._emptyEntity(this.room_id);
				};
				b_empty_room.style.marginLeft = "7px";
				b_empty_room.style.verticalAlign = "bottom";
				td2.appendChild(b_empty_room);
			}
			t._total_assigned += parseInt(rooms[i].applicants);
		}
		//Set the total row
		var tr_total = document.createElement("tr");
		var td1 = document.createElement("td");
		var td2 = document.createElement("td");
		tr_total.appendChild(td1);
		tr_total.appendChild(td2);
		table.appendChild(tr_total);
		td1.appendChild(document.createTextNode("Total rooms:"));
		td1.style.textAlign = "right";
		td2.style.textAlign = "left";
		td2.appendChild(document.createTextNode(t._total_assigned));
		
		//Set the non-assigned row
		total_applicants_in_session = parseInt(total_applicants_in_session);
		var not_assigned = total_applicants_in_session - t._total_assigned;
		var tr_no_room = document.createElement("tr");
		var td1_no = document.createElement("td");
		var td2_no = document.createElement("td");
		td1_no.style.textAlign = "right";
		td2_no.style.textAlign = "left";
		tr_no_room.appendChild(td1_no);
		tr_no_room.appendChild(td2_no);
		table.appendChild(tr_no_room);
		td1_no.appendChild(document.createTextNode("No room:"));
		var link = t._createFigureElement(not_assigned,null,true);
		link.style.color = not_assigned > 0 ? "red" : "green";
		td2_no.appendChild(link);
		if(not_assigned > 0 && can_manage){
			//Add the automatic assign button
			var b_assign_auto = document.createElement("div");
			b_assign_auto.className = "button";
			b_assign_auto.appendChild(document.createTextNode("Assign"));
			b_assign_auto.title = "Automatically assign the remaining applicants to the rooms";
			b_assign_auto.onclick = function(){
				service.json("selection","applicant/automaticallyAssignToExamRooms",{session_id:session.event.id},function(res){
					if(!res){
						error_dialog("An error occured, the applicants were not assigned");
						return;
					}
					if(res.error != null){
						error_dialog(res.error);
						return;
					}
					window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, res.assigned+" applicants have been succesfully assigned to the rooms!", [{action:"close"}], 5000));
					t.reset();
				});
			};
			b_assign_auto.style.marginLeft = "7px";
			td2_no.appendChild(b_assign_auto);
		}
		//Set the total session row
		var tr_all = document.createElement("tr");
		var td1_all = document.createElement("td");
		var td2_all = document.createElement("td");
		td1_all.style.textAlign = "right";
		td2_all.style.textAlign = "left";
		tr_all.appendChild(td1_all);
		tr_all.appendChild(td2_all);
		table.appendChild(tr_all);
		td1_all.appendChild(document.createTextNode("Total session:"));
		var all = t._createFigureElement(total_applicants_in_session, null, false);
		td2_all.appendChild(all);
		if(can_manage && total_applicants_in_session > 0){
			var b_empty_session = document.createElement("img");
			b_empty_session.src = theme.icons_10.remove;
			b_empty_session.className = "button_verysoft";
			b_empty_session.title = "Unassign all the applicants from this session and the rooms";
			b_empty_session.onclick = function(){t._emptyEntity(null);};
			b_empty_session.style.marginLeft = "7px";
			b_empty_session.style.verticalAlign = "bottom";
			td2_all.appendChild(b_empty_session);
		}
		list_container.appendChild(table);
	};
	
	/**
	 * Unassign all the applicants from an exam entity
	 * @param room_id {Number|NULL} exam room ID if the aim is to unassign all from a given room, else NULL means to unassign all the applicants from the session (and so all the rooms at the same time)
	 */
	t._emptyEntity = function(room_id){
		var locker = lock_screen();
		service.json("selection","applicant/unassign_all_from_center_entity",{session_id:session.event.id,room_id:room_id},function(res){
			unlock_screen(locker);
			if(!res)
				error_dialog("An error occured, applicants were not unassigned");
			else {
				var error_has_grade = null;
				var total_unassigned = 0;
				for(var i = 0; i < res.length; i++){
					if(res[i].done)
						total_unassigned++;
					else {
						if (res[i].error_has_grade){
							if(error_has_grade == null)
								error_has_grade = [];
							error_has_grade.push(res[i].applicant);
						}
					}
				}
				if(total_unassigned > 0){
					if(room_id)
						window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, total_unassigned+" applicants have been succesfully unassigned from this room!", [{action:"close"}], 5000));
					else
						window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, total_unassigned+" applicants have been succesfully unassigned from this session!", [{action:"close"}], 5000));
				}					
				if(error_has_grade){
					var cont = document.createElement("div");
					var header = document.createElement("div");
					var body = document.createElement("div");
					cont.appendChild(header);
					cont.appendChild(body);
					header.appendChild(document.createTextNode("Following applicants have not been unassigned because they already have exam results:"));
					var ul = document.createElement("ul");
					for(var i = 0; i < error_has_grade.length; i++){
						var li = document.createElement("li");
						var link = document.createElement("a");
						link.people_id = error_has_grade[i].people_id;
						link.className = "black_link";
						link.title = "See Applicant profile";
						link.appendChild(document.createTextNode(getApplicantMainDataDisplay(error_has_grade[i])));
						link.onclick = function(){
							var people_id = this.people_id;
							require("popup_window.js",function(){
								var p = new popup_window("Applicant Profile");
								p.setContentFrame("/dynamic/people/profile?people="+people_id);
								p.show();
							});
							return false;
						};
						li.appendChild(link);
						ul.appendChild(li);
					}
					body.appendChild(ul);
					error_dialog_html(cont);
				}
				t.reset();
			}
		});
	};
	
	/**
	 * Create a number element which is a link to a given applicants list
	 * @param {Number} figure figure to be overrided into a link
	 * @param {Number|NULL} room_id exam center room ID if the link is supposed to point on the list of applicants assigned to this room
	 * @param {Boolean} not_in_room true if the link is supposed to point on the list of applicants not assigned to any room
	 * @param {String|NULL} room_name (not null in the case of room_id is not null) name of the exam center room
	 */
	t._createFigureElement = function(figure,room_id, not_in_room, room_name){
		var link = document.createElement("a");
		figure = (figure == null || isNaN(figure)) ? 0 : parseInt(figure);
		link.appendChild(document.createTextNode(figure));
		if(figure > 0){
			link.className= "black_link";
			link.style.fontStyle = "italic";
			link.title = (can_manage == false || not_in_room == true) ? "See / Export the list": "See / Edit / Export the list";
			link.onclick = function(){				
				require(["prepare_applicant_list.js","popup_window.js"],function(){					
					var for_list = new prepare_applicant_list();
					for_list.forbidApplicantCreation();
					for_list.forbidApplicantImport();
					var popup_name;
					var button_name = null;
					if(!room_id && !not_in_room){
						//Session list
						if(can_manage)
							for_list.makeApplicantsSelectable();
						for_list.addFilter("Exam Session",session.event.id,true);
						popup_name = "Session "+getExamSessionNameFromEvent(session.event)+" applicants";
						button_name = "Unassign from session";
					} else if(!room_id && not_in_room){
						//Not in room list						
						for_list.addFilter("Exam Session",session.event.id,true);
						for_list.addFilter("Exam Center Room",null,true);
						popup_name = "Applicants with no room in session "+getExamSessionNameFromEvent(session.event);
					} else {
						//Room list
						if(can_manage)
							for_list.makeApplicantsSelectable();
						for_list.addFilter("Exam Session",session.event.id, true);
						for_list.addFilter("Exam Center Room",room_id,true);
						popup_name = "Session "+getExamSessionNameFromEvent(session.event);
						button_name = "Unassign from room";
						if(room_name){
							popup_name += " in room "+room_name;
							button_name += " "+room_name;
						}
						
					}
					var p = new popup_window(popup_name);					
					var frame = p.setContentFrame("/dynamic/selection/page/applicant/list",null,for_list.getDataToPost());
					if(can_manage && button_name != null){
						p.addIconTextButton(null,button_name,"unassign",function(){
							//Get the applicants to remove
							var win = getIFrameWindow(frame);
							var to_unassign = win.applicants_selected;							
							if(to_unassign.length == 0){
								//Nothing to do
								p.close();
								return;
							}
							t._answers_to_wait = to_unassign.length;
							t._answers_received = 0;
							t._errors = [];
							t._popToClose = p;
							t._locker = lock_screen();
							for(var i = 0; i < to_unassign.length; i++){
								service.json("selection","applicant/unassign_from_center_entity",{people_id:to_unassign[i],room_id:room_id,session_id:session.event.id},t._onApplicantUnassigned);
							}
						});
						
						p.onclose = function(){							
							//Refresh because applicant can have been unassigned
							t.reset();
						};
					}
					p.show();
				});
			};
		}
		return link;
	};
	
	/**
	 * Method called when an applicant is unassigned
	 * This method will wait for all the applicants to be handled by the service (called for each applicant to unassign) before poping error message (if any)
	 * If the applicant was well unassigned, the success status message is created gradually
	 * @param {Object} res result from applicant/unassign_from_center_entity service
	 */
	t._onApplicantUnassigned = function(res){
		t._answers_received++;
		if(res && (res.error_performing || res.error_assigned_to_session || res.error_assigned_to_room || res.error_has_grade)){
			var error = {applicant:res.applicant};
			if(res && res.error_performing)
				error.detail = "Error while removing";
			if(res && res.error_assigned_to_session)
				error.detail = "Already assigned to session";
			if(res && res.error_assigned_to_room)
				error.detail = "Already assigned to room";
			if(res && res.error_has_grade)
				error.detail = "Already has exam results";
			t._errors.push(error);
		}
		if(res.done)
			window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "Applicant "+getApplicantMainDataDisplay(res.applicant)+" has been unassigned", [{action:"close"}], 5000));
		if(t._answers_received == t._answers_to_wait){
			//Pop the errors if any
			if(t._errors.length > 0){
				var cont = document.createElement("div");
				var head = document.createElement("div");
				head.appendChild(document.createTextNode("The following applicants couldn't be unassigned:"));
				cont.appendChild(head);
				var table = document.createElement("table");
				var tr_head = document.createElement("tr");
				var th1 = document.createElement("th");
				var th2 = document.createElement("th");
				th1.appendChild(document.createTextNode("Applicant"));
				th2.appendChild(document.createTextNode("Reason"));
				tr_head.appendChild(th1);
				tr_head.appendChild(th2);
				table.appendChild(tr_head);
				cont.appendChild(table);
				for(var i = 0; i < t._errors.length; i++){
					var tr = document.createElement("tr");
					var td1 = document.createElement("td");
					var td2 = document.createElement("td");
					tr.appendChild(td1);
					tr.appendChild(td2);
					table.appendChild(tr);
					var link = document.createElement("a");
					link.className = "black_link";
					link.people_id = t._errors[i].applicant.people_id;
					link.appendChild(document.createTextNode(getApplicantMainDataDisplay(t._errors[i].applicant)));
					link.onclick = function(){
						var people_id = this.people_id;
						require("popup_window.js",function(){
							var pop = new popup_window("Applicant Profile");
							pop.setContentFrame("/dynamic/people/page/profile?people="+people_id);
							pop.show();
						});
						return false;
					};
					td1.appendChild(link);
					td2.appendChild(document.createTextNode(t._errors[i].detail));
				}
				error_dialog_html(cont);
			}
			unlock_screen(t._locker);
			t._popToClose.close();
		}
	};
	
	/**
	 * Refresh and populate the exam supervisors sub section
	 */
	t._refreshSupervisorsContent = function(){
		while(supervisor_container.firstChild)
			supervisor_container.removeChild(supervisor_container.firstChild);
		new session_supervisor_section(supervisor_container, session.event.id, session.supervisors, can_manage, t.reset);
	};
	
	/**
	 * Create a loading.gif element
	 * @returns {HTMLElement} div containing the GIF
	 */
	t._getLoading = function(){
		var e = document.createElement("div");
		e.innerHTML = "<img src = '"+theme.icons_16.loading+"'/>";
		return e;
	};
	
	require([["typed_field.js"],["session_supervisor_section.js","pop_select_date_and_time.js","section.js","field_time.js"]],function(){
		t._init();
	});
}