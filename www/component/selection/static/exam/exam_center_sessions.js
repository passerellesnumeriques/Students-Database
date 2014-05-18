if (typeof theme != 'undefined') theme.css("header_bar.css");
if (typeof require != 'undefined') {
	require(["horizontal_layout.js","event_date_time_duration.js","popup_window.js","calendar_objects.js"]);
}

function exam_center_sessions(container, rooms, sessions, applicants, linked_is, default_duration, calendar_id) {
	if (typeof container == 'string') container = document.getElementById(container);
	
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
				var slots = r.room.capacity - r.applicants_list.applicants.length;
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
		if (r.applicants_list.applicants.length == r.room.capacity)
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
		this._initRoomsAndSessions();
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
	this._initRoomsAndSessions = function() {
		// split into 2 horizontal elements: (1) the rooms and non-assigned applicants, (2) scheduled sessions
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
		
		// left side - rooms list
		this._initRooms();
		this._left_div.appendChild(document.createElement("BR"));
		// left side - not assigned
		this._initNotAssignedList();
		
		// sessions and rooms
		for (var i = 0; i < this.sessions.length; ++i)
			this._createSession(this.sessions[i]);
		
		// listen to events on linked information sessions
		var t=this;
		linked_is.onapplicantsadded.add_listener(function(list) {
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
		this._section_rooms.element.style.display = "inline-block";
		this._section_rooms.element.style.margin = "5px";
		this._left_div.appendChild(this._section_rooms.element);
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
		var not_assigned_cb = document.createElement("INPUT"); not_assigned_title.appendChild(not_assigned_cb);
		not_assigned_cb.type = "checkbox";
		not_assigned_cb.style.marginRight = "3px";
		var not_assigned_nb = document.createElement("SPAN"); not_assigned_title.appendChild(not_assigned_nb);
		not_assigned_title.appendChild(document.createTextNode(" applicant(s) without schedule"));
		this._section_not_assigned = new section(null, not_assigned_title, not_assigned_container, true, false, 'sub', false);
		this._section_not_assigned.element.style.display = "inline-block";
		this._section_not_assigned.element.style.margin = "5px";
		this._section_not_assigned.element.style.verticalAlign = "top";
		this._left_div.appendChild(this._section_not_assigned.element);
		var t=this;
		this.not_assigned = new ApplicantsList(not_assigned_container, list, not_assigned_nb, not_assigned_cb, function(people_id) {
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
		// make the title red when applicants are there
		this.not_assigned.__refresh = this.not_assigned._refresh;
		this.not_assigned._refresh = function() {
			this.__refresh();
			t._section_not_assigned.header.style.background = this.applicants.length > 0 ? "#FF8000" : "";
		};
		// add remove button for each applicant
		this.not_assigned.appendApplicantButtons = function(td, applicant) {
			var button = document.createElement("BUTTON");
			button.className = "flat small";
			button.title = "Remove this applicant from this exam center";
			button.innerHTML = "<img src='/static/selection/common_centers/remove_applicant_from_center.png'/>";
			button.onclick = function() {
				confirm_dialog("Are you sure this applicant will not come to this exam center ?", function(yes) {
					if (yes) t.removeApplicantFromCenter(applicant);
				});
			};
			td.appendChild(button);
		};
		// add assign button
		this.not_assigned.addSelectionAction("Assign", "action", "Assign selected applicants to a session and room", function(button, app_list) {
			require("context_menu.js", function() {
				var menu = new context_menu();
				menu.addIconItem(null, "Automatically assign", function() {
					var applicants = t.not_assigned.getSelectedApplicants();
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
							var applicants = t.not_assigned.getSelectedApplicants();
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
		this.not_assigned.addSelectionAction("<img src='/static/selection/common_centers/remove_applicant_from_center.png'/> Remove", "action important", "Remove selected applicants from this exam center", function(button, app_list) {
			confirm_dialog("Are you sure those applicants will not come to this exam center ?", function(yes) {
				if (yes) {
					var applicants = t.not_assigned.getSelectedApplicants();
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
				for (var j = 0; j < this._rooms[i].applicants_list.applicants.length; ++j) {
					this._rooms[i].applicants_list.applicants[i].exam_session_id = null;
					this._rooms[i].applicants_list.applicants[i].exam_room_id = null;
					sessions.not_assigned.addApplicant(this._rooms[i].applicants_list.applicants[j]);
				}
				this._rooms.splice(i,1);
				break;
			}
		}
		this.refreshNbApplicants();
	};
	this.roomCapacityChanged = function(room) {
		var r = this.getRoomSection(room.id);
		while (r.applicants_list.applicants.length > room.capacity) {
			var app = r.applicants_list.applicants[room.capacity];
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
		this.applicants_list = new ApplicantsList(content, [], room_usage, checkbox, function(people_id) {
			// applicant dropped
			t.session_section.sessions._moveApplicant(people_id, t.session_section, t);
		});
		// add unassign button for selected ones
		this.applicants_list.addSelectionAction("Unassign", "action", "Unassign selected applicants from this room and session", function(button,app_list) {
			var list = app_list.getSelectedApplicants();
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
		this.applicants_list.appendApplicantButtons = function(td, applicant) {
			var button = document.createElement("BUTTON");
			button.className = "flat small";
			button.title = "Unassign this applicant";
			button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
			td.appendChild(button);
			button.onclick = function() {
				t.session_section.sessions._unassign(applicant, t.session_section, t);
			};
		};
		layout.invalidate(container);
	};
	this._init();
}

function ApplicantsList(container, applicants, nb_span, global_checkbox, ondrop) {
	this.applicants = applicants;
	this._checkboxes = [];
	
	this.addApplicant = function(applicant) {
		this.applicants.push(applicant);
		global_checkbox.checked = "";
		this.refresh();
	};
	this.removeApplicant = function(applicant) {
		this.applicants.remove(applicant);
		global_checkbox.checked = "";
		this.refresh();
	};
	this.getSelectedApplicants = function() {
		var list = [];
		for (var i = 0; i < this._checkboxes.length; ++i)
			if (this._checkboxes[i].checked)
				list.push(this.applicants[i]);
		return list;
	};

	this._refresh_asked = false;
	this.refresh = function () {
		if (this._refresh_asked) return;
		var t=this;
		setTimeout(function() { t._refresh(); },1);
	};
	this._refresh = function() {
		this._refresh_asked = false;
		nb_span.innerHTML = this.applicants.length;
		this.table.removeAllChildren();
		this.nb_selected_span.innerHTML = "0/0";
		this.nb_selected = 0;
		for (var i = 0; i < this._selection_buttons.length; ++i)
			this._selection_buttons[i].disabled = 'disabled';
		this._checkboxes = [];
		if (this.applicants.length == 0) {
			this.table.innerHTML = "<tr><td align=center><i>No applicant here</i></td></tr>";
		} else {
			for (var i = 0; i < this.applicants.length; ++i)
				this._createRow(this.applicants[i]);
		}
		layout.invalidate(this.table);
	};
	this._refreshSelection = function() {
		this.nb_selected = 0;
		for (var i = 0; i < this._checkboxes.length; ++i)
			if (this._checkboxes[i].checked)
				this.nb_selected++;
		this.nb_selected_span = this.nb_selected + "/" + this.applicants.length;
		for (var i = 0; i < this._selection_buttons.length; ++i)
			this._selection_buttons[i].disabled = this.nb_selected > 0 ? "" : "disabled";
	};
	this._createRow = function(app) {
		var tr, td;
		this.table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		var cb = document.createElement("INPUT");
		cb.type = "checkbox";
		cb.t = this;
		this._checkboxes.push(cb);
		td.appendChild(cb);
		cb.onchange = function() {
			if (this.checked && !global_checkbox.checked) global_checkbox.checked = 'checked';
			else if (!this.checked && global_checkbox.checked) global_checkbox.checked = '';
			this.t._refreshSelection();
		};
		tr.appendChild(td = document.createElement("TD"));
		var name_container = document.createElement("SPAN");
		var span = document.createElement("SPAN"); name_container.appendChild(span);
		span.appendChild(document.createTextNode(app.people.first_name));
		window.top.datamodel.registerCellSpan(window, "People", "first_name", app.people.id, span);
		name_container.appendChild(document.createTextNode(" "));
		span = document.createElement("SPAN"); name_container.appendChild(span);
		span.appendChild(document.createTextNode(app.people.last_name));
		window.top.datamodel.registerCellSpan(window, "People", "last_name", app.people.id, span);
		td.appendChild(name_container);
		name_container.style.cursor = "default";
		name_container.draggable = true;
		name_container.ondragstart = function(event) {
			event.dataTransfer.setData("applicant",app.people.id);
			event.dataTransfer.effectAllowed = "move";
			return true;
		};

		tr.appendChild(td = document.createElement("TD"));
		var profile_button = document.createElement("BUTTON");
		profile_button.className = "flat small";
		profile_button.innerHTML = "<img src='/static/people/profile_16.png'/>";
		profile_button.title = "See profile of this applicant";
		td.appendChild(profile_button);
		profile_button.people_id = app.people.id;
		profile_button.onclick = function() {
			window.top.popup_frame("/static/people/profile_16.png", "Applicant Profile", "/dynamic/people/page/profile?people="+this.people_id, null, 95, 95);
		};
		this.appendApplicantButtons(td, app);
	};
	this.appendApplicantButtons = function(td, applicant) {};
	
	this._selection_buttons = [];
	this.addSelectionAction = function(html, css, tooltip, onclick) {
		var button = document.createElement("BUTTON");
		button.className = css;
		button.title = tooltip;
		button.innerHTML = html;
		this.header.appendChild(button);
		button.disabled = this.nb_selected > 0 ? "" : "disabled";
		button.t = this;
		button.onclick = function() {
			onclick(this, this.t);
		};
		this._selection_buttons.push(button);
	};
	
	this._init = function () {
		this.header = document.createElement("DIV");
		container.appendChild(this.header);
		this.header.className = "header_bar_menubar_style";
		this.header.style.padding = "3px 5px 3px 5px";
		this.header.style.height = "22px";
		this.nb_selected_span = document.createElement("SPAN");
		this.nb_selected = 0;
		this.header.appendChild(this.nb_selected_span);
		this.header.appendChild(document.createTextNode(" selected "));
		this.table = document.createElement("TABLE");
		container.appendChild(this.table);
		this._refresh();
		global_checkbox.t = this;
		global_checkbox.onchange = function() {
			var val = this.checked ? 'checked' : '';
			for (var i = 0; i < this.t._checkboxes.length; ++i)
				this.t._checkboxes[i].checked = val;
			this.t._refreshSelection();
		};
		
		var t=this;
		container.ondragover = function(event) {
			if (event.dataTransfer.types.contains("applicant")) {
				var people_id = event.dataTransfer.getData("applicant");
				for (var i = 0; i < t.applicants.length; ++i)
					if (t.applicants[i].people.id == people_id) return true; // same target
				container.style.outline = "2px dotted #808080";
				event.dataTransfer.dropEffect = "move";
				event.preventDefault();
				return false;
			}
		};
		container.ondragenter = function(event) {
			if (event.dataTransfer.types.contains("applicant")) {
				var people_id = event.dataTransfer.getData("applicant");
				for (var i = 0; i < t.applicants.length; ++i)
					if (t.applicants[i].people.id == people_id) return true; // same target
				container.style.outline = "2px dotted #808080";
				event.dataTransfer.dropEffect = "move";
				event.preventDefault();
				return true;
			}
		};
		container.ondragleave = function(event) {
			container.style.outline = "";
		};
		container.ondrop = function(event) {
			container.style.outline = "";
			var people_id = event.dataTransfer.getData("applicant");
			ondrop(people_id);
			event.stopPropagation();
			return false;
		};

	};
	this._init();
}