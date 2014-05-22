if (typeof theme != 'undefined') theme.css("header_bar.css");
if (typeof require != 'undefined') {
	require(["horizontal_layout.js","event_date_time_duration.js","popup_window.js","calendar_objects.js"]);
}

function exam_center_sessions(container, rooms_container, rooms, sessions, applicants, linked_is, default_duration, calendar_id) {
	if (typeof container == 'string') container = document.getElementById(container);
	if (typeof rooms_container == 'string') rooms_container = document.getElementById(rooms_container);
	
	this.rooms = rooms;
	this.sessions = sessions;
	this.applicants = applicants;
	this.linked_is = linked_is;
	
	this._new_event_id_counter = -1;
	this._new_room_id_counter = -1;

	// TODO print/export applicants list per room
	
	
	/* ----- Rooms ----- */
	
	this.newRoom = function() {
		var r = new ExamCenterRoom(-1, this._new_room_id_counter--, "", 10);
		this.rooms.push(r);
		// refresh rooms list
		this._refreshRooms();
		// refresh all sessions to add the new room
		for (var i = 0; i < this._sessions_sections.length; ++i)
			this._sessions_sections[i].refresh();
		window.pnapplication.dataUnsaved("ExamCenterRooms");
	};
	this._refreshRooms = function() {
		this._table_rooms.removeAllChildren();
		var tr, th;
		this._table_rooms.appendChild(tr = document.createElement("TR"));
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
		layout.invalidate(this._table_rooms);
	};
	this._createRoomRow = function(room) {
		var tr, td;
		this._table_rooms.appendChild(tr = document.createElement("TR"));
		// room name
		tr.appendChild(td = document.createElement("TD"));
		var field_name = new field_text(room.name, true, {can_be_null:false,max_length:20});
		td.appendChild(field_name.getHTMLElement());
		field_name.register_datamodel_cell("ExamCenterRoom", "name", room.id);
		field_name.onchange.add_listener(function (f) {
			room.name = f.getCurrentData();
		});
		// room capacity
		tr.appendChild(td = document.createElement("TD"));
		var field_capacity = new field_integer(room.capacity, true, {can_be_null:false,min:1,max:999});
		td.appendChild(field_capacity.getHTMLElement());
		field_capacity.register_datamodel_cell("ExamCenterRoom", "capacity", room.id);
		field_capacity.t = this;
		field_capacity.onchange.add_listener(function(f) {
			var new_cap = f.getCurrentData();
			// calculate number of applicants already done with a past session
			var now = new Date().getTime();
			var nb_applicants_done = 0;
			for (var i = 0; i < f.t.sessions.length; ++i)
				if (f.t.sessions[i].start.getTime() < now) {
					// check if we have applicants there
					var nb = 0;
					for (var j = 0; j < f.t.applicants.length; ++j)
						if (f.t.applicants[j].exam_session_id == f.t.sessions[i].id && f.t.applicants[j].exam_center_room_id == room.id)
							nb++;
					if (nb > nb_applicants_done) nb_applicants_done = nb;
				};
			
			if (new_cap < nb_applicants_done) {
				field_capacity.setData(nb_applicants_done);
				alert("A session which is already done has "+nb_applicants_done+" applicant(s) assigned to this room: you should unassign applicants before to set the capacity under "+nb_applicants_done);
				return;
			}
			room.capacity = new_cap;
			// signal change to all sessions
			for (var i = 0; i < f.t._sessions_sections.length; ++i)
				f.t._sessions_sections[i].roomCapacityChanged(room);
			window.pnapplication.dataUnsaved("ExamCenterRooms");
		});
		// button remove
		tr.appendChild(td = document.createElement("TD"));
		var button = document.createElement("BUTTON");
		button.className = "flat";
		button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
		td.appendChild(button);
		button.t = this;
		button.onclick = function() {
			// calculate number of applicants already done with a past session
			var now = new Date().getTime();
			var nb_applicants_done = 0;
			for (var i = 0; i < this.t.sessions.length; ++i)
				if (this.t.sessions[i].start.getTime() < now) {
					// check if we have applicants there
					var nb = 0;
					for (var j = 0; j < this.t.applicants.length; ++j)
						if (this.t.applicants[j].exam_session_id == this.t.sessions[i].id && this.t.applicants[j].exam_center_room_id == room.id)
							nb++;
					if (nb > nb_applicants_done) nb_applicants_done = nb;
				};

			if (nb_applicants_done > 0) {
				alert("This room contains applicant(s) in a session which is already done. You must unassign them first, before to remove the room.");
				return;
			}
			this.t.rooms.remove(room);
			this.t._refreshRooms();
			// remove room from each session
			for (var i = 0; i < this.t._sessions_sections.length; ++i)
				this.t._sessions_sections[i].removeRoom(room);
			window.pnapplication.dataUnsaved("ExamCenterRooms");
		};
	};

	
	
	/* ----- Sessions ----- */
	
	this.newSession = function() {
		var t=this;
		require(["event_date_time_duration.js","popup_window.js","calendar_objects.js"], function() {
			var content = document.createElement("DIV");
			content.style.backgroundColor = "white";
			content.style.padding = "10px";
			var popup = new popup_window("New session", null, content);
			var date = new event_date_time_duration(content, null, default_duration, null, null, false, false);
			popup.addOkCancelButtons(function() {
				if (date.date == null) { alert('Please select a date'); return; }
				if (date.duration == null || date.duration == 0) { alert('Please select a duration'); return; }
				var d = new Date(date.date.getTime());
				d.setHours(0,date.time,0,0);
				var doit = function() {
					var event = new CalendarEvent(
						t._new_event_id_counter--, 
						'PN', 
						calendar_id, 
						null, 
						d, 
						new Date(d.getTime()+date.duration*60*1000)
					);
					t.sessions.push(event);
					t._createSession(event);
					popup.close();
					window.pnapplication.dataUnsaved("ExamCenterSessions");
				};
				if (d.getTime() < new Date().getTime())
					confirm_dialog("You are creating a session in the past.<br/>Do you confirm the session already occured ?", function(yes) {
						if (yes) doit();
					});
				else
					doit();
			});
			popup.show();
		});
	};
	this._sessions_sections = [];
	this.getSession = function(id) {
		for (var i = 0; i < this.sessions.length; ++i)
			if (this.sessions[i].id == id)
				return this.sessions[i];
		return null;
	};
	this._createSession = function(event) {
		this._sessions_sections.push(new ExamSessionSection(this._sessions_container, event, this));
		this._span_nb_sessions.innerHTML = this.sessions.length;
		layout.invalidate(this._sessions_container);
	};
	this._removeSession = function(event) {
		this.sessions.remove(event);
		for (var i = 0; i < this._sessions_sections.length; ++i) {
			if (this._sessions_sections[i].event.id == event.id) {
				this._sessions_container.removeChild(this._sessions_sections[i].session_section.element);
				break;
			};
		}
		for (var i = 0; i < this.applicants.length; ++i) {
			if (this.applicants[i].exam_session_id == event.id) {
				this.applicants[i].exam_session_id = null;
				this.applicants[i].exam_center_room_id = null;
				this.not_assigned.addApplicant(this.applicants[i]);
			}
		}
		this._span_nb_sessions.innerHTML = this.sessions.length;
		layout.invalidate(this._sessions_container);
		window.pnapplication.dataUnsaved("ExamCenterSessions");
	};
	this._getSessionSection = function(session_id) {
		for (var i = 0; i < this._sessions_sections.length; ++i)
			if (this._sessions_sections[i].event.id == session_id) 
				return this._sessions_sections[i];
		return null;
	};

	
	
	/* ----- Applicants ----- */
	
	this.getApplicant = function(people_id) {
		for (var i = 0; i < this.applicants.length; ++i)
			if (this.applicants[i].people.id == people_id)
				return this.applicants[i];
		return null;
	};
	this.getApplicantsAssignedTo = function(session_id, room_id) {
		var list = [];
		for (var i = 0; i < this.applicants.length; ++i)
			if (this.applicants[i].exam_session_id == session_id && this.applicants[i].exam_center_room_id == room_id)
				list.push(this.applicants[i]);
		return list;
	};
	this._assignAuto = function(applicant) {
		var max_slots = 0;
		var max_room = null;
		var max_session = null;
		var now = new Date().getTime();
		for (var i = 0; i < this._sessions_sections.length; ++i) {
			var s = this._sessions_sections[i];
			if (s.event.start.getTime() < now) continue; // do not assign to a session which already started
			for (var j = 0; j < s._rooms.length; ++j) {
				var r = s._rooms[j];
				var slots = r.room.capacity - r.applicants_list.getList().length;
				if (slots > max_slots) {
					max_slots = slots;
					max_room = r.room;
					max_session = s.event;
				}
			}
		}
		if (max_slots == 0) return false;
		return this._assignTo(applicant, max_session, max_room, true);
	};
	this._assignTo = function(applicant, session, room, already_confirmed_for_past_session) {
		var s = this._getSessionSection(session.id);
		var r = s.getRoomSection(room.id);
		if (r.applicants_list.getList().length == r.room.capacity)
			return false; // no more seat
		if (!already_confirmed_for_past_session && session.start.getTime() < new Date().getTime()) {
			var t=this;
			confirm_dialog("You are going to assign an applicant to a session which is already done (in the past). Are you sure you want to do this ?", function(yes) {
				if (yes) t._assignTo(applicant, session, room, true);
			});
			return true;
		}
		applicant.exam_center_room_id = room.id;
		applicant.exam_session_id = session.id;
		this.not_assigned.removeApplicant(applicant);
		r.applicants_list.addApplicant(applicant);
		s.refreshNbApplicants();
		window.pnapplication.dataUnsaved("ExamCenterApplicants");
		return true;
	};
	this._unassign = function(applicant, session_section, room_section, ondone, already_confirmed_for_past_session) {
		if (!already_confirmed_for_past_session && session_section.event.start.getTime() < new Date().getTime()) {
			var t=this;
			confirm_dialog("The exam session is already done. If this applicant already has exam results, those results will be removed. Are you sure you want to remove this applicant from this session ?", function(yes) {
				if (yes) t._unassign(applicant, session_section, room_section, ondone, true);
			});
			return;
		}
		room_section.applicants_list.removeApplicant(applicant);
		applicant.exam_session_id = null;
		applicant.exam_center_room_id = null;
		this.not_assigned.addApplicant(applicant);
		session_section.refreshNbApplicants();
		if (ondone) ondone();
		window.pnapplication.dataUnsaved("ExamCenterApplicants");
	};
	this._moveApplicant = function(people_id, session_section, room_section) {
		var applicant = null;
		for (var i = 0; i < this.applicants.length; ++i)
			if (this.applicants[i].people.id == people_id) { applicant = this.applicants[i]; break; }
		if (!applicant) return;
		if (!applicant.exam_session_id) {
			// not assigned
			if (!this._assignTo(applicant, session_section.event, room_section.room))
				alert("No more seat available in this room");
			return;
		}
		var from_session_section = this._getSessionSection(applicant.exam_session_id);
		if (!from_session_section) return;
		var from_room_section = from_session_section.getRoomSection(applicant.exam_center_room_id);
		if (!from_room_section) return;
		// unassign
		var t=this;
		this._unassign(applicant, from_session_section, from_room_section, function() {
			// assign
			if (!t._assignTo(applicant, session_section.event, room_section.room))
				alert("No more seat available in this room: the applicant is now in the list of applicants without schedule");
		});
	};
	this.removeApplicantFromCenter = function(applicant) {
		this.applicants.remove(applicant);
		if (applicant.exam_session_id == null) {
			// not assigned
			window.pnapplication.dataUnsaved("ExamCenterApplicants");
			this.not_assigned.removeApplicant(applicant);
			return;
		}
		var s = this.getSessionSection(applicant.exam_session_id);
		var r = s.getRoomSection(applicant.exam_center_room_id);
		r.applicants_list.removeApplicant(applicant);
		s.refreshNbApplicants();
		window.pnapplication.dataUnsaved("ExamCenterApplicants");
	};
	
	
	
	/* ----- Initialization of the screen ----- */
	
	this._init = function() {
		this._initHeader();
		this._initRooms();
		this._initSessions();
	};
	this._initHeader = function() {
		// header
		this._header = document.createElement("DIV");
		container.appendChild(this._header);
		this._header.className = "header_bar_toolbar_style";
		this._header.style.padding = "3px 5px 3px 5px";
		this._header.style.height = "22px";
		this._span_total_applicants = document.createElement("SPAN");
		this._span_total_applicants.innerHTML = this.applicants.length;
		this._header.appendChild(this._span_total_applicants);
		this._header.appendChild(document.createTextNode(" applicant(s) are assigned to this exam center. "));
		this._span_nb_sessions = document.createElement("SPAN");
		this._span_nb_sessions.innerHTML = this.sessions.length;
		this._header.appendChild(this._span_nb_sessions);
		this._header.appendChild(document.createTextNode(" session(s) scheduled. "));
		var button_new_session = document.createElement("BUTTON");
		button_new_session.className = "action";
		button_new_session.innerHTML = "Schedule new session";
		this._header.appendChild(button_new_session);
		button_new_session.t = this;
		button_new_session.onclick = function() { this.t.newSession(); };
	};
	this._initSessions = function() {
		// split into 2 horizontal elements: (1) the non-assigned applicants, (2) scheduled sessions
		this._horiz_div = document.createElement("DIV");
		this._horiz_div.style.verticalAlign = "top";
		this._left_div = document.createElement("DIV");
		this._left_div.style.verticalAlign = "top";
		this._horiz_div.appendChild(this._left_div);
		this._sessions_container = document.createElement("DIV");
		this._sessions_container.setAttribute('layout','fill');
		this._sessions_container.style.overflowX = "auto";
		this._sessions_container.style.verticalAlign = "top";
		this._sessions_container.style.margin = "5px";
		this._horiz_div.appendChild(this._sessions_container);
		container.appendChild(this._horiz_div);
		var t=this;
		require("horizontal_layout.js", function() {
			new horizontal_layout(t._horiz_div);
		});
		
		// left side - not assigned
		this._initNotAssignedList();
		
		// sessions and rooms
		for (var i = 0; i < this.sessions.length; ++i)
			this._createSession(this.sessions[i]);
		
		// listen to events on linked information sessions
		var t=this;
		linked_is.onapplicantsadded.add_listener(function(list) {
			window.pnapplication.dataUnsaved("ExamCenterApplicants");
			for (var i = 0; i < list.length; ++i) {
				var app = list[i];
				// check we don't have it yet
				var found = false;
				for (var j = 0; j < t.applicants.length; ++j)
					if (t.applicants[j].people.id == app.people.id) { found = true; break; };
				if (found) continue;
				// new applicant => not assigned to as session
				app.exam_center_room_id = null;
				app.exam_session_id = null;
				t.applicants.push(app);
				t.not_assigned.addApplicant(app);
			}
			t._span_total_applicants.innerHTML = t.applicants.length;
		});
		linked_is.onapplicantsremoved.add_listener(function(list) {
			window.pnapplication.dataUnsaved("ExamCenterApplicants");
			var assigned_and_done = [];
			var now = new Date().getTime();
			for (var i = 0; i < list.length; ++i) {
				for (var j = 0; j < t.applicants.length; ++j) {
					if (t.applicants[j].people.id == list[i].people.id) {
						// if not assigned yet, no problem just remove it
						if (t.applicants[j].exam_session_id == null) {
							t.not_assigned.removeApplicant(t.applicants[j]);
							t.applicants.splice(j,1);
							break;
						}
						// already assigned to a session and room
						var session = t.getSession(t.applicants[j].exam_session_id);
						if (session.start.getTime() < now) {
							// the session already started
							assigned_and_done.push(t.applicants[j]);
							break;
						}
						// not yet started, this is ok we can remove it
						t.applicants.splice(j,1);
						break;
					}
				}
			}
			for (var i = 0; i < t._sessions_sections.length; ++i)
				t._sessions_sections[i].refresh();
			t._span_total_applicants.innerHTML = t.applicants.length;
			if (assigned_and_done.length > 0) {
				// some are assigned to a session which is in the past, ask confirmation
				var content = document.createElement("DIV");
				content.appendChild(document.createTextNode("The following applicants you are going to remove, already passed the exam:"));
				var ul = document.createElement("UL"); content.appendChild(ul);
				var boxes = [];
				for (var i = 0; i < assigned_and_done.length; ++i) {
					var li = document.createElement("LI"); ul.appendChild(li);
					var cb = document.createElement("INPUT");
					cb.type = "checkbox";
					cb.applicant = assigned_and_done[i];
					li.appendChild(cb);
					boxes.push(cb);
					li.appendChild(document.createTextNode(" "+assigned_and_done[i].people.first_name+" "+assigned_and_done[i].people.last_name));
				}
				content.appendChild(document.createTextNode("Please select the ones you really want to remove. For those applicants, any result already imported will be removed (when you will save)."));
				confirm_dialog(content, function(yes) {
					if (!yes) return;
					var list = [];
					for (var i = 0; i < boxes.length; ++i)
						if (boxes[i].checked) list.push(boxes[i].applicant);
					if (list.length == 0) return;
					for (var i = 0; i < list.length; ++i)
						t.applicants.remove(list[i]);
					for (var i = 0; i < t._sessions_sections.length; ++i)
						t._sessions_sections[i].refresh();
					t._span_total_applicants.innerHTML = t.applicants.length;
				});
			}
		});
	};
	this._initRooms = function() {
		this._table_rooms = document.createElement("TABLE");
		this._section_rooms = new section(null, "Available Rooms", this._table_rooms, false, false, "soft", false);
		rooms_container.appendChild(this._section_rooms.element);
		var button_new = document.createElement("BUTTON");
		button_new.className = "action";
		button_new.innerHTML = "New Room";
		this._section_rooms.addToolBottom(button_new);
		button_new.t = this;
		button_new.onclick = function() { this.t.newRoom(); };
		this._refreshRooms();
	};
	this._initNotAssignedList = function() {
		var list = [];
		for (var i = 0; i < this.applicants.length; ++i)
			if (this.applicants[i].exam_session_id == null)
				list.push(this.applicants[i]);
		var not_assigned_container = document.createElement("DIV");
		var not_assigned_title = document.createElement("SPAN");
		var not_assigned_nb = document.createElement("SPAN"); not_assigned_title.appendChild(not_assigned_nb);
		not_assigned_title.appendChild(document.createTextNode(" applicant(s) without schedule"));
		this._section_not_assigned = new section(null, not_assigned_title, not_assigned_container, true, false, 'sub', false);
		this._section_not_assigned.element.style.display = "inline-block";
		this._section_not_assigned.element.style.margin = "5px";
		this._section_not_assigned.element.style.verticalAlign = "top";
		this._left_div.appendChild(this._section_not_assigned.element);
		var t=this;
		this.not_assigned = new applicant_data_grid(not_assigned_container, function(obj) { return obj; });
		var listener = function() {
			not_assigned_nb.innerHTML = t.not_assigned.getList().length;
			t._section_not_assigned.header.style.background = t.not_assigned.getList().length > 0 ? "#FF8000" : "";
		};
		listener();
		this.not_assigned.object_added.add_listener(listener);
		this.not_assigned.object_removed.add_listener(listener);
		this.not_assigned.addDropSupport("applicant", function(people_id) {
			// check applicant before drop
			for (var i = 0; i < t.not_assigned.getList().length; ++i)
				if (t.not_assigned.getList()[i].people.id == people_id) return null; // same target
			return "move";
		}, function(people_id) {
			// applicant dropped
			var applicant = t.getApplicant(people_id);
			if (!applicant) return;
			if (!applicant.exam_session_id) return;
			if (!applicant.exam_center_room_id) return;
			var session_section = null;
			for (var i = 0; i < t._sessions_sections.length; ++i)
				if (t._sessions_sections[i].event.id == applicant.exam_session_id) { session_section = t._sessions_sections[i]; break; }
			if (!session_section) return;
			var room_section = null;
			for (var i = 0; i < session_section._rooms.length; ++i)
				if (session_section._rooms[i].room.id == applicant.exam_center_room_id) { room_section = session_section._rooms[i]; break; }
			if (!room_section) return;
			t._unassign(applicant, session_section, room_section);
		});
		this.not_assigned.addDragSupport("applicant", function(obj) { return obj.people.id; });
		this.not_assigned.addPeopleProfileAction();
		this.not_assigned.addAction(function(container, applicant) {
			var button = document.createElement("BUTTON");
			button.className = "flat small";
			button.title = "Remove this applicant from this exam center";
			button.innerHTML = "<img src='/static/selection/common_centers/remove_applicant_from_center.png'/>";
			button.onclick = function() {
				confirm_dialog("Are you sure this applicant will not come to this exam center ?", function(yes) {
					if (yes) t.removeApplicantFromCenter(applicant);
				});
				return false;
			};
			container.appendChild(button);
		});
		this.not_assigned.makeSelectable();
		for (var i = 0; i < list.length; ++i)
			this.not_assigned.addApplicant(list[i]);
		var not_assigned_header = new ApplicantsListHeader(this.not_assigned);
		// add assign button
		not_assigned_header.addSelectionAction("Assign", "action", "Assign selected applicants to a session and room", function() {
			var button = this;
			require("context_menu.js", function() {
				var menu = new context_menu();
				menu.addIconItem(null, "Automatically assign", function() {
					var applicants = t.not_assigned.getSelection();
					for (var i = 0; i < applicants.length; ++i)
						if (!t._assignAuto(applicants[i])) {
							alert("No more available seat in future sessions. Please add a new schedule or a new room.");
							break;
						}
				});
				menu.addSeparator();
				for (var i = 0; i < t.sessions.length; ++i) {
					for (var j = 0; j < t.rooms.length; ++j) {
						// check if not full
						var list = t.getApplicantsAssignedTo(t.sessions[i].id, t.rooms[j].id);
						if (list.length >= t.rooms[j].capacity) continue; // already full
						// add the room as a possible assignment
						var text = "Session on "+getDateString(t.sessions[i].start)+" at "+getTimeString(t.sessions[i].start)+" in room "+t.rooms[j].name;
						menu.addIconItem(null, text, function(o) {
							var applicants = t.not_assigned.getSelection();
							var doit = function() {
								for (var i = 0; i < applicants.length; ++i)
									if (!t._assignTo(applicants[i], o.session, o.room, true)) {
										alert("No more available seat in this room.");
										break;
									}
							};
							if (o.session.start.getTime() < new Date().getTime()) {
								confirm_dialog("You are going to assign applicants to a session which is already done (in the past). Are you sure you want to do this ?", function(yes) {
									if (!yes) return;
									doit();
								});
							} else
								doit();
						}, {session:t.sessions[i], room:t.rooms[j]});
					}
				}
				menu.showBelowElement(button);
			});
		});
		// add remove button
		not_assigned_header.addSelectionAction("<img src='/static/selection/common_centers/remove_applicant_from_center.png'/> Remove", "action important", "Remove selected applicants from this exam center", function() {
			confirm_dialog("Are you sure those applicants will not come to this exam center ?", function(yes) {
				if (yes) {
					var applicants = t.not_assigned.getSelection();
					for (var i = 0; i < applicants.length; ++i)
						t.removeApplicantFromCenter(applicants[i]);
				}
			});
		});
	};
	
	
	this._init();
}

