if (typeof theme != 'undefined') theme.css("header_bar.css");

function interview_sessions(container, sessions, applicants, linked_exam_centers, calendar_id, staffs, can_edit) {
	if (typeof container == 'string') container = document.getElementById(container);

	this.sessions = sessions;
	this.applicants = applicants;
	
	this.sessions_controls = [];
	
	this._new_event_id_counter = -1;
	
	var t=this;
	
	/* -------- Sessions -------- */
	
	this.newSession = function() {
		var t=this;
		require(["event_date_time_duration.js","popup_window.js","calendar_objects.js",["typed_field.js","field_integer.js"]], function() {
			var content = document.createElement("DIV");
			content.style.backgroundColor = "white";
			content.style.padding = "10px";
			var popup = new popup_window("New session", null, content);
			new event_date_time_duration(content, null, 60, null, null, false, false,function(date) {
				var div = document.createElement("DIV");
				div.style.marginTop = "10px";
				div.appendChild(document.createTextNode("Duration of an interview in minutes: "));
				var every_minutes = new field_integer(30,true,{min:5,max:120,can_be_null:false});
				div.appendChild(every_minutes.getHTMLElement());
				content.appendChild(div);
				popup.addOkCancelButtons(function() {
					if (date.date == null) { alert('Please select a date'); return; }
					if (date.duration == null || date.duration == 0) { alert('Please enter a duration'); return; }
					if (every_minutes.getCurrentData() == null) { alert('Please enter a duration of an interview'); return; }
					if (every_minutes.getCurrentData() < 5) { alert('Please enter a reasonable duration, this is too low'); return; }
					if (every_minutes.getCurrentData() > 120) { alert('Please enter a reasonable duration, this is too high'); return; }
					var d = new Date(date.date.getTime());
					d.setHours(0,date.time,0,0);
					var doit = function() {
						popup.close();
						window.pnapplication.dataUnsaved("InterviewCenterSessions");
						var session = {
							event:new CalendarEvent(t._new_event_id_counter--,'PN',calendar_id,null,d,new Date(d.getTime()+date.duration*60*1000)),
							every_minutes: every_minutes.getCurrentData()
						}
						t.sessions.push(session);
						t.sessions_controls.push(new InterviewSession(t, session, staffs, can_edit));
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
		});
	};
	
	this.removeSession = function(session) {
		// TODO
	};
	
	/* -------- Applicants -------- */
	
	this.removeApplicantFromCenter = function(applicant) {
		if (applicant.interview_session_id == null)
			this._not_assigned.not_assigned.removeApplicant(applicant);
		else
			for (var i = 0; i < sessions.length; ++i)
				if (sessions[i].event.id == applicant.interview_session_id)
					sessions_controls[i].applicants_list.removeApplicant(applicant);
		this.applicants.remove(applicant);
		this._updateHeader();
	};
	
	this.unassign = function(applicant) {
		// TODO
	};
	
	this.assignApplicantAutomatically = function(applicant) {
		// TODO
	};
	
	this.assign = function(applicant, session_event_id) {
		// TODO
	};
	
	/* -------- Header Management -------- */
	
	this.updateHeader = function() {
		this._span_total_applicants.innerHTML = this.applicants.length;
		this._span_nb_sessions.innerHTML = this.sessions.length;
	};
	
	/* -------- Initialization of the screen -------- */
	
	this._init = function() {
		this._initHeader();
		this._initListeners();
		var table = document.createElement("TABLE");
		container.appendChild(table);
		this.tr_sessions = document.createElement("TR");
		table.appendChild(this.tr_sessions);
		var td = document.createElement("TD");
		td.style.verticalAlign = "top";
		this.tr_sessions.appendChild(td);
		this._not_assigned = new NotAssignedApplicants(this, td, can_edit);
		for (var i = 0; i < this.sessions.length; ++i)
			this.sessions_controls.push(new InterviewSession(this, this.sessions[i], staffs, can_edit));
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
		this._header.appendChild(document.createTextNode(" applicant(s) are assigned to this interview center. "));
		this._span_nb_sessions = document.createElement("SPAN");
		this._span_nb_sessions.innerHTML = this.sessions.length;
		this._header.appendChild(this._span_nb_sessions);
		this._header.appendChild(document.createTextNode(" session(s) scheduled. "));
		if (can_edit) {
			var button_new_session = document.createElement("BUTTON");
			button_new_session.className = "action green";
			button_new_session.innerHTML = "Schedule new session";
			this._header.appendChild(button_new_session);
			button_new_session.onclick = function() { t.newSession(); };
		}
	};
	this._initListeners = function() {
		linked_exam_centers.onapplicantsadded.add_listener(function(new_applicants) {
			for (var i = 0; i < new_applicants.length; ++i) {
				t.applicants.push(new_applicants[i]);
				t._not_assigned.not_assigned.addApplicant(new_applicants[i]);
			}
			t.updateHeader();
		});
		linked_exam_centers.onapplicantsremoved.add_listener(function(removed_applicants) {
			for (var i = 0; i < removed_applicants.length; ++i) {
				t.applicants.remove(removed_applicants[i]);
				if (removed_applicants[i].interview_session_id == null)
					t._not_assigned.not_assigned.removeApplicant(removed_applicants[i]);
				// TODO else
			}
			t.updateHeader();
		});
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
		layout.changed(this.header);
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
		layout.changed(this.header);
	};
	
	var t=this;
	data_grid.selection_changed.add_listener(function() { t.refresh(); });
	data_grid.object_added.add_listener(function() { t.refresh(); });
	data_grid.object_removed.add_listener(function() { t.refresh(); });
}

function NotAssignedApplicants(interview_sessions, container, can_edit) {
	var list = [];
	for (var i = 0; i < interview_sessions.applicants.length; ++i)
		if (interview_sessions.applicants[i].interview_session_id == null)
			list.push(interview_sessions.applicants[i]);
	var not_assigned_container = document.createElement("DIV");
	var not_assigned_title = document.createElement("SPAN");
	var not_assigned_nb = document.createElement("SPAN"); not_assigned_title.appendChild(not_assigned_nb);
	not_assigned_title.appendChild(document.createTextNode(" applicant(s) without schedule"));
	this._section_not_assigned = new section(null, not_assigned_title, not_assigned_container, true, false, 'sub', false);
	this._section_not_assigned.element.style.display = "inline-block";
	this._section_not_assigned.element.style.margin = "5px";
	this._section_not_assigned.element.style.verticalAlign = "top";
	container.appendChild(this._section_not_assigned.element);
	var t=this;
	this.not_assigned = new applicant_data_grid(not_assigned_container, function(obj) { return obj; });
	var listener = function() {
		not_assigned_nb.innerHTML = t.not_assigned.getList().length;
		t._section_not_assigned.header.style.background = t.not_assigned.getList().length > 0 ? "#FF8000" : "";
	};
	listener();
	this.not_assigned.object_added.add_listener(listener);
	this.not_assigned.object_removed.add_listener(listener);
	if (can_edit)
		this.not_assigned.addDropSupport("applicant", function(people_id) {
			// check applicant before drop
			for (var i = 0; i < t.not_assigned.getList().length; ++i)
				if (t.not_assigned.getList()[i].people.id == people_id) return null; // same target
			return "move";
		}, function(people_id) {
			// applicant dropped
			var applicant = t.getApplicant(people_id);
			if (!applicant) return;
			if (!applicant.interview_session_id) return;
			interview_sessions.unassign(applicant);
		});
	this.not_assigned.addDragSupport("applicant", function(obj) { return obj.people.id; });
	this.not_assigned.addPeopleProfileAction();
	if (can_edit)
		this.not_assigned.addAction(function(container, applicant) {
			var button = document.createElement("BUTTON");
			button.className = "flat small";
			button.title = "Remove this applicant from this interview center";
			button.innerHTML = "<img src='/static/selection/common_centers/remove_applicant_from_center.png'/>";
			button.onclick = function() {
				confirm_dialog("Are you sure this applicant will not come to this interview center ?", function(yes) {
					if (yes) interview_sessions.removeApplicantFromCenter(applicant);
				});
				return false;
			};
			container.appendChild(button);
		});
	if (can_edit)
		this.not_assigned.makeSelectable();
	for (var i = 0; i < list.length; ++i)
		this.not_assigned.addApplicant(list[i]);
	if (can_edit) {
		var not_assigned_header = new ApplicantsListHeader(this.not_assigned);
		// add assign button
		not_assigned_header.addSelectionAction("Assign", "action", "Assign selected applicants to a session", function() {
			var button = this;
			require("context_menu.js", function() {
				var menu = new context_menu();
				menu.addIconItem(null, "Automatically assign", function() {
					var applicants = t.not_assigned.getSelection();
					for (var i = 0; i < applicants.length; ++i)
						if (!t.assignApplicantAutomatically(applicants[i])) {
							alert("No more available time in future sessions. Please add a new schedule or extend a session.");
							break;
						}
				});
				menu.addSeparator();
				for (var i = 0; i < interview_sessions.sessions.length; ++i) {
					var slots = interview_sessions.sessions_controls[i].getAvailableSlots();
					if (slots == 0) continue; // already full
					var text = "Session on "+getDateString(interview_sessions.sessions[i].event.start)+" at "+getTimeString(interview_sessions.sessions[i].event.start);
					menu.addIconItem(null, text, function(ev,session) {
						var applicants = t.not_assigned.getSelection();
						var doit = function() {
							for (var i = 0; i < applicants.length; ++i)
								if (!interview_sessions.assign(applicants[i], session.event.id)) {
									alert("No more available slot in this session.");
									break;
								}
						};
						if (session.event.start.getTime() < new Date().getTime()) {
							confirm_dialog("You are going to assign applicants to a session which is already done (in the past). Are you sure you want to do this ?", function(yes) {
								if (!yes) return;
								doit();
							});
						} else
							doit();
					}, interview_sessions.sessions[i]);
				}
				menu.showBelowElement(button);
			});
		});
		// add remove button
		not_assigned_header.addSelectionAction("<img src='/static/selection/common_centers/remove_applicant_from_center.png'/> Remove", "action red", "Remove selected applicants from this interview center", function() {
			confirm_dialog("Are you sure those applicants will not come to this interview center ?", function(yes) {
				if (yes) {
					var applicants = t.not_assigned.getSelection();
					for (var i = 0; i < applicants.length; ++i)
						interview_sessions.removeApplicantFromCenter(applicants[i]);
				}
			});
		});
	}
}

function InterviewSession(interview_sessions, session, staffs, can_edit) {
	this.session = session;
	
	var t=this;
	
	this.getAvailableSlots = function() {
		// TODO
	};
	
	this.reschedule = function() {
		// TODO
	};
	
	this.refresh = function() {
		this.span_date.innerHTML = getDateString(session.event.start,true);
		this.span_start_time.innerHTML = getTimeString(session.event.start,true);
		this.span_end_time.innerHTML = getTimeString(session.event.end,true);
		var nb_staff = this.who.peoples.length;
		var nb_applicants = this.applicants_list.list.length;
		var duration = session.event.end.getTime()-session.event.start.getTime();
		duration = Math.floor(duration/60000);
		var nb_slots = Math.floor(duration/session.every_minutes);
		this.span_nb_staff.innerHTML = nb_staff;
		this.nb_interviews.setMinimum(nb_staff == 0 ? 0 : 1);
		this.nb_interviews.setMaximum(nb_staff);
		this.nb_interviews.validate();
		var nb_done = this.nb_interviews.getCurrentData();
		if (nb_done == null) nb_done = 0;
		nb_slots *= nb_done;
		this.span_every_minutes.innerHTML = session.every_minutes;
		this.span_duration.innerHTML = duration;
		this.span_nb_slots.innerHTML = nb_slots;
		this.span_nb_applicants.innerHTML = nb_applicants;
		layout.changed(this.header);
		layout.changed(this.span_date);
	};
	
	this._init = function() {
		this.container = document.createElement("TD");
		interview_sessions.tr_sessions.appendChild(this.container);
		var title = document.createElement("SPAN");
		title.appendChild(document.createTextNode("Session on "));
		this.span_date = document.createElement("SPAN");
		title.appendChild(this.span_date);
		title.appendChild(document.createTextNode("From "));
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
		this.container.appendChild(this.session_section.element);

		var wc = document.createElement("DIV"); content.appendChild(wc);
		wc.style.borderBottom = "1px solid #808080";
		this.who = new who_container(wc, staffs, can_edit, 'interview');
		this.who.onadded.add_listener(function(people) {
			var a;
			if (typeof people == 'string') {
				a = new CalendarEventAttendee(people,calendar_event_role_requested,calendar_event_participation_unknown,false,false);
			} else {
				a = new CalendarEventAttendee(people.people.first_name+' '+people.people.last_name,calendar_event_role_requested,calendar_event_participation_unknown,false,false,null,people.people.id);
			}
			session.event.attendees.push(a);
			t.refresh();
		});
		this.who.onremoved.add_listener(function(people) {
			if (typeof people == 'string') {
				for (var i = 0; i < event.attendees.length; ++i)
					if (session.event.attendees[i].people == null && session.event.attendees[i].name == people) {
						session.event.attendees.splice(i,1);
						break;
					}
			} else
				for (var i = 0; i < event.attendees.length; ++i)
					if (session.event.attendees[i].people == people.people.id) {
						session.event.attendees.splice(i,1);
						break;
					}
			t.refresh();
		});

		this.header = document.createElement("DIV");
		this.header.className = "header_bar_menubar_style";
		this.header.style.padding = "3px 5px 3px 5px";
		this.header.style.height = "inherit";
		content.appendChild(this.header);
		this.span_nb_staff = document.createElement("SPAN");
		this.header.appendChild(this.span_nb_staff);
		this.header.appendChild(document.createTextNode(" staff(s) assigned."));
		this.header.appendChild(document.createElement("BR"));
		this.header.appendChild(document.createTextNode("How many parallel interviews can be done by those staffs ? "));
		this.nb_interviews = new field_integer(0,true,{min:0,max:0,can_be_null:false});
		this.header.appendChild(this.nb_interviews.getHTMLElement());
		this.nb_interviews.onchange = function() { t.refresh(); };
		this.header.appendChild(document.createElement("BR"));
		this.header.appendChild(document.createTextNode("One interview done every "));
		this.span_every_minutes = document.createElement("SPAN");
		this.header.appendChild(this.span_every_minutes);
		this.header.appendChild(document.createTextNode(" minutes, during "));
		this.span_duration = document.createElement("SPAN");
		this.header.appendChild(this.span_duration);
		this.header.appendChild(document.createTextNode(" minutes"));
		this.header.appendChild(document.createElement("BR"));
		this.header.appendChild(document.createTextNode("Total possible interviews: "));
		this.span_nb_slots = document.createElement("SPAN");
		this.header.appendChild(this.span_nb_slots);
		this.header.appendChild(document.createElement("BR"));
		this.header.appendChild(document.createTextNode("Assigned applicants: "));
		this.span_nb_applicants = document.createElement("SPAN");
		this.header.appendChild(this.span_nb_applicants);
		
		this.applicants_list = new applicant_data_grid(content, function(obj) { return obj; },true);

		if (can_edit) {
			var reschedule_button = document.createElement("BUTTON");
			reschedule_button.innerHTML = "<img src='/static/selection/date_clock_picker.png'/>";
			reschedule_button.title = "Modify the schedule of this session";
			reschedule_button.className = "flat";
			this.session_section.addToolRight(reschedule_button);
			reschedule_button.onclick = function() { t.reschedule(); };

			this.session_section.addToolRight(this.who.createAddButton("Which staff will be at this interview session ?"));
			
			var remove_button = document.createElement("BUTTON");
			remove_button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
			remove_button.title = "Remove this session, and unassign all applicants from this session";
			remove_button.className = "flat";
			this.session_section.addToolRight(remove_button);
			remove_button.onclick = function() {
				var message;
				if (session.event.start.getTime() < new Date().getTime())
					message = "This session is in the past. If you remove it, all applicants assigned to it will be marked as not assigned, and if any already has interview results, those results will be removed.<br/>Are you sure you want to remove this session ?";
				else
					message = "Are you sure you want to remove this session, and unassign all applicants from it ?";
				confirm_dialog(message, function(yes) {
					if (yes) interview_sessions.removeSession(session);
				});
			};
		}
		
		this.refresh();
	};
	this._init();
}