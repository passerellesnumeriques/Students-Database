/** unknown participation */
window.calendar_event_participation_unknown = "UNKNOWN";
/** participates */
window.calendar_event_participation_yes = "YES";
/** does not participate */
window.calendar_event_participation_no = "NO";
/** tentatively */
window.calendar_event_participation_tentative = "TENTATIVE";
/** delegates its participation */
window.calendar_event_participation_delegate = "DELEGATE";

/** participant is requested */
window.calendar_event_role_requested = "REQUESTED";
/** participant is optional */
window.calendar_event_role_optional = "OPTIONAL";
/** for information only, but participant does not need to participate */
window.calendar_event_role_for_info = "FOR_INFO";

/**
 * Object representing a calendar event, used between front-end and back-end
 * @param {Number} id internal id of the event (unique in the calendar, but not with other calendars)
 * @param {String} calendar_provider_id id of the calendar provider
 * @param {Number} calendar_id id of the calendar this event belongs to
 * @param {String} uid id of the event which is unique among all calendars
 * @param {Date} start timestamp in seconds of the start of the event
 * @param {Date} end timestamp in seconds of the end of the event
 * @param {Boolean} all_day indicates if the event is an <i>all day</i> event (meaning no specific time)
 * @param {Number} last_modified timestamp in seconds when this event has been modified for the last time
 * @param {String} title title of the event
 * @param {String} description text giving more details about the event
 * @param {String} location_freetext where this event occurs
 * @param {String} organizer person organizing the event
 * @param {String} participation participation of the organizer
 * @param {String} role role of the organizer
 * @param {CalendarEventFrequency} frequency recurrence
 * @param {String} app_link url
 * @param {String} app_link_name name to display
 */
function CalendarEvent(id, calendar_provider_id, calendar_id, uid, start, end, all_day, last_modified, title, description, location_freetext, organizer, participation, role, frequency, app_link, app_link_name) {
	this.id = id;
	this.calendar_provider_id = calendar_provider_id;
	this.calendar_id = calendar_id;
	this.uid = uid;
	this.all_day = all_day;
	/** {Date} start timestamp in seconds of the start of the event */
	this.start = typeof start == 'number' ? new Date(start*1000) : typeof start == 'string' ? new Date(parseInt(start)*1000) : start;
	/** {Date} end timestamp in seconds of the end of the event */
	this.end = typeof end == 'number' ? new Date(end*1000) : typeof end == 'string' ? new Date(parseInt(end)*1000) : end;
	this.last_modified = last_modified;
	this.title = title;
	this.description = description;
	this.location_freetext = location_freetext;
	this.organizer = organizer;
	this.participation = participation;
	this.role = role;
	this.frequency = frequency;
	this.app_link = app_link;
	this.app_link_name = app_link_name;
}
/**
 * Create a copy of this event
 * @param {CalendarEvent} ev the event to copy
 * @returns {CalendarEvent} the copy
 */
function copyCalendarEvent(ev) {
	if (ev == null) return null;
	return new CalendarEvent(
		ev.id,
		ev.calendar_provider_id,
		ev.calendar_id,
		ev.uid,
		new Date(ev.start.getTime()),
		new Date(ev.end.getTime()),
		ev.all_day,
		ev.last_modified,
		ev.title,
		ev.description,
		ev.location_freetext,
		ev.organizer,
		ev.participation,
		ev.role,
		copyCalendarEventFrequency(ev.frequency),
		ev.app_link,
		ev.app_link_name
	);
};

/** daily recurrence */
window.calendar_event_frequency_daily = "DAILY";
/** weekly recurrence */
window.calendar_event_frequency_weekly = "WEEKLY";
/** monthly recurrence */
window.calendar_event_frequency_monthly = "MONTHLY";
/** yearly recurrence */
window.calendar_event_frequency_yearly = "YEARLY";

/**
 * Recurrence information of a CalendarEvent
 * @param {String} frequency frequency of the event (DAILY, WEEKLY, MONTHLY or YEARLY)
 * @param {Date} until last date of the recurrence, or null
 * @param {Number} count number of recurrences, or null
 * @param {Number} interval interval of the recurrence
 * @param {String} by_month list (separated by comma) of month number
 * @param {String} by_week_no list (separated by comma) of week number
 * @param {String} by_year_day list (separated by comma) of year day
 * @param {String} by_month_day list (separated by comma) of month day
 * @param {String} by_week_day list (separated by comma) of week day that is an optional number followed by a 2-letter day. Example: SU means every sunday, 2SU means the second Sunday of the month, -1SU means the last Sunday of the month...
 * @param {String} by_hour list (separated by comma) of hour
 * @param {String} by_setpos list (separated by comma) of number, each representing an index of the occurence. Example: 1,3,5 means the 1st, 3rd and 5th occurences
 * @param {String} week_start 2-letter of the first day of the week
 */
function CalendarEventFrequency(frequency, until, count, interval, by_month, by_week_no, by_year_day, by_month_day, by_week_day, by_hour, by_setpos, week_start) {
	this.frequency = frequency;
	this.until = until;
	this.count = count;
	this.interval = interval;
	this.by_month = by_month;
	this.by_week_no = by_week_no;
	this.by_year_day = by_year_day;
	this.by_month_day = by_month_day;
	this.by_week_day = by_week_day;
	this.by_hour = by_hour;
	this.by_setpos = by_setpos;
	this.week_start = week_start;
}

/**
 * Create a copy of a CalendarEventFrequency object
 * @param {CalendarEventFrequency} f the object to copy 
 * @returns {CalendarEventFrequency} the copy
 */
function copyCalendarEventFrequency(f) {
	if (f == null) return null;
	return new CalendarEventFrequency(
		f.frequency,
		f.until,
		f.count,
		f.interval,
		f.by_month,
		f.by_week_no,
		f.by_year_day,
		f.by_month_day,
		f.by_week_day,
		f.by_hour,
		f.by_setpos,
		f.week_start
	);
}
