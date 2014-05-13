<?php
require_once("/../SelectionPage.inc");
require_once("component/selection/SelectionJSON.inc");
class page_exam_center_main_page extends SelectionPage {
	
	public function getRequiredRights() {return array("see_exam_center_detail");}
	
	/**
	 * Create two sections: one containing the exam center caracteristics and the other one the data related to applicant assignment
	 * @see SelectionPage::executeSelectionPage()
	 */
	public function executeSelectionPage() {
		$this->addJavascript("/static/widgets/grid/grid.js");
		$this->addJavascript("/static/data_model/data_list.js");
		$this->onload("init_organizations_list();");
		$list_container_id = $this->generateID();
		$can_create = PNApplication::$instance->user_management->has_right("manage_exam_center",true);
		$status_container_id = $this->generateID();
		$ECIS_status_container_id = $this->generateID();
		$applicants_assignement_container_id = $this->generateID();
		$this->requireJavascript("horizontal_layout.js");
		$this->onload("new horizontal_layout('horizontal_split',true);");
		$this->requireJavascript("section.js");
		$this->onload("sectionFromHTML('status_section');");
		$this->onload("sectionFromHTML('status_ECIS_section');");
		$this->onload("sectionFromHTML('status_applicants_assignment');");
		$steps = PNApplication::$instance->selection->getSteps();
		if($steps["exam_center"]){			
			$this->requireJavascript("exam_center_status.js");
			$this->onload("new exam_center_status('$status_container_id');");
		}
		$this->requireJavascript("exam_center_and_informations_sessions_status.js");
		$this->onload("new exam_center_and_informations_sessions_status('".$ECIS_status_container_id."');");
		$this->requireJavascript("applicants_assignment_to_EC_status.js");
		$this->onload("new applicants_assignment_to_EC_status('".$applicants_assignement_container_id."');");
		
		?>		<script type='text/javascript'>
					function onCreateNewCenter(button){
						require("context_menu.js",function(){
							var menu = new context_menu();
							var from_scratch = document.createElement("div");
							from_scratch.appendChild(document.createTextNode("Create a center from scratch"));
							from_scratch.className = "context_menu_item";
							from_scratch.onclick = function(){
								location.assign("/dynamic/selection/page/exam/center_profile");
							};
							menu.addItem(from_scratch);
							from_IS = document.createElement("div");
							from_IS.className = "context_menu_item";
							from_IS.appendChild(document.createTextNode("Create from an Information Session"));
							from_IS.onclick = function(){
								require("popup_window.js",function(){
									var pop = new popup_window("Link Exam Centers and Information Sessions","");
									pop.setContentFrame("/dynamic/selection/page/exam/convert_IS_into_center");
									pop.onclose = function(){ //Refresh in the case of any centers have been created
										location.reload();
									};
									pop.show();
								});
							};
							menu.addItem(from_IS);
							menu.showBelowElement(button);
						});
					}
				</script>
				<div id='horizontal_split'>
					<div style ="display:inline-block;">
						<div id='status_section' title='Exam Centers Status' collapsable='false' css='soft' style='margin:10px; width:360px;'>
							<div id = '<?php echo $status_container_id; ?>'>
							<?php 
							if(!$steps["exam_center"]){
							?>
							<div><i>There is no exam center yet</i><div class = "button" onclick = "onCreateNewCenter(this);" style = "margin-left:3px; margin-top:3px;">Create First</div></div>
							<?php
							}
							?>
							</div>
						</div>
						<div id='status_ECIS_section' title='Exam Centers and Information Sessions' collapsable='false' css='soft' style='margin:10px; width:360px;'>
							<div id = '<?php echo $ECIS_status_container_id;?>'></div>						
						</div>
						<div id='status_applicants_assignment' title='Applicants Assignment to Exam Centers' collapsable='false' css='soft' style='margin:10px; width:360px;'>
							<div id = '<?php echo $applicants_assignement_container_id;?>'></div>						
						</div>
					</div>
					
					<div style="padding: 10px;display:inline-block" layout='fill'>
						<div id = '<?php echo $list_container_id; ?>' class="section soft">
						</div>
					</div>
				</div>
				<script type='text/javascript'>
					function init_organizations_list() {
						new data_list(
							'<?php echo $list_container_id;?>',
							'ExamCenter', <?php echo PNApplication::$instance->selection->getCampaignId();?>,
							[
								'Exam Center.Name',
								'Exam Center.Applicants'
							],
							[],
							-1,
							function (list) {
								list.addTitle("/static/selection/exam/exam_center_16.png", "Exam Centers");
								var new_EC = document.createElement("DIV");
								new_EC.className = 'button_verysoft';
								new_EC.innerHTML = "<img src='"+theme.build_icon("/static/selection/exam/exam_center_16.png",theme.icons_10.add)+"'/> New Exam Center";
								new_EC.onclick = function() {
									onCreateNewCenter(this);
								};
								list.addHeader(new_EC);
								list.makeRowsClickable(function(row){
									var ec_id = list.getTableKeyForRow('ExamCenter',row.row_id);
									location.href = "/dynamic/selection/page/exam/center_profile?id="+ec_id;
								});
							}
						);
					}
					
					
					
				</script>				
		<?php 
	}
}