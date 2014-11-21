/* #depends[/static/calendar/calendar.js] */

function load_google_calendars(ondone, feedback_handler) {
	var calendarApiReady = function() {
		if (feedback_handler) feedback_handler("Loading your Google Calendars...");
		var req = window.top.gapi.client.calendar.calendarList.list();
		req.execute(function(resp){
			var calendars = [];
			if (resp.items)
			for (var i = 0; i < resp.items.length; ++i) {
				// check if this is a calendar from us
				if (resp.items[i].description == "Students Management Software Calendar") continue;
				// accessRole=reader,owner,writer
				var write = resp.items[i].accessRole == "owner" || resp.items[i].accessRole == "writer"; 
				var cal = new GoogleCalendar(resp.items[i].id, resp.items[i].summary, resp.items[i].backgroundColor.substring(1), resp.items[i].selected, write);
				calendars.push(cal);
			}
			try { ondone(calendars); } catch (e) {} // in case the page requesting it already disappear
		});
	};
	var googleConnected = function() {
		if (window.top.gapi.client.calendar) { calendarApiReady(); return; }
		var load_calendar_api = function() {
			if (feedback_handler) feedback_handler("Connecting to Google Calendar...");
			window.top.gapi.client.load('calendar','v3',function(){
				if (window.top.gapi.client.calendar) { calendarApiReady(); return; }
				if (feedback_handler) feedback_handler("Connected to Google, but cannot connect to Google Calendar");
				ondone(null);
				setTimeout(load_calendar_api, 10000);
			});
		};
		load_calendar_api();
	};
	var googleApiReady = function() {
		if (window.top.google.connection_status == 1) { googleConnected(); return; };
		var listener = function() {
			switch (window.top.google.connection_status) {
			case -1: if (feedback_handler) feedback_handler("Not connected to Google"); ondone(null); break;
			case 0: if (feedback_handler) feedback_handler("Connecting to Google..."); break;
			case 1:
				window.top.google.connection_event.remove_listener(listener);
				googleConnected();
				break;
			}
		};
		window.top.google.connection_event.add_listener(listener);
		listener();
	};
	var googleApiNotReady = function() {
		if (window.top.google) { googleApiReady(); return; }
		if (feedback_handler) feedback_handler("Loading...");
		window.top.addJavascript("/static/google/google.js",function() {
			googleApiReady();
		});
	};
	googleApiNotReady();
}

if (typeof require != 'undefined') require("calendar_objects.js");

