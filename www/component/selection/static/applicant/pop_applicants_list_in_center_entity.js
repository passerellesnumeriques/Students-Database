function pop_applicants_list_in_center_entity(EC_id,session_id,room_id, can_edit, field_null){
	var t = this;
	if(EC_id != null)
		this._mode = "exam center";
	else if(session_id != null && room_id == null)
		this._mode = "exam session";
	else if (room_id != null && session_id != null)
		this._mode = "exam center room";
	
	t._init = function(){
		var container = document.createElement("div");
		t._table = document.createElement('table');
		container.appendChild(t._table);
		t.pop = new popup_window("Applicants list","",container);
		t._setTableHeader();
		t._refreshList("name");
		t.pop.show();
	};
	t._order_by = "name";
	
	t._setTableHeader = function(){
		//Create the select "order by" row
		var thead = document.createElement("thead");
		var tr = document.createElement("tr");
		var td = document.createElement("td");
		td.colSpan = 2;
		var select = document.createElement("select");
		var by_name = document.createElement("option");
		by_name.value = "name";
		by_name.appendChild(document.createTextNode("Last name"));
		var by_id = document.createElement("option");
		by_id.value = "applicant_id";
		by_id.appendChild(document.createTextNode("Applicant ID"));
		by_id.selected = "selected";//Pre selected value
		select.appendChild(by_name);
		select.appendChild(by_id);
		select.onchange = function(){
			t._order_by = this.options[this.selectedIndex].value;
			t._refreshList();
		};
		td.appendChild(document.createTextNode("Sort by: "));
		td.appendChild(select);
		tr.appendChild(td);
		//Create the export list button
		var b = document.createElement("div");
		b.style.marginLeft = "10px";
		b.className = "button";
		b.innerHTML = "<img src = '"+theme.icons_16._export+"'/> Export List";
		b.onclick = function(){
			var button = this;
			require("context_menu.js",function(){
				var menu = new context_menu();
				menu.addTitleItem(null,"Export Format");
				var old = document.createElement("div");
				old.className = "context_menu_item";
				old.innerHTML = "<img src = '/static/excel/excel_16.png'/> Excel 5 (.xls)";
				old.onclick = function(){
					export_applicant_list("excel5",null,null,EC_id,session_id,room_id,t._order_by,field_null);
				};
				menu.addItem(old);
				var new_excel = document.createElement("div");
				new_excel.className = "context_menu_item";
				new_excel.innerHTML = "<img src = '/static/excel/excel_16.png'/> Excel 2007 (.xlsx)";
				new_excel.onclick = function(){
					export_applicant_list("excel2007",null,null,EC_id,session_id,room_id,t._order_by,field_null);
				};
				menu.addItem(new_excel);				
				menu.showBelowElement(button);
			});
		};
		td.appendChild(b);
		thead.appendChild(tr);
		t._table.appendChild(thead);
	};
	
	t._refreshList = function(){
		if(!t._tbody){
			t._tbody = document.createElement("tbody");
			t._table.appendChild(t._tbody);
		}
		if(!t._loading){
			t._loading = document.createElement("tr");
			var td = document.createElement("td");
			td.colSpan = 2;
			var img = document.createElement("img");
			img.src = theme.icons_16.loading;
			td.appendChild(img);
			t._loading.appendChild(td);
		}			
		while(t._tbody.firstChild)
			t._tbody.removeChild(t._tbody.firstChild);
		t._tbody.appendChild(t._loading);
		service.json("selection","exam/get_applicants_assigned_to_center_entity",{EC_id:EC_id, session_id:session_id, room_id:room_id, order_by:t._order_by, field_null:field_null},function(res){
			if(!res)
				error_dialog("An error occured");
			else{
				t._tbody.removeChild(t._loading);
				t._populateList(res.applicants);
			}			
		});
	};
	
	t._populateList = function(applicants){
		if(applicants == null){
			var tr = document.createElement("tr");
			var td = document.createElement("td");
			td.appendChild(document.createTextNode("No applicant"));
			td.style.fontStyle = "italic";
			td.colSpan = 2;
			tr.appendChild(td);
			t._tbody.appendChild(tr);
		} else {
			for(var i = 0; i < applicants.length;i++){
				var tr = document.createElement("tr");
				var td1 = document.createElement("td");
				var td2 = document.createElement("td");					
				tr.appendChild(td1);
				tr.appendChild(td2);
				t._tbody.appendChild(tr);
				//Set the name td
				td1.appendChild(document.createTextNode(" - "));
				var link = document.createElement("a");
				link.appendChild(document.createTextNode(getApplicantMainDataDisplay(applicants[i])));
				link.className = "black_link";
				link.people_id = applicants[i].people_id;
				link.title = "See profile";
				link.onclick = function(){
					var pop = new popup_window("Applicant Profile");
					pop.setContentFrame("/dynamic/people/page/profile?people="+this.people_id);
					pop.show();
					return false;
				};
				td1.appendChild(link);
				if(can_edit){
					//Add an unassign button
					var b = document.createElement("div");
					td2.appendChild(b);
					b.innerHTML = "<img src = '"+theme.icons_16.remove+"'/>";
					b.className = "button_verysoft";
					b.center_id = EC_id;
					b.people_id = applicants[i].people_id;
					b.title = "Unassign this applicant from the "+t._mode;
					b.onclick = function(){
						var EC_id = this.center_id;
						var people_id = this.people_id;
						var lock = lock_screen();
						service.json("selection","applicant/unassign_from_center_entity",{EC_id:EC_id,session_id:session_id,room_id:room_id,people_id:people_id},function(res){
							unlock_screen(lock);
							if(!res || (res && res.error_performing)){
								error_dialog('An error occured, the applicant was not unassigned from this '+t._mode);									
								return;
							}
							//Else cannot because of an assignment to a session or a room
							if(res.error_assigned_to_session != null && res.error_assigned_to_room != null){
								var ul = document.createElement("ul");
								var li1 = document.createElement("li");
								var li2 = document.createElement("li");
								li1.appendChild(document.createTextNode(res.session));
								li2.appendChild(document.createTextNode(res.room));
								ul.appendChild(li1);
								ul.appendChild(li2);
								error_dialog_html(ul);
							} else if (res.error_assigned_to_session != null)
								error_dialog(res.session);
							else if (res.room != null)
								error_dialog(res.error_assigned_to_room);
							else if (res.error_has_grade != null)
								error_dialog(res.error_has_grade);
							else if (res.done){
								window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "The applicant has been succesfully unassigned!", [{action:"close"}], 5000));
								t._refreshList(t._order_by);
							}
							
						});
					};
				}
			}
		}
		t.pop.resize();
	};
	
	require("popup_window.js",function(){
		t._init();
	});
}