function convert_IS_into_center(container, can_add_EC, can_edit_EC, all_IS, all_EC, db_locks){
	//TODO get save button in pn_application_content frame!!
	var t = this;
	container = typeof container == "string" ? document.getElementById(container) : container;
	
	t._selected_IS = [];
	t._actionURL = "/dynamic/selection/page/exam/convert_IS_into_center?lockec="+db_locks.EC+"&lockis="+db_locks.IS+"&lockecis="+db_locks.ECIS;
	t._init = function(){
		t._refreshISSection();
		t._refreshECSection();
		container.appendChild(t._IS_section.element);
		container.appendChild(t._EC_section.element);
		
		layout.invalidate(container);
	};
	
	t._refreshISSection = function(){
		t._IS_cb = []; //contains all the chekboxes of the IS list
		if(!t._IS_list_container){
			t._IS_list_container = document.createElement("div");
			t._IS_list_container.style.overflowY = "scroll";//Anticipate scrollbar
		}
		if(!t._IS_section){
			t._IS_section = new section("","Non-assigned Information Sessions",t._IS_list_container,true);
			t._IS_section.margin = "5px";
			t._IS_section.element.style.display = "inline-block";
		}
		while(t._IS_list_container.firstChild)//Empty the section content
			t._IS_list_container.removeChild(t._IS_list_container.firstChild);
		//Add a right menu
		if(!t._convertButton){
			t._convertButton = document.createElement("div");
			t._convertButton.className = 'button';
			t._convertButton.innerHTML = "<img src = '"+theme.icons_16.right+"'/>";
			t._convertButton.title = "Convert the selected information sessions into exam centers, or link them to an existing center";
			t._convertButton.onclick = function(){
				//TODO
			};
			t._IS_section.addToolRight(t._convertButton);
		};
		
		if(all_IS.length == 0){
			var div = document.createElement("div");
			div.appendChild(document.createTextNode("No information session remaining"));
			div.style.fontStyle = "italic";
			t._IS_list_container.appendChild(div);
		} else {
			var table = document.createElement("table");
			t._IS_list_container.appendChild(table);
			for(var i = 0; i < all_IS.length; i++){
				var tr = document.createElement("tr");
				var td1 = document.createElement("td");
				var td2 = document.createElement("td");				
				//Add a check box on the left column
				var cb = document.createElement("input");
				cb.IS_id = all_IS[i].id;
				cb.type = "checkbox";
				cb.onchange = function(){
					//Update the t._selected_IS array
					if(!this.checked){
						//Remove from t._selected_IS array
						var index = t._findISIndexInSelected(this.IS_id);
						if(index != null)
							t._selected_IS.splice(index,1);
					} else {
						//Add in t._selected_IS array
						t._selected_IS.push(this.IS_id);
					}
					//Update the t._convertButton visibility
					t._convertButton.disabled = t._selected_IS.length == 0 ? "disabled" : "";
				};
				td1.appendChild(cb);
				t._IS_cb.push(cb);
				td2.appendChild(t._createLinkToISProfile(all_IS[i].id, all_IS[i].name));
				tr.appendChild(td1);
				tr.appendChild(td2);
				table.appendChild(tr);
			}
		}
	};
	
	t._createLinkToISProfile = function(IS_id,name){
		var link = document.createElement("a");
		link.IS_id = IS_id;
		link.className = "black_link";
		link.appendChild(document.createTextNode(name));
		a.onclick = function(){
			var IS_id = this.IS_id;
			require("popup_window.js",function(){
				var pop = new popup_window("Information Session Profile");
				pop.setContentFrame("/dynamic/selection/page/IS/profile?id="+IS_id+"&readonly=true&hideback=true");
				pop.show();
			});
			return false;
		};
		return link;
	};
	
	t._refreshECSection = function(){
		t._EC_cb = [];
		t._remove_IS_from_EC_row_buttons = [];
		t._EC_checked = null;
		if(!t._EC_list_container){
			t._EC_list_container = document.createElement("div");
			t._EC_list_container.style.overflowY = "scroll";//Anticipate scrollbar
		}
		if(!t._EC_section){
			t._EC_section = new section("","Exam centers",t._EC_list_container,true);
			t._EC_section.margin = "5px";
			t._EC_section.element.style.display = "inline-block";
		}
		while(t._EC_list_container.firstChild)//Empty the section content
			t._EC_list_container.removeChild(t._EC_list_container.firstChild);
		//Create the list of the exam centers
		if(all_EC.length == 0){
			var div = document.createElement("div");
			div.appendChild(document.createTextNode("No exam center yet"));
			div.style.fontStyle = "italic";
			t._IS_list_container.appendChild(div);
		} else {
			var table = document.createElement("table");
			for(var i = 0; i < all_EC.length; i++){
				var tr = document.createElement("tr");
				var td1 = document.createElement("td");
				var td2 = document.createElement("td");
				var cb = document.createElement("input");
				cb.type = "checkbox";
				cb.EC_id = all_EC[i].id;
				cb.style.visibility = "hidden";
				cb.onclick = function(){
					//update t._EC_checked attribute
					t._EC_checked = this.EC_id;
					//Uncheck all the other checkboxes
					for(var c = 0; c < t._EC_cb.length; c++){
						if(t._EC_cb[c].EC_id != t._EC_checked)
							t._EC_cb[c].checked = "";
					}
				};
				t._EC_cb.push(cb);
				td1.appendChild(cb);
				td2.appendChild(t._createECCell(td2, i));
				
				tr.appendChild(td1);
				tr.appendChild(td2);
				table.appendChild(tr);
			}
		}
	};
	
	t._createECCell = function(td, index){
		//Set the name
		var div_name = document.createElement("div");
		var link = document.createElement("a");
		link.EC_id = all_EC[index].id;
		link.onclick = function(){
			var EC_id = this.EC_id;
			require("popup_window.js",function(){
				var pop = new popup_window("Exam Center Profile");
				pop.setContentFrame("/dynamic/selection/page/exam/center_profile?id="+EC_id+"&readonly=true&hideback=true");
				pop.show();
			});
			return false;
		};
		link.appendChild(document.createTextNode(all_EC[index].name));
		link.className = "black_link";
		div_name.appendChild(link);
		td.appendChild(div_name);
		//Set the linked IS list
		for(var i = 0; i < all_EC[index].information_sessions.length;i++){
			var IS_index = t._findISIndex(all_EC[index].information_sessions[i]);
			if(IS_index != null){
				var div = document.createElement("div");
				div.appendChild(document.createTextNode(" - "));
				div.appendChild(t._createLinkToISProfile(all_IS[IS_index].id, all_IS[IS_index].name));
				div.style.fontStyle = "italic";
				div.style.fontSize = "small";
				if(can_edit_EC){
					var remove = document.createElement("div");
					remove.className = "button_verysoft";
					remove.innerHTML = "<img src = '"+theme.icons_10.remove+"'/>";
					remove.IS_id = all_IS[IS_index].id;
					remove.EC_id = all_EC[index].id;
					remove.onclick = function(){
						//remove the IS from the exam center
//						var EC_index = t._findECIndex(this.EC_id);
//						if(EC_index == null) return;
//						var IS_index = null;
//						for(var i = 0; i < all_EC[EC_index].information_sessions.length; i++){
//							if(all_EC[EC_index].inforamtion_sessions[i] == all_EC[EC_index].id){
//								IS_index = i;
//								break;
//							}
//						}
//						if(IS_index == null) return;
//						all_EC[EC_index].information_sessions[IS_index].
						location.assign(t._actionURL+"&remove=true&isids[0]="+this.IS_id+"&ec="+this.EC_id);
					};
					remove.style.marginLeft = "3px";
					div.appendChild(remove);
				}
				td.appendChild(div);
			}
		}
	};	
	
	t._findISIndex = function(id){
		for(var i = 0; i < all_IS.length; i++){
			if(all_IS[i].id == id)
				return i;
		}
		return null;
	};
	
	t._findISIndexInSelected = function(id){
		for(var i = 0; i < t._selected_IS.length; i++){
			if(t._selected_IS[i] == id)
				return i;
		}
		return null;
	};
	
	t._findECIndex = function(id){
		for(var i = 0; i < all_EC.length; i++){
			if(all_EC[i].id == id)
				return i;
		}
		return null;
	}
	
	require(["section.js","context_menu.js"],function(){
		t._init();
	});
}