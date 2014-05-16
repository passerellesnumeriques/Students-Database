if (typeof theme != 'undefined') theme.css("header_bar.css");
if (typeof require != 'undefined') {
	require(["event_date_time_duration.js","popup_window.js","calendar_objects.js"]);
}

function exam_center_sessions(container, sessions, applicants, center_rooms, linked_is, default_duration, calendar_id) {
	if (typeof container == 'string') container = document.getElementById(container);
	
	this.sessions = sessions;
	this.applicants = applicants;
	this.center_rooms = center_rooms;
	this.linked_is = linked_is;
	
	this._new_event_id_counter = -1;
	
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
				var event = new CalendarEvent(
					t._new_event_id_counter--, 
					'PN', 
					calendar_id, 
					null, 
					Math.floor(date.date.getTime()/1000), 
					Math.floor(date.date.getTime()/1000+date.duration*60)
				);
				t.sessions.push(event);
				t._createSession(event);
				popup.close();
			});
			popup.show();
		});
	};
	
	this._init = function() {
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
		
		this._sessions_container = document.createElement("DIV");
		this._sessions_container.style.overflowX = "auto";
		this._sessions_container.style.verticalAlign = "top";
		container.appendChild(this._sessions_container);
		
		// not assigned
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
		this._sessions_container.appendChild(this._section_not_assigned.element);
		var t=this;
		this.not_assigned = new ApplicantsList(not_assigned_container, list, not_assigned_nb, not_assigned_cb, "Assign", function(button, app_list) {
			require("context_menu.js", function() {
				var menu = new context_menu();
				menu.addIconItem(null, "Automatically assign", function() {
					var applicants = t.not_assigned.getSelectedApplicants();
					for (var i = 0; i < applicants.length; ++i)
						if (!t._assignAuto(applicants[i])) {
							alert("No more available seat. Please add a new schedule.");
							break;
						}
				});
				menu.addSeparator();
				for (var i = 0; i < t.sessions.length; ++i) {
					for (var j = 0; j < t.center_rooms.rooms.length; ++j) {
						var text = "Session on "+getDateString(new Date(t.sessions[i].start))+" at "+getTimeString(new Date(t.sessions[i].start))+" in room "+t.center_rooms.rooms[j].name;
						menu.addIconItem(null, text, function(o) {
							var applicants = t.not_assigned.getSelectedApplicants();
							for (var i = 0; i < applicants.length; ++i)
								if (!t._assignTo(applicants[i], o.session, o.room)) {
									alert("No more available seat in this room.");
									break;
								}
						}, {session:t.sessions[i], room:t.center_rooms.rooms[j]});
					}
				}
				menu.showBelowElement(button);
			});
		}, function(people_id) {
			// applicant dropped
			var applicant = null;
			for (var i = 0; i < t.applicants.length; ++i)
				if (t.applicants[i].people.id == people_id) { applicant = t.applicants[i]; break; }
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
		
		// sessions and rooms
		for (var i = 0; i < this.sessions.length; ++i)
			this._createSession(this.sessions[i]);
		
		var t=this;
		linked_is.onapplicantsadded.add_listener(function(list) {
			for (var i = 0; i < list.length; ++i) {
				var app = list[i];
				app.exam_center_room_id = null;
				app.exam_session_id = null;
				t.applicants.push(app);
				t.not_assigned.addApplicant(app);
				t._span_total_applicants.innerHTML = t.applicants.length;
			}
		});
		linked_is.onapplicantsremoved.add_listener(function(list) {
			for (var i = 0; i < list.length; ++i) {
				for (var j = 0; j < t.applicants.length; ++j) {
					if (t.applicants[j].people.id == list[i].people.id) {
						if (t.applicants[j].exam_session_id == null)
							t.not_assigned.removeApplicant(t.applicants[j]);
						t.applicants.splice(j,1);
						break;
					}
				}
			}
			for (var i = 0; i < t._sessions_sections.length; ++i)
				t._sessions_sections[i].refresh();
			t._span_total_applicants.innerHTML = t.applicants.length;
		});
	};
	
	this._assignAuto = function(applicant) {
		var max_slots = 0;
		var max_room = null;
		var max_session = null;
		for (var i = 0; i < this._sessions_sections.length; ++i) {
			var s = this._sessions_sections[i];
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
		return this._assignTo(applicant, max_session, max_room);
	};
	this._assignTo = function(applicant, session, room) {
		var s = null;
		for (var i = 0; i < this._sessions_sections.length; ++i)
			if (this._sessions_sections[i].event == session) { s = this._sessions_sections[i]; break; }
		var r = null;
		for (var i = 0; i < s._rooms.length; ++i)
			if (s._rooms[i].room == room) { r = s._rooms[i]; break; }
		if (r.applicants_list.applicants.length == r.room.capacity)
			return false; // no more seat
		applicant.exam_center_room_id = room.id;
		applicant.exam_session_id = session.id;
		this.not_assigned.removeApplicant(applicant);
		r.applicants_list.addApplicant(applicant);
		return true;
	};
	this._unassign = function(applicant, session_section, room_section) {
		room_section.applicants_list.removeApplicant(applicant);
		applicant.exam_session_id = null;
		applicant.exam_center_room_id = null;
		this.not_assigned.addApplicant(applicant);
	};
	this._moveApplicant = function(people_id, session_section, room_section) {
		var applicant = null;
		for (var i = 0; i < this.applicants.length; ++i)
			if (this.applicants[i].people.id == people_id) { applicant = this.applicants[i]; break; }
		if (!applicant) return;
		if (!applicant.exam_session_id) {
			// not assigned
			this._assignTo(applicant, session_section.event, room_section.room);
			return;
		}
		var from_session_section = null;
		for (var i = 0; i < this._sessions_sections.length; ++i)
			if (this._sessions_sections[i].event.id == applicant.exam_session_id) { from_session_section = this._sessions_sections[i]; break; }
		if (!from_session_section) return;
		var from_room_section = null;
		for (var i = 0; i < from_session_section._rooms.length; ++i)
			if (from_session_section._rooms[i].room.id == applicant.exam_center_room_id) { from_room_section = from_session_section._rooms[i]; break; }
		if (!from_room_section) return;
		// unassign
		this._unassign(applicant, from_session_section, from_room_section);
		// assign
		this._assignTo(applicant, session_section.event, room_section.room);
	};
	
	this._sessions_sections = [];
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
	};
	
	this._init();
}

function ExamSessionSection(container, event, sessions) {
	this.event = event;
	this.sessions = sessions;
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
		
		var remove_button = document.createElement("BUTTON");
		remove_button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
		remove_button.title = "Remove this session, and unassign all applicants from this session";
		remove_button.className = "flat";
		this.session_section.addToolRight(remove_button);
		remove_button.t = this;
		remove_button.onclick = function() { sessions._removeSession(event); };
		
		var header = document.createElement("DIV"); content.appendChild(header);
		header.className = "header_bar_menubar_style";
		header.style.padding = "3px 5px 3px 5px";
		header.style.height = "20px";
		this.nb_applicants_span = document.createElement("SPAN");
		header.appendChild(this.nb_applicants_span);
		header.appendChild(document.createTextNode(" applicant(s) for this session."));
		
		this.rooms_container = document.createElement("DIV");
		content.appendChild(this.rooms_container);
		
		var t=this;
		sessions.center_rooms.onroomadded.add_listener(function(room) {
			t._rooms.push(new RoomSection(t.rooms_container, room, t));
		});
		sessions.center_rooms.onroomremoved.add_listener(function(room) {
			for (var i = 0; i < t._rooms.length; ++i) {
				if (t._rooms[i].room == room) {
					t._rooms[i].room_section.element.parentNode.removeChild(t._rooms[i].room_section.element);
					for (var j = 0; j < t._rooms[i].applicants_list.applicants.length; ++j) {
						t._rooms[i].applicants_list.applicants[i].exam_session_id = null;
						t._rooms[i].applicants_list.applicants[i].exam_room_id = null;
						sessions.not_assigned.addApplicant(t._rooms[i].applicants_list.applicants[j]);
					}
					t._rooms.splice(i,1);
					break;
				}
			}
		});
		sessions.center_rooms.onroomcapacitychanged.add_listener(function (room) {
			var r = null;
			for (var i = 0; i < t._rooms.length; ++i)
				if (t._rooms[i].room == room) { r = t._rooms[i]; break; }
			while (r.applicants_list.applicants.length > room.capacity) {
				var app = r.applicants_list.applicants[room.capacity];
				r.applicants_list.removeApplicant(app);
				app.exam_session_id = null;
				app.exan_room_id = null;
				sessions.not_assigned.addApplicant(app);
			}
		});
		
		this.refresh();
	};
	this._rooms = [];
	this.refresh = function() {
		this.span_date.innerHTML = getDateString(new Date(event.start));
		this.span_start_time.innerHTML = getTimeString(new Date(event.start));
		this.span_end_time.innerHTML = getTimeString(new Date(event.end));
		
		this.rooms_container.innerHTML = "";
		this._rooms = [];
		for (var i = 0; i < sessions.center_rooms.rooms.length; ++i)
			this._rooms.push(new RoomSection(this.rooms_container, sessions.center_rooms.rooms[i], this));
		
		var total_applicants = 0;
		for (var i = 0; i < sessions.applicants.length; ++i) {
			if (sessions.applicants[i].exam_session_id != event.id) continue;
			total_applicants++;
			for (var i = 0; i < this._rooms.length; ++i)
				if (sessions.applicants[i].exam_room_id == this._rooms[i].room.id)
					this._rooms[i].applicants_list.addApplicant(sessions.applicants[i]);
		}
		
		this.nb_applicants_span.innerHTML = total_applicants;
		layout.invalidate(this.session_section.element);
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
		this.applicants_list = new ApplicantsList(content, [], room_usage, checkbox, "Unassign", function(button,app_list) {
			var list = app_list.getSelectedApplicants();
			for (var i = 0; i < list.length; ++i)
				t.session_section.sessions._unassign(list[i], t.session_section, t);
		}, function(people_id) {
			// applicant dropped
			t.session_section.sessions._moveApplicant(people_id, t.session_section, t);
		});
		layout.invalidate(container);
	};
	this._init();
}

