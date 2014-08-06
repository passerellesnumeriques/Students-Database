<?php 
function getArrayStepsToDisplay ($steps_to_display){
	$json = "";
	if(count($steps_to_display) == 0)
		$json.= "[];";
	else {
		$json.=  "[";
		$first = true;
		foreach($steps_to_display as $s){
			if(!$first)
				$json.=  ", ";
			$first = false;
			$json.=  "{name:".json_encode($s["name"]).", id:".json_encode($s["id"])."}";
		}
		$json.=  "]";
	}
	return $json;
}
require_once("SelectionPage.inc");
class page_selection_main_page extends SelectionPage {
	public function getRequiredRights() { return array(); }
	public function executeSelectionPage(){
		
		$calendar_id = PNApplication::$instance->selection->getCalendarId();
		
		$this->addJavascript("/static/widgets/header_bar.js");
		$this->onload("new header_bar('steps_header','small');");

		$this->addJavascript("/static/widgets/splitter_vertical/splitter_vertical.js");
		$this->onload("new splitter_vertical('selection_main_page_split',0.35);");
		
		//TODO set rights to calendar table? bypass_security required above...
		
		$this->addJavascript("/static/widgets/section/section.js");
		$this->addJavascript("/static/news/news.js");
		
		if (PNApplication::$instance->user_management->has_right("manage_selection_campaign"))
			$this->onload("sectionFromHTML('section_preparation');");
		$this->onload("sectionFromHTML('section_status_is');");
		$this->onload("sectionFromHTML('section_status_exam_center');");
		$this->onload("sectionFromHTML('section_status_exam_results');");
		$this->onload("sectionFromHTML('section_status_interview');");
		$this->onload("loadISStatus();");
		$this->onload("loadExamCenterStatus();");
		$this->onload("loadExamResultsStatus();");
		$this->onload("loadInterviewStatus();");
		?>
		<div id = "selection_main_page_split" style = 'height:100%; width:100%'>
				<div style="display:flex;flex-direction:column;">
					<div id='steps_header' style='flex:none;' icon='/static/selection/dashboard_steps.png' title='Selection Steps'></div>
					<div style="overflow:auto;flex:1 1 auto">
						<?php if (PNApplication::$instance->user_management->has_right("manage_selection_campaign")) {?>
						<div id='section_preparation' title="Selection Process Preparation" collapsable="true" style="width: 95%; margin-left: 10px; margin-top: 15px;">
							<div style='text-align:center'>
								<a href='config/manage'><button>
									<img src='<?php echo theme::$icons_16["config"];?>'/>
									Configure how this selection process will work
								</button></a>
							</div>
						</div>
						<?php } ?>
						<div id='section_status_is' title='Information Sessions' collapsable='true' style="width: 95%; margin-left: 10px; margin-top: 15px;">
							<div id='status_is' class='selection_status'></div>
						</div>
						<div id='section_status_exam_center' title='Exam Centers' collapsable='true' style="width: 95%; margin-left: 10px; margin-top: 15px;">
							<div id='status_exam_centers' class='selection_status'></div>
						</div>
						<div id='section_status_exam_results' title='Exam Results' collapsable='true' style="width: 95%; margin-left: 10px; margin-top: 15px;">
							<div id='status_exam_results' class='selection_status'></div>
						</div>
						<div id='section_status_interview' title='Interview' collapsable='true' style="width: 95%; margin-left: 10px; margin-top: 15px;">
							<div id='status_interview' class='selection_status'></div>
						</div>
					</div>
				</div>
				<div id = 'right' style='overflow-y:auto;'>
					<div id='calendar_section'
						icon='/static/calendar/event.png'
						title='Selection Calendar'
						collapsable='true'
						style='margin:5px'
					>
						<div id='calendar_container' style='height:300px;'></div>
					</div>
					<div id='updates_section'
						icon='/static/news/news.png'
						title='Selection Updates'
						collapsable='true'
						style='margin:5px'
					>
						<div id='updates_container' style='padding:5px;'></div>
					</div>
				</div>
		</div>
		<!--  <a href = "/dynamic/selection/page/test_functionalities">Tests</a>  -->
		<script type = 'text/javascript'>
			var calendar_id = null;
			<?php
			if(isset($calendar_id)) echo "calendar_id = ".json_encode($calendar_id).";";
			?>
			calendar_section = sectionFromHTML('calendar_section');
			require(["calendar.js","popup_window.js"],function(){
				if(calendar_id != null){
					var cal_manager = new CalendarManager();
					var PN_cal = window.top.pn_calendars_provider.getCalendar(calendar_id);
					var init_calendar = function() {
						cal_manager.addCalendar(PN_cal);
						require("calendar_view.js",function(){
							new CalendarView(cal_manager, "week", 60, "calendar_container", function(){});
						});
						var extend = document.createElement("IMG");
						extend.className = "button_verysoft";
						extend.src = theme.icons_16.window_popup;
						extend.onclick = function(){
							var content = document.createElement("div");
							content.id = 'content_calendar_extend';
							content.style.width = "100%";
							content.style.height = "100%";
							var pop = new popup_window("Selection Calendar","/static/calendar/event.png",content);
							pop.showPercent(95,95);
							require("calendar_view.js",function(){
								new CalendarView(cal_manager, "week", 30, content, function(){});
							});
						};
						window.calendar_section.addToolRight(extend);
					};
					if (PN_cal) init_calendar();
					else {
						var retry_calendar = function() {
							PN_cal = window.top.pn_calendars_provider.getCalendar(calendar_id);
							if (PN_cal) init_calendar();
							else setTimeout(retry_calendar, 500);
						};
						window.top.pn_calendars_provider.refreshCalendars();
						retry_calendar();
					}
				}
			});

			updates_section = sectionFromHTML('updates_section');
			new news('updates_container', [{name:"selection",tags:["campaign<?php echo PNApplication::$instance->selection->getCampaignId();?>"]}], [], function(){
			}, function(){
			});

			function loadISStatus() {
				var container = document.getElementById('status_is');
				container.innerHTML = "<center><img src='"+theme.icons_16.loading+"'/></center>";
				service.html("selection","is/status",null,container);
			}
			function loadExamCenterStatus() {
				var container = document.getElementById('status_exam_centers');
				container.innerHTML = "<center><img src='"+theme.icons_16.loading+"'/></center>";
				service.html("selection","exam/status",null,container);
			}
			function loadExamResultsStatus() {
				var container = document.getElementById('status_exam_results');
				container.innerHTML = "<center><img src='"+theme.icons_16.loading+"'/></center>";
				service.html("selection","exam/results_status",null,container);
			}
			function loadInterviewStatus() {
				var container = document.getElementById('status_interview');
				container.innerHTML = "<center><img src='"+theme.icons_16.loading+"'/></center>";
				service.html("selection","interview/status",null,container);
			}
			
		</script>
	<?php
	}
	
}