function ExamSessionSection(container, event, sessions) {
	this.event = event;
	this.sessions = sessions;
	this.getRoomSection = function(room_id) {
		for (var i = 0; i < this._rooms.length; ++i)
			if (this._rooms[i].room.id == room_id)
				return this._rooms[i];
		return null;
	};
	this.removeRoom = function(room) {
		for (var i = 0; i < this._rooms.length; ++i) {
			if (this._rooms[i].room.id == room.id) {
				this._rooms[i].room_section.element.parentNode.removeChild(this._rooms[i].room_section.element);
				// unassign applicants
				var list = this._rooms[i].applicants_list.getList();
				for (var j = 0; j < list.length; ++j) {
					list[j].exam_session_id = null;
					list[j].exam_room_id = null;
					sessions.not_assigned.addApplicant(list[j]);
				}
				this._rooms.splice(i,1);
				break;
			}
		}
		this.refreshNbApplicants();
	};
	this.roomCapacityChanged = function(room) {
		var r = this.getRoomSection(room.id);
		while (r.applicants_list.getList().length > room.capacity) {
			var app = r.applicants_list.getList()[room.capacity];
			r.applicants_list.removeApplicant(app);
			app.exam_session_id = null;
			app.exan_room_id = null;
			sessions.not_assigned.addApplicant(app);
		}
		this.refreshNbApplicants();
	};
	this.reschedule = function () {
		var t=this;
		require(["event_date_time_duration.js","popup_window.js","calendar_objects.js"], function() {
			var content = document.createElement("DIV");
			content.style.backgroundColor = "white";
			content.style.padding = "10px";
			var popup = new popup_window("New session", null, content);
			var date = new event_date_time_duration(content, t.event.start, Math.floor((t.event.end.getTime()-t.event.start.getTime())/60000), null, null, false, false);
			popup.addOkCancelButtons(function() {
				if (date.date == null) { alert('Please select a date'); return; }
				if (date.duration == null || date.duration == 0) { alert('Please select a duration'); return; }
				var d = new Date(date.date.getTime());
				d.setHours(0,date.time,0,0);
				var doit = function() {
					t.event.start = d;
					t.event.end = new Date(d.getTime()+date.duration*60*1000);
					t.refresh();
					popup.close();
					window.pnapplication.dataUnsaved("ExamCenterSessions");
				};
				var now = new Date().getTime();
				if (t.event.start.getTime() < now && d.getTime() > now)
					confirm_dialog("This session was in the past, and you reschedule it in the future.<br/>If any applicant assigned to this session already has exam results, those results will be removed! (when you will save)<br/>Are you sure you want to do this ?", function(yes) {
						if (yes) doit();
					});
				else if (t.event.start.getTime() > now && d.getTime() < now)
					confirm_dialog("This session was in the future, and you reschedule it in the past.<br/>Do you confirm this session already occured ?", function(yes) {
						if (yes) doit();
					});
				else
					doit();
			});
			popup.show();
		});
	};
	this._init = function() {
		var title = document.createElement("SPAN");
		title.appendChild(document.createTextNode("Session on "));
		this.span_date = document.createElement("SPAN");
		title.appendChild(this.span_date);
		title.appendChild(document.createTextNode(" from "));
		this.span_start_time = document.createElement("SPAN");
		title.appendChild(this.span_start_time);
		title.appendChild(document.createTextNode(" to "));
		this.span_end_time = document.createElement("SPAN");
		title.appendChild(this.span_end_time);
		var content = document.createElement("DIV");
		this.session_section = new section(null, title, content, true, false, 'soft', false);
		this.session_section.element.style.display = "inline-block";
		this.session_section.element.style.margin = "5px";
		this.session_section.element.style.verticalAlign = "top";
		container.appendChild(this.session_section.element);
		
		var reschedule_button = document.createElement("BUTTON");
		reschedule_button.innerHTML = "<img src='/static/selection/date_clock_picker.png'/>";
		reschedule_button.title = "Reschedule this session to another date/time";
		reschedule_button.className = "flat";
		this.session_section.addToolRight(reschedule_button);
		reschedule_button.t = this;
		reschedule_button.onclick = function() { this.t.reschedule(); };
		
		var remove_button = document.createElement("BUTTON");
		remove_button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
		remove_button.title = "Remove this session, and unassign all applicants from this session";
		remove_button.className = "flat";
		this.session_section.addToolRight(remove_button);
		remove_button.t = this;
		remove_button.onclick = function() {
			var message;
			if (event.start.getTime() < new Date().getTime())
				message = "This session is in the past. If you remove it, all applicants assigned to it will be marked as not assigned, and if any already has exam results, those results will be removed.<br/>Are you sure you want to remove this session ?";
			else
				message = "Are you sure you want to remove this session, and unassign all applicants from it ?";
			confirm_dialog(message, function(yes) {
				if (yes) sessions._removeSession(event);
			});
		};
		
		var header = document.createElement("DIV"); content.appendChild(header);
		header.className = "header_bar_menubar_style";
		header.style.padding = "3px 5px 3px 5px";
		header.style.height = "20px";
		this.nb_applicants_span = document.createElement("SPAN");
		header.appendChild(this.nb_applicants_span);
		header.appendChild(document.createTextNode(" applicant(s) for this session."));
		
		this.rooms_container = document.createElement("DIV");
		content.appendChild(this.rooms_container);
		
		this.refresh();
	};
	this._rooms = [];
	this.refresh = function() {
		this.span_date.innerHTML = getDateString(event.start);
		this.span_start_time.innerHTML = getTimeString(event.start);
		this.span_end_time.innerHTML = getTimeString(event.end);
		
		this.rooms_container.removeAllChildren();
		this._rooms = [];
		for (var i = 0; i < sessions.rooms.length; ++i)
			this._rooms.push(new RoomSection(this.rooms_container, sessions.rooms[i], this));

		for (var i = 0; i < sessions.applicants.length; ++i) {
			if (sessions.applicants[i].exam_session_id != event.id) continue;
			for (var i = 0; i < this._rooms.length; ++i)
				if (sessions.applicants[i].exam_room_id == this._rooms[i].room.id)
					this._rooms[i].applicants_list.addApplicant(sessions.applicants[i]);
		}

		this.refreshNbApplicants();
		layout.invalidate(this.session_section.element);
	};
	this.refreshNbApplicants = function() {
		var total_applicants = 0;
		for (var i = 0; i < sessions.applicants.length; ++i) {
			if (sessions.applicants[i].exam_session_id != event.id) continue;
			total_applicants++;
		}
		
		this.nb_applicants_span.innerHTML = total_applicants;
	};
	this._init();
}

