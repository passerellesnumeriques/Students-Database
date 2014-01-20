// #depends[/static/calendar/calendar.js]

function load_google_calendars(calendars_manager, ondone) {
	var onload = function() {
		var req = window.top.gapi.client.calendar.calendarList.list();
		req.execute(function(resp){
			var calendars = [];
			if (resp.items)
			for (var i = 0; i < resp.items.length; ++i) {
				// accessRole=reader,owner,writer
				var write = resp.items[i].accessRole == "owner" || resp.items[i].accessRole == "writer"; 
				var cal = new GoogleCalendar(resp.items[i].id, resp.items[i].summary, resp.items[i].backgroundColor.substring(1), resp.items[i].selected, write);
				calendars_manager.addCalendar(cal);
				calendars.push(cal);
			}
			ondone(calendars);
		});
	};
	window.top.add_javascript("/static/google/google.js",function() {
		window.top.google.need_connection(function(){
			window.top.gapi.client.load('calendar','v3',function(){
				if (window.top.gapi.client.calendar) { onload(); return; }
				// try again
				window.top.gapi.client.load('calendar','v3',function(){
					if (window.top.gapi.client.calendar) { onload(); return; }
					// failed again
					ondone(null);
				});
			});
		});
	});
}

if (typeof require != 'undefined') require("calendar_objects.js");

function GoogleCalendar(id, name, color, show, writable) {
	Calendar.call(this, name, color, show);
	this.icon = '/static/google/google.png';
	this.id = id;
	this.refresh = function(ondone) {
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
					ondone();
				}
				
				var removed_events = t.events;
				t.events = [];
				for (var i = 0; i < google_events.length; ++i) {
					var gev = google_events[i];
					var ev = new CalendarEvent(-1, t.id, gev.iCalUID, null, null, false, gev.last_modified, gev.summary, gev.description ? gev.description : "", "", "", "", "");
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
						continue;
					}
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