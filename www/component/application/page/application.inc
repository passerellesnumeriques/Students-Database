<?php 
theme::css($this, "application.css");

// mandatory part
$this->require_javascript("calendar.js");
?><script type='text/javascript'>
window.top.default_country_code = <?php $d = PNApplication::$instance->get_domain_descriptor(); echo json_encode($d["geography"]["country_code"]).";"; ?>
window.top.default_country_id = <?php $c = PNApplication::$instance->geography->getLocalCountry(); echo $c["id"]; ?>;
window.top.default_country_name = <?php $c = PNApplication::$instance->geography->getLocalCountry(); echo json_encode($c["name"]); ?>;

setTimeout(function() {
	window.top.require("geography.js", function() {
		window.top.geography.getCountryData(window.top.default_country_id,function(){});
	});
}, 1000);

window.top.calendar_manager = new CalendarManager();
window.top.CalendarsProviders.get(function(provider) {
	if (!window.top.calendar_manager) return;
	for (var i = 0; i < provider.calendars.length; ++i)
		window.top.calendar_manager.addCalendar(provider.calendars[i]);
	provider.on_calendar_added.add_listener(function(cal){
		if (!window.top.calendar_manager) return;
		window.top.calendar_manager.addCalendar(cal);
	});
	provider.on_calendar_removed.add_listener(function(cal){
		if (!window.top.calendar_manager) return;
		window.top.calendar_manager.removeCalendar(cal);
	});
	provider._refresh_interval = setInterval(function() {
		provider.refreshCalendars();
	},5*60*1000);
	provider.refreshCalendars();
});
window.top.pnapplication.onlogout.add_listener(function() {
	if (!window.top.calendar_manager) return;
	while (window.top.calendar_manager.calendars.length > 0) window.top.calendar_manager.removeCalendar(window.top.calendar_manager.calendars[0]);
	window.top.CalendarsProviders.get(function(provider) {
		clearInterval(provider._refresh_interval);
	});
	window.top.calendar_manager = null;
});
document.body.style.overflow = "hidden";
</script><?php
if (isset($_GET["page"])) {
	// only the frame
	echo "<iframe id='application_frame' name='application_frame' src='".$_GET["page"]."' style='width:100%;height:100%;border:none;'></iframe>";
	return;
}
global $theme;
include "component/theme/page/".$theme."/main_screen.inc";
