// #depends[/static/javascript/utils.js]
if (typeof require != 'undefined') require("calendar_objects.js");
/**
 * Manage a list of calendars.
 */
function CalendarManager() {
	/** List of calendars */
	this.calendars = [];
	/** Index of the calendar used by default to create new events */
	this.default_calendar_index = 0;
	
	/** listener called when an event is added to any calendar. The new event is given as parameter. */
	this.on_event_added = null;
	/** listener called when an event is removed from any calendar. The event is given as parameter. */
	this.on_event_removed = null;
	/** listener called when an event is updated in any calendar. The event is given as parameter. */
	this.on_event_updated = null;

	/**
	 * Add a calendar to manage.
	 * @param {Calendar} cal the calendar to add
	 * @returns {Calendar} the given calendar
	 */
	this.addCalendar = function(cal) {
		cal.manager = this;
		this.calendars.push(cal);
		if (cal.show)
			this.refreshCalendar(cal);
		return cal;
	};
	
	/**
	 * Remove a calendar.
	 * @param {Calendar} cal the calendar to remove
	 */
	this.removeCalendar = function(cal) {
		cal.manager = null;
		if (cal.show)
			this.hideCalendar(cal);
		for (var i = 0; i < this.calendars.length; ++i)
			if (this.calendars[i] == cal) {
				this.calendars.splice(i, 1);
				break;
			}
	};
	
	/**
	 * Get the calendar having the given id
	 * @param {Number} id calendar id
	 * @returns {Calendar} the calendar 
	 */
	this.getCalendar = function(id) {
		for (var i = 0; i < this.calendars.length; ++i)
			if (this.calendars[i].id == id) return this.calendars[i];
		return null;
	};
	
	/**
	 * Signal that the events of the given calendar should not be displayed.
	 * @param {Calendar} cal the calendar to hide 
	 */
	this.hideCalendar = function(cal) {
		if (!cal.show) return;
		cal.show = false;
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_removed(cal.events[i]);
	};
	
	/**
	 * Signal that the events of the given calendar should be displayed.
	 * @param {Calendar} cal the calendar to show
	 */
	this.showCalendar = function(cal) {
		if (cal.show) return;
		cal.show = true;
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_added(cal.events[i]);
		if (cal.last_update < new Date().getTime() - 60000)
			this.refreshCalendar(cal);
	};
	
	/**
	 * Request to update the given calendar.
	 * @param {Calendar} cal the calendar to refresh
	 * @param {Function} ondone called when the calendar is updated. Note that it is not called in case the calendar is already in process of refreshing.
	 */
	this.refreshCalendar = function(cal,ondone) {
		if (cal.updating) return; // already in progress
		cal.updating = true;
		cal.onrefresh.fire();
		cal.refresh(function() {
			cal.updating = false;
			if (ondone) ondone();
			cal.onrefreshdone.fire();
		});
	};
	
	/**
	 * Refresh all calendars of this CalendarManager.
	 */
	this.refreshCalendars = function() {
		for (var i = 0; i < this.calendars.length; ++i)
			this.refreshCalendar(this.calendars[i]);
	};
}

/**
 * Abstract class of a calendar.
 * @param {String} name name of the calendar
 * @param {String} color hexadecimal RGB color or null for a default one. ex: C0C0FF
 * @param {Boolean} show indicates if the events of the calendar should be displayed or not
 */
function Calendar(name, color, show) {
	if (!color) color = "A0A0FF";
	/** {CalendarManager} filled when added to a calendar manager */
	this.manager = null;
	/** URL of the icon for the type of calendar. Must be set by the implementation */
	this.icon = null;
	/** name of the calendar */
	this.name = name;
	/** hexadecimal RGB color or null for a default one. ex: C0C0FF */
	this.color = color;
	/** indicates if the events of the calendar should be displayed or not */
	this.show = show;
	/** indicates if the calendar is currently updating its events */
	this.updating = false;
	/** event called when the calendar is going to be refreshed (just before) */
	this.onrefresh = new Custom_Event();
	/** event called when the calendar has been refreshed */
	this.onrefreshdone = new Custom_Event();
	/** list of events in the calendar */
	this.events = [];
	/** called to refresh the calendar. It must be overrided by the implementation of the calendar.
	 * @param {CalendarManager} manager the CalendarManager calling
	 * @param {Function} ondone to be called when the refresh is done
	 */
	this.refresh = function(ondone) {
		window.top.status_manager.add_status(new window.top.StatusMessageError(null, "Calendar.refresh not implemented"));
	};
	/** {Function} function called to save an event. If it is not defined, it means the calendar is read only. This function takes the event to save as parameter. */
	this.saveEvent = null; // must be overriden if the calendar supports modifications
	var t=this;
	var ref = function(){
		if (t.manager) t.manager.refreshCalendar(t,function(){setTimeout(ref,5*60*1000);});
		else setTimeout(ref,60000);
	};
	setTimeout(ref,5*60*1000);
}

/**
 * Implementation of Calendar, for an internal calendar (stored in database)
 * @param {Number} id the calendar id
 * @param {String} name the name of the calendar
 * @param {String} color the color
 * @param {Boolean} show indicates if the events should be displayed
 * @param {Boolean} writable indicates if the calendar can be modified
 */
function PNCalendar(id, name, color, show, writable) {
	Calendar.call(this, name, color, show);
	this.icon = "/static/application/logo.png";
	/** Id of this PN Calendar */
	this.id = id;
	this.refresh = function(ondone) {
		var t=this;
		require("calendar_objects.js", function(){
			service.json("calendar", "get", {id:t.id}, function(result) {
				if (!result) { ondone(); return; }
				var removed_events = t.events;
				t.events = [];
				for (var i = 0; i < result.length; ++i) {
					var ev = result[i];
					ev.start = new Date(parseInt(ev.start)*1000);
					ev.end = new Date(parseInt(ev.end)*1000);
					var found = false;
					for (var j = 0; j < removed_events.length; ++j) {
						if (ev.uid == removed_events[j].uid) {
							found = true;
							t.events.push(ev);
							if (ev.last_modified != removed_events[j].last_modified)
								t.manager.on_event_updated(ev);
							removed_events.splice(j,1);
							break;
						}
					}
					if (!found) {
						t.events.push(ev);
						t.manager.on_event_added(ev);
					}
				}
				for (var i = 0; i < removed_events.length; ++i)
					t.manager.on_event_removed(removed_events[i]);
				ondone();
			});
		});
	};
	if (writable) {
		var t = this;
		this.saveEvent = function(event) {
			service.json("calendar","save_event",{event:event},function(res){
				if (!event.uid && res && res.uid) {
					event.uid = res.uid;
					event.id = res.id;
					t.events.push(event);
					t.manager.on_event_added(event);
				} else if (event.uid && res) {
					for (var i = 0; i < cal.events.length; ++i)
						if (t.events[i].uid == event.uid) {
							t.events.splice(i,1,event);
							t.manager.on_event_updated(event);
							break;
						}
				}
			});
		};
	}
}
PNCalendar.prototype = new Calendar();
PNCalendar.prototype.constructor = PNCalendar;
