<?php 
require_once("/../SelectionPage.inc");
class page_IS_main_page extends SelectionPage {
	public function getRequiredRights() { return array("see_information_session_details"); }
	public function executeSelectionPage(){
		$this->addJavascript("/static/widgets/grid/grid.js");
		$this->addJavascript("/static/data_model/data_list.js");
		$this->onload("init_organizations_list();");
		$list_container_id = $this->generateID();
		$can_create = PNApplication::$instance->user_management->has_right("manage_information_session",true);
		$status_container_id = $this->generateID();
		$this->requireJavascript("section.js");
		$this->onload("sectionFromHTML('status_section');");
		$steps = PNApplication::$instance->selection->getSteps();
		if($steps["information_session"]){
			$this->onload("new IS_status('$status_container_id');");
			$this->requireJavascript("IS_status.js");
		}
		$this->requireJavascript("horizontal_layout.js");
		$this->onload("new horizontal_layout('horizontal_split',true);");
		
		?>
		<div id='horizontal_split'>
			<div style="padding:5px;padding-right:0px;display:inline-block">
				<div id='status_section' title='Information Sessions Status' collapsable='false' css='soft' style='display:inline-block;width:340px;'>
					<div id = '<?php echo $status_container_id; ?>'>
					<?php 
					if(!$steps["information_session"]){
					?>
					<div><i>There is no information session yet</i><button onclick="newIS();" style = "margin-left:3px; margin-top:3px;">Create First</button></div>
					<?php
					}
					?>
					</div>
				</div>
				<a href='#' onclick="testNewIS();return false;">Test new IS</a>
			</div>
			<div style="padding: 5px;display:inline-block" layout='fill'>
				<div id = '<?php echo $list_container_id; ?>' class="section soft">
				</div>
			</div>
		</div>
		
		<script type='text/javascript'>
			function newIS() {
				require("popup_window.js",function() {
					var popup = new popup_window("Information Session", "/static/selection/IS/IS_16.png", "");
					popup.setContentFrame("/dynamic/selection/page/IS/profile");
					popup.onclose = function() {
						location.reload();
					};
					popup.showPercent(95,95);
				});
			}
			function testNewIS() {
				require("popup_window.js",function() {
					var popup = new popup_window("Information Session", "/static/selection/IS/IS_16.png", "");
					popup.setContentFrame("/dynamic/selection/page/IS/session");
					popup.onclose = function() {
						location.reload();
					};
					popup.showPercent(95,95);
				});
			}
			function init_organizations_list() {
				new data_list(
					'<?php echo $list_container_id;?>',
					'InformationSession', <?php echo PNApplication::$instance->selection->getCampaignId();?>,
					[
						'Information Session.Name',
						'Information Session.Date',
						'Information Session.Expected',
						'Information Session.Attendees',
						'Information Session.Applicants'
					],
					[],
					-1,
					function (list) {
						list.addTitle("/static/selection/IS/IS_16.png", "Information Sessions");
						var new_IS = document.createElement("BUTTON");
						new_IS.className = 'flat';
						new_IS.innerHTML = "<img src='"+theme.build_icon("/static/selection/IS/IS_16.png",theme.icons_10.add)+"'/> New Information Session";
						new_IS.onclick = newIS;
						list.addHeader(new_IS);

						var create_applicant = document.createElement("BUTTON");
						create_applicant.className = "flat";
						create_applicant.innerHTML = "<img src='"+theme.build_icon("/static/selection/applicant/applicant_16.png",theme.icons_10.add)+"' style='vertical-align:bottom'/> Create Applicant";
						create_applicant.onclick = function() {
							window.top.require("popup_window.js",function() {
								var p = new window.top.popup_window('New Applicant', theme.build_icon("/static/selection/applicant/applicant_16.png",theme.icons_10.add), "");
								var frame = p.setContentFrame(
									"/dynamic/people/page/popup_create_people?root=Applicant&sub_model=<?php echo PNApplication::$instance->selection->getCampaignId();?>&types=applicant&ondone=reload_list",
									null,
									{
										sub_models:{SelectionCampaign:<?php echo PNApplication::$instance->selection->getCampaignId();?>}
									}
								);
								frame.reload_list = function() { list.reloadData(); };
								p.show();
							});
						};
						list.addHeader(create_applicant);
						
						var import_applicants = document.createElement("BUTTON");
						import_applicants.className = "flat";
						import_applicants.innerHTML = "<img src='"+theme.icons_16._import+"' style='vertical-align:bottom'/> Import Applicants";
						import_applicants.onclick = function() {
							window.top.require("popup_window.js",function() {
								var p = new window.top.popup_window('Import Applicants', theme.icons_16._import, "");
								var frame = p.setContentFrame(
									"/dynamic/selection/page/applicant/popup_import?ondone=reload_list",
									null,
									{
									}
								);
								frame.reload_list = function() { list.reloadData(); };
								p.show();
							});
						};
						list.addHeader(import_applicants);
							
						list.makeRowsClickable(function(row){
							var is_id = list.getTableKeyForRow('InformationSession',row.row_id);
							require("popup_window.js",function() {
								var popup = new popup_window("Information Session", "/static/selection/IS/IS_16.png", "");
								popup.setContentFrame("/dynamic/selection/page/IS/profile?id="+is_id);
								popup.onclose = function() {
									location.reload();
								};
								popup.showPercent(95,95);
							});
						});
					}
				);
			}
			
			
			
		</script>
	
	<?php		
	}
	
}