function RoomSection(container, room, session_section) {
	this.room = room;
	this.session_section = session_section;
	this._init = function() {
		var title = document.createElement("SPAN");
		var checkbox = document.createElement("INPUT"); title.appendChild(checkbox);
		checkbox.type = "checkbox";
		checkbox.style.marginRight = "3px";
		title.appendChild(document.createTextNode("Room "));
		var room_name = document.createElement("SPAN"); title.appendChild(room_name);
		room_name.appendChild(document.createTextNode(room.name));
		window.top.datamodel.registerCellSpan(window, "ExamCenterRoom", "name", room.id, room_name);
		title.appendChild(document.createTextNode(" ("));
		var room_usage = document.createElement("SPAN");
		title.appendChild(room_usage);
		title.appendChild(document.createTextNode("/"));
		var room_capacity = document.createElement("SPAN");
		room_capacity.innerHTML = room.capacity;
		title.appendChild(room_capacity);
		window.top.datamodel.registerCellSpan(window, "ExamCenterRoom", "capacity", room.id, room_capacity);
		title.appendChild(document.createTextNode(")"));
		var content = document.createElement("DIV");
		this.room_section = new section(null, title, content, true, false, 'sub');
		this.room_section.element.style.display = "inline-block";
		this.room_section.element.style.margin = "2px";
		this.room_section.element.style.verticalAlign = "top";
		container.appendChild(this.room_section.element);
		var t=this;
		this.applicants_list = new applicant_data_grid(content, function(obj) { return obj; });
		var listener = function() {
			room_usage.innerHTML = t.applicants_list.getList().length;
		};
		listener();
		this.applicants_list.object_added.add_listener(listener);
		this.applicants_list.object_removed.add_listener(listener);
		this.applicants_list.addDropSupport()

		this.applicants_list.addDropSupport("applicant", function(people_id) {
			// check applicant before drop
			for (var i = 0; i < t.applicants_list.getList().length; ++i)
				if (t.applicants_list.getList()[i].people.id == people_id) return null; // same target
			return "move";
		}, function(people_id) {
			// applicant dropped
			t.session_section.sessions._moveApplicant(people_id, t.session_section, t);
		});
		this.applicants_list.addDragSupport("applicant", function(obj) { return obj.people.id; });
		this.applicants_list.addPeopleProfileAction();
		var list_header = new ApplicantsListHeader(this.applicants_list);
		// add unassign button for selected ones
		list_header.addSelectionAction("Unassign", "action", "Unassign selected applicants from this room and session", function() {
			var list = t.applicants_list.getSelection();
			var doit = function() {
				for (var i = 0; i < list.length; ++i)
					t.session_section.sessions._unassign(list[i], t.session_section, t, null, true);
			};
			if (t.session_section.event.start.getTime() < new Date().getTime()) {
				// the session is in the past !
				confirm_dialog("The session is already done. When you will save, if any of those applicants already has results for the exams, the results will be removed. Are you sure you want to unassign those applicants ?", function(yes) {
					if (yes) doit();
				});
			} else
				doit();
		});
		// add unassign button per applicant
		this.applicants_list.addAction(function(container, applicant) {
			var button = document.createElement("BUTTON");
			button.className = "flat small";
			button.title = "Unassign this applicant";
			button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
			container.appendChild(button);
			button.onclick = function() {
				t.session_section.sessions._unassign(applicant, t.session_section, t);
				return false;
			};
		});
		for (var i = 0; i < session_section.sessions.applicants.length; ++i) {
			var applicant = session_section.sessions.applicants[i];
			if (applicant.exam_session_id == session_section.event.id && applicant.exam_center_room_id == room.id)
				this.applicants_list.addApplicant(applicant);
		}
		layout.invalidate(container);
	};
	this._init();
}

