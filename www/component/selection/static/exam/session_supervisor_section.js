/**
 * Create a list containing the supervisors assigned to a given exam session
 * @param {String| HTMLElement} container
 * @param {Number} session_id exam session ID
 * @param {ExamSessionSupervisors} supervisors
 * @param {Boolean} can_manage
 * @param {Function} reset to call to reset (and get the updated data within the database)
 */
function session_supervisor_section (container, session_id, supervisors, can_manage, reset){
	if(typeof container == "string") container = document.getElementById(container);
	var t = this;
	
	/** Private properties and methods */
	
	/**
	 * Launch the process, create a table with for each row the supervisor name (custom or staff) and an unassign button (if allowed)
	 * An assign button is added at the bottom of the table
	 */
	t._init = function(){
		var header = document.createElement("div");
		header.appendChild(document.createTextNode("Supervisors"));
		header.style.fontWeight = "bold";
		header.style.textAlign = "center";
		header.style.padding = "5px";
		container.appendChild(header);
		if(supervisors.staffs.length > 0 || supervisors.customs.length > 0){
			var table = document.createElement("table");
			table.style.padding = "5px";
			container.appendChild(table);
			if(supervisors.staffs.length > 0){
				for(var i = 0; i < supervisors.staffs.length; i++){
					var tr = document.createElement("tr");
					var td1 = document.createElement("td");
					var td2 = document.createElement("td");
					tr.appendChild(td1);
					tr.appendChild(td2);
					table.appendChild(tr);
					td1.appendChild(document.createTextNode(supervisors.staffs[i].people.first_name+', '+supervisors.staffs[i].people.last_name));
					if(can_manage){
						var remove = document.createElement("div");
						remove.className = "button_verysoft";
						remove.innerHTML = "<img src = '"+theme.icons_10.remove+"' style = 'vertical-align:bottom'/>";
						remove.title = "Unassign this supervisor";
						remove.staff_id = supervisors.staffs[i].people.id;
						remove.style.display = "inline-block";
						remove.onclick = function(){
							service.json("selection","exam/unassign_supervisor_from_session",{session_id:session_id,staff_id:this.staff_id},function(res){
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
				for(var i = 0; i < supervisors.customs.length; i++){
					var tr = document.createElement("tr");
					var td1 = document.createElement("td");
					var td2 = document.createElement("td");
					tr.appendChild(td1);
					tr.appendChild(td2);
					table.appendChild(tr);
					td1.appendChild(document.createTextNode(supervisors.customs[i].name));
					if(can_manage){
						var remove = document.createElement("div");
						remove.className = "button_verysoft";
						remove.innerHTML = "<img src = '"+theme.icons_10.remove+"' style = 'vertical-align:bottom'/>";
						remove.title = "Unassign this supervisor";
						remove.custom_id = supervisors.customs[i].id;
						remove.style.display = "inline-block";
						remove.onclick = function(){
							service.json("selection","exam/unassign_supervisor_from_session",{session_id:session_id,custom_id:this.custom_id},function(res){
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
		} else {
			var text = document.createElement("div");
			text.appendChild(document.createTextNode("No supervisor"));
			text.style.fontStyle = "italic";
			text.style.textAlign = "center";
			container.appendChild(text);
		}
		if(can_manage){
			var footer = document.createElement("div");
			footer.style.padding = "5px";
			var create = document.createElement("div");
			create.style.display = "inline-block";
			create.className = "button";
			create.appendChild(document.createTextNode("Assign"));
			create.title = "Assign new supervisors to this session";
			create.onclick = function(){
				var p = new pop_supervisor_selection(session_id);
				var set_later, set_onclose;
				set_later = function(){
					setTimeout(set_onclose,500);
				};
				set_onclose = function(){
					if(p.pop)
						p.pop.onclose = reset;
					else
						set_later();
				};
				set_onclose();
				
			};
			footer.appendChild(create);
			container.appendChild(footer);
		}
	};
	
	require("pop_supervisor_selection.js",function(){
		t._init();
	});
}