function CalendarManager() {
	this.calendars = [];
	
	this.onloading = null;
	this.onloaded = null;
	
	this.onaction = null;
	this.onaction_done = null;
	
	this.on_event_added = null;
	this.on_event_removed = null;
	this.on_event_updated = null;

	this.add_calendar = function(name, color, show, refresher) {
		var cal = new Calendar(name, color, refresher);
		cal.show = show;
		this.calendars.push(cal);
		if (show)
			this.refresh_calendar(cal);
		return cal;
	};
	
	this.remove_calendar = function(cal) {
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
	
	this.refresh_calendar = function(cal) {
		if (cal.updating) return; // already in progress
		cal.updating = true;
		if (this.onloading) this.onloading(cal);
		var t=this;
		cal.refresher.refresh(this, cal, function() {
			cal.updating = false;
			if (t.onloaded) t.onloaded(cal);
		});
	};
	
	this.refresh_calendars = function() {
		for (var i = 0; i < this.calendars.length; ++i)
			this.refresh_calendar(this.calendars[i]);
	};
}

function Calendar(name, color, refresher) {
	if (!color) color = "A0A0FF";
	this.name = name;
	this.color = color;
	this.refresher = refresher;
	this.updating = false;
	this.events = [];
}

function PNCalendarRefresher(id) {
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