function ApplicantsListHeader(data_grid) {
	this.header = document.createElement("DIV");
	data_grid.container.insertBefore(this.header, data_grid.grid.grid_element);
	this.header.className = "header_bar_menubar_style";
	this.header.style.padding = "3px 5px 3px 5px";
	this.header.style.height = "22px";
	this.nb_selected_span = document.createElement("SPAN");
	this.nb_selected_span.innerHTML = "0/0";
	this.header.appendChild(this.nb_selected_span);
	this.header.appendChild(document.createTextNode(" selected "));

	this._selection_buttons = [];
	this.refresh = function() {
		var sel = data_grid.getSelection();
		this.nb_selected_span.innerHTML = sel.length + "/" + data_grid.getList().length;
		for (var i = 0; i < this._selection_buttons.length; ++i)
			this._selection_buttons[i].disabled = sel.length > 0 ? "" : "disabled";
		layout.invalidate(this.header);
	};
	this.addSelectionAction = function(html, css, tooltip, onclick) {
		var button = document.createElement("BUTTON");
		button.className = css;
		button.title = tooltip;
		button.innerHTML = html;
		this.header.appendChild(button);
		button.disabled = this.nb_selected > 0 ? "" : "disabled";
		button.t = this;
		button.onclick = onclick;
		this._selection_buttons.push(button);
		layout.invalidate(this.header);
	};
	
	var t=this;
	data_grid.selection_changed.add_listener(function() { t.refresh(); });
	data_grid.object_added.add_listener(function() { t.refresh(); });
	data_grid.object_removed.add_listener(function() { t.refresh(); });
}