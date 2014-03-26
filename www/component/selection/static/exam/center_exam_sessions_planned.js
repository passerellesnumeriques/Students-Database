function center_exam_sessions_planned(container,EC_id,can_manage){
	var t = this;
	if( typeof container == "string") container = document.getElementById(container);
	container.style.padding = "10px";
	
	t.reset = function(){
		t._refreshSessionsRequiredContent();
		t._refreshSessionsList();
		t._refreshNotAssignedRow();
	};
	
	t._sessions = null;
	t._init = function(){
		t._div_sessions_required = document.createElement("div");
		t._div_list = document.createElement("div");
		t._div_not_assigned = document.createElement("div");
		t._div_footer = document.createElement("div");
		container.appendChild(t._div_sessions_required);
		container.appendChild(t._div_list);
		container.appendChild(t._div_not_assigned);
		container.appendChild(t._div_footer);
		t._refreshSessionsRequiredContent();
		t._refreshSessionsList();
		t._refreshNotAssignedRow();		
	};
	
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
//			var b_list = document.createElement("div");
//			b_list.className = "button_verysoft";
//			b_list.appendChild(document.createTextNode("See / Edit List"));
//			b_list.onclick = function(){
//				var pop = new pop_applicants_list_in_center_entity(EC_id,null,null,can_manage);
//				pop.pop.onclose = t.reset;
//			};
//			var b_export = document.createElement("div");
//			b_export.className = "button_verysoft";
//			b_export.innerHTML = "<img src = '"+theme.icons_16._export+"'/> Export List";
//			b_export.onclick = function(){
//				var button = this;
//				require("context_menu.js",function(){
//					var menu = new context_menu();
//					menu.addTitleItem(null,"Export Format");
//					var old = document.createElement("div");
//					old.className = "context_menu_item";
//					old.innerHTML = "<img src = '/static/excel/excel_16.png'/> Excel 5 (.xls)";
//					old.onclick = function(){
//						export_applicant_list("excel5",null,null,EC_id,null,null,'name');
//					};
//					menu.addItem(old);
//					var new_excel = document.createElement("div");
//					new_excel.className = "context_menu_item";
//					new_excel.innerHTML = "<img src = '/static/excel/excel_16.png'/> Excel 2007 (.xlsx)";
//					new_excel.onclick = function(){
//						export_applicant_list("excel2007",null,null,EC_id,null,null,'name');
//					};
//					menu.addItem(new_excel);				
//					menu.showBelowElement(button);
//				});
//			};
//			div1.appendChild(b_list);
//			div1.appendChild(b_export);
			div2.appendChild(document.createTextNode("Exam sessions required: "+res.required));
			var info = document.createElement("img");
			info.src = theme.icons_16.info;
			info.style.marginLeft = "10px";
			tooltip(info,"Number of exam sessions that you should create to comply with the number of applicants assigned to this exam center and its capacity");
			div2.appendChild(info);
		});
	};
	
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
				var create = document.createElement("div");
				create.className = "button";
				create.appendChild(document.createTextNode("Create Session"));
				create.title = "Create a new exam session in this center";
				create.style.marginRight = "10px";
				create.onclick = t._createSession;
				t._div_list.appendChild(create);
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
					th2.appendChild(document.createTextNode("Applicants Assigned"));
					var th3 = document.createElement("th");
					var th4 = document.createElement("th");
					tr_head.appendChild(th1);
					tr_head.appendChild(th2);
					tr_head.appendChild(th3);
					tr_head.appendChild(th4);
					table.appendChild(tr_head);
					for(var i = 0; i < t._sessions.length;i++){
						var tr = document.createElement("tr");
						var td1 = document.createElement("td");//Contains the session date & link
						var td2 = document.createElement("td");//Contains the number of applicants assigned
						var td3 = document.createElement("td");//Contains see / edit list button
						var td4 = document.createElement("td");//Contains export list button
						tr.appendChild(td1);
						tr.appendChild(td2);
						tr.appendChild(td3);
						tr.appendChild(td4);
						table.appendChild(tr);
						//Set td1
						var link = document.createElement("a");												
						var name = get_exam_session_name_from_event(t._sessions[i].event);
						link.className = "black_link";
						link.appendChild(document.createTextNode(" - "+name));
						link.session_id = t._sessions[i].event.id;
						link.title = "See session profile";
						link.onclick = function(){
							var pop = new popup_window("Session Profile");
							pop.setContentFrame("/dynamic/selection/page/exam/session_profile?id="+this.session_id);
							pop.onclose = t.reset;
							pop.show();
						};
						td1.appendChild(link);
						//Set td2
						var assigned = document.createElement("div");
						for(var j = 0; j < t._nb_applicants_per_session.length; j++){
							if(t._sessions[i].event.id == t._nb_applicants_per_session[j].session){
//								assigned.appendChild(document.createTextNode(t._nb_applicants_per_session[j].count));
								assigned.appendChild(t._createFigureElement(t._nb_applicants_per_session[j].count,t._sessions[i].event.id));
								break;
							}
						}
						td2.appendChild(assigned);
						assigned.style.textAlign = 'center';
						//Set td3
//						var b_list = document.createElement("div");
//						b_list.className = "button_verysoft";
//						b_list.appendChild(document.createTextNode("See / Edit List"));
//						b_list.session_id = t._sessions[i].event.id;
//						b_list.onclick = function(){
//							var pop = new pop_applicants_list_in_center_entity(null,this.session_id,null,can_manage);
//							pop.pop.onclose = t.reset;
//						};
//						td3.appendChild(b_list);
//						//Set td4
//						var b_export = document.createElement("div");
//						b_export.className = "button_verysoft";
//						b_export.innerHTML = "<img src = '"+theme.icons_16._export+"'/> Export";
//						b_export.session_id = t._sessions[i].event.id;
//						b_export.onclick = function(){
//							var button = this;
////							require("context_menu.js",function(){
//								var menu = new context_menu();
//								menu.addTitleItem(null,"Export Format");
//								var old = document.createElement("div");
//								old.className = "context_menu_item";
//								old.innerHTML = "<img src = '/static/excel/excel_16.png'/> Excel 5 (.xls)";
//								old.onclick = function(){
//									export_applicant_list("excel5",null,null,EC_id,button.session_id,null,null);
//								};
//								menu.addItem(old);
//								var new_excel = document.createElement("div");
//								new_excel.className = "context_menu_item";
//								new_excel.innerHTML = "<img src = '/static/excel/excel_16.png'/> Excel 2007 (.xlsx)";
//								new_excel.onclick = function(){
//									export_applicant_list("excel2007",null,null,EC_id,button.session_id,null,null);
//								};
//								menu.addItem(new_excel);
//								menu.showBelowElement(button);
////							});
//						};
//						td4.appendChild(b_export);						
					}
					//Set the last row with total figures
					var tr_foot = document.createElement("tr");
					var td1 = document.createElement("td");//Contains the create session button
					var td2 = document.createElement("td");//Contains the total number of applicants assigned
					var td3 = document.createElement("td");//Contains see list button
					var td4 = document.createElement("td");//Contains export list button
					tr_foot.appendChild(td1);
					tr_foot.appendChild(td2);
					tr_foot.appendChild(td3);
					tr_foot.appendChild(td4);
					table.appendChild(tr_foot);
					//Set td1
					var create = document.createElement("div");
					create.className = "button";
					create.appendChild(document.createTextNode("Create Session"));
					create.title = "Create a new exam session in this center";
					create.style.marginRight = "10px";
					create.onclick = t._createSession;
					td1.appendChild(create);
					td1.appendChild(document.createTextNode("Total:"));
					td1.style.textAlign = "right";
					//Set td2					
					for(var j = 0; j < t._nb_applicants_per_session.length;j++)
						t._total_assigned_to_sessions += parseInt(t._nb_applicants_per_session[j].count);
					td2.appendChild(document.createTextNode(t._total_assigned_to_sessions));
					td2.style.textAlign = "center";
					t._setContentDivStillToAssign();
					//TODO td3,4 see, export list? the same as exam center list
					//Set the footer, depending on the number of sessions in t._sessions array
					t._refreshFooter();
				});
			}
		});
	};
	
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
	
	t._setContentDivStillToAssign = function(){
		if(t._div_still_to_assign){
			if(t._div_still_to_assign.firstChild)
				t._div_still_to_assign.removeChild(t._div_still_to_assign.firstChild);
			if(!isNaN(t._total_assigned_to_center) && !isNaN(t._total_assigned_to_sessions)){
				var text = parseInt(t._total_assigned_to_center) - parseInt(t._total_assigned_to_sessions);
				var link = t._createFigureElement(text,null,true);
				link.style.color = text > 0 ? "red" : "green";
				t._div_still_to_assign.appendChild(link);
			}
		}
	};
	
	t._refreshFooter = function(){
		while(t._div_footer.firstChild)
			t._div_footer.removeChild(t._div_footer.firstChild);
		var div1 = document.createElement("div");
		var table = document.createElement("table");
		t._div_footer.appendChild(div1);
		t._div_footer.appendChild(table);
		div1.appendChild(document.createTextNode("Actions:"));
		div1.style.fontWeight = "bold";
		div1.style.fontSize = "large";
		div1.style.textAlign = "left";
		//Set table
		var tr_head = document.createElement("tr");
		var tr1 = document.createElement("tr");
		var tr2 = document.createElement("tr");
		var th1 = document.createElement("th");//Automatic actions column
		var th2 = document.createElement("th");//Manual actions column		
		var td11 = document.createElement("td");
		var td12 = document.createElement("td");
		var td21 = document.createElement("td");
		var td22 = document.createElement("td");
		tr_head.appendChild(th1);
		tr_head.appendChild(th2);
		tr1.appendChild(td11);
		tr1.appendChild(td12);
		tr2.appendChild(td21);
		tr2.appendChild(td22);
		table.appendChild(tr_head);
		table.appendChild(tr1);
		table.appendChild(tr2);
		th1.appendChild(document.createTextNode("Automatic"));
		th2.appendChild(document.createTextNode("Manual"));
		var b_auto_assign_to_session = document.createElement("div");
		b_auto_assign_to_session.className = "button";
		b_auto_assign_to_session.title = "Automatically assign applicants to the sessions planned in this center";
		b_auto_assign_to_session.appendChild(document.createTextNode("Assign to sessions"));
		b_auto_assign_to_session.onclick = function(){
			service.json("selection","applicant/automaticallyAssignToExamSessions",{EC_id:EC_id},function(res){
				if(!res){
					error_dialog("An error occured, the applicants were not assigned");
					return;
				}
				else if (res.error != null)
					error_dialog(res.error);
				else{
					window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, res.assigned+" applicants have been succesfully assigned to the sessions!", [{action:"close"}], 5000));
					t.reset();
				}
			});
		};
		td11.appendChild(b_auto_assign_to_session);
		var b_auto_assign_to_session_and_room = document.createElement("div");
		b_auto_assign_to_session_and_room.className = "button";
		b_auto_assign_to_session_and_room.title = "Automatically assign applicants to the sessions planned in this center, and also in the rooms";
		b_auto_assign_to_session_and_room.appendChild(document.createTextNode("Assign to sessions and rooms"));
		b_auto_assign_to_session_and_room.onclick = function(){
			var locker = lock_screen();
			service.json("selection","applicant/automaticallyAssignToSessionsAndRooms",{EC_id:EC_id},function(res){
				unlock_screen(locker);
				if(!res){
					error_dialog("An error occured, the applicants were not assigned");
					return;
				}
				window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, res.assigned+" applicants have been succesfully assigned to the sessions and rooms!", [{action:"close"}], 5000));
				t.reset();
			});
		};
		td21.appendChild(b_auto_assign_to_session_and_room);
		var b_manually_assign_to_session = document.createElement("div");
		b_manually_assign_to_session.className = "button";
		b_manually_assign_to_session.title = "Manually assign applicants to the sessions planned in this center";
		b_manually_assign_to_session.appendChild(document.createTextNode("Assign to sessions"));
		b_manually_assign_to_session.onclick = function(){
			var pop = new popup_window("Assign applicants to exam sessions");
			pop.setContentFrame("/dynamic/selection/page/applicant/manually_assign_to_exam_entity?mode=session&center="+EC_id);
			pop.onclose = t.reset;
			pop.show();
		};
		td12.appendChild(b_manually_assign_to_session);
	};
	
	t._createSession = function(){
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
					"Create an exam session",
					null,
					all_values,
					default_duration_seconds,
					t._performCreateSession
				);
			});
		});
	};
	
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
			var pop = new popup_window("Exam Session");
			pop.setContentFrame("/dynamic/selection/page/exam/session_profile?id="+res.event_id);
			pop.show();
			pop.onclose = t.reset;
		});
	};
	
	t._getLoading = function(){
		var e = document.createElement("div");
		e.innerHTML = "<img src = '"+theme.icons_16.loading+"'/>";
		return e;
	};
	
	t._createFigureElement = function(figure,session_id, not_in_session){
		var link = document.createElement("a");
		figure = (figure == null || isNaN(figure)) ? 0 : parseInt(figure);
		link.appendChild(document.createTextNode(figure));
		if(figure > 0){
			link.className= "black_link";
			link.style.fontStyle = "italic";
			link.title = (can_manage == false || not_in_session == true) ? "See / Export the list": "See / Edit / Export the list";
			link.onclick = function(){
				var menu = new context_menu();
				var see = document.createElement("div");
				see.className = "context_menu_item";
				if(can_manage == false || not_in_session == true)
					see.appendChild(document.createTextNode("See List"));
				else
					see.appendChild(document.createTextNode("See / Edit List"));
				see.onclick = function(){
					var onPopReady = function(p){
						if(!not_in_session)
							p.pop.onclose = t.reset;
					};
					if(!session_id && !not_in_session)
						var pop = new pop_applicants_list_in_center_entity(EC_id,null,null,can_manage,null,onPopReady);
					else if(!session_id && not_in_session)
						var pop = new pop_applicants_list_in_center_entity(EC_id,null,null,false,"exam_session",onPopReady);
					else
						var pop = new pop_applicants_list_in_center_entity(null,session_id,null,can_manage,null,onPopReady);					
				};				
				var b_export = document.createElement("div");
				b_export.className = "context_menu_item";
				b_export.innerHTML = "<img src = '"+theme.icons_16._export+"'/> Export List";
				b_export.onclick = function(){
					var m = new context_menu();
//					m.addTitleItem(null,"Export Format");
					var old = document.createElement("div");
					old.className = "context_menu_item";
					old.innerHTML = "<img src = '/static/excel/excel_16.png'/> Excel 5 (.xls)";
					old.onclick = function(){
						menu.hide();
						if(not_in_session)
							export_applicant_list("excel5",null,null,EC_id,null,null,'applicant_id',"exam_session");
						else
							export_applicant_list("excel5",null,null,EC_id,session_id,null,'applicant_id');
					};
					m.addItem(old);
					var new_excel = document.createElement("div");
					new_excel.className = "context_menu_item";
					new_excel.innerHTML = "<img src = '/static/excel/excel_16.png'/> Excel 2007 (.xlsx)";
					new_excel.onclick = function(){
						menu.hide();
						if(not_in_session)
							export_applicant_list("excel2007",null,null,EC_id,null,null,'applicant_id',"exam_session");
						else
							export_applicant_list("excel2007",null,null,EC_id,session_id,null,'applicant_id');
					};
					m.addItem(new_excel);				
					m.showBelowElement(this);
				};
				menu.addItem(see);
				menu.addItem(b_export,true);
				menu.showBelowElement(this);
			};
		}
		return link;
	};
	
	require([["typed_field.js","popup_window.js","context_menu.js","pop_applicants_list_in_center_entity.js","calendar_objects.js"],["field_time.js"]],function(){
		t._init();		
	});
}