function pop_supervisor_selection(session_id){
	var t = this;
	
	t.pop = null;
	
	/**Private methods and attributes */
	
	t._init = function(){
		t._container = document.createElement("div");
		t.pop = new popup_window("Assign new supervisors",null,t._container);
		t._setPopContent();
		t.pop.addOkCancelButtons(t._onok,t._oncancel);
	};
	
	t._selected = [];
	
	t._setPopContent = function(){
		if(t._staffs && t._staffs.length > 0){
			for(var i = 0; i < t._staffs.length; i++){
				var row = document.createElement("div");
				var cb = document.createElement("input");
				cb.type = "checkbox";
				cb.people_id = t._staffs[i].people_id;
				cb.onclick = function(){
					if(this.checked == true){
						if(!t._selected.contains(this.people_id))
							t._selected.push(this.people_id);
					} else {
						t._selected.remove(this.people_id);
					}
				};
				cb.style.display = "inline-block";
				var text = document.createElement("div");
				text.appendChild(document.createTextNode(t._staffs[i].first_name+", "+t._staffs[i].last_name));
				text.style.display = "inline-block";
				row.appendChild(cb);
				row.appendChild(text);
				row.style.padding = "5px";
				t._container.appendChild(row);
			}
		} else {
			var m = document.createElement("div");
			m.appendChild(document.createTextNode("There is no more available staff for this session"));
			m.style.textAlign = "center";
			m.style.fontStyle = "italic";
		}
		//Add the custom supervisor row
		var row = document.createElement("div");
		row.style.padding = "5px";
		row.appendChild(document.createTextNode("Custom:"));
		t._custom = new field_text(null,true,{can_be_null:true});
		var input = t._custom.getHTMLElement();
		input.style.paddingLeft = "3px";
		input.style.display = "inline-block";
		row.appendChild(input);
	};
	
	t._onok = function(){
		//If any value has been entered, call the save service
		if((t._selected && t._selected.length > 0 )|| t._custom.getCurrentData() != null){
			service.json("selection","exam/add_supervisors_to_session",{session_id:session_id,people_ids:t._selected,custom:t._custom.getCurrentData()},function(res){
				if(!res){
					error_dialog("An error occured");
					return;
				}
				t.pop.close();
			});
		} else
			t.pop.close();
	};
	
	t._oncancel = function(){
		t.pop.close();
	};
	
	require([["popup_window.js","typed_field.js"],["field_text.js"]],function(){
		service.json("selection","exam/get_available_supervisors_for_session",{session_id:session_id},function(res){
			if(!res){
				error_dialog("An error occured");
				return;
			}
			t._staffs = res;
			t._init();
		});
	});
}