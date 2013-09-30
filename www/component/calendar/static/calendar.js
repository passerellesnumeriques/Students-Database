function CalendarManager() {
	this.calendars = [];
	this.default_calendar_index = 0;
	
	this.on_event_added = null;
	this.on_event_removed = null;
	this.on_event_updated = null;

	this.add_calendar = function(cal) {
		cal.manager = this;
		this.calendars.push(cal);
		if (cal.show)
			this.refresh_calendar(cal);
		return cal;
	};
	
	this.remove_calendar = function(cal) {
		cal.manager = null;
		if (cal.show)
			this.hide_calendar(cal);
		for (var i = 0; i < this.calendars.length; ++i)
			if (this.calendars[i] == cal) {
				this.calendars.splice(i, 1);
				break;
			}
	};
	
	this.hide_calendar = function(cal) {
		if (!cal.show) return;
		cal.show = false;
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_removed(cal.events[i]);
	};
	
	this.show_calendar = function(cal) {
		if (cal.show) return;
		cal.show = true;
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_added(cal.events[i]);
		if (cal.last_update < new Date().getTime() - 60000)
			this.refresh_calendar(cal);
	};
	
	this.refresh_calendar = function(cal,ondone) {
		if (cal.updating) return; // already in progress
		cal.updating = true;
		cal.onrefresh.fire();
		cal.refresh(this, cal, function() {
			cal.updating = false;
			if (ondone) ondone();
			cal.onrefreshdone.fire();
		});
	};
	
	this.refresh_calendars = function() {
		for (var i = 0; i < this.calendars.length; ++i)
			this.refresh_calendar(this.calendars[i]);
	};
}

function Calendar(name, color, show) {
	if (!color) color = "A0A0FF";
	this.name = name;
	this.color = color;
	this.show = show;
	this.updating = false;
	if (typeof Custom_Event != 'undefined') { // for auto-loading in wrong order
		this.onrefresh = new Custom_Event();
		this.onrefreshdone = new Custom_Event();
	}
	this.events = [];
	this.refresh = function(manager, calendar, ondone) {
		window.top.status_manager.add_status(new window.top.StatusMessageError(null, "Calendar.refresh not implemented"));
	};
	this.save_event = null; // must be overriden if the calendar supports modifications
	var t=this;
	var ref = function(){
		if (t.manager) t.manager.refresh_calendar(t,function(){setTimeout(ref,5*60*1000);});
		else setTimeout(ref,60000);
	};
	setTimeout(ref,5*60*1000);
}

function PNCalendar(id, name, color, show) {
	Calendar.call(this, name, color, show);
	this.icon = "/static/application/logo.png";
	this.id = id;
	this.refresh = function(manager, cal, ondone) {
		var t=this;
		service.json("calendar", "get", {id:t.id}, function(result) {
			if (!result) { ondone(); return; }
			var removed_events = cal.events;
			cal.events = [];
			for (var i = 0; i < result.events.length; ++i) {
				var ev = result.events[i];
				ev.calendar = cal;
				ev.start = new Date(ev.start);
				ev.end = new Date(ev.end);
				var found = false;
				for (var j = 0; j < removed_events.length; ++j) {
					if (ev.uid == removed_events[j].uid) {
						found = true;
						cal.events.push(ev);
						if (ev.last_modified != removed_events[j].last_modified)
							manager.on_event_updated(ev);
						removed_events.splice(j,1);
						break;
					}
				}
				if (!found) {
					cal.events.push(ev);
					manager.on_event_added(ev);
				}
			}
			for (var i = 0; i < removed_events.length; ++i)
				manager.on_event_removed(removed_events[i]);
			ondone();
		});
	};
}
PNCalendar.prototype = new Calendar();
PNCalendar.prototype.constructor = PNCalendar;
