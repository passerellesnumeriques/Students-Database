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
require_once("selection_page.inc");
class page_selection_main_page extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(&$page){
		$calendar_id = PNApplication::$instance->selection->getCalendarId();
		$calendar_name = SQLQuery::create()->bypassSecurity()->select("Calendar")->field("name")->where("id",$calendar_id)->executeSingleValue();
		
		$page->add_javascript("/static/widgets/header_bar.js");
		$page->onload("new header_bar('steps_header','small');");

		$page->add_javascript("/static/widgets/splitter_vertical/splitter_vertical.js");
		$page->onload("new splitter_vertical('selection_main_page_split',0.35);");
		
		$page->add_javascript("/static/widgets/vertical_layout.js");
		$this->onload("new vertical_layout('left');");
		//TODO set rights to calendar table? bypass_security required above...
		
		$page->add_javascript("/static/widgets/section/section.js");
		$page->add_javascript("/static/news/news.js");
		
		$status_to_display = include("selection_main_page_status_screens.inc");
		$steps = PNApplication::$instance->selection->getSteps();
		$unvalid_steps_to_display = array();
		$valid_steps_to_display = array();

	?>
		<div id = "selection_main_page_split" style = 'height:100%; width:100%'>
				<div id = 'left'>
					<div id = 'steps_header' icon='/static/selection/dashboard_steps.png' title='Selection Steps'></div>
					<div style = "overflow:auto" layout = "fill">
						<?php
						echo "<div style = 'width:100%'>";
						$js_to_run = array();
						foreach($status_to_display as $s){
							$id = $page->generateID();
							echo "<div id = '".$id."' style = 'width:100%'></div>";
							if(!$steps[$s[0]])
								array_push($unvalid_steps_to_display,array(
									"id" => $id,
									"name" => $s[2]
								));
							else {
								array_push($valid_steps_to_display,array(
									"id" => $id,
									"name" => $s[2]
								));
								$url = $s[3];
								$url .= "/";
								$url .= $s[1];
								$page->add_javascript("/static/selection/".$url);
								$js_name = str_replace(".js","",$s[1]);
								array_push($js_to_run,"new ".$js_name."('content_".$id."');");
							}
						}
						echo "</div>";
						?>
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
		<a href = "/dynamic/selection/page/test_functionalities">Tests</a>
		<script type = 'text/javascript'>
			var calendar_id = null;
			var calendar_name = null;
			var steps = null;
			<?php
			if(isset($calendar_id)) echo "calendar_id = ".json_encode($calendar_id).";";
			if(isset($calendar_name)) echo "calendar_name = ".json_encode($calendar_name).";";
			if(isset($steps)) echo "steps = ".json_encode($steps).";";
			
			echo "var unvalid_steps_to_display = ";
			echo getArrayStepsToDisplay($unvalid_steps_to_display);
			echo ";";
			echo "var valid_steps_to_display = ";
			echo getArrayStepsToDisplay($valid_steps_to_display);
			echo ";";
			
			?>
			calendar_section = section_from_html('calendar_section');
			require(["calendar.js","popup_window.js"],function(){
				if(calendar_id != null && calendar_name != null){
					var cal_manager = new CalendarManager();
					var PN_cal = window.top.calendar_manager.getCalendar(calendar_id);
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
							var width = parseFloat(getWindowWidth())-30;
							var height = parseFloat(getWindowHeight())-60;
							content.style.width = width.toString()+"px";
							content.style.height = height.toString()+"px";
							require("calendar_view.js",function(){
								new CalendarView(cal_manager, "week", 30, content, function(){});
							});
							var pop = new popup_window("Selection Calendar","/static/calendar/event.png",content);
							pop.show();
						};
						window.calendar_section.addToolRight(extend);
					}
					if (PN_cal) init_calendar();
					else {
						var retry_calendar = function() {
							PN_cal = window.top.calendar_manager.getCalendar(calendar_id);
							if (PN_cal) init_calendar();
							else setTimeout(retry_calendar, 500);
						};
						window.top.pn_calendars_provider.refreshCalendars();
						retry_calendar();
					}
				}
			});

			updates_section = section_from_html('updates_section');
			new news('updates_container', [{name:"selection",tags:["campaign<?php echo PNApplication::$instance->selection->getCampaignId();?>"]}], [], function(){
			}, function(){
			});

			/**
			 * Create the left part of the selection main_page
			 * @param {array} unvalid_steps coming from getArrayStepsToDisplay function
			 * @param {array} valid_steps coming from getArrayStepsToDisplay function
			 */
			function setStatusScreens (unvalid_steps, valid_steps){
				var t = this;

				/**
				 * start creating the page
				 */
				t._init = function(){
					//set the unvalid steps
					for(var i = 0; i < unvalid_steps.length; i++){
						var content = document.createElement("div");
						var container = document.getElementById(unvalid_steps[i].id);
						t._setContainerStyle(container);
						t._setUnvalidContent(content);
						var sec = new section("",unvalid_steps[i].name,content,true);
						container.appendChild(sec.element);
					}
					//set the valid steps
					for(var i = 0; i < valid_steps.length; i++){
						var content = document.createElement("div");
						var container = document.getElementById(valid_steps[i].id);
						t._setContainerStyle(container);
						t._prepareContainerForValidContent(content, valid_steps[i].id);
						var sec = new section("",valid_steps[i].name,content,true);
						container.appendChild(sec.element);
					}
					//once everything is set, run the js
					t._run();
				};

				/**
				 * Set the container style
				 */
				t._setContainerStyle = function(container){
					container.style.width = "95%";
					container.style.marginLeft = "10px";
					container.style.marginTop = "15px";
				};

				/**
				 * The set the section content with the default message when the current step is not validated yet
				 */
				t._setUnvalidContent = function(content){
					var back = document.createElement("div");
					// back.style.backgroundColor = "rgba(128,128,128,0.5)";
					back.innerHTML = "<center><i>This step is not started yet</i></center>";
					content.appendChild(back);
				};

				/**
				 * Create the container for the valid content with a suitable id
				 */
				t._prepareContainerForValidContent = function(content, id){
					var div = document.createElement("div");
					div.id = "content_"+id;
					content.appendChild(div);
				};

				/**
				 * Launch all the scripts defined for each selection sub component
				 * that appear in the selection_main_page_status_screen array
				 */
				t._run = function(){
					<?php
					foreach($js_to_run as $js)
						echo "\n".$js;
					?>
				}
				
				require("section.js",function(){
					t._init();
				});
			}
			
			new setStatusScreens(unvalid_steps_to_display, valid_steps_to_display);
		</script>
	<?php
	}
	
}