<?php 
require_once("selection_page.inc");
class page_IS_home extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(&$page){
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('IS_home');");
		$rights = array();
		$config = null;
		$rights["read"] = PNApplication::$instance->user_management->has_right("see_information_session_details",true);
		$rights["write"] = PNApplication::$instance->user_management->has_right("edit_information_session",true);
		$rights["add"] = PNApplication::$instance->user_management->has_right("manage_information_session",true);
		$rights["remove"] = PNApplication::$instance->user_management->has_right("manage_information_session",true);
		$config = PNApplication::$instance->selection->get_config();
		$calendar_id = PNApplication::$instance->selection->get_calendar_id();
	?>
		<div id='IS_home'
			icon='/static/selection/IS_32.png' 
			title='Information Sessions'
			page='/dynamic/selection/page/IS_main_page'
		>
		<?php
		if($rights["read"]){
			if($rights["add"]) echo "<span class='button' onclick='dialogAddIS()'><img src='/static/theme/default/icons_16/add.png'/> Old add </span>";
		}
		?>
		<a class = 'button' href = '/dynamic/selection/page/IS_profile'> IS Profile </a>
		</div>
		<script type='text/javascript'>
		var config = null;
		<?php if($config <> null) echo "config = ".json_encode($config).";";?>
		var calendar_id = null;
		<?php if($calendar_id <> null) echo "calendar_id = ".json_encode($calendar_id).";";?>
		var required = 0;
		require("popup_window.js",function(){required = required + 1; everything_ready();});
		require("typed_field.js",function(){required = required + 1; everything_ready();});
		require("field_date.js",function(){required = required + 1; everything_ready();});
		
		
		everything_ready = function(){
			if(required == 3){
				findConfigIndex = function(name){
					var index = null;
					for(var i = 0; i < config.length; i++){
						if(config[i].name == name){
							index = i;
							break;
						}
					}
					return index;
				}
		
				dialogAddIS = function(){
					if(config != null && calendar_id != null){
						var container = document.createElement("div");
						var table = document.createElement("table");
						var tr1 = document.createElement("tr");
						var tr2 = document.createElement("tr");
						var foot= document.createElement("span");
						var td11 = document.createElement("td");
						var td12 = document.createElement("td");
						var td21 = document.createElement("td");
						var td22 = document.createElement("td");
						
						var new_name = "";
						
						if(config[findConfigIndex("give_name_to_IS")].value == true){
							td11.innerHTML = "Name: ";
							var input = document.createElement("input");
							input.type = "text";
							input.length = "30";
							td12.appendChild(input);
							tr1.appendChild(td11);
							tr1.appendChild(td12);
							input.onchange = function(){new_name = input.value;};
						}
						
						td21.innerHTML = "Date: ";
						// var date = new field_date(null,true);
						// td22.appendChild(date.getHTMLElement());
						
						foot.innerHTML = "<br/><center>You will be able to set the address, the partners...<br/><i/>once the information session is created</i></center>";
						foot.onclick = function(){
							d = new Date(1386212249);
							alert(dateToSQL(d));
							// alert(d.getFullYear());
							// alert(d.getMonth());
							// alert(d.getDay());
						};
						
						tr1.appendChild(td11);
						tr1.appendChild(td12);
						tr2.appendChild(td21);
						tr2.appendChild(td22);
						table.appendChild(tr1);
						table.appendChild(tr2);
						container.appendChild(table);
						container.appendChild(foot);
						var pop = new popup_window("Create an Information Session","/static/selection/IS_16.png",container);
						pop.addOkCancelButtons(function(){checkAddIS(new_name, date, pop);});
						pop.show();
					}
				}
				
				checkAddIS = function(new_name, date, pop){
					pop.close();
					var div_locker = window.top.lock_screen();
					if(date.getCurrentData() == null) new error_dialog("You must select a date");
					else addIS(new_name, date, pop,div_locker);
				}
				
				addIS = function(new_name, date, pop,div_locker){
					/** if the name is not set or the config option to create a custom
					 * name is disabled, the name is set to the date(format yyyy-mm-dd) by default
					 */
					 
					if(!new_name.checkVisible()) new_name = date.getCurrentData();
					else new_name = new_name.uniformFirstLetterCapitalized();
					/* Convert the time as a timestamp */
					var start = parseSQLDate(date.getCurrentData());
					
					// alert(start_array[0]+" "+start_array[1]+" " +start_array[2]);
					/* The event last a whole day, so the end date value is date + 23h59mn59s */
					var end = new Date(start.getTime());
					end.setHours(23,59,59);
					// alert("start: year: "+start.getFullYear()+", day: "+ start.getDate()+", month: "+start.getMonth()+", hours: "+start.getHours()+", minutes: "+start.getMinutes() +", end:year: "+end.getFullYear()+", day: "+ end.getDate()+", month: "+end.getMonth()+", hours: "+end.getHours()+", minutes: "+end.getMinutes());
					var event = {calendar:calendar_id,start:start.getTime(),end:end.getTime(),all_day:true,title:new_name,description:""};
					
					service.json("calendar","save_event",{event:event},function(res){
						if(!res){
							error_dialog("An error occured, the IS was not created");
							window.top.unlock_screen(div_locker);
							return;
						} else {
							service.json("selection","save_IS",{name:new_name, date:res.id},function(r){
								if(!r){
									error_dialog("An error occured, the IS was not created");
									window.top.unlock_screen(div_locker);
									return;
								} else {
									window.top.unlock_screen(div_locker);
									//TODO:location.assign r.id!
								}
							});
						}
					});
				}
				
				convertDate = function(date_to_convert){
					var end_array = date_to_convert.split("-");
					for(var i = 0; i < end_array.length; i++){
						end_array[i] = parseInt(end_array[i]);
					}
					return end_array;
				}
			}
		}
		

		</script>
		
	<?php
	}
	
}