function GoogleCalendar(id, name, color, show, writable) {
	Calendar.call(this, window.top.google_calendars_provider, name, color, show, null);
	this.id = id;
	this._refresh = function(ondone) {
		var t=this;
		var google_events = [];
		var next_page = function(token) {
			var data = {calendarId:t.id,maxResults:2500,singleEvents:false};
			if (token) data.pageToken = token;
			var req = window.top.gapi.client.calendar.events.list(data);
			req.execute(function(resp){
				if (resp && resp.items) {
					for (var i = 0; i < resp.items.length; ++i)
						google_events.push(resp.items[i]);
					if (resp.nextPageToken) {
						next_page(resp.nextPageToken);
						return;
					}
				} else {
					// bug
					google_events = null;
					t = null;
					ondone();
					return;
				}
				
				var removed_events = t.events;
				t.events = [];
				for (var i = 0; i < google_events.length; ++i) {
					var gev = google_events[i];
					var ev = new CalendarEvent(-1, 'Google', t.id, gev.iCalUID, null, null, false, gev.last_modified, gev.summary, gev.description ? gev.description : "", "", "", "", "");
					if (gev.location) ev.location_freetext = gev.location;
					if (gev.start && gev.start.date) {
						ev.start = new Date();
						var d = gev.start.date.split("-");
						ev.start.setFullYear(parseInt(d[0]));
						ev.start.setMonth(parseInt(d[1])-1);
						ev.start.setDate(parseInt(d[2]));
						ev.all_day = true;
					}
					if (gev.start && gev.start.dateTime) {
						ev.start = parseGoogleDateTime(gev.start.dateTime);
					}
					if (gev.end && gev.end.date) {
						ev.end = new Date();
						var d = gev.end.date.split("-");
						ev.end.setFullYear(parseInt(d[0]));
						ev.end.setMonth(parseInt(d[1])-1);
						ev.end.setDate(parseInt(d[2]));
						ev.end.setTime(ev.end.getTime()-24*60*60*1000);
						ev.all_day = true;
					}
					if (gev.end && gev.end.dateTime) {
						ev.end = parseGoogleDateTime(gev.end.dateTime);
					}
					if (gev.recurrence) {
						for (var j = 0; j < gev.recurrence.length; ++j) {
							if (gev.recurrence[j].substring(0,6) == "RRULE:") {
								var list = gev.recurrence[j].substring(6).split(";");
								var params = {};
								for (var k = 0; k < list.length; ++k) {
									var l = list[k].indexOf('=');
									if (l < 0) continue;
									params[list[k].substring(0,l)] = list[k].substring(l+1);
								}
								ev.frequency = new CalendarEventFrequency(
									params.FREQ,
									params.UNTIL ? parseRRuleDate(params.UNTIL) : null,
									params.COUNT ? parseInt(params.COUNT) : null,
									params.INTERVAL ? parseInt(params.INTERVAL) : null,
									params.BYMONTH,
									params.BYWEEKNO,
									params.BYYEARDAY,
									params.BYMONTHDAY,
									params.BYDAY,
									params.BYHOUR,
									params.BYSETPOS,
									params.WKST
								);
								break;
							}
						}
					}
					var err = null;
					if (!ev.start) err = "No start date";
					else if (isNaN(ev.start.getDay())) err = "Invalid start date";
					if (!ev.end) err = "No end date";
					else if (isNaN(ev.end.getDay())) err = "Invalid end date";
					if (err) {
						var s = "Error parsing Google event from calendar "+t.name+":\r\n";
						s += err+"\r\n";
						s += "Details of the event returned from Google:\r\n";
						s += debug_object_to_string(gev);
						for (var n in ev) ev[n] = null;
						ev = null;
						continue;
					}
					var found = false;
					for (var j = 0; j < removed_events.length; ++j) {
						if (ev.uid == removed_events[j].uid) {
							found = true;
							if (ev.last_modified != removed_events[j].last_modified) {
								t.events.push(ev);
								t.on_event_updated.fire(ev);
								for (var n in removed_events[j])
									removed_events[j][n] = null;
							} else {
								t.events.push(removed_events[j]);
							}
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
				removed_events = null;
				google_events = null;
				t = null;
				ondone();
			});
		};
		require("calendar_objects.js",function(){
			next_page(null);
		});
	};
	if (writable) {
		this.saveEvent = function(event) {
			// TODO
		};
	}
}
GoogleCalendar.prototype = new Calendar();
GoogleCalendar.prototype.constructor = GoogleCalendar;

function parseGoogleDateTime(d) {
	var googleDate = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(\.(\d{3}))?([+-]\d{2}):(\d{2})$/;
    var m = googleDate.exec(d);
    if (m == null) {
    	googleDate = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(\.(\d{3}))?Z$/;
    	m = googleDate.exec(d);
    	if (m == null)
    		alert(d);
    }
    var year   = +m[1];
    var month  = +m[2];
    var day    = +m[3];
    var hour   = +m[4];
    var minute = +m[5];
    var second = +m[6];
    var msec   = m[7] ? +m[7] : 0;
    var tzHour = m.length > 9 ? +m[9] : 0;
    var tzMin  = m.length > 9 ? +m[10] : 0;
    var tzOffset = new Date().getTimezoneOffset() + tzHour * 60 + tzMin;

    return new Date(year, month - 1, day, hour, minute - tzOffset, second, msec);
}
function parseRRuleDate(s) {
	var year = parseInt(s.substring(0,4));
	var month = parseInt(s.substring(4,6));
	var day = parseInt(s.substring(6,8));
	var hour = 0, minute = 0, second = 0;
	if (s.length > 8 && s.charAt(8) == 'T') {
		hour = parseInt(s.substring(9,11));
		minute = parseInt(s.substring(11,13));
		second = parseInt(s.substring(13,15));
	}
    return new Date(year, month - 1, day, hour, minute, second, 0);
}

function GoogleCalendarsProvider() {
	CalendarsProvider.call(this,"Google");
	var t=this;
	// limit to 10 minutes to avoid reaching the maximum of 100 000 request per day
	this.minimum_time_to_autorefresh_calendar = 10*60*1000;
	this.minimum_time_to_autorefresh_calendars_list = 20*60*1000;
	this._retrieveCalendars = function(handler) {
		if (window.closing) return;
		load_google_calendars(function(calendars) {
			if (calendars == null) return;
			t.connectionStatus("");
			handler(calendars);
		}, function(feedback) {
			t.connectionStatus(feedback);
		});
	};
	this.getProviderIcon = function() {
		return '/static/google/google.png';
	};
	this.getProviderName = function() {
		return "Google Calendars";
	};
}
GoogleCalendarsProvider.prototype = new CalendarsProvider();
GoogleCalendarsProvider.prototype.constructor = GoogleCalendarsProvider;

if (!window.top.google_calendars_provider)
	window.top.CalendarsProviders.add(window.top.google_calendars_provider = new GoogleCalendarsProvider());