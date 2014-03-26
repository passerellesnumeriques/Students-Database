function exam_session_profile(container,date_container, supervisor_container, list_container,actions_container, session, can_manage){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	if(typeof date_container == "string")
		date_container = document.getElementById(date_container);
	if(typeof supervisor_container == "string")
		supervisor_container = document.getElementById(supervisor_container);
	if(typeof list_container == "string")
		list_container = document.getElementById(list_container);
	if(typeof actions_container == "string")
		actions_container = document.getElementById(actions_container);
	
	t._init = function(){
		t._refreshDateContent();
		date_container.style.margin = "15px";
		t._refreshListContent();
		t._refreshFooter();
	};
	
	t._refreshDateContent = function(){
		if(!t._date_content){
			t._date_content = document.createElement("div");
			t._date_content.style.padding = "10px";
		}			
		if(!t._date_section){
			t._date_section = new section("","Date",t._date_content);
			date_container.appendChild(t._date_section.element);
			if(can_manage){
				//Set the set date button
				var b = document.createElement("div");
				b.className = "button";
				b.onclick = function(){
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
								function(event){
									session.event = event;
									service.json("calendar","save_event",{event:session.event},function(r){
										if(!r)
											error_dialog("An error occured, the session date was not updated");
										else
											t._refreshDateContent();
									});									
								},
								null,
								session.event
							);
						});
					});
				};
				b.innerHTML = "<img src = '/static/selection/IS/date_clock_picker.png'/> Set the date";
				t._date_section.addToolBottom(b);
			}			
		}
		while(t._date_content.firstChild)
			t._date_content.removeChild(t._date_content.firstChild);
		var row1 = document.createElement("div");
		row1.style.marginBottom = "5px";
		var row2 = document.createElement("div");
		//Set the first row, with the date field		
		var date = new Date(parseInt(session.event.start) * 1000);
		row1.appendChild(document.createTextNode("Date: "+dateToSQL(date)));
		//Set the second row with the start and end time
		var d_start = new Date(parseInt(session.event.start) * 1000);
		var _start = d_start.getHours()+":"+d_start.getMinutes();
		var start_field = new field_time(_start, false, {can_be_null:false});
		var start_elem = start_field.getHTMLElement();
		var d_end = new Date(parseInt(session.event.end) * 1000);
		var _end = d_end.getHours()+":"+d_end.getMinutes();
		var end_field = new field_time(_end, false, {can_be_null:false});
		var end_elem = end_field.getHTMLElement();
		row2.appendChild(document.createTextNode("Start time:"));
		start_elem.style.marginLeft = "3px";
		start_elem.style.marginRight = "30px";
		row2.appendChild(start_elem);
		row2.appendChild(document.createTextNode("End time:"));
		end_elem.style.marginLeft = "3px";
		row2.appendChild(end_elem);
		t._date_content.appendChild(row1);
		t._date_content.appendChild(row2);
	};
	
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
			} else {
				t._populateList(res.rooms, res.count_session);
			}
		});
	};
	
	t._populateList = function(rooms, total_applicants_in_session){
		t._total_assigned = 0;
		//Set the header
		var header = document.createElement("div");
		header.appendChild(document.createTextNode("Applicants assigned to this session:"));
		var all = t._createFigureElement(total_applicants_in_session, null, false);
		all.style.marginLeft = "3px";
		header.appendChild(all);
		list_container.appendChild(header);
		var table = document.createElement("table");
		//Set the column headers
		var tr_head = document.createElement("tr");
		var th_rooms = document.createElement("th");
		var th_applicants = document.createElement("th");
		th_rooms.appendChild(document.createTextNode('Rooms'));
		th_applicants.appendChild(document.createTextNode("Applicants Assigned"));
		tr_head.appendChild(th_rooms);
		tr_head.appendChild(th_applicants);
		table.appendChild(tr_head);
		//Set the body
		for(var i = 0; i < rooms.length; i++){
			var tr = document.createElement("tr");
			var td1 = document.createElement("td");
			var td2 = document.createElement("td");
			td1.style.textAlign = "center";
			td2.style.textAlign = "center";
			tr.appendChild(td1);
			tr.appendChild(td2);
			table.appendChild(tr);
			//Set the room name in td1
			td1.appendChild(document.createTextNode(rooms[i].name));
			//Set the number of applicants in the td2
			td2.appendChild(t._createFigureElement(rooms[i].applicants, rooms[i].id, false));
			t._total_assigned += parseInt(rooms[i].applicants);
		}
		//Set the total row
		var tr_total = document.createElement("tr");
		var td1 = document.createElement("td");
		var td2 = document.createElement("td");
		tr_total.appendChild(td1);
		tr_total.appendChild(td2);
		table.appendChild(tr_total);
		td1.appendChild(document.createTextNode("Total:"));
		td1.style.textAlign = "right";
		td2.style.textAlign = "center";
		td2.appendChild(document.createTextNode(t._total_assigned));
		list_container.appendChild(table);
		//Set the non-assigned row
		total_applicants_in_session = parseInt(total_applicants_in_session);
		var not_assigned = total_applicants_in_session - t._total_assigned;
		var footer = document.createElement("div");		
		footer.appendChild(document.createTextNode("Applicants assigned to this session but not to any room:"));
		var link = t._createFigureElement(not_assigned,null,true);
		link.style.marginLeft = "3px";
		link.style.color = not_assigned > 0 ? "red" : "green";
		footer.appendChild(link);
		list_container.appendChild(footer);
	};
	
	t._refreshFooter = function(){
		while(actions_container.firstChild)
			actions_container.removeChild(actions_container.firstChild);
		var table = document.createElement("table");
		var tr_head = document.createElement("tr");
		var tr_buttons = document.createElement("tr");
		var th = document.createElement("th");
		var td_auto = document.createElement("td");
		var td_manual = document.createElement("td");
		tr_head.appendChild(th);
		th.colSpan = 2;
		tr_buttons.appendChild(td_auto);
		tr_buttons.appendChild(td_manual);
		table.appendChild(tr_head);
		table.appendChild(tr_buttons);
		actions_container.appendChild(table);
		th.appendChild(document.createTextNode("Assign to rooms"));
		var b_assign_auto = document.createElement("div");
		b_assign_auto.className = "button";
		b_assign_auto.appendChild(document.createTextNode("Automatic"));
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
				t._refreshListContent();
			});
		};
		td_auto.appendChild(b_assign_auto);
		var b_assign_manually = document.createElement("div");
		b_assign_manually.className = 'button';
		b_assign_manually.appendChild(document.createTextNode("Manual"));
		b_assign_manually.session_id = session.event.id;
		b_assign_manually.onclick = function(){
			var session_id = this.session_id;
			require("popup_window.js",function(){
				var pop = new popup_window("Manually assign applicants to rooms");
				pop.onclose = t._refreshListContent;
				pop.setContentFrame("/dynamic/selection/page/applicant/manually_assign_to_exam_entity?mode=room&session="+session_id);
				pop.show();
			});
		};
		td_manual.appendChild(b_assign_manually);
	};
	
	t._createFigureElement = function(figure,room_id, not_in_room){
		var link = document.createElement("a");
		figure = (figure == null || isNaN(figure)) ? 0 : parseInt(figure);
		link.appendChild(document.createTextNode(figure));
		if(figure > 0){
			link.className= "black_link";
			link.style.fontStyle = "italic";
			link.title = (can_manage == false || not_in_room == true) ? "See / Export the list": "See / Edit / Export the list";
			link.onclick = function(){
				var menu = new context_menu();
				var see = document.createElement("div");
				see.className = "context_menu_item";
				if(can_manage == false || not_in_room == true)
					see.appendChild(document.createTextNode("See List"));
				else
					see.appendChild(document.createTextNode("See / Edit List"));
				see.onclick = function(){
					var onPopReady = function(p){
						if(!not_in_room)
							p.pop.onclose = t._refreshListContent;
					};	
					if(!room_id && !not_in_room)
						var pop = new pop_applicants_list_in_center_entity(null,session.event.id,null,can_manage,null,onPopReady);
					else if(!room_id && not_in_room)
						var pop = new pop_applicants_list_in_center_entity(null,session.event.id,null,false,"exam_center_room",onPopReady);
					else
						var pop = new pop_applicants_list_in_center_entity(null,session.event.id,room_id,can_manage,null,onPopReady);								
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
						if(not_in_room)
							export_applicant_list("excel5",null,null,null,session.event.id,null,'applicant_id',"exam_center_room");
						else{
							export_applicant_list("excel5",null,null,null,session.event.id,room_id,'applicant_id');
						}
					};
					m.addItem(old);
					var new_excel = document.createElement("div");
					new_excel.className = "context_menu_item";
					new_excel.innerHTML = "<img src = '/static/excel/excel_16.png'/> Excel 2007 (.xlsx)";
					new_excel.onclick = function(){
						menu.hide();
						if(not_in_room)
							export_applicant_list("excel2007",null,null,null,session.event.id,null,'applicant_id',"exam_center_room");
						else
							export_applicant_list("excel2007",null,null,null,session.event.id,room_id,'applicant_id');
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
	
	t._getLoading = function(){
		var e = document.createElement("div");
		e.innerHTML = "<img src = '"+theme.icons_16.loading+"'/>";
		return e;
	};
	
	require([["typed_field.js"],["pop_select_date_and_time.js","pop_applicants_list_in_center_entity.js","section.js","field_time.js","context_menu.js"]],function(){
		t._init();
	});
}