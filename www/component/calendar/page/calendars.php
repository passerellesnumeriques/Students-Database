<?php 
class page_calendars extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
?>
<div style='height:100%;width:100%' id='calendars'>
	<div style='overflow:auto'>
		<div class='collapsable_section' style='margin:5px'>
			<div class='collapsable_section_header'>
				<img src='/static/application/logo.png' width='16px' style='vertical-align:bottom'/>
				PN Calendars
			</div>
			<div class='collapsable_section_content' id='pn_calendars' style='padding:5px'>
				<img src='<?php echo theme::$icons_16['loading'];?>' id='loading_pn_calendars'/>
			</div>
		</div>
		<br/>
		<div class='collapsable_section' style='margin:5px'>
			<div class='collapsable_section_header'>
				<img src='/static/google/google.png' style='vertical-align:bottom'/>
				Google Calendars
			</div>
			<div class='collapsable_section_content' id='google_calendars' style='padding:5px'>
				<img src='<?php echo theme::$icons_16['loading'];?>' id='loading_google_calendars'/>
			</div>
		</div>
		</div>
	<div id='calendars_view'></div>
</div>
<?php
		$this->add_javascript("/static/widgets/splitter_vertical/splitter_vertical.js");
		$this->onload("new splitter_vertical('calendars',0.3);");
		$this->add_javascript("/static/calendar/calendar.js");
		$this->add_javascript("/static/calendar/calendar_view.js");
		$this->onload("init_calendars();");
?>
<script type='text/javascript'>
function init_calendars() {
	window.calendars = new CalendarManager();
	load_pn_calendars();
	_load_google_calendars();
	new CalendarView(window.calendars, 'week', 'calendars_view', function(){ add_test_events(); });
}
function load_pn_calendars() {
	var list = document.getElementById("pn_calendars");
<?php 
$ids = PNApplication::$instance->calendar->getAccessibleCalendars();
$calendars = SQLQuery::create()->bypass_security()->select("Calendar")->where_in("Calendar", "id", $ids)->execute();
foreach ($calendars as $cal) echo "new CalendarElement(list,window.calendars.add_calendar(new PNCalendar(".$cal["id"].",".json_encode($cal["name"]).",".json_encode($cal["color"]).",true)));";
?>
	var loading = document.getElementById('loading_pn_calendars');
	loading.parentNode.removeChild(loading);
}
function TestCalendar() {
	Calendar.call(this, "Test", "D0FFD0", true);
	this.refresh = function(manager, cal, ondone) {
		if (cal.events.length > 0) return;
		var ev = {
			uid:1,
			calendar: cal,
			start: new Date(new Date().getTime()-30*60*1000),
			end: new Date(new Date().getTime()+30*60*1000),
			title: "Before",
			description: "La description de l'evenement qui est avant"
		};
		cal.events.push(ev);
		manager.on_event_added(ev);
		ev = {
			uid:2,
			calendar: cal,
			start: new Date(new Date().getTime()+120*60*1000),
			end: new Date(new Date().getTime()+180*60*1000),
			title: "After"
		};
		cal.events.push(ev);
		manager.on_event_added(ev);
		ev = {
			uid:3,
			calendar: cal,
			start: new Date(new Date().getTime()-60*60*1000),
			end: new Date(new Date().getTime()+200*60*1000),
			title: "Large"
		};
		cal.events.push(ev);
		manager.on_event_added(ev);
		ev = {
			uid:4,
			calendar: cal,
			start: new Date(new Date().getTime()+15*60*1000),
			end: new Date(new Date().getTime()+75*60*1000),
			title: "Overlap"
		};
		cal.events.push(ev);
		manager.on_event_added(ev);
		ev = {
			uid:5,
			calendar: cal,
			start: new Date(new Date().getTime()+45*60*1000),
			end: new Date(new Date().getTime()+90*60*1000),
			title: "Inside"
		};
		cal.events.push(ev);
		manager.on_event_added(ev);
	};
}
TestCalendar.prototype = new Calendar();
TestCalendar.prototype.constructor = TestCalendar;
function add_test_events() {
	new CalendarElement(document.getElementById("pn_calendars"), window.calendars.add_calendar(new TestCalendar())); 
}
function CalendarElement(container, cal) {
	var t=this;
	this._init = function() {
		this.div = document.createElement("DIV"); container.appendChild(this.div);
		this.box = document.createElement("DIV"); this.div.appendChild(this.box);
		this.box.style.display = "inline-block";
		this.box.style.width = "10px";
		this.box.style.height = "10px";
		this.box.style.border = "1px solid #"+cal.color;
		if (cal.show)
			this.box.style.backgroundColor = "#"+cal.color;
		this.box.onclick = function() {
			if (!cal.manager) return;
			if (cal.show) {
				cal.manager.hide_calendar(cal);
				t.box.style.backgroundColor = '';
			} else {
				cal.manager.show_calendar(cal);
				t.box.style.backgroundColor = "#"+cal.color;
	}
		};
		this.name = document.createElement("SPAN"); this.div.appendChild(this.name);
		this.name.style.paddingLeft = "3px";
		this.name.innerHTML = cal.name;
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
function _load_google_calendars() {
	var loading = document.getElementById('loading_google_calendars');
	var doit = function() {
		load_google_calendars(window.calendars, function(calendars){
			if (calendars == null) {
				var div = document.createElement("DIV");
				div.innerHTML = "<span style='color:red'>Unable to connect to Google Calendar</span> ";
				var a = document.createElement("A");
				a.href = '#';
				a.onclick = function() {
					var d = document.createElement("DIV");
					d.innerHTML = "<img src='"+theme.icons_16.loading+"'/>";
					loading.parentNode.insertBefore(d, loading);
					loading.parentNode.removeChild(loading);
					d.id = 'loading_google_calendars';
					_load_google_calendars();
				};
				a.innerHTML = "Retry";
				div.appendChild(a);
				loading.parentNode.insertBefore(div, loading);
				loading.parentNode.removeChild(loading);
				div.id = 'loading_google_calendars';
				return;
			}
			var list = document.getElementById("google_calendars");
			for (var i = 0; i < calendars.length; ++i)
				new CalendarElement(list, calendars[i]);
			loading.parentNode.removeChild(loading);
			doit = null;
		});		
	};
	add_javascript("/static/google/google_calendar.js",function(){
		window.top.add_javascript("/static/google/google.js",function() {
			if (window.top.google.connection_status != 1) {
				var container = loading.parentNode;
				container.removeChild(loading);
				loading = document.createElement("DIV");
				loading.id = 'loading_google_calendars';
				container.appendChild(loading);
				loading.innerHTML = window.top.connection_status == 0 ? "Connecting to Google..." : "Not connected to Google";
				window.top.google.connection_listeners.push(function(){
					if (!doit) return;
					if (window.top.google.connection_status == 1)
						doit();
					else
						loading.innerHTML = window.top.connection_status == 0 ? "Connecting to Google..." : "Not connected to Google";
				});						
			} else
				doit();
		});
	});
}
</script>
<?php
	}
	
}
?>