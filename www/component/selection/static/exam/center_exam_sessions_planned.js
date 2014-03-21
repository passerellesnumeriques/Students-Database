function center_exam_sessions_planned(container,EC_id,can_manage){
	var t = this;
	if( typeof container == "string") container = document.getElementById(container);
	
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
		t._setFooter();
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
			div1.appendChild(document.createTextNode("Applicants assigned to the center: "+res.total_assigned));
			div2.appendChild(document.createTextNode("Exam sessions required: "+res.required));
			var info = document.createElement("img");
			info.src = theme.icons_16.info;
			info.style.marginLeft = "10px";
			tooltip(info,"Number of exam sessions that you should create to fit with the number of applicants assigned to this exam center and its capacity");
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
			if(t._sessions.length == 0){
				var div = document.createElement("div");
				div.appendChild(document.createTextNode("No session planned yet"));
				div.style.fontStyle = "italic";
				div.style.padding = "5px";
				div.style.textAlign = "center";
				t._div_list.appendChild(div);
				t._div_list.removeChild(loading);
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
						var start = new Date(t._sessions[i].event.start*1000);//Convert timestamp into ms
						var end = new Date(t._sessions[i].event.end*1000);//Convert timestamp into ms
						var date = dateToSQL(start);
						var link = document.createElement("div");
						link.className = "black_link";
						link.appendChild(document.createTextNode(" - "+date+" ("+start.getHours()+":"+start.getMinutes()+" to "+end.getHours()+":"+end.getMinutes()+")"));
						link.session_id = t._sessions[i].event.id;
						link.onclick = function(){
							//TODO
						};
						//Set td2
						var assigned = document.createElement("div");
						for(var j = 0; j < t._nb_applicants_per_session.length; i++){
							if(t._sessions.event.id == t._nb_applicants_per_session[j].session){
								assigned.appendChild(document.createTextNode(t._nb_applicants_per_session[j].count));
								break;
							}
						}
						td2.appendChild(assigned);
						assigned.style.textAlign = 'center';
						//Set td3
						var b_list = document.createElement("div");
						b_list.className = "button_verysoft";
						b_list.appendChild(document.createTextNode("See / Edit List"));
						b_list.onclick = function(){
							//TODO
						};
						td3.appendChild(b_list);
						//Set td4
						var b_export = document.createElement("div");
						b_export.className = "button_verysoft";
						b_export.innerHTML = "<img src = '"+theme.icons_16._export+"'/> Export";
						b_export.session_id = t._sessions[i].event.id;
						b_export.onclick = function(){
							var button = this;
							require("context_menu.js",function(){
								var menu = new context_menu();
								menu.addTitleItem(null,"Export Format");
								var old = document.createElement("div");
								old.className = "context_menu_item";
								old.innerHTML = "<img src = '/static/excel/excel_16.png'/> Excel 5 (.xls)";
								old.onclick = function(){
									export_applicant_list("excel5",null,null,EC_id,null,null,'name');
								};
								menu.addItem(old);
								var new_excel = document.createElement("div");
								new_excel.className = "context_menu_item";
								new_excel.innerHTML = "<img src = '/static/excel/excel_16.png'/> Excel 2007 (.xlsx)";
								new_excel.onclick = function(){
									export_applicant_list("excel2007",null,null,EC_id,null,button.session_id,'name');
								};
								menu.addItem(new_excel);
								menu.showBelowElement(button);
							});
						};
						td4.appendChild(b_export);						
					}
					//Set the last row with total figures
					var tr_foot = document.createElement("tr");
					var td1 = document.createElement("td");
					var td2 = document.createElement("td");//Contains the total number of applicants assigned
					var td3 = document.createElement("td");//Contains see list button
					var td4 = document.createElement("td");//Contains export list button
					tr_foot.appendChild(td1);
					tr_foot.appendChild(td2);
					tr_foot.appendChild(td3);
					tr_foot.appendChild(td4);
					table.appendChild(tr_foot);
					//Set td1
					td1.appendChild(document.createTextNode("Total:"));
					td1.style.textAlign = "right";
					//Set td2
					t._total_assigned = 0;
					for(var j = 0; j < t._nb_applicants_per_session.length;j++)
						t._total_assigned += t._nb_applicants_per_session[j].count;
					td2.appendChild(document.createTextNode(t._total_assigned));
					td2.style.textAlign = "center";
					//TODO td3,4 see, export list? the same as exam center list
				});
			}
		});
	};
	
	t._refreshNotAssignedRow = function(){
		while(t._div_not_assigned.firstChild)
			t._div_not_assigned.removeChild(t._div_not_assigned.firstChild);
		var div1 = document.createElement("div");
		div1.style.display = "inline-block";
		div1.appendChild(document.createTextNode("Applicants assigned to this center but not assigned to any session:"));
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
			var total_center = res.count;
			var div2 = document.createElement("div");
			div2.style.display = "inline-block";
			var text = total_center - t._total_assigned;
			div2.appendChild(document.createTextNode(text));
			t._div_not_assigned.appendChild(div2);
		});
	};
	
	t._setFooter = function(){
		//TODO
		
	};
	
	t._getLoading = function(){
		var e = document.createElement("div");
		e.innerHTML = "<img src = '"+theme.icons_16.loading+"'/>";
		return e;
	};
	
	require("popup_window.js",function(){
		t._init();		
	});
}