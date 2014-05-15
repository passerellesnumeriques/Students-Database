<?php 
require_once("/../SelectionPage.inc");
class page_IS_main_page extends SelectionPage {
	public function getRequiredRights() { return array("see_information_session_details"); }
	public function executeSelectionPage(){
		$this->addJavascript("/static/widgets/grid/grid.js");
		$this->addJavascript("/static/data_model/data_list.js");
		$this->onload("initISList();");
		$can_create_session = PNApplication::$instance->user_management->has_right("manage_information_session",true);
		$can_create_applicant = PNApplication::$instance->user_management->has_right("edit_applicants",true);
		$this->requireJavascript("section.js");
		$this->onload("sectionFromHTML('status_section');");
		$this->requireJavascript("horizontal_layout.js");
		$this->onload("new horizontal_layout('horizontal_split',true);");
		$this->onload("loadStatus();");
		?>
		<div id='horizontal_split'>
			<div style="padding:5px;padding-right:0px;display:inline-block">
				<div id='status_section' title='Status' collapsable='false' css='soft' style='display:inline-block;'>
					<div id='is_status' style='padding:10px'></div>
				</div>
			</div>
			<div style="padding: 5px;display:inline-block" layout='fill'>
				<div id = 'is_list' class="section soft">
				</div>
			</div>
		</div>
		
		<script type='text/javascript'>
		var is_list;
			function initISList() {
				is_list = new data_list(
					'is_list',
					'InformationSession', <?php echo PNApplication::$instance->selection->getCampaignId();?>,
					[
						'Information Session.Name',
						'Information Session.Date',
						'Information Session.Hosting Partner',
						'Information Session.Expected',
						'Information Session.Attendees',
						'Information Session.Applicants'
					],
					[],
					-1,
					function (list) {
						list.addTitle("/static/selection/IS/IS_16.png", "Information Sessions");
						<?php if ($can_create_session) { ?>
						var new_IS = document.createElement("BUTTON");
						new_IS.className = 'flat';
						new_IS.innerHTML = "<img src='"+theme.build_icon("/static/selection/IS/IS_16.png",theme.icons_10.add)+"'/> New Information Session";
						new_IS.onclick = newIS;
						list.addHeader(new_IS);
						<?php } ?>

						<?php if ($can_create_applicant) { ?>
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
						<?php } ?>
						
						list.makeRowsClickable(function(row){
							var is_id = list.getTableKeyForRow('InformationSession',row.row_id);
							require("popup_window.js",function() {
								var popup = new popup_window("Information Session", "/static/selection/IS/IS_16.png", "");
								var frame = popup.setContentFrame("/dynamic/selection/page/IS/profile?id="+is_id+"&onsaved=saved");
								frame.saved = function() { ISchanged(); };
								popup.onclose = function() { refreshPage(); };
								popup.showPercent(95,95);
							});
						});
					}
				);
			}
			function newIS() {
				require("popup_window.js",function() {
					var popup = new popup_window("Information Session", "/static/selection/IS/IS_16.png", "");
					var frame = popup.setContentFrame("/dynamic/selection/page/IS/profile?onsaved=saved");
					frame.saved = function() { ISchanged(); };
					popup.onclose = function() {
						location.reload();
					};
					popup.showPercent(95,95);
				});
			}
			function ISchanged() {
				refreshPage();
			}
			function loadStatus() {
				var container = document.getElementById('is_status');
				container.innerHTML = "<center><img src='"+theme.icons_16.loading+"'/></center>";
				service.html("selection","IS/new_status",null,container);
			}
			function refreshPage() {
				is_list.reloadData();
				loadStatus();
			}
		</script>
	
	<?php		
	}
	
}