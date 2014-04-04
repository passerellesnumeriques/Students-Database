function session_supervisor_section (container, session_id, supervisors, can_manage, reset){
	if(typeof container == string) container = document.getElementById(container);
	var t = this;
	
	t._init = function(){
		var header = document.createElement("div");
		header.appendChild(document.createTextNode("Supervisors"));
		header.style.fontWeight = "bold";
		header.style.textAlign = "center";
		header.style.padding = "5px";
		container.appendChild(header);
		if(supervisors.staffs.length > 0 || supervisors.customs.length > 0){
			if(supervisors.staffs.length > 0){
				var table = document.createElement("table");
				table.style.padding = "5px";
				container.appendChild(table);
				var tr_head = document.createElement("tr");
				var th_head_1 = document.createElement("th");
				var th_head_2 = document.createElement("th");
				th_head_1.appendChild(document.createTextNode("Staffs"));
				tr_head.appendChild(th_head_1);
				tr_head.appendChild(th_head_2);
				table.appendChild(tr_head);
				for(var i = 0; i < supervisors.staffs.length; i++){
					var tr = document.createElement("tr");
					var td1 = document.createElement("td");
					var td2 = document.createElement("td");
					tr.appendChild(td1);
					tr.appendChild(td2);
					table.appendChild(tr);
					td1.appendChild(document.createTextNode(supervisors.staffs[i].first_name+', '+supervisors.staffs[i].last_name));
					if(can_manage){
						var remove = document.createElement("img");
						remove.title = "Unassign this supervisor";
						remove.scr = theme.icons_16.remove;
						remove.people_id = supervisors.staffs[i].people_id;
						remove.className = "button_verysoft";
						remove.style.verticalAlign = "bottom";
						remove.onclick = function(){
							service.json("selection","unassign_supervisor_from_session",{session_id:session_id,people_id:this.people_id},function(res){
								if(!res){
									error_dialog("An error occured");
									return;
								}
								window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "The supervisor has been succesfully unassigned!", [{action:"close"}], 5000));
								reset();
							});
						};
						td2.appendChild(remove);
					}
				}
			}
			if(supervisors.customs.length > 0){
				var table = document.createElement("table");
				table.style.padding = "5px";
				container.appendChild(table);
				var tr_head = document.createElement("tr");
				var th_head_1 = document.createElement("th");
				var th_head_2 = document.createElement("th");
				th_head_1.appendChild(document.createTextNode("Customs:"));
				tr_head.appendChild(th_head_1);
				tr_head.appendChild(th_head_2);
				table.appendChild(tr_head);
				for(var i = 0; i < supervisors.customs.length; i++){
					var tr = document.createElement("tr");
					var td1 = document.createElement("td");
					var td2 = document.createElement("td");
					tr.appendChild(td1);
					tr.appendChild(td2);
					table.appendChild(tr);
					td1.appendChild(document.createTextNode(supervisors.customs[i].text));
					if(can_manage){
						var remove = document.createElement("img");
						remove.title = "Unassign this supervisor";
						remove.scr = theme.icons_16.remove;
						remove.custom_id = supervisors.customs[i].id;
						remove.className = "button_verysoft";
						remove.style.verticalAlign = "bottom";
						remove.onclick = function(){
							service.json("selection","unassign_supervisor_from_session",{session_id:session_id,custom_id:this.custom_id},function(res){
								if(!res){
									error_dialog("An error occured");
									return;
								}
								window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "The supervisor has been succesfully unassigned!", [{action:"close"}], 5000));
								reset();
							});
						};
						td2.appendChild(remove);
					}
				}
			}
			if(can_manage){
				var footer = document.createElement("div");
				footer.style.padding = "5px";
				var create = document.createElement("div");
				create.style.display = "inline-block";
				create.className = "button";
				create.appendChild(document.createTextNode("Assign"));
				create.onclick = function(){
					var p = new pop_supervisor_selection(session_id);
					p.pop.onclose = reset;
				};
				footer.appendChild(create);
				container.appendChild(footer);
			}
		} else {
			var text = document.createElement("div");
			text.appendChild(document.createTextNode("No supervisor yet"));
			text.style.fontStyle = "italic";
			text.style.textAlign = "center";
			container.appendChild(text);
		}
	};
	
	require("pop_supervisor_selection.js",function(){
		t._init();
	});
}