function ApplicantsList(container, applicants, nb_span, global_checkbox, selection_button_text, selection_button_onclick, ondrop) {
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
		this.table.innerHTML = "";
		this.nb_selected_span.innerHTML = "0/0";
		this.nb_selected = 0;
		this.button_unassign.disabled = 'disabled';
		this._checkboxes = [];
		for (var i = 0; i < this.applicants.length; ++i)
			this._createRow(this.applicants[i]);
		layout.invalidate(this.table);
	};
	this._refreshSelection = function() {
		this.nb_selected = 0;
		for (var i = 0; i < this._checkboxes.length; ++i)
			if (this._checkboxes[i].checked)
				this.nb_selected++;
		this.nb_selected_span = this.nb_selected + "/" + this.applicants.length;
		this.button_unassign.disabled = this.nb_selected > 0 ? "" : "disabled";
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
		profile_button.className = "flat";
		profile_button.innerHTML = "<img src='/static/people/profile_16.png'/>";
		profile_button.title = "See profile of this applicant";
		td.appendChild(profile_button);
		profile_button.people_id = app.people.id;
		profile_button.onclick = function() {
			window.top.popup_frame("/static/people/profile_16.png", "Applicant Profile", "/dynamic/people/page/profile?people="+this.people_id, null, 95, 95);
		};
	};
	
	this._init = function () {
		var header = document.createElement("DIV");
		container.appendChild(header);
		header.className = "header_bar_menubar_style";
		header.style.padding = "3px 5px 3px 5px";
		header.style.height = "22px";
		this.nb_selected_span = document.createElement("SPAN");
		this.nb_selected = 0;
		header.appendChild(this.nb_selected_span);
		header.appendChild(document.createTextNode(" selected "));
		this.button_unassign = document.createElement("BUTTON");
		this.button_unassign.className = "action";
		this.button_unassign.innerHTML = selection_button_text;
		header.appendChild(this.button_unassign);
		this.button_unassign.t = this;
		this.button_unassign.onclick = function() {
			selection_button_onclick(this, this.t);
		};
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