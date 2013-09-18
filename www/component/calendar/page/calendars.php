<?php 
class page_calendars extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		echo "<div style='height:100%;width:100%' id='calendars'>";
		echo "<div id='calendars_list' style='overflow:auto'><a href='#' onclick='load_google();return false;'>Load Google</a></div>";
		echo "<div id='calendars_view'></div>";
		echo "</div>";
		$this->add_javascript("/static/widgets/splitter_vertical/splitter_vertical.js");
		$this->onload("new splitter_vertical('calendars',0.3);");
		$this->add_javascript("/static/calendar/calendar.js");
		$this->add_javascript("/static/calendar/calendar_view.js");
		$this->onload("init_calendars();");
?>
<script type='text/javascript'>
function init_calendars() {
	window.calendars = new CalendarManager();
	var list = document.getElementById("calendars_list");
<?php 
$ids = PNApplication::$instance->calendar->getAccessibleCalendars();
$calendars = SQLQuery::create()->bypass_security()->select("Calendar")->where_in("Calendar", "id", $ids)->execute();
foreach ($calendars as $cal) echo "new CalendarElement(list,window.calendars.add_calendar(".json_encode($cal["name"]).",".json_encode($cal["color"]).",true,new PNCalendarRefresher(".$cal["id"].")));";
?>
	new CalendarView(window.calendars, 'week', 'calendars_view',function(){
		setTimeout(function(){
		var ev = {
			uid:1,
			calendar: window.calendars.calendars[0],
			start: new Date(new Date().getTime()-30*60*1000),
			end: new Date(new Date().getTime()+30*60*1000),
			description: "Before"
		};
		window.calendars.calendars[0].events.push(ev);
		window.calendars.on_event_added(ev);
		ev = {
			uid:2,
			calendar: window.calendars.calendars[0],
			start: new Date(new Date().getTime()+120*60*1000),
			end: new Date(new Date().getTime()+180*60*1000),
			description: "After"
		};
		window.calendars.calendars[0].events.push(ev);
		window.calendars.on_event_added(ev);
		ev = {
			uid:3,
			calendar: window.calendars.calendars[0],
			start: new Date(new Date().getTime()-60*60*1000),
			end: new Date(new Date().getTime()+200*60*1000),
			description: "Large"
		};
		window.calendars.calendars[0].events.push(ev);
		window.calendars.on_event_added(ev);
		ev = {
			uid:4,
			calendar: window.calendars.calendars[0],
			start: new Date(new Date().getTime()+15*60*1000),
			end: new Date(new Date().getTime()+75*60*1000),
			description: "Overlap"
		};
		window.calendars.calendars[0].events.push(ev);
		window.calendars.on_event_added(ev);
		ev = {
			uid:5,
			calendar: window.calendars.calendars[0],
			start: new Date(new Date().getTime()+45*60*1000),
			end: new Date(new Date().getTime()+90*60*1000),
			description: "Inside"
		};
		window.calendars.calendars[0].events.push(ev);
		window.calendars.on_event_added(ev);
		},1000);
	});
}
function CalendarElement(container, cal) {
	this._init = function() {
		this.div = document.createElement("DIV"); container.appendChild(this.div);
		this.box = document.createElement("DIV"); this.div.appendChild(this.box);
		this.box.style.display = "inline-block";
		this.box.style.width = "10px";
		this.box.style.height = "10px";
		this.box.style.border = "1px solid #"+cal.color;
		this.box.style.backgroundColor = "#"+cal.color;
		this.name = document.createElement("SPAN"); this.div.appendChild(this.name);
		this.name.style.paddingLeft = "3px";
		this.name.innerHTML = cal.name;
	};
	this._init();
}
function load_google() {
	add_javascript("https://apis.google.com/js/client.js?onload=google_api_loaded");
}
function google_api_loaded(){
	gapi.client.setApiKey("AIzaSyBy-4f3HsbxvXJ6sULM87k35JrsGSGs3q8");
	gapi.auth.init();
	gapi.auth.authorize({client_id:"459333498575-p8k0toe6hpcjfe29k83ah77adnocqah4.apps.googleusercontent.com",scope:"https://www.googleapis.com/auth/calendar"},function(auth_token){
		gapi.client.load('calendar','v3',function(){
			var req = gapi.client.calendar.calendarList.list();
			req.execute(function(resp){
				var list = document.getElementById("calendars_list");
				for (var i = 0; i < resp.items.length; ++i) {
					new CalendarElement(list,
							window.calendars.add_calendar(resp.items[i].summary, resp.items[i].backgroundColor.substring(1), true, new GoogleCalendarRefresher(resp.items[i].id))
					);
				}
			});
		});
	});
}
function GoogleCalendarRefresher(id) {
	this.id = id;
	this.refresh = function(manager, cal, ondone) {
		var req = gapi.client.calendar.events.list({calendarId:this.id,maxResults:10000});
		req.execute(function(resp){
			if (!resp || !resp.items) { ondone(); return; }
			var removed_events = cal.events;
			cal.events = [];
			for (var i = 0; i < resp.items.length; ++i) {
				var gev = resp.items[i];
				ev = {};
				ev.calendar = cal;
				ev.description = gev.summary;
				ev.uid = gev.iCalUID;
				if (gev.start && gev.start.date) {
					ev.start = new Date();
					ev.start.setHours(0,0,0,0);
					var d = gev.start.date.split("-");
					ev.start.setFullYear(parseInt(d[0]));
					ev.start.setMonth(parseInt(d[1])-1);
					ev.start.setDate(parseInt(d[2]));
				}
				if (gev.end && gev.end.date) {
					ev.end = new Date();
					//ev.end.setHours(3,0,0,0);
					var d = gev.end.date.split("-");
					ev.end.setFullYear(parseInt(d[0]));
					ev.end.setMonth(parseInt(d[1])-1);
					ev.end.setDate(parseInt(d[2]));
				}
				if (!ev.start || !ev.end) continue;
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
</script>
<?php
	}
	
}
?>