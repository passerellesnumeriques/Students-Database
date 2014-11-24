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
		var col = parse_hex_color(cal.color);
		if (col[0]+col[1]+col[2] < 0x60*3) div.style.color = "white"; else div.style.color = "black";
		div.style.border = "1px solid "+color_string(color_darker(col, 0x60));
	});
	div.style.padding = "1px";
	div.style.fontSize = '8pt';
	div.appendChild(document.createTextNode(ev.title));
	div.title = cal.name+"\r\n"+ev.title+(ev.description ? "\r\n"+ev.description : "");
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
	setBorderRadius(head,4,4,0,0,0,0,3,3);
	head.style.fontSize = "90%";
	head.style.color = "#FFFFFF";
	require("color.js", function() {
		head.style.backgroundColor = color_string(color_darker(parse_hex_color(cal.color), 0x60));
	});
	head.style.paddingLeft = "1px";
	head.style.paddingRight = "2px";
	head.style.marginRight = "2px";
	head.style.marginLeft = "-1px";
	head.style.verticalAlign = "top";
	div.style.paddingTop = "0px";
	div.style.paddingBottom = "0px";
	div.style.lineHeight = "10px";
	setBorderRadius(div,4,4,4,4,4,4,4,4);
	setBoxShadow(div,1,2,5,0,'#'+cal.color)
	head.appendChild(document.createTextNode(getTimeString(ev.start,true)+"-"+getTimeString(ev.end,true)));
	head.style.whiteSpace = "nowrap";
	div.insertBefore(head, div.childNodes[0]);
	return div;
}

function createAllDayEventDiv(ev, cal) {
	var div = _createAbstractEventDiv(ev, cal);
	
	return div;
}