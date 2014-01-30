<?php 
class page_calendars extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/splitter_vertical/splitter_vertical.js");
		$this->onload("new splitter_vertical('calendars',0.3);");
		$this->add_javascript("/static/calendar/calendar.js");
		$this->add_javascript("/static/calendar/calendar_view.js");
		$this->add_javascript("/static/widgets/section/section.js");
		$this->onload("init_calendars();");
?>
<div style='height:100%;width:100%' id='calendars'>
	<div style='overflow:auto' id='left'></div>
	<div id='calendars_view'></div>
</div>
<script type='text/javascript'>
function init_calendars() {
	var manager = new CalendarManager();
	new CalendarView(manager, 'week', 30, 'calendars_view', function(){ });
	window.top.CalendarsProviders.get(function(provider) {
		var content = document.createElement("DIV");
		content.style.padding = "5px";
		var sec = new section(provider.getProviderIcon(), provider.getProviderName(), content, true);
		var left = document.getElementById('left');
		sec.element.style.margin = "5px";
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
							fireLayoutEventFor(left);
							manager.addCalendar(cal);
						});
					},function(){});
			};
		}
		provider.getCalendars(function(calendars) {
			while (content.childNodes.length > 0) content.removeChild(content.childNodes[0]);
			for (var i = 0; i < calendars.length; ++i) {
				new CalendarControl(content, calendars[i]);
				fireLayoutEventFor(left);
				manager.addCalendar(calendars[i]);
			}
		},function(feedback){
			content.innerHTML = feedback;
		});
	});
	
}
</script>
<?php
	}
	
}
?>