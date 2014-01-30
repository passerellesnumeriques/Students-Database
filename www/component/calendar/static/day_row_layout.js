/**
 * Handle the display and layout of <i>all day</i> events: one box per day, containing boxes for each event
 * @param {CalendarManager} calendar_manager calendar manager
 */
function day_row_layout(calendar_manager) {
	/** List of events */
	this.events = [];
	
	/** Layout the given events
	 * @param {Array} events list of events
	 * @param {Array} day_boxes list of containers: one per day
	 * @param {Date} first_day first day displayed
	 */
	this.layout = function(events, day_boxes, first_day) {
		this.removeEvents();
		
		var by_day = [];
		for (var i = 0; i < day_boxes.length; ++i) by_day.push([]);
		
		for (var i = 0; i < events.length; ++i) {
			var ev = events[i];
			var day1 = Math.floor((ev.start.getTime()-first_day.getTime())/(24*60*60*1000));
			if (day1 >= day_boxes.length) continue; // after
			var day_end = Math.floor((ev.end.getTime()-first_day.getTime())/(24*60*60*1000));
			if (day_end < 0) continue; // before
			if (day1 < 0) day1 = 0;
			if (day_end >= day_boxes.length) day_end = day_boxes.length-1;
			
			var y;
			var ok = false;
			for (y = 0; !ok; ++y) {
				ok = true;
				for (var day = day1; day <= day_end; ++day)
					if (y < by_day[day].length && by_day[day][y] != null) { ok = false; break; }
			}
			--y;
			
			for (var day = day1; day <= day_end; ++day) {
				while (by_day[day].length <= y) by_day[day].push(null);
				by_day[day][y] = ev;
			}
			
			var div = document.createElement("DIV");
			div.style.position = "absolute";
			div.style.zIndex = 2;
			div.style.backgroundColor = "#"+calendar_manager.getCalendar(ev.calendar_id).color;
			require("color.js", function() {
				div.style.border = "1px solid "+color_string(color_darker(parse_hex_color(calendar_manager.getCalendar(ev.calendar_id).color), 0x60));
			});
			div.style.overflow = 'hidden';
			div.style.left = (day_boxes[day1].offsetLeft+2)+"px";
			div.style.top = (1+y*18)+"px";
			div.style.height = "13px";
			var w = 0;
			for (var day = day1; day <= day_end; ++day) w += day_boxes[day].offsetWidth;
			w -= 2+4+(day_end-day1)+4;
			div.style.width = w+"px";
			div.style.padding = "1px";
			div.style.fontSize = '8pt';
			div.innerHTML = ev.title;
			div.title = calendar_manager.getCalendar(ev.calendar_id).name+"\r\n"+ev.title+"\r\n"+ev.description;
			div.style.cursor = "pointer";
			div.event = ev;
			div.onclick = function() {
				var ev = this.event;
				require("event_screen.js",function() {
					event_screen(ev.original_event, calendar_manager.getCalendar(ev.calendar_id));
				});
			};
			day_boxes[0].parentNode.appendChild(div);
			this.events.push(div);
		}
		
		var h = 0;
		for (var i = 0; i < by_day.length; ++i)
			if (by_day[i].length*18 > h) h = by_day[i].length*18;
		h += 1;
		if (h < 10) h = 10;
		for (var i = 0; i < day_boxes.length; ++i)
			day_boxes[i].style.height = h+"px";
		return h;
	};
	
	/** Remove all events from the display */
	this.removeEvents = function() {
		for (var i = 0; i < this.events.length; ++i)
			this.events[i].parentNode.removeChild(this.events[i]);
		this.events = [];
	};
	
}