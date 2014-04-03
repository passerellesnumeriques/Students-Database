/**
 * Create the content of the applicant assignment section for a given exam center
 * This screen contains all the figures related to the applicant assignment (to center, to sessions, to rooms...) and the buttons implementing all the actions to manage the applicants assignment
 * @param {HTMLElement | String} container
 * @param {Number} EC_id exam center ID
 * @param {String} EC_name exam center name
 * @param {Boolean} can_manage
 * @param {Custom_Event} onupdateroom custom_event to be listened to by this object. When the exam center rooms of this center are updated (in exam center caracteristics section), this event is fired, so the reset method is called to update all the displayed data
 * @param {Custom_Event} onupdateapplicants custom_event to be fired when the applicants assignment to sessions / rooms is updated. This way the manage_exam_center_room object is reseted, and the user rights may be updated (rooms cannot be removed after applicants assignment)
 */
function center_exam_sessions_planned(container,EC_id, EC_name,can_manage,onupdateroom,onupdateapplicants){
	var t = this;
	if( typeof container == "string") container = document.getElementById(container);
	container.style.padding = "10px";
	
	/**
	 * Reset the content of the section
	 */
	t.reset = function(){
		t._refreshSessionsRequiredContent();
		t._refreshSessionsList();
		t._refreshNotAssignedRow();
	};
	
	/** Private methods and attributes */
	
	t._sessions = null;
	
	/**
	 * Launch the process, add the listener to onupdateroom custom event
	 */
	t._init = function(){
		t._div_sessions_required = document.createElement("div");
		t._div_list = document.createElement("div");
		t._div_not_assigned = document.createElement("div");
		t._div_footer = document.createElement("div");
		container.appendChild(t._div_sessions_required);
		container.appendChild(t._div_not_assigned);
		container.appendChild(t._div_list);		
		container.appendChild(t._div_footer);
		t._refreshSessionsRequiredContent();
		t._refreshSessionsList();
		t._refreshNotAssignedRow();		
		if(onupdateroom)
			onupdateroom.add_listener(t.reset);
	};
	
	/**
	 * Create a row containing the number of sessions required in this center (based on the center capacity and on the number of applicants assigned)
	 */
	t._refreshSessionsRequiredContent = function(){
		while(t._div_sessions_required.firstChild)
			t._div_sessions_required.removeChild(t._div_sessions_required.firstChild);
		var loading = t._getLoading();
		t._div_sessions_required.appendChild(loading);
		service.json("selection","exam/center_get_number_sessions_required",{EC_id:EC_id},function(res){
			if(!res){
				error_dialog("An error occured");
				return;
			}
			t._div_sessions_required.removeChild(loading);
			var div1 = document.createElement("div");
			var div2 = document.createElement("div");
			t._div_sessions_required.appendChild(div1);
			t._div_sessions_required.appendChild(div2);
			div1.appendChild(document.createTextNode("Applicants assigned to the center: "));
			div1.appendChild(t._createFigureElement(res.total_assigned));
			div2.appendChild(document.createTextNode("Exam sessions required: "+res.required));
			var info = document.createElement("img");
			info.src = theme.icons_16.info;
			info.style.marginLeft = "10px";
			tooltip(info,"Number of exam sessions that you should create to comply with the number of applicants assigned to this exam center and its capacity");
			div2.appendChild(info);
		});
	};
	
	/**
	 * Set a table containing all the sessions set in this exam center
	 * One row is created per session, and populated with an exam_session_profile object
	 * The user can update the session date by clicking on it
	 * Two buttons are added: empty session (unassign all the applicants from a session) and create new session
	 */
	t._refreshSessionsList = function(){
		while(t._div_list.firstChild)
			t._div_list.removeChild(t._div_list.firstChild);
		var loading = t._getLoading();
		t._div_list.appendChild(loading);
		service.json("selection","exam/center_get_all_sessions",{EC_id:EC_id},function(res){
			if(!res){
				error_dialog("An error ocurred");
				return;	
			}			
			t._sessions = res;
			t._total_assigned_to_sessions = 0;
			if(t._sessions.length == 0){
				var div = document.createElement("div");
				div.appendChild(document.createTextNode("No session planned yet"));
				div.style.fontStyle = "italic";
				div.style.padding = "5px";
				div.style.textAlign = "center";
				t._div_list.appendChild(div);
				if(can_manage){
					t._div_list.appendChild(t._getCreateSessionButton());
				}
				t._div_list.removeChild(loading);
				t._setContentDivStillToAssign();
			} else {
				service.json("selection","applicant/get_assigned_to_sessions_for_center",{EC_id:EC_id,count:true},function(r){
					if(!r){
						error_dialog("An error ocurred");
						return;	
					}
					t._nb_applicants_per_session = r.data;
					t._div_list.removeChild(loading);
					var table = document.createElement("table");
					t._div_list.appendChild(table);
					var tr_head = document.createElement("tr");
					var th1 = document.createElement("th");
					th1.appendChild(document.createTextNode("Sessions"));
					var th2 = document.createElement("th");
//					th2.appendChild(document.createTextNode("Applicants Assigned"));
					tr_head.appendChild(th1);
					tr_head.appendChild(th2);
					table.appendChild(tr_head);
					for(var i = 0; i < t._sessions.length;i++){
						var tr = document.createElement("tr");
						var td1 = document.createElement("td");//Contains the session date & link
						var td2 = document.createElement("td");//Contains the number of applicants assigned
						td1.style.borderBottom = "1px solid #808080";
						td1.style.padding = "15px";
						tr.appendChild(td1);
						tr.appendChild(td2);
						table.appendChild(tr);
						//Set td1
						var link = document.createElement("a");												
						var name = getExamSessionNameFromEvent(t._sessions[i].event);
						link.className = "black_link";
						link.appendChild(document.createTextNode(" - "+name));
						link.session_index = i;
						link.title = "Set the date";
						link.onclick = function(){
							t._popSelectSessionDate(this.session_index);
							return false;
						};
						td1.appendChild(link);
						if(can_manage){
							var b_remove_session = document.createElement("img");
							b_remove_session.className = "button_verysoft";
							b_remove_session.src = theme.icons_16.remove;
							b_remove_session.session_id = t._sessions[i].event.id;
							b_remove_session.title = "Remove this exam session";
							b_remove_session.onclick = function(){
								var session_id = this.session_id;
								new confirm_dialog("Do you really want to remove this exam session?<br/><i>Note: all the applicants assigned will be automatically unassigned</i>",function(r){
									if(r){
										var locker = lock_screen();
										service.json("selection","exam/remove_session",{id:session_id},function(res){
											unlock_screen(locker);
											if(!res){
												error_dialog("An error occured, this exam session was not removed");
												return;
											}
											var total_unassigned = 0;
											var error_has_grade = [];
											if(res.applicants != null){
												for(var i = 0; i < res.applicants.length; i++){
													if(!res.performed){
														if(res.error_has_grade)
															error_has_grade.push(res.applicants[i].applicant);
													} else 
														total_unassigned++;
												}
											}
											if(!res.performed){
												if(error_has_grade.length > 0){
													var cont = document.createElement("div");
													var header = document.createElement("div");
													var body = document.createElement("div");
													cont.appendChild(header);
													cont.appendChild(body);
													header.appendChild(document.createTextNode("Following applicants cannot be unassigned because they already have exam results:"));
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
											} else {
												if(total_unassigned > 0)
													window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "The exam session has been succesfully removed ("+total_unassigned+" applicants unassigned)!", [{action:"close"}], 5000));
												else
													window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "The exam session has been succesfully removed!", [{action:"close"}], 5000));
											}
											t.reset();
											if(onupdateapplicants)
												onupdateapplicants.fire();
										});
									}
								});							
							};
							b_remove_session.style.marginLeft = "7px";
							b_remove_session.style.verticalAlign = "bottom";
							td1.appendChild(b_remove_session);
						}
						//Set td2
						var div_list = document.createElement("div");
						div_list.style.display = "inline-block";
						var div_supervisor = document.createElement("div");
						div_supervisor.style.display = "inline-block";
						td2.appendChild(div_list);
						td2.appendChild(div_supervisor);
						td2.style.borderBottom = "1px solid #808080";
						td2.style.padding = "15px";
						var onreset;
						if(onupdateapplicants){
							onreset = function(){
								t.reset();
								onupdateapplicants.fire();
							};
						} else 
							onreset = t.reset;
						new exam_session_profile(td2, div_list, div_supervisor, t._sessions[i], can_manage, onreset);
					}
					//Set the last row with total figures
					var tr_foot = document.createElement("tr");
					var td1 = document.createElement("td");//Contains the create session button
					var td2 = document.createElement("td");//Contains the total number of applicants assigned
					tr_foot.appendChild(td1);
					tr_foot.appendChild(td2);
					table.appendChild(tr_foot);
					//Set td1
					if(can_manage){
						td1.appendChild(t._getCreateSessionButton());
					}
					td1.appendChild(document.createTextNode("Total:"));
					td1.style.textAlign = "right";
					//Set td2					
					for(var j = 0; j < t._nb_applicants_per_session.length;j++)
						t._total_assigned_to_sessions += parseInt(t._nb_applicants_per_session[j].count);
					td2.appendChild(document.createTextNode(t._total_assigned_to_sessions));
					td2.style.textAlign = "center";
					t._setContentDivStillToAssign();
				});
			}
		});
	};
	
	/**
	 * Create the create session button
	 * @returns {HTMLElement}
	 */
	t._getCreateSessionButton = function(){
		var create = document.createElement("div");
		create.className = "button";
		create.appendChild(document.createTextNode("Create Session"));
		create.title = "Create a new exam session in this center";
		create.style.marginRight = "10px";
		create.onclick = function(){t._popSelectSessionDate(null);};
		return create;
	};
	
	/**
	 * Pop up the select session date (calling pop_select_date_and_time.js script), prepare the method called when ok button is pressed
	 * @param {Number | NULL} index if NULL means the popup shall create a new exam session, else must be the index of the session within t._sessions event
	 */
	t._popSelectSessionDate = function(index){
		var title = index == null ? "Create a exam session" : "Set the exam session date";
		var _event = index == null ? null : t._sessions[index].event;
		var onok;
		if(index != null){
			onok = function(event){
				var locker = lock_screen();
				service.json("calendar","save_event",{event:event},function(r){
					unlock_screen(locker);
					if(!r)
						error_dialog("An error occured, the session date was not updated");
					else
						t.reset();
				});									
			};
		} else {
			onok = t._performCreateSession;
		}
		require("pop_select_date_and_time.js",function(){
			service.json("selection","config/get_all_values_and_default",{name:"default_duration_exam_session"},function(res){
				if(!res){
					error_dialog('An error occured, functionality not available');
					return;
				}
				var all_values = [];
				for(var i = 0; i < res.all_values.length; i++){
					var duration_in_seconds = res.all_values[i].split(" ");
					duration_in_seconds = parseInt(duration_in_seconds[0]) * 60 * 60;
					all_values.push({name:res.all_values[i], value:duration_in_seconds});
				}
				var default_duration_seconds = res.default_value.split(" ");
				default_duration_seconds = parseInt(default_duration_seconds[0]) * 60 * 60;
				new pop_select_date_and_time(
					title,
					null,
					all_values,
					default_duration_seconds,
					onok,
					null,
					_event
				);
			});
		});
	};
	
	/**
	 * Create a row containing the number of applicants assigned to this center but not assigned to any session
	 */
	t._refreshNotAssignedRow = function(){
		while(t._div_not_assigned.firstChild)
			t._div_not_assigned.removeChild(t._div_not_assigned.firstChild);
		var div1 = document.createElement("div");
		div1.style.display = "inline-block";
		div1.appendChild(document.createTextNode("Applicants assigned to this center but not assigned to any session: "));
		t._div_not_assigned.appendChild(div1);
		var loading = document.createElement("div");
		loading.style.display = "inline-block";
		loading.appendChild(t._getLoading());
		t._div_not_assigned.appendChild(loading);
		service.json("selection","exam/get_applicants_assigned_to_center_entity",{EC_id:EC_id,count:true},function(res){
			if(!res){
				error_dialog("An error occured");
				return;
			}
			t._div_not_assigned.removeChild(loading);
			t._total_assigned_to_center = res.count;
			t._div_still_to_assign = document.createElement("div");
			t._div_still_to_assign.style.display = "inline-block";
			t._div_still_to_assign.style.marginLeft = "3px";
			t._setContentDivStillToAssign();
			t._div_not_assigned.appendChild(t._div_still_to_assign);
		});
	};
	
	/**
	 * Update the number of applicants still to assign within the container displaying it
	 * Must be updated separetely because this figures depends on the result of several sevices
	 */
	t._setContentDivStillToAssign = function(){
		if(t._div_still_to_assign){
			while(t._div_still_to_assign.firstChild)
				t._div_still_to_assign.removeChild(t._div_still_to_assign.firstChild);
			if(!isNaN(t._total_assigned_to_center) && !isNaN(t._total_assigned_to_sessions)){
				var text = parseInt(t._total_assigned_to_center) - parseInt(t._total_assigned_to_sessions);
				var link = t._createFigureElement(text,null,true);
				link.style.color = text > 0 ? "red" : "green";
				t._div_still_to_assign.appendChild(link);
				if(text > 0 && can_manage){
					var div_buttons = document.createElement("div");
					div_buttons.appendChild(document.createTextNode('Assign remainings: '));
					var b_auto = document.createElement("div"); 
					b_auto.className = "button";
					b_auto.title = "Automatically assign applicants to the sessions planned in this center, and also in the rooms";
					b_auto.appendChild(document.createTextNode("Automatically"));
					b_auto.onclick = function(){
						var locker = lock_screen();
						service.json("selection","applicant/automaticallyAssignToSessionsAndRooms",{EC_id:EC_id},function(res){
							unlock_screen(locker);
							if(!res){
								error_dialog("An error occured, the applicants were not assigned");
								return;
							}
							window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, res.assigned+" applicants have been succesfully assigned to the sessions and rooms!", [{action:"close"}], 5000));
							t.reset();
							if(onupdateapplicants)
								onupdateapplicants.fire();
						});
					};
					b_auto.style.display = "inline-block";
					b_auto.style.marginLeft = "3px";
					b_auto.style.marginRight = "3px";
					div_buttons.appendChild(b_auto);
					var b_manually = document.createElement("div");
					b_manually.className = "button";
					b_manually.title = "Manually assign applicants to the sessions planned in this center";
					b_manually.appendChild(document.createTextNode("Manually"));
					b_manually.EC_id = EC_id;
					b_manually.onclick = function(){
						var pop = new popup_window("Assign applicants to exam sessions");
						pop.setContentFrame("/dynamic/selection/page/applicant/manually_assign_to_exam_entity?mode=session&center="+EC_id);
						pop.onclose = function(){t.reset(); if(onupdateapplicants) onupdateapplicants.fire();};
						pop.show();
					};
					b_manually.style.display = "inline-block";
					b_manually.style.marginLeft = "3px";
					div_buttons.appendChild(b_manually);
					div_buttons.style.display = "inline-block";
					div_buttons.style.marginLeft = "15px";
					div_buttons.style.fontStyle = "italic";
					t._div_still_to_assign.appendChild(div_buttons);
				}
			}
		}
	};
	
	/**
	 * Create an exam session
	 * Prepares the event object and call the exam/create_session service
	 * @param {CalendarEvent} event the session event to process
	 */
	t._performCreateSession = function(event){
		//Process the event		
		event.description = "Exam session";
		event.organizer = "Selection";
		event.participation = calendar_event_participation_unknown;
		event.role = calendar_event_role_for_info;
		//title, app_link will be updated by create session service
		var locker = lock_screen();
		service.json("selection","exam/create_session",{event:event,EC_id:EC_id},function(res){
			unlock_screen(locker);
			if(!res){
				error_dialog("An error occured, your session was not created");
				return;
			}
			t.reset();
		});
	};
	
	/**
	 * Create an image containing a loading gif
	 * @returns {HTMLElement}
	 */
	t._getLoading = function(){
		var i = document.createElement("img");
		i.src = theme.icons_16.loading;
		return i;
	};
	
	/**
	 * Create a link to the datalist related (list of applicants assigned to a session, not assigned to a session, assigned to the center), with the possibility to unassign the applicants from the matching entity
	 * @param {Number} figure the content of the link. If this figure is greater than 0, the link is clickable, and fontStyle is italic to show to the user that he can click on it
	 * @param {Number | NULL} session_index if not NULL must be the index of the session (from which applicants list is populated) within t._sessions array
	 * @param {Boolean} not_in_session if true means that the link must refer to the list of the applicants assigned to the center but no assigned to any session
	 * @returns {HTMLElement}
	 */
	t._createFigureElement = function(figure, session_index, not_in_session){
		var link = document.createElement("a");
		figure = (figure == null || isNaN(figure)) ? 0 : parseInt(figure);
		link.appendChild(document.createTextNode(figure));
		if(figure > 0){
			link.className= "black_link";
			link.style.fontStyle = "italic";
			link.title = (can_manage == false || not_in_session == true) ? "See / Export the list": "See / Edit / Export the list";
			link.onclick = function(){				
				require(["prepare_applicant_list.js","popup_window.js"],function(){					
					var for_list = new prepare_applicant_list();
					for_list.forbidApplicantCreation();
					for_list.forbidApplicantImport();
					var popup_name;
					var button_name = null;
					if(!session_index && !not_in_session){
						//Center list
						if(can_manage)
							for_list.makeApplicantsSelectable();
						for_list.addFilter("Exam Center",EC_id,true);
						popup_name = "Exam center "+EC_name+" applicants";
						button_name = "Unassign from exam center";
					} else if(!session_index && not_in_session){
						//Not in session list						
						for_list.addFilter("Exam Session",null,true);
						for_list.addFilter("Exam Center",EC_id,true);
						popup_name = "Applicants with no session in "+EC_name;
					} else {
						//Session list
						if(can_manage)
							for_list.makeApplicantsSelectable();
						for_list.addFilter("Exam Session",t._sessions[session_index].event.id, true);
						popup_name = "Session "+getExamSessionNameFromEvent(t._sessions[session_index].event)+" in "+EC_name+" center";
						button_name = "Unassign from session";
					}
					var p = new popup_window(popup_name);
					var frame = p.setContentFrame("/dynamic/selection/page/applicant/list",null,for_list.getDataToPost());
					if(can_manage && button_name != null){
						p.onclose = t.reset;
						var session_id = session_index != null ? t._sessions[session_index].event.id : null;
						var _EC_id = session_id != null ? null : EC_id;
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
								service.json("selection","applicant/unassign_from_center_entity",{people_id:to_unassign[i],session_id:session_id,EC_id:_EC_id},t._onApplicantUnassigned);
							}
						});
						p.onclose = t.reset; //Refresh because applicant can have been unassigned
					}
					p.show();
				});
			};
		}
		return link;
	};
	
	/**
	 * Method called when applicants are unassigned from an entity from the applicants datalist
	 * When the unassign button is clicked, the service applicant/unassign_from_center_entity is called for each applicant (only unassign the applicants that can really be unassigned)
	 * This method gets all the results from the service calls, and once all are gotten (no more pending answer), an error message is displayed to explain why any applicants couldn't be unassigned, and the popup window is closed
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
					td2.style.paddingLeft = "20px";
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
	
	require([["typed_field.js","popup_window.js","context_menu.js","calendar_objects.js","exam_session_profile.js"],["field_time.js"]],function(){
		t._init();		
	});
}