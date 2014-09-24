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
	
	/** Event called when a new calendar is added to this manager */
	this.on_calendar_added = new Custom_Event();
	/** Event called when a calendar is removed from this manager */
	this.on_calendar_removed = new Custom_Event();
	
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
	
	/** Listeners this manager registered to its calendars, stored there to unregister when removing a calendar */
	this._calendars_listeners = [];
	
	/**
	 * Add a calendar to manage.
	 * @param {Calendar} cal the calendar to add
	 * @returns {Calendar} the given calendar
	 */
	this.addCalendar = function(cal) {
		for (var i = 0; i < this.calendars.length; ++i)
			if (this.calendars[i].provider.id == cal.provider.id && this.calendars[i].id == cal.id) return this.calendars[i]; // already there
		this.calendars.push(cal);
		var t=this;
		var listeners = {calendar:cal,listeners:[
		  function() { t.on_refresh.fire(cal); },
		  function() { t.on_refresh_done.fire(cal); },
		  function(ev) { t.on_event_added.fire(ev); },
		  function(ev) { t.on_event_updated.fire(ev); },
		  function(ev) { t.on_event_removed.fire(ev); }
		]};
		this._calendars_listeners.push(listeners);
		cal.onrefresh.add_listener(listeners.listeners[0]);
		cal.onrefreshdone.add_listener(listeners.listeners[1]);
		cal.on_event_added.add_listener(listeners.listeners[2]);
		cal.on_event_updated.add_listener(listeners.listeners[3]);
		cal.on_event_removed.add_listener(listeners.listeners[4]);
		if (!cal.last_update) cal.last_update = 0;
		if (cal.show && cal.last_update < new Date().getTime() - 60000)
			cal.refresh();
		this.on_calendar_added.fire(cal);
		return cal;
	};
	
	/**
	 * Remove a calendar.
	 * @param {Calendar} cal the calendar to remove
	 */
	this.removeCalendar = function(cal) {
		for (var i = 0; i < this._calendars_listeners.length; ++i) {
			if (this._calendars_listeners[i].calendar == cal) {
				var listeners = this._calendars_listeners[i].listeners;
				cal.onrefresh.remove_listener(listeners[0]);
				cal.onrefreshdone.remove_listener(listeners[1]);
				cal.on_event_added.remove_listener(listeners[2]);
				cal.on_event_updated.remove_listener(listeners[3]);
				cal.on_event_removed.remove_listener(listeners[4]);
				this._calendars_listeners.splice(i,1);
				break;
			}
		}
		if (cal.show) {
			for (var i = 0; i < cal.events.length; ++i)
				this.on_event_removed.fire(cal.events[i]);
		}
		for (var i = 0; i < this.calendars.length; ++i)
			if (this.calendars[i] == cal) {
				this.calendars.splice(i, 1);
				break;
			};
		this.on_calendar_removed.fire(cal);
	};
	
	/**
	 * Signal that the events of the given calendar should not be displayed.
	 * @param {Calendar} cal the calendar to hide 
	 */
	this.hideCalendar = function(cal) {
		if (!cal.show) return;
		cal.show = false;
		if (cal.saveShow)
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
		if (cal.saveShow)
			cal.saveShow(true);
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_added.fire(cal.events[i]);
		if (cal.last_update < new Date().getTime() - 60000)
			cal.refresh();
	};
	
	/**
	 * Set the color of a calendar: save the color, remove all events from view, add back all events (so the events in the view will be in the new color)
	 * @param {Calendar} cal the calendar
	 * @param {String} color the new color
	 */
	this.setCalendarColor = function(cal, color) {
		if (cal.color == color) return;
		cal.color = color;
		if (cal.saveColor)
			cal.saveColor(color);
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_removed.fire(cal.events[i]);
		for (var i = 0; i < cal.events.length; ++i)
			this.on_event_added.fire(cal.events[i]);
	};
	
	/**
	 * Refresh all calendars of this CalendarManager.
	 */
	this.refreshCalendars = function() {
		for (var i = 0; i < this.calendars.length; ++i)
			this.calendars[i].refresh();
	};
}

/**
 * Abstract class of a calendars provider
 * @param {String} id the unique identifier for the provider 
 */
function CalendarsProvider(id) {
	this.id = id;
}
CalendarsProvider.prototype = {
	/** {Array} list of Calendar owned by this provider */
	calendars: [],
	/** {Custom_Event} event raised when a new calendar appears on this provider */
	on_calendar_added: new Custom_Event(),
	/** {Custom_Event} event raised when a calendar disappears on this provider */
	on_calendar_removed: new Custom_Event(),
	/** {Number} minimum time (in milliseconds) before calendars from this provider can be automatically refreshed */
	minimum_time_to_autorefresh: 5*60*1000,
	/** Reload the list of calendars from this provider */
	refreshCalendars: function() {
		var t=this;
		this._retrieveCalendars(function (list) {
			var removed = [];
			for (var i = 0; i < t.calendars.length; ++i) removed.push(t.calendars[i]);
			t.calendars = list;
			for (var i = 0; i < list.length; ++i) {
				var found = false;
				for (var j = 0; j < removed.length; ++j) {
					if (removed[j].id == list[i].id) {
						found = true;
						removed.splice(j,1);
						break;
					}
				}
				if (!found) t.on_calendar_added.fire(list[i]);
			}
			for (var i = 0; i < removed.length; ++i) {
				t.on_calendar_removed.fire(removed[i]);
				removed[i].cleanup();
			}
		});
	},
	/** Function to be overriden by the implementation, to load the list of calendars on this provider
	 * @param {Function} handler called when the list is ready, the list of calendars is given as parameter
	 */
	_retrieveCalendars: function(handler) { },
	/** Retrieve a calendar by id on this provider
	 * @param {String} id the identifier of the calendar to retrieve
	 * @returns {Calendar} the calendar, or null if it does not exist
	 */
	getCalendar: function(id) {
		for (var i = 0; i < this.calendars.length; ++i)
			if (this.calendars[i].id == id) return this.calendars[i];
		return null;
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
	/** Indicates a status about the connection to the provider, or empty if it is connected */
	connection_status: "",
	/** Event called when the connection_status changed */
	on_connection_status: new Custom_Event(),
	/** Update the connection_status
	 * @param {String} status the new status (empty string if it is already connected)
	 */
	connectionStatus: function(status) {
		this.connection_status = status;
		this.on_connection_status.fire(status);
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
if (window == window.top && !window.top.CalendarsProviders) {
	/**
	 * Handle the list of existing providers of calendars (PN, Google...)
	 */
	window.top.CalendarsProviders = {
		/**
		 * Add a calendar provider 
		 * @param {CalendarsProvider} provider the new calendar provider
		 */
		add: function(provider) {
			provider._last_auto_refresh = new Date().getTime();
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
		},
		/** Retrieve a calendar provider by id
		 * @param {String} id identifier of the provider
		 * @returns {CalendarsProvider} the provider, or null if it does not exist
		 */
		getProvider: function(id) {
			for (var i = 0; i < this._providers.length; ++i)
				if (this._providers[i].id == id) return this._providers[i];
			return null;
		},
		/** Internal function to refresh the list of calendars on all providers */
		_refresh: function(force) {
			var now = new Date().getTime();
			for (var i = 0; i < this._providers.length; ++i) {
				if (!force && now - this._providers[i]._last_auto_refresh < this._providers[i].minimum_time_to_autorefresh) continue;
				for (var j = 0; j < this._providers[i].calendars.length; ++j)
					this._providers[i].calendars[j].refresh();
			}
		}
	};
	var listen_login_logout = function() {
		if (!window.top.pnapplication) {
			setTimeout(listen_login_logout, 20);
		}
		window.top.pnapplication.onlogout.add_listener(function() {
			for (var i = 0; i < window.top.CalendarsProviders._providers.length; ++i) {
				var prev_calendars = window.top.CalendarsProviders._providers[i].calendars;
				window.top.CalendarsProviders._providers[i].calendars = [];
				for (var j = 0; j < prev_calendars.length; ++j) {
					window.top.CalendarsProviders._providers[i].on_calendar_removed.fire(prev_calendars[j]);
					prev_calendars[j].cleanup();
				}
			}
		});
		window.top.pnapplication.onlogin.add_listener(function() {
			window.top.CalendarsProviders._refresh(true);
		});
	};
	listen_login_logout();
	setTimeout(function() { window.top.CalendarsProviders._refresh(); }, 60*1000);
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
	/** Event called when a new event appear on this calendar */
	this.on_event_added = new Custom_Event();
	/** Event called when an event was updated on this calendar */
	this.on_event_updated = new Custom_Event();
	/** Event called when an event disappear from this calendar */
	this.on_event_removed = new Custom_Event();
	/** list of events in the calendar */
	this.events = [];
	/** called to refresh the calendar. It must be overrided by the implementation of the calendar.
	 * @param {Function} ondone to be called when the refresh is done
	 */
	this.refresh = function(ondone) {
		if (this.updating) return; // already in progress
		this.updating = true;
		this.onrefresh.fire();
		var t=this;
		this._refresh(function() {
			t.last_update = new Date().getTime();
			t.updating = false;
			if (ondone) ondone();
			t.onrefreshdone.fire();
		});
		
	};
	/** called to refresh the calendar. It must be overrided by the implementation of the calendar.
	 * @param {Function} ondone to be called when the refresh is done
	 */
	this._refresh = function(ondone) {
		var type = getObjectClassName(this);
		window.top.status_manager.add_status(new window.top.StatusMessageError(null, "Calendar._refresh not implemented: "+type));
		if (ondone) ondone();
	};
	/** {Function} function called to save an event. If it is not defined, it means the calendar is read only. This function takes the event to save as parameter. */
	this.saveEvent = null; // must be overriden if the calendar supports modifications
	/** Save the visibility of the calendar (if supported by the provider)
	 * @param {Boolean} show visibility: true to be visible, false to be hidden
	 */
	this.saveShow = null; // function(show) {}; to be defined if supported
	/** Save the color of the calendar (if supported by the provider)
	 * @param {String} color the color to save
	 */
	this.saveColor = null; // function(color) {}; to be defined if supported
	/** {Function} function to rename the calendar: null if not supported by the provider, else this attribute must be defined */
	this.rename = null; // must be overriden if this is supported
	
	this.cleanup = function() {
		for (var i = 0; i < this.events.length; ++i)
			for (var n in this.events[i])
				this.events[i][n] = null;
		this.events = null;
		this.provider = null;
		t = null;
	};
}

/**
 * UI Control for a calendar: controls its color, visibility, name...
 * @param {Element} container where to put it
 * @param {Calendar} cal the calendar to control
 */
function CalendarControl(container, cal, manager) {
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
			if (cal.show) {
				manager.hideCalendar(cal);
				t.box.style.backgroundColor = '';
			} else {
				manager.showCalendar(cal);
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
							manager.setCalendarColor(cal, col);
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
			if (window.closing) return;
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
 * @param {String} icon icon of the calendar
 */
function PNCalendar(provider, id, name, color, show, writable, icon) {
	Calendar.call(this, provider, name, color, show, icon);
	/** Id of this PN Calendar */
	this.id = id;
	this._refresh = function(ondone) {
		var t=this;
		require("calendar_objects.js", function(){
			service.json("calendar", "get", {id:t.id}, function(result) {
				if (!result) { ondone(); return; }
				try {
					var removed_events = t.events;
					t.events = [];
					for (var i = 0; i < result.length; ++i) {
						var ev = result[i];
						var found = false;
						for (var j = 0; j < removed_events.length; ++j) {
							if (ev.uid == removed_events[j].uid) {
								found = true;
								t.events.push(ev);
								if (ev.last_modified != removed_events[j].last_modified)
									t.on_event_updated.fire(ev);
								for (var n in removed_events[j])
									removed_events[j][n] = null;
								removed_events.splice(j,1);
								break;
							}
						}
						if (!found) {
							t.events.push(ev);
							t.on_event_added.fire(ev);
						}
					}
					for (var i = 0; i < removed_events.length; ++i) {
						t.on_event_removed.fire(removed_events[i]);
						for (var n in removed_events[i])
							removed_events[i][n] = null;
					}
				} catch (e) {
					log_exception(e, "Error while refreshing PN calendar");
				}
				ondone();
			});
		});
	};
	if (id > 0)
		this.saveShow = function(show) {
			service.json("calendar", "set_configuration", {calendar:id,show:show},function(res){});
		};
	if (id > 0)
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
					t.on_event_added.fire(event);
				} else if (event.uid && res) {
					for (var i = 0; i < t.events.length; ++i)
						if (t.events[i].uid == event.uid) {
							t.events.splice(i,1,event);
							t.on_event_updated.fire(event);
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
	CalendarsProvider.call(this,"PN");
	var t=this;
	this.minimum_time_to_autorefresh = 2*60*1000; // we are in local, we can refresh regularly
	this._retrieveCalendars = function(handler) {
		t.connectionStatus("<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Loading PN Calendars...");
		service.json("calendar", "get_my_calendars", {}, function(calendars) {
			t.connectionStatus("");
			if (!calendars) return;
			if (!PNCalendar) return;
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

if (!window.top.pn_calendars_provider)
	window.top.CalendarsProviders.add(window.top.pn_calendars_provider = new PNCalendarsProvider());