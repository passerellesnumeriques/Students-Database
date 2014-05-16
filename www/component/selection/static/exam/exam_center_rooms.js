function exam_center_rooms(container, rooms) {
	if (typeof container == 'string') container = document.getElementById(container);
	
	this.rooms = rooms;
	this.onroomadded = new Custom_Event();
	this.onroomremoved = new Custom_Event();
	this.onroomcapacitychanged = new Custom_Event();
	this._new_room_id_counter = -1;
	
	// create the section
	this._init = function() {
		this._table = document.createElement("TABLE");
		this._section = new section(null, "Available Rooms", this._table, false, false, "soft", false);
		container.appendChild(this._section.element);
		this._refresh();
		var button_new = document.createElement("BUTTON");
		button_new.className = "action";
		button_new.innerHTML = "New Room";
		this._section.addToolBottom(button_new);
		button_new.t = this;
		button_new.onclick = function() {
			var r = new ExamCenterRoom(-1, this.t._new_room_id_counter--, "", 10);
			this.t.rooms.push(r);
			this.t._refresh();
			this.t.onroomadded.fire(r);
		};
	};
	
	this._refresh = function() {
		this._table.innerHTML = "";
		var tr, th;
		this._table.appendChild(tr = document.createElement("TR"));
		if (this.rooms.length == 0) {
			tr.appendChild(th = document.createElement("TD"));
			th.innerHTML = "<i style='color:red'>No room yet here</i>";
			return;
		}
		tr.appendChild(th = document.createElement("TH"));
		th.appendChild(document.createTextNode("Room Name"));
		tr.appendChild(th = document.createElement("TH"));
		th.appendChild(document.createTextNode("Capacity"));
		for (var i = 0; i < this.rooms.length; ++i)
			this._createRoomRow(this.rooms[i]);
		layout.invalidate(this._table);
	};
	this._createRoomRow = function(room) {
		var tr, td;
		this._table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		var field_name = new field_text(room.name, true, {can_be_null:false,max_length:20});
		td.appendChild(field_name.getHTMLElement());
		field_name.register_datamodel_cell("ExamCenterRoom", "name", room.id);
		field_name.onchange.add_listener(function (f) {
			room.name = f.getCurrentData();
		});
		tr.appendChild(td = document.createElement("TD"));
		var field_capacity = new field_integer(room.capacity, true, {can_be_null:false,min:1,max:999});
		td.appendChild(field_capacity.getHTMLElement());
		field_capacity.register_datamodel_cell("ExamCenterRoom", "capacity", room.id);
		field_capacity.t = this;
		field_capacity.onchange.add_listener(function(f) {
			room.capacity = f.getCurrentData();
			f.t.onroomcapacitychanged.fire(room);
		});
		tr.appendChild(td = document.createElement("TD"));
		var button = document.createElement("BUTTON");
		button.className = "flat";
		button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
		td.appendChild(button);
		button.t = this;
		button.onclick = function() {
			this.t.rooms.remove(room);
			this.t.onroomremoved.fire(room);
			this.t._refresh();
		};
	};
	
	this._init();
}