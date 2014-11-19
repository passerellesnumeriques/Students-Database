function createEventDiv(ev, calendar) {
	if (!calendar) calendar = window.top.CalendarsProviders.getProvider(ev.calendar_provider_id).getCalendar(ev.calendar_id);
	if (!calendar) return null;
	if (ev.all_day)
		return createAllDayEventDiv(ev, calendar);
	return createTimedEventDiv(ev, calendar);
}

function _createAbstractEventDiv(ev, cal) {
	var div = document.createElement("DIV");
	div.style.backgroundColor = "#"+cal.color;
	require("color.js", function() {
		div.style.border = "1px solid "+color_string(color_darker(parse_hex_color(cal.color), 0x60));
	});
	div.style.padding = "1px";
	div.style.fontSize = '8pt';
	div.appendChild(document.createTextNode(ev.title));
	div.title = cal.name+"\r\n"+ev.title+"\r\n"+ev.description;
	div.style.cursor = "pointer";
	div.event = ev;
	div.onclick = function(e) {
		var ev = this.event;
		require("event_screen.js",function() {
			event_screen(ev.original_event, cal);
		});
		stopEventPropagation(e);
		return false;
	};
	return div;
}

function createTimedEventDiv(ev, cal) {
	var div = _createAbstractEventDiv(ev, cal);
	var head = document.createElement("DIV");
	head.style.display = "inline-block";
	setBorderRadius(head,0,0,0,0,0,0,3,3);
	head.style.fontSize = "90%";
	head.style.color = "#FFFFFF";
	require("color.js", function() {
		head.style.backgroundColor = color_string(color_darker(parse_hex_color(cal.color), 0x60));
	});
	head.style.padding = "1px";
	head.style.paddingRight = "2px";
	head.style.marginRight = "2px";
	head.style.marginLeft = "-1px";
	div.style.paddingTop = "0px";
	div.style.paddingBottom = "0px";
	var time_str = ev.start.getHours()+":"+_2digits(ev.start.getMinutes());
	time_str += "-"+ev.end.getHours()+":"+_2digits(ev.end.getMinutes());
	head.appendChild(document.createTextNode(time_str));
	div.insertBefore(head, div.childNodes[0]);
	return div;
}

function createAllDayEventDiv(ev, cal) {
	var div = _createAbstractEventDiv(ev, cal);
	
	return div;
}