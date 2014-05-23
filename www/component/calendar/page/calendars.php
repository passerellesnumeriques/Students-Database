<?php 
class page_calendars extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$this->requireJavascript("horizontal_layout.js");
		$this->onload("new horizontal_layout('calendars',true,'top');");
		$this->addJavascript("/static/calendar/calendar.js");
		$this->addJavascript("/static/calendar/calendar_view.js");
		$this->addJavascript("/static/widgets/section/section.js");
		$this->onload("init_calendars();");
?>
<div style='height:100%;width:100%;padding-top:5px' id='calendars'>
	<div id='left' style='padding-left:5px;'></div>
	<div id='calendars_view' style='height:10px;margin:5px;' layout="fill" class='section'></div>
</div>
<script type='text/javascript'>
function init_calendars() {
	new CalendarView(window.top.calendar_manager, 'week', 30, 'calendars_view', function(){ });
	var providers = [];
	var left = document.getElementById('left');
	window.top.CalendarsProviders.get(function(provider) {
		var p = {provider:provider,calendars:[]};
		providers.push(p);
		var content = document.createElement("DIV");
		p.div = content;
		content.style.padding = "5px";
		var sec = new section(provider.getProviderIcon(), provider.getProviderName(), content, true);
		left.appendChild(sec.element);
		if (provider.canCreateCalendar()) {
			var create_button = document.createElement("IMG");
			create_button.title = "Create a new calendar";
			create_button.src = theme.build_icon("/static/calendar/event.png",theme.icons_10.add,"right_bottom");
			create_button.style.verticalAlign = "bottom";
			create_button.className = "button";
			sec.addTool(create_button);
			create_button.onclick = function() {
				input_dialog(
					theme.build_icon("/static/calendar/event.png",theme.icons_10.add,"right_bottom"),
					"New Calendar",
					"Enter the name of the new calendar",
					"",
					100,
					function(name) {
						if (name.length == 0) return "Please enter a name";
						return null;
					}, function(name) {
						if (!name) return;
						provider.createCalendar(name, null, null, function(cal) {
							new CalendarControl(content, cal);
							layout.invalidate(left);
							window.top.calendar_manager.addCalendar(cal);
						});
					},function(){}
				);
			};
		}
		content.innerHTML = provider.connection_status;
		provider.on_connection_status.add_listener(function(status) {
			if (p.calendars.length == 0)
				content.innerHTML = status;
		});
	});

	
	var new_calendar = function(cal) {
		for (var i = 0; i < providers.length; ++i) {
			if (providers[i].provider == cal.provider) {
				providers[i].calendars.push(cal);
				new CalendarControl(providers[i].div, cal);
				layout.invalidate(left);
				return;
			}
		}
	};
	for (var i = 0; i < window.top.calendar_manager.calendars.length; ++i)
		new_calendar(window.top.calendar_manager.calendars[i]);
	window.top.calendar_manager.on_calendar_added.add_listener(new_calendar);
	pnapplication.onclose.add_listener(function() {
		window.top.calendar_manager.on_calendar_added.remove_listener(new_calendar);
	});
}
</script>
<?php
	}
	
}
?>