function si_visits(section, events, calendar_id, applicant_id, known_peoples, can_edit) {
	section.content.style.padding = "5px";
	this.events = events;
	this.whos = [];
	var t=this;
	this.createEvent = function(event) {
		if (this.events.length == 1) section.content.innerHTML = ""; // first one, remove the 'None'
		var container = document.createElement("DIV");
		section.content.appendChild(container);
		var div = document.createElement("DIV");
		container.appendChild(div);
		div.innerHTML = "<b>When ?</b> ";
		var date = document.createElement(can_edit ? "A" : "SPAN");
		div.appendChild(date);
		if (can_edit) {
			var updateText = function() {
				if (!event.start) date.innerHTML = "Not set";
				else {
					var d = new Date();
					d.setFullYear(event.start.getUTCFullYear());
					d.setMonth(event.start.getUTCMonth());
					d.setDate(event.start.getUTCDate());
					date.innerHTML = getDateString(d);
				}
			};
			updateText();
			date.href = '#';
			date.className = "black_link";
			date.onclick = function() {
				require(["date_picker.js","context_menu.js"],function() {
					var menu = new context_menu();
					new date_picker(event.start,null,null,function(picker){
						picker.onchange = function(picker, date) {
							event.start = new Date();
							event.start.setUTCHours(0,0,0,0);
							event.start.setUTCFullYear(date.getFullYear());
							event.start.setUTCMonth(date.getMonth());
							event.start.setUTCDate(date.getDate());
							updateText();
						};
						picker.getElement().style.border = 'none';
						menu.addItem(picker.getElement());
						picker.getElement().onclick = null;
						menu.element.className = menu.element.className+" popup_date_picker";
						menu.showBelowElement(date);
					});
				});
				return false;
			};
			var remove = document.createElement("BUTTON");
			remove.className = "flat small_icon";
			remove.style.marginLeft = "5px";
			remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
			remove.title = "Remove this visit";
			div.appendChild(remove);
			remove.onclick = function() {
				var i = t.events.indexOd(event);
				t.events.splice(i,1);
				t.whos.splice(i,1);
				section.content.removeChild(container);
			};
		} else {
			var d = new Date();
			d.setFullYear(event.start.getUTCFullYear());
			d.setMonth(event.start.getUTCMonth());
			d.setDate(event.start.getUTCDate());
			date.appendChild(document.createTextNode(getDateString(d)));
		}
		div = document.createElement("DIV");
		div.innerHTML = "<b>Who ?</b>";
		container.appendChild(div);
		var peoples = [];
		for (var i = 0; i < event.attendees.length; ++i) {
			if (event.attendees[i].organizer) continue;
			if (event.attendees[i].people) {
				for (var j = 0; j < known_peoples.length; ++j)
					if (known_peoples[j].people.id == event.attendees[i].people) {
						peoples.push(known_peoples[j]);
						break;
					}
			} else
				peoples.push(event.attendees[i].name);
		}
		var who = new who_container(container,peoples,can_edit,'si');
		if (can_edit) container.appendChild(who.createAddButton("Which Social Investigators ?"));
		this.whos.push(who);
	};
	if (events.length == 0) section.content.innerHTML = "<i>None</i>";
	for (var i = 0; i < events.length; ++i) this.createEvent(events[i]);
	if (can_edit) {
		var add_button = document.createElement("BUTTON");
		add_button.className = "flat icon";
		add_button.innerHTML = "<img src='"+theme.build_icon("/static/calendar/calendar_16.png",theme.icons_10.add)+"'/>";
		add_button.title = "Schedule a new visit";
		section.addToolRight(add_button);
		var id_counter = -1;
		add_button.onclick = function() {
			var ev = new CalendarEvent(id_counter--, 'PN', calendar_id, null, null, null, true);
			t.events.push(ev);
			t.createEvent(ev);
		};
		this.save = function(ondone) {
			var locker = lockScreen(null, "Saving Visits Schedules...");
			for (var i = 0; i < this.events.length; ++i) {
				this.events[i].attendees = [];
				for (var j = 0; j < this.whos[i].peoples.length; ++j) {
					if (typeof this.whos[i].peoples[j] == 'string') {
						this.events[i].attendees.push(new CalendarEventAttendee(this.whos[i].peoples[j]));
					} else {
						this.events[i].attendees.push(new CalendarEventAttendee(null, null, null, null, null, null, this.whos[i].peoples[j].people.id));
					}
				}
			}
			service.json("selection","si/save_visits",{applicant:applicant_id,visits:this.events},function(res) {
				pnapplication.dataSaved('who');
				if (res && res.length > 0)
					for (var i = 0; i < res.length; ++i)
						for (var j = 0; j < t.events.length; ++j)
							if (t.events[j].id == res[i].given_id) { t.events[j].id = res[i].new_id; break; }
				unlockScreen(locker);
				ondone();
			});
		};
	}
}
