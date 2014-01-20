<?php 
require_once("selection_page.inc");
class page_selection_main_page extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(&$page){
		$calendar_id = PNApplication::$instance->selection->get_calendar_id();
		$calendar_name = SQLQuery::create()->bypass_security()->select("Calendar")->field("name")->where("id",$calendar_id)->execute_single_value();
		
		$page->add_javascript("/static/widgets/page_header.js");
		$page->add_javascript("/static/widgets/splitter_vertical/splitter_vertical.js");
		$page->onload("new splitter_vertical('selection_main_page_split',0.5);");
		//TODO set rights to calendar table? bypass_security required above...
		// $steps = PNApplication::$instance->selection->getJsonSteps();
		// var_dump(PNApplication::$instance->selection->getSteps());
	?>
		<!--<div style = 'color:red'>
		TODO main page
		</div>
		<div id = 'selection_calendar' style='height:300px'></div>
		<div id = 'selection_steps_dashboard'></div>-->
		<div id = "selection_main_page_split" style = 'height:100%; width:100%'>
				<div id = 'rigth'>
					right side <br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>toto
				</div>
				<div id = 'left'>
					left side
				</div>
		</div>
		<script type = 'text/javascript'>
			var calendar_id = null;
			var calendar_name = null;
			var steps = null;
			<?php
			if(isset($calendar_id)) echo "calendar_id = ".json_encode($calendar_id).";";
			if(isset($calendar_name)) echo "calendar_name = ".json_encode($calendar_name).";";
			if(isset($steps)) echo "steps = ".$steps.";";
			?>
			// require(["calendar.js","dashboard_steps.js"],function(){
				// if(calendar_id != null && calendar_name != null){
					// var cal_manager = new CalendarManager();
					// var PN_cal = new PNCalendar(calendar_id, calendar_name, "C0C0FF", true, true);
					// cal_manager.add_calendar(PN_cal);
					// require("calendar_view.js",function(){
						// new CalendarView(cal_manager, "week", "selection_calendar", function(){});
					// });
				// }
				// if(steps != null)
					// new dashboard_steps("selection_steps_dashboard",steps);
			// });
		</script>
	<?php
	}
	
}