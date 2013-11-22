<?php 
require_once("selection_page.inc");
class page_selection_main_page extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(&$page){
		$calendar_id = PNApplication::$instance->selection->get_calendar_id();
		$calendar_name = SQLQuery::create()->bypass_security()->select("Calendar")->field("name")->where("id",$calendar_id)->execute_single_value();
		//TODO set rights to calendar table? bypass_security required above...
	?>
		<div style = 'color:red'>
		TODO main page
		</div>
		<div id = 'selection_calendar'></div>
		<script type = 'text/javascript'>
			var calendar_id = null;
			var calendar_id = null;
			<?php
			if(isset($calendar_id) && $calendar_id <> null) echo "calendar_id = ".json_encode($calendar_id).";";
			if(isset($calendar_name) && $calendar_name <> null) echo "calendar_name = ".json_encode($calendar_name).";";
			?>
			require("calendar.js",function(){
				if(calendar_id != null && calendar_name != null){
					var cal_manager = new CalendarManager();
					var PN_cal = new PNCalendar(calendar_id, calendar_name, "blue", true, true);
					require("calendar_view.js",function(){
						new CalendarView(cal_manager, "week", "selection_calendar", function(){});
					});
				}
			});
		</script>
	<?php
	}
	
}