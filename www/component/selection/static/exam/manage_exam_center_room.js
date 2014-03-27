/**
 * Manage the rooms of an exam center
 * A section object is created into the container
 * @param {HTMLElement | String} container or its ID
 * @param {ExamCenterRooms} rooms
 * @param {Number} EC_id exam center ID
 * @param {Boolean}can_manage
 * @param {Boolean} generate_name true if the room name must be generated and cannot be manually setted
 */
function manage_exam_center_room(container, rooms, EC_id, can_manage, generate_name){
	var t = this;
	t.rooms = rooms;
	container = typeof container == "string" ? document.getElementById(container) : container;
	t.onupdate = new Custom_Event();
	
	/**
	 * Reset the section content
	 * @param {ExamCenterRooms | NULL} new_rooms not null if romms attribute must be updated
	 */
	t.reset = function(new_rooms){
		while(t._section_content.firstChild)
			t._section_content.removeChild(t._section_content.firstChild);
		t.section.resetToolBottom();
		if(new_rooms)
			rooms = new_rooms;
		t._getRoomsRemoveRight();	
		t.onupdate.fire();
	};
	
	/**
	 * Get the updated ExamCenterRooms object
	 * @returns {ExamCenterRooms}
	 */
	t.getNewRoomsArray = function(){
		var new_rooms = [];
		for(var i = 0; i < t._fields_room.length;i++){
			var r = new ExamCenterRoom(
					t._fields_room[i].id,
					t._fields_room[i].name_field.getCurrentData(),
					t._fields_room[i].capacity_field.getCurrentData()
			);
			new_rooms.push(r);
		}
		return new_rooms;
	};
	
	
	t.getInfoRow = function(external_error_assigned, external_error_capacity){
		if(typeof external_error_assigned == "undefined")
			external_error_assigned = null;
		if(typeof external_error_capacity == "undefined")
			external_error_capacity = null;
		var cont = document.createElement("div");		
		cont.appendChild(document.createTextNode("Some rooms cannot be updated / removed because of applicants assignment"));
		var detail = document.createElement("div");
		detail.style.marginLeft = '3px';
		detail.className = "button_verysoft";
		detail.appendChild(document.createTextNode("Detail"));
		detail.onclick = function(){
			var list_container = document.createElement("div");
			require("popup_window.js",function(){
				var pop = new popup_window("Applicants assigned","",list_container);
				var row_error_assigned = t._createErrorAssignedElement(external_error_assigned);
				if(row_error_assigned){
					list_container.appendChild(row_error_assigned);
					row_error_assigned.style.marginBottom = "15px";
				}
				var row_error_capacity = t._createErrorCapacityElement(external_error_capacity);
				if(row_error_capacity)
					list_container.appendChild(row_error_capacity);
				pop.show();		
			});
		};
		cont.appendChild(detail);
		return cont;
	};
	
	/**Private methods and attributes*/
	
	t._applicants_assigned = null;//array of objects (one per room) with two attributes <ul><li><code>room</code> room id</li><li><code>assigned</code> NULL if no one assigned, else array of applicants IDs</li></ul>
	
	/**
	 * Launch the process, add the rights restrictions info row if needed,
	 * add the rooms list, and set the footer (button create room)
	 */
	t._init = function(){
		if(t._error_assigned != null || t._error_assigned != null){
			var info_row = t.getInfoRow();
			info_row.className = "info_header";
			t._section_content.appendChild(info_row);
		}
		t._setRoomsTable();
		t._setFooter();
		if(can_manage)
			container.appendChild(t.section.element);
	};	
	
	/**
	 * One row is created per room
	 * Each row contains the name, the capacity, and a remove button
	 */
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
			var total_capacity = 0;
			for(var i = 0; i < rooms.length; i++){
				var tr = document.createElement("tr");
				var td1 = document.createElement("td");
				var td2 = document.createElement("td");
				var td3 = document.createElement("td");
				var name, capacity;
				total_capacity += rooms[i].capacity == null ? 0 : parseInt(rooms[i].capacity);
				if(t._canBeUpdated(rooms[i].id)){
					//This room can be updated, create editable fields and remove button
					var name_editable = generate_name == true ? false : true;
					name = new field_text(rooms[i].name,name_editable,{can_be_null:true});//Can_be_null config = true so that all the empty strings are replaced by null
					capacity = new field_integer(rooms[i].capacity,true,{can_be_null:false,min:1});					
					td1.appendChild(name.getHTMLElement());
					td2.appendChild(capacity.getHTMLElement());
					td3.appendChild(t._createRemoveButton(rooms[i].id));
					capacity.onchange.add_listener(t._updateTotalCapicityField);
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
			//Add a row with the max capacity
			var tr_total = document.createElement("tr");
			var td1 = document.createElement("td");
			td1.appendChild(document.createTextNode("Max: "));
			t._td_total = document.createElement("td");
			t._td_total.appendChild(document.createTextNode(total_capacity));
			tr_total.appendChild(td1);
			tr_total.appendChild(t._td_total);
			table.appendChild(tr_total);
		}
	};
	
	/**
	 * Listener added on the capacity fields
	 * When one of this field is updated, the max capacity element is updated
	 */
	t._updateTotalCapicityField = function(){
		if(t._td_total.firstChild)
			t._td_total.removeChild(t._td_total.firstChild);
		var total = 0;
		for(var i = 0; i < t._fields_room.length; i++){
			total += t._fields_room[i].capacity_field.getCurrentData() == null ? 0 : parseInt(t._fields_room[i].capacity_field.getCurrentData());
		}
		t._td_total.appendChild(document.createTextNode(total));
	};
	
	/**
	 * Set the section footer
	 * Add a create room button
	 */
	t._setFooter = function(){
		var create = document.createElement("div");
		create.className = "button";
		create.appendChild(document.createTextNode("Create"));
		if(!generate_name){
			create.onclick = function(){
				rooms = t.getNewRoomsArray();//Update the rooms array with the field values (maybe room created, capacity set, the create a new room)
				var room = new ExamCenterRoom(-1,"New",1);
				rooms.push(room);
				t.reset();
			};
		} else {
			create.onclick = function(){
				rooms = t.getNewRoomsArray();//Update the rooms array with the field values (maybe room created, capacity set, the create a new room)
				var room = new ExamCenterRoom(-1,t._getUniqueName(),1);
				rooms.push(room);
				t.reset();
			};
		}
		t.section.addToolBottom(create);
	};
	
	/**
	 * Get a unique name for the room created
	 * @returns {String} name
	 */
	t._getUniqueName = function(){
		var max = 0;
		for(var i = 0; i < rooms.length; i++){
			if(!isNaN(rooms[i].name) && parseInt(rooms[i].name) > max)
				max = parseInt(rooms[i].name);
		}
		return ++max;
	};
	
	/**
	 * Create a remove room button
	 * @param {Number} id the room to remove ID
	 * @returns {HTMLELement} button created
	 */
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
	
	/**
	 * Check if a room can be updated (capacity, removed) by looking at the applicants assigned / center capacity consequences
	 * This method can only restrict the can_manage right
	 * @param {Number} id the room ID
	 * @returns {Boolean}
	 */
	t._canBeUpdated = function(id){
		if(!can_manage)
			return false;
		if(id == -1 || id == '-1')
			return can_manage;
		return t._remove_rooms_rights[id];
	};
	
	t._createErrorCapacityElement = function(external_error_capacity){
		var error_data = (external_error_capacity != null) ? external_error_capacity : t._error_capacity;
		if(error_data != null){
			var cont = document.createElement("div");
			var head = document.createElement("div");
			head.appendChild(document.createTextNode("The following rooms cannot be removed / updated otherwize the exam center capacity would be lesser than the number of applicants assigned to some sessions:"));
			cont.appendChild(head);
			var ul_room = document.createElement("ul");
			cont.appendChild(ul_room);
			for(var i = 0; i < error_data.length; i++){
				var li_room = document.createElement("li");
				ul_room.appendChild(li_room);
				li_room.appendChild(document.createTextNode("Room "+error_data[i].name));
				var ul = document.createElement("ul");
				li_room.appendChild(ul);
				for(var j = 0; j < error_data[i].sessions.length; j++){
					var li = document.createElement("li");
					var link = document.createElement("a");
					link.className = "black_link";
					link.appendChild(document.createTextNode(get_exam_session_name_from_event(error_data[i].sessions[j].session_event)));					
					link.style.paddingRight = "3px";
					link.session_id = error_data[i].sessions[j].session_event.id;
					link.title = "See session profile";
					link.onclick = function(){
						var session_id = this.session_id;
						require("popup_window.js",function(){
							var pop = new popup_window("Exam Session Profile");
							pop.setContentFrame("/dynamic/selection/page/exam/session_profile?id="+session_id);
							pop.show();
						});
						return false;
					};
					li.appendChild(document.createTextNode(error_data[i].sessions[j].assigned+" applicants assigned in session on "));
					li.appendChild(link);
					ul.appendChild(li);
				}
			}
			return cont;
		}
	};
	
	t._createErrorAssignedElement = function(external_error_assigned){
		var error_data = (external_error_assigned != null) ? external_error_assigned : t._error_assigned;
		if(error_data != null){
			var cont = document.createElement("div");
			var head = document.createElement("div");
			head.appendChild(document.createTextNode("The following rooms cannot be removed / updated because some applicants are assigned to them:"));
			cont.appendChild(head);
			var ul_room = document.createElement("ul");
			cont.appendChild(ul_room);
			for(var i = 0; i < error_data.length; i++){
				var li_room = document.createElement("li");
				ul_room.appendChild(li_room);
				li_room.appendChild(document.createTextNode("Room "+error_data[i].name));
				var ul = document.createElement("ul");
				li_room.appendChild(ul);
				for(var j = 0; j < error_data[i].applicants.length; j++){
					var li = document.createElement("li");
					var link = document.createElement("a");
					link.appendChild(document.createTextNode(getApplicantMainDataDisplay(error_data[i].applicants[j])));
					link.className = "black_link";
					link.people_id = error_data[i].applicants[j].people_id;
					link.title = "See people profile";
					link.onclick = function(){
						require("popup_window.js",function(){
							var p = new popup_window("People profile");
							p.setContentFrame("/dynamic/people/page/profile?people="+this.people_id);
							p.show();
						});
						return false;
					};
					link.style.paddingRight = "3px";
					li.appendChild(link);
					ul.appendChild(li);
				}
			}
			return cont;
		}
	};
	
	/**
	 * Get a room name from its ID
	 * @param {Number} id
	 * @returns {String | NULL} room name if found, else NULL
	 */
	t._getRoomName = function(id){
		for(var i = 0; i < rooms.length; i++){
			if(rooms[i].id == id)
				return rooms[i].name;
		}
		return null;
	};
	
	t._remove_rooms_rights = null;
	t._error_assigned = null;
	t._error_capacity = null;
	
	t._getRoomsRemoveRight = function(){
		if(!can_manage|| rooms.length == 0) //Nothing to check
			t._init();
		else {
			service.json("selection","exam/can_rooms_be_removed_for_center",{EC_id:EC_id},function(res){
				if(!res) return;
				//Reset the rights attribute
				t._remove_rooms_rights = {};
				t._error_assigned = null;
				t._error_capacity = null;
				if(res.rooms != null){
					res = res.rooms;
					for(var i = 0; i < res.length; i++){
						t._remove_rooms_rights[res[i].id] = res[i].can_be_removed;
						if(res[i].error_applicants != null){
							if(t._error_assigned == null)
								t._error_assigned = [];
							t._error_assigned.push({id:res[i].id, name:res[i].name, applicants:res[i].error_applicants});
						}
						if(res[i].error_capacity != null){
							if(t._error_capacity == null)
								t._error_capacity = [];
							t._error_capacity.push({id: res[i].id, name:res[i].name, sessions:res[i].error_capacity});
						}
					}
				}
				t._init();
			});
		}
	};
	
	require(["section.js","exam_objects.js",["typed_field.js",["field_text.js","field_integer.js"]]],function(){		
		t._section_content = document.createElement("div");
		t.section = new section("","Exam Rooms",t._section_content,false,false,"soft");
		t._getRoomsRemoveRight();
	});
}