<?php
require_once("component/selection/page/SelectionPage.inc");
class page_is_profile extends SelectionPage {
	
	public function getRequiredRights() { return array("see_information_session"); }
	
	public function executeSelectionPage(){
		// TODO assign people/staff to do this information session
		$id = @$_GET["id"];
		$onsaved = @$_GET["onsaved"];
		if ($id <> null && $id <= 0) $id = null;
		if ($id <> null) {
			$q = SQLQuery::create()
				->select("InformationSession")
				->whereValue("InformationSession", "id", $id)
				;
			PNApplication::$instance->geography->joinGeographicArea($q, "InformationSession", "geographic_area");
			require_once("component/geography/GeographyJSON.inc");
			GeographyJSON::GeographicAreaTextSQL($q);
			$q->fieldsOfTable("InformationSession");
			$session = $q->executeSingleRow();
		} else
			$session = null;
		if (@$_GET["readonly"] == "true") $editable = false;
		else $editable = $id == null || PNApplication::$instance->user_management->has_right("manage_information_session");
		$db_lock = null;
		if ($editable && $id <> null) {
			$locked_by = null;
			$db_lock = $this->performRequiredLocks("InformationSession",$id,null,$this->component->getCampaignID(), $locked_by);
			//if db_lock = null => read only
			if($db_lock == null){
				$editable = false;
				echo "<div class='info_header'>This Information Session is already open by ".$locked_by.": you cannot edit it</div>";
			}
		}
		$all_configs = include("component/selection/config.inc");
		$this->requireJavascript("is_date.js");
		$this->requireJavascript("who.js");
		$this->requireJavascript("is_statistics.js");
		?>
		<div style='width:100%;height:100%;overflow:auto;'>
		<div style='display:inline-block;margin:10px;margin-right:5px;vertical-align:top;'>
			<?php if ($this->component->getOneConfigAttributeValue("give_name_to_IS")) {
				$this->requireJavascript("center_name.js");
			?>
				<div id='center_name_container'></div>
				<script type='text/javascript'>
				window.center_name = new center_name(
					'center_name_container', 
					<?php echo $session <> null ? json_encode($session["name"]) : "null";?>,
					<?php echo json_encode($editable);?>,
					"Information Session name"
				); 
				</script>
			<?php } ?>
			<div id='is_schedule'></div>
			<script type='text/javascript'>
			window.is_schedule = new is_date(
				'is_schedule', 
				<?php echo json_encode(@$session["date"]);?>,
				<?php echo $session <> null ? $session["id"] : "-1";?>,
				<?php echo $this->component->getCalendarId();?>,
				<?php echo json_encode($this->component->getOneConfigAttributeValue("default_duration_IS"));?>,
				<?php echo json_encode($editable);?>,
				<?php echo json_encode($all_configs["default_duration_IS"][2]);?>
			); 
			</script>
			<div id='is_who'></div>
			<script type='text/javascript'>
			window.is_who = new who_section(
				'is_who',
				[<?php
				if ($id <> null) {
					$q = SQLQuery::create()->select("InformationSessionAnimator")->whereValue("InformationSessionAnimator","information_session",$id);
					PNApplication::$instance->people->joinPeople($q, "InformationSessionAnimator", "people", true);
					require_once("component/people/PeopleJSON.inc");
					$q->field("InformationSessionAnimator","custom_name","custom_name");
					$q->join("People","StaffStatus",array("id"=>"people"));
					$q->field("StaffStatus","is","can_do");
					$list = $q->execute();
					$first = true;
					foreach ($list as $animator) {
						if ($first) $first = false; else echo ",";
						if ($animator["people_id"] <> null)
							echo "{people:".PeopleJSON::People($animator).",can_do:".json_encode($animator["can_do"] == 1)."}";
						else
							echo json_encode($animator["custom_name"]);
					}
				} 
				?>],
				<?php echo json_encode($editable);?>,
				'is'
			); 
			</script>
			<div id='is_stats'></div>
			<script type='text/javascript'>
			window.is_stats = new is_statistics(
				'is_stats', 
				<?php echo json_encode($this->component->getOneConfigAttributeValue("separate_boys_girls_IS"));?>,
				<?php echo json_encode($editable);?>,
				<?php echo json_encode(@$session["number_boys_expected"]);?>,
				<?php echo json_encode(@$session["number_boys_real"]);?>,
				<?php echo json_encode(@$session["number_girls_expected"]);?>,
				<?php echo json_encode(@$session["number_girls_real"]);?>
			); 
			</script>
		</div>
		<div style='display:inline-block;margin:10px;margin-left:0px;vertical-align:top;' id='location_and_partners'>
		<?php
		require_once("component/selection/page/common_centers/location_and_partners.inc");
		locationAndPartners($this, $id, "InformationSession", $session <> null ? GeographyJSON::GeographicAreaText($session) : "null", $editable, true); 
		?>
		</div>
		<div style='margin:0px 5px 5px 5px;' id='applicants_list_container'>
		</div>
		</div>
		<script type='text/javascript'>
		var is_popup = window.parent.get_popup_window_from_frame(window);
		var is_id = <?php echo $id <> null ? $id : -1;?>;

		function save_is() {
			if (window.center_location.geographic_area_text == null) {
				error_dialog("You must at set a location before saving");
				return;
			}
			is_popup.freeze("Saving...");
			// get date (calendar event)
			var event = window.is_schedule.getEvent();
			// prepare data of information session
			var data = {};
			data.id = is_id;
			data.geographic_area = window.center_location.geographic_area_text.id;
			// get from statistics
			var figures = window.is_stats.getFigures();
			data.number_boys_expected = figures.boys_expected;
			data.number_girls_expected = figures.girls_expected;
			data.number_boys_real = figures.boys_real;
			data.number_girls_real = figures.girls_real;
			// get from name
			if (window.center_name) {
				data.name = window.center_name.getName();
				if (data.name != null && !data.name.checkVisible()) data.name = null;
			} else
				data.name = null;
			// partners
			data.partners = [];
			for (var i = 0; i < window.center_location.partners.length; ++i) {
				var partner = window.center_location.partners[i];
				var p = {host:partner.host,host_address:partner.host_address_id,organization:partner.organization.id,contact_points_selected:partner.selected_contact_points_id};
				data.partners.push(p);
			}
			data.who = [];
			for (var i = 0; i < window.is_who.peoples.length; ++i)
				if (typeof window.is_who.peoples[i] == 'string')
					data.who.push(window.is_who.peoples[i]);
				else
					data.who.push(window.is_who.peoples[i].people.id);

			service.json("selection","is/save",{event:event, data:data},function(res){
				if(!res) {
					is_popup.unfreeze();
					error_dialog("An error occured, your informations were not saved");
				} else {
					window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "Information session successfuly saved!", [{action:"close"}], 5000));
					// Update the data on the page (some ids have been generated)
					if (is_id == -1) {
						// first save
						is_id = res.id;
						displayApplicantsList();
					}
					if (res.date) window.is_schedule.setEventId(res.date);
					window.pnapplication.cancelDataUnsaved(); // everything is saved
					// TODO add remove button ?
					is_popup.unfreeze();
					<?php if ($onsaved <> null) echo "window.frameElement.".$onsaved."();"?>
				}
			});
		}

		function displayApplicantsList() {
			var frame = document.createElement("IFRAME");
			frame.style.display = "block";
			frame.style.width = "100%";
			frame.style.height = "300px";
			frame.className = "section soft";
			frame.name = "applicants_frame";
			document.getElementById('applicants_list_container').appendChild(frame);
			postFrame('/dynamic/selection/page/applicant/list?all=true',{filters:[{category:'Selection',name:'Information Session',force:true,data:{values:[is_id]}}]}, 'applicants_frame');
		}
		
		is_popup.removeButtons();
		<?php if ($editable && $id <> null) {?>
		is_popup.addIconTextButton(theme.icons_16.remove, "Remove this session", "remove", function() {
			confirm_dialog("Are you sure you want to remove this information session ?<br/>Note: Any applicant already assigned to this information session will remain in the system, but without information session.",function(res){
				if(res){
					is_popup.freeze();
					service.json("selection","is/remove",{id:<?php echo $id;?>},function(r){
						is_popup.unfreeze();
						if(r) {
							<?php if ($onsaved <> null) echo "window.frameElement.".$onsaved."();"?>
							is_popup.close();
						} else
							error_dialog("An error occured");
					});
				}
			});
		});
		<?php } ?>
		<?php if($id <> null){?>
		displayApplicantsList();
		<?php }?>
		<?php if ($editable || $id == null) {?>
		is_popup.addFrameSaveButton(save_is);
		<?php }?>
		is_popup.addCloseButton();
		</script>
		<?php 
	}
	
}
?>