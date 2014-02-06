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
	
	/** Event called when an event is added to any calendar. The new event is given as parameter. */
	this.on_event_added = new Custom_Event();
	/** Event called when an event is removed from any calendar. The event is given as parameter. */
	this.on_event_removed = new Custom_Event();
	/** Event called when an event is updated in any calendar. The event is given as parameter. */
	this.on_event_updated = new Custom_Event;
	
	/** Event when a calendar is going to be refreshed */
	this.on_refresh = new Custom_Event();
	/** Event when a calendar just finished to be refreshed */
	this.on_refresh_done = new Custom_Event();

	var w = window;
	var t=this;
	window.top.pnapplication.onlogout.add_listener(function() {
		while (t.calendars.length > 0) t.removeCalendar(t.calendars[0]);
	});
	
	/**
	 * Add a calendar to manage.
	 * @param {Calendar} cal the calendar to add
	 * @returns {Calendar} the given calendar
	 */
	this.addCalendar = function(cal) {
		cal.manager = this;
		this.calendars.push(cal);
		if (!cal.last_update) cal.last_update = 0;
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
		if (cal.show) {
			for (var i = 0; i < cal.events.length; ++i)
				this.on_event_removed.fire(cal.events[i]);
		}
		for (var i = 0; i < this.calendars.length; ++i)
			if (this.calendars[i] == cal) {
				this.calendars.splice(i, 1);
				break;
			};
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
		cal.saveShow(false);
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_removed.fire(cal.events[i]);
	};
	
	/**
	 * Signal that the events of the given calendar should be displayed.
	 * @param {Calendar} cal the calendar to show
	 */
	this.showCalendar = function(cal) {
		if (cal.show) return;
		cal.show = true;
		cal.saveShow(true);
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_added.fire(cal.events[i]);
		if (cal.last_update < new Date().getTime() - 60000)
			this.refreshCalendar(cal);
	};
	
	/**
	 * Set the color of a calendar: save the color, remove all events from view, add back all events (so the events in the view will be in the new color)
	 * @param {Calendar} cal the calendar
	 * @param {String} color the new color
	 */
	this.setCalendarColor = function(cal, color) {
		if (cal.color == color) return;
		cal.color = color;
		cal.saveColor(color);
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_removed.fire(cal.events[i]);
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_added.fire(cal.events[i]);
	};
	
	/**
	 * Request to update the given calendar.
	 * @param {Calendar} cal the calendar to refresh
	 * @param {Function} ondone called when the calendar is updated. Note that it is not called in case the calendar is already in process of refreshing.
	 */
	this.refreshCalendar = function(cal,ondone) {
		if (!w.theme) return; // our window does not exist anymore
		if (cal.updating) return; // already in progress
		cal.updating = true;
		cal.onrefresh.fire();
		this.on_refresh.fire(cal);
		var t=this;
		cal.refresh(function() {
			cal.last_update = new Date().getTime();
			cal.updating = false;
			if (ondone) ondone();
			cal.onrefreshdone.fire();
			t.on_refresh_done.fire(cal);
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
 * Abstract class of a calendars provider
 */
function CalendarsProvider() {
}
CalendarsProvider.prototype = {
	/**
	 * Retrieve the list of calendars for the current user
	 * @param {Function} handler called with the list of Calendar
	 * @param {Function} feedback_handler called with a message as parameter, at each step, in order to provide a feedback to the user while retrieving the list of calendars
	 */
	getCalendars: function(handler, feedback_handler) {
		
	},
	/**
	 * Icon (16x16) of the provider
	 * @returns {String} the url of the icon
	 */
	getProviderIcon: function() {
		
	},
	/**
	 * Name of the provider
	 * @returns {String} name of the provider
	 */
	getProviderName: function() {
		
	},
	/**
	 * Indicates if the provider supports the creation of calendars
	 * @returns {Boolean} true if the user can create calendars using this provider
	 */
	canCreateCalendar: function () { return false; },
	/**
	 * Indicates if the provider supports specifying a color when creating a calendar
	 * @returns {Boolean} true if the functionality is supported
	 */
	canCreateCalendarWithColor: function() { return false; },
	/**
	 * Indicates if the provider supports specifying an icon when creating a calendar
	 * @returns {Boolean} true if the functionality is supported
	 */
	canCreateCalendarWithIcon: function() { return false; },
	/**
	 * Create a calendar
	 * @param {String} name name of the calendar to create
	 * @param {String} color color of the calendar to create, or null
	 * @param {String} icon icon of the calendar to create, or null
	 * @param {Function} oncreate called with the calendar's id when the creation succeed
	 */
	createCalendar: function(name, color, icon, oncreate) {}
};
if (!window.top.CalendarsProviders) {
	/**
	 * Handle the list of existing providers of calendars (PN, Google...)
	 */
	window.top.CalendarsProviders = {
		/**
		 * Add a calendar provider 
		 * @param {CalendarsProvider} provider the new calendar provider
		 */
		add: function(provider) {
			for (var i = 0; i < this._providers.length; ++i)
				if (this._providers[i].getProviderName() == provider.getProviderName())
					return; // same
			this._providers.push(provider);
			for (var i = 0; i < this._handlers.length; ++i)
				this._handlers[i](provider);
		},
		/** List of providers */
		_providers: [],
		/** List of handlers to call when a new provider is available */
		_handlers: [],
		/** Gives a function to be called for each available provider first, then each time a new provider becomes available
		 * @param {Function} handler_for_each_provider function which will be called, with a CalendarProvider as parameter
		 */
		get: function(handler_for_each_provider) {
			for (var i = 0; i < this._providers.length; ++i)
				handler_for_each_provider(this._providers[i]);
			this._handlers.push(handler_for_each_provider);
		}
	};
}

/**
 * Abstract class of a calendar.
 * @param {CalendarsProvider} provider providing this calendar
 * @param {String} name name of the calendar
 * @param {String} color hexadecimal RGB color or null for a default one. ex: C0C0FF
 * @param {Boolean} show indicates if the events of the calendar should be displayed or not
 * @param {String} icon URL
 */
function Calendar(provider, name, color, show, icon) {
	if (!color) color = "A0A0FF";
	/** {CalendarManager} filled when added to a calendar manager */
	this.manager = null;
	this.provider = provider;
	/** URL of the icon for the calendar */
	this.icon = icon;
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
	 * @param {Function} ondone to be called when the refresh is done
	 */
	this.refresh = function(ondone) {
		window.top.status_manager.add_status(new window.top.StatusMessageError(null, "Calendar.refresh not implemented"));
	};
	/** {Function} function called to save an event. If it is not defined, it means the calendar is read only. This function takes the event to save as parameter. */
	this.saveEvent = null; // must be overriden if the calendar supports modifications
	/** Save the visibility of the calendar (if supported by the provider)
	 * @param {Boolean} show visibility: true to be visible, false to be hidden
	 */
	this.saveShow = function(show) {}; // to be overriden if supported
	/** Save the color of the calendar (if supported by the provider)
	 * @param {String} color the color to save
	 */
	this.saveColor = function(color) {}; // to be overriden if supported
	/** {Function} function to rename the calendar: null if not supported by the provider, else this attribute must be defined */
	this.rename = null; // must be overriden if this is supported
	var t=this;
	var ref = function(){
		if (t.manager) t.manager.refreshCalendar(t,function(){setTimeout(ref,5*60*1000);});
		else setTimeout(ref,60000);
	};
	setTimeout(ref,5*60*1000);
}

/**
 * UI Control for a calendar: controls its color, visibility, name...
 * @param {DOMNode} container where to put it
 * @param {Calendar} cal the calendar to control
 */
function CalendarControl(container, cal) {
	var t=this;
	/** Creates the control */
	this._init = function() {
		this.div = document.createElement("DIV"); container.appendChild(this.div);
		this.box = document.createElement("DIV"); this.div.appendChild(this.box);
		this.box.style.display = "inline-block";
		this.box.style.width = "10px";
		this.box.style.height = "10px";
		this.box.style.border = "1px solid #"+cal.color;
		this.box.title = "Show/Hide Calendar";
		this.box.style.cursor = "pointer";
		if (cal.show)
			this.box.style.backgroundColor = "#"+cal.color;
		this.box.onclick = function() {
			if (!cal.manager) return;
			if (cal.show) {
				cal.manager.hideCalendar(cal);
				t.box.style.backgroundColor = '';
			} else {
				cal.manager.showCalendar(cal);
				t.box.style.backgroundColor = "#"+cal.color;
			}
		};
		if (cal.icon) {
			this.icon = document.createElement("IMG");
			this.icon.style.paddingLeft = "3px";
			this.icon.style.verticalAlign = "bottom";
			this.icon.src = cal.icon;
			this.div.appendChild(this.icon);
		}
		this.name = document.createElement("SPAN"); this.div.appendChild(this.name);
		this.name.style.paddingLeft = "3px";
		this.name.innerHTML = cal.name;
		if (!cal.saveEvent) {
			var img = document.createElement("IMG");
			img.src = "/static/calendar/read_only.png";
			img.title = "Read-only: you cannot modify this calendar";
			t.div.appendChild(img);
		}
		this.menu_button = document.createElement("IMG");
		this.menu_button.className = "button";
		this.menu_button.style.padding = "0px";
		this.menu_button.src = theme.icons_10.arrow_down_context_menu;
		this.menu_button.style.verticalAlign = "bottom";
		this.menu_button.onclick = function() {
			require("context_menu.js", function() {
				var menu = new context_menu();
				menu.addIconItem(cal.show ? theme.icons_16.hide : theme.icons_16.show, cal.show ? "Hide" : "Show", function() {
					t.box.onclick();
				});
				menu.addIconItem(theme.icons_16.color, "Change color", function() {
					require(["color_choice.js","popup_window.js"], function() {
						var content = document.createElement("DIV");
						var chooser = new color_choice(content, "#"+cal.color);
						var popup = new popup_window("Change Color", theme.icons_16.color, content);
						popup.addOkCancelButtons(function() {
							var col = color_string(chooser.color).substring(1);
							cal.manager.setCalendarColor(cal, col);
							t.box.style.backgroundColor = "#"+col;
							t.box.style.border = "1px solid #"+col;
							popup.hide();
						});
						popup.show();
					});
				});
				if (cal.rename != null)
					menu.addIconItem(theme.icons_16.edit, "Rename", function() {
						input_dialog(theme.icons_16.edit,"Rename Calendar","Enter the new name",cal.name,100,function(name) {
							if (name.length == 0) return "Please enter a name";
							return null;
						},function(name) {
							if (!name) return;
							cal.rename(name,function(){
								t.name.innerHTML = name;
							});
						}, function(){});
					});
				menu.showBelowElement(t.menu_button);
			});
		};
		this.div.appendChild(this.menu_button);
		var start_refresh = function() {
			t.loading = document.createElement("IMG");
			t.loading.src = theme.icons_10.loading;
			t.div.appendChild(t.loading);
		};
		cal.onrefresh.add_listener(start_refresh);
		cal.onrefreshdone.add_listener(function(){
			if (!t.loading) return;
			t.div.removeChild(t.loading);
			t.loading = null;
		});
		if (cal.updating) start_refresh();
	};
	this._init();
}


/**
 * Implementation of Calendar, for an internal calendar (stored in database)
 * @param {PNCalendarsProvider} provider the provider
 * @param {Number} id the calendar id
 * @param {String} name the name of the calendar
 * @param {String} color the color
 * @param {Boolean} show indicates if the events should be displayed
 * @param {Boolean} writable indicates if the calendar can be modified
 */
function PNCalendar(provider, id, name, color, show, writable, icon) {
	Calendar.call(this, provider, name, color, show, icon);
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
								t.manager.on_event_updated.fire(ev);
							removed_events.splice(j,1);
							break;
						}
					}
					if (!found) {
						t.events.push(ev);
						t.manager.on_event_added.fire(ev);
					}
				}
				for (var i = 0; i < removed_events.length; ++i)
					t.manager.on_event_removed.fire(removed_events[i]);
				ondone();
			});
		});
	};
	this.saveShow = function(show) {
		service.json("calendar", "set_configuration", {calendar:id,show:show},function(res){});
	};
	this.saveColor = function(color) {
		service.json("calendar", "set_configuration", {calendar:id,color:color},function(res){});
	};
	if (writable) {
		var t = this;
		this.saveEvent = function(event) {
			service.json("calendar","save_event",{event:event},function(res){
				if (!event.uid && res && res.uid) {
					event.uid = res.uid;
					event.id = res.id;
					t.events.push(event);
					t.manager.on_event_added.fire(event);
				} else if (event.uid && res) {
					for (var i = 0; i < cal.events.length; ++i)
						if (t.events[i].uid == event.uid) {
							t.events.splice(i,1,event);
							t.manager.on_event_updated.fire(event);
							break;
						}
				}
			});
		};
		this.rename = function(name,ondone) {
			service.json("calendar","rename_calendar",{id:t.id,name:name},function(res){
				if (!res) return;
				t.name = name;
				if (ondone) ondone();
			});
		};
	}
}
PNCalendar.prototype = new Calendar();
PNCalendar.prototype.constructor = PNCalendar;

/** Implementation of CalendarsProvider for internal(PN) calendar (stored in the database) */
function PNCalendarsProvider() {
	var t=this;
	this.getCalendars = function(handler, feedback_handler) {
		if (feedback_handler) feedback_handler("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Loading PN Calendars...");
		service.json("calendar", "get_my_calendars", {}, function(calendars) {
			if (!calendars) return;
			var list = [];
			for (var i = 0; i < calendars.length; ++i)
				list.push(new PNCalendar(t, calendars[i].id, calendars[i].name, calendars[i].color, calendars[i].show, calendars[i].writable, calendars[i].icon));
			try { handler(list); } catch (e) {} // in case the page requesting it already disappear
		});
	};
	this.getProviderIcon = function() {
		return "/static/application/logo_16.png";
	};
	this.getProviderName = function() {
		return "PN Calendars";
	};
	this.canCreateCalendar = function() { return true; };
	this.canCreateCalendarWithColor = function() { return true; };
	this.canCreateCalendarWithIcon = function() { return true; };
	this.createCalendar = function(name, color, icon, oncreate) {
		service.json("calendar", "create_user_calendar", {name:name,color:color,icon:icon},function(res) {
			if (!res || !res.id) return;
			oncreate(new PNCalendar(t, res.id, name, color, true, true, icon));
		});
	};
}
PNCalendarsProvider.prototype = new CalendarsProvider();
PNCalendarsProvider.prototype.constructor = PNCalendarsProvider;

window.top.CalendarsProviders.add(window.top.pn_calendars_provider = new PNCalendarsProvider());