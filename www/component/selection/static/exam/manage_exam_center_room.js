function manage_exam_center_room(container, rooms, can_manage, generate_name){
	var t = this;
	t.rooms = rooms;
	container = typeof container == "string" ? document.getElementById(container) : container;
	
	t.reset = function(){
		while(t._section_content.firstChild)
			t._section_content.removeChild(t._section_content.firstChild);
		t.section.resetToolBottom();
		t._getApplicantsAssigned();		
	};
	
	t.getNewRoomsArray = function(){
		var new_rooms = [];
		for(var i = 0; i < t._fields_room.length;i++){
			var r = new ExamCenterRoom(
					t._fields_room[i].id,
					t._fields_room[i].field_name.getCurrentData(),
					t._fields_room[i].field_capacity.getCurrentData()
			);
			new_rooms.push(r);
		}
		return new_rooms;
	};
	
	t.getInfoRow = function(external_applicants_assigned){
		var applicants_assigned = (typeof external_applicants_assigned != "undefined" && external_applicants_assigned != null) ? external_applicants_assigned : t._applicants_assigned;
		var div = document.createElement("div");
		var text = document.createElement("div");
		text.innerHTML = "Some rooms have applicant assigned <br/>so you cannot update them";
		var detail = document.createElement("div");
		detail.className = "button_verysoft";
		detail.appendChild(document.createTextNode("Detail"));
		detail.onclick = function(){
			var list = document.createElement("table");
			for(var i = 0 ; i < applicants_assigned.length; i++){
				var tr1 = document.createElement("tr");
				var tr2 = document.createElement("tr");
				var th = document.createElement("th");
				th.appendChild(document.createTextNode(t._getRoomName(applicants_assigned[i].room)));
				tr1.appendChild(th);
				var td = document.createElement("td");
				tr2.appendChild(td);
				list.appendChild(tr1);
				list.appendChild(tr2);
				var ul = document.createElement("ul");
				for(var j = 0; j < applicants_assigned.assigned.length; j++){
					var li = document.createElement("li");
					li.appendChild(document.createTextNode(applicants_assigned[i].assigned[j]));
					ul.appendChild(li);
				}
				td.appendChild(ul);
			}
			require("popup_window.js",function(){
				var pop = new popup_window("Applicants assigned","",list);
				pop.show();				
			});
		};
		div.appendChild(text);
		div.appendChild(detail);
		return div;
	};
	
	t._applicants_assigned = null;
	t._init = function(){
		if(t._applicants_assigned != null)
			t._section_content.appendChild(t.getInfoRow());
		t._setRoomsTable();
		t._setFooter();
		container.appendChild(t.section.element);
	};	
	
	t._setRoomsTable = function(){
		t._fields_room = [];
		var table = document.createElement("table");
		t._section_content.appendChild(table);
		if(rooms.length == 0){
			var tr = document.createElement("tr");
			var td = document.createElement("td");
			td.innerHTML = "<i><center>No Room yet</i></center>";
			td.colSpan = 3;
			tr.appendChild(td);
			table.appendChild(tr);
		} else {
			//Set the title row
			var tr_head = document.createElement("tr");
			var th_head_1 = document.createElement("th");
			var th_head_2 = document.createElement("th");
			var th_head_3 = document.createElement("th");
			tr_head.appendChild(th_head_1);//First column contains the room name
			tr_head.appendChild(th_head_2);//Second contains the room capacity
			tr_head.appendChild(th_head_3);//Third contains the remove buttons
			table.appendChild(tr_head);
			th_head_1.appendChild(document.createTextNode("Name"));
			th_head_2.appendChild(document.createTextNode("Capacity"));
			//Set the body
			for(var i = 0; i < rooms.length; i++){
				var tr = document.createElement("tr");
				var td1 = document.createElement("td");
				var td2 = document.createElement("td");
				var td3 = document.createElement("td");
				var name, capacity;
				if(t._canBeUpdated(rooms[i].id)){
					//This room can be updated, create editable fields and remove button
					var name_editable = generate_name == true ? false : true;
					name = new field_text(rooms[i].name,name_editable,{can_be_null:true});//Can_be_null config = true so that all the empty strings are replaced by null
					capacity = new field_integer(rooms[i].capacity,true,{can_be_null:false,min:1});					
					td1.appendChild(name.getHTMLElement());
					td2.appendChild(capacity.getHTMLElement());
					td3.appendChild(t._createRemoveButton(rooms[i].id));
				} else {
					name = new field_text(rooms[i].name,false,{can_be_null:true});
					capacity = new field_integer(rooms[i].capacity,false,{can_be_null:false,min:1});
					td1.appendChild(name.getHTMLElement());
					td2.appendChild(capacity.getHTMLElement());
				}
				tr.appendChild(td1);
				tr.appendChild(td2);
				tr.appendChild(td3);
				table.appendChild(tr);
				t._fields_room.push({id:rooms[i].id, name_field:name,capacity_field:capacity});
			}
		}
	};
	
	t._setFooter = function(){
		var create = document.createElement("div");
		create.className = "button";
		create.appendChild(document.createTextNode("Create"));
		if(!generate_name){
			create.onclick = function(){
				var room = new ExamCenterRoom(-1,"New",1);
				rooms.push(room);
				t.reset();
			};
		} else {
			create.onclick = function(){				
				var room = new ExamCenterRoom(-1,t._getUniqueName(),1);
				rooms.push(room);
				t.reset();
			};
		}
		t.section.addToolBottom(create);
	};
	
	t._getUniqueName = function(){
		var max = 0;
		for(var i = 0; i < rooms.length; i++){
			if(!isNaN(rooms[i].name) && parseInt(rooms[i].name) > max)
				max = parseInt(rooms[i].name);
		}
		return ++max;
	};
	
	t._createRemoveButton = function(id){
		var div = document.createElement("div");
		div.className = "button_verysoft";
		div.innerHTML = "<img src = '"+theme.icons_16.remove+"'/>";
		div.room_ID = id;
		div.onclick = function(){
			//Remove from rooms array
			var index = null;
			for(var i = 0; i < rooms.length; i++){
				if(rooms[i].id == this.room_ID){
					index = i;
					break;
				}
			}
			if(index == null) return;
			rooms.splice([index],1);
			//Reset
			t.reset();
		};
		return div;
	};
	
	t._canBeUpdated = function(id){
		if(!can_manage)
			return false;
		if(t._applicants_assigned == null)
			return can_manage;
		for(var i = 0; i < t._applicants_assigned.length;i++){
			if(t._applicants_assigned[i].room == id && t._applicants_assigned[i].assigned != null)
				return false;
		}
		return true;
	};
	
	t._getRoomName = function(id){
		for(var i = 0; i < rooms.length; i++){
			if(rooms[i].id == id)
				return rooms[i].name;
		}
		return null;
	};
	
	t._getApplicantsAssigned = function(){
		if(!can_manage|| rooms.length == 0) //Nothing to check
			t._init();
		else{
			var ids = [];
			for(var i = 0; i < rooms.length; i++)
				ids.push(rooms[i].id);
			service.json("selection","exam/get_applicants_assigned_to_rooms",{ids:ids},function(res){
				if(!res) return;
				//Reset the attribute
				t._applicants_assigned = null;
				for(var i = 0; i < res.length; i++){
					if(res[i].assigned != null){
						t._applicants_assigned = t._applicants_assigned == null ? [] : t._applicants_assigned;
						t._applicants_assigned.push(res[i]);
					}
				}
				t._init();
			});
		}
		
	};
	
	require(["section.js","exam_objects.js",["typed_field.js",["field_text.js","field_integer.js"]]],function(){		
		t._section_content = document.createElement("div");
		t.section = new section("","Exam Rooms",t._section_content,false);
		t._getApplicantsAssigned();
	});
}