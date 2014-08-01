<?php 
require_once("/../SelectionPage.inc");
class page_interview_centers extends SelectionPage {
	
	public function getRequiredRights() { return array("see_interview_center"); }
	
	public function executeSelectionPage() {
		$this->addJavascript("/static/widgets/grid/grid.js");
		$this->addJavascript("/static/data_model/data_list.js");
		$this->onload("initInterviewCentersList();");
		$this->requireJavascript("section.js");
		$this->onload("sectionFromHTML('interview_status_section');");
		$this->onload("loadInterviewStatus();");
		
		$can_create = PNApplication::$instance->user_management->has_right("manage_interview_center",true);
		?>
		<div style="display:flex;flex-direction:row">
			<div style ="display:inline-block;padding:5px;flex:none">
				<div id='interview_status_section' title='Status' collapsable='false' css='soft' style='display:inline-block;'>
					<div id='interview_status' class='selection_status'></div>
				</div>
			</div>
			
			<div style="padding:5px;display:inline-block;flex:1 1 auto;">
				<div id='interview_centers_list' class="section soft">
				</div>
			</div>
		</div>
		<script type='text/javascript'>
			var dl;
			function initInterviewCentersList() {
				dl = new data_list(
					'interview_centers_list',
					'InterviewCenter', <?php echo PNApplication::$instance->selection->getCampaignId();?>,
					[
						'Interview Center.Name',
						'Interview Center.Applicants',
						'Interview Center.Sessions'
					],
					[],
					-1,
					function (list) {
						list.addTitle("/static/selection/exam/exam_center_16.png", "Interview Centers");
						<?php if ($can_create) {?>
						var new_EC = document.createElement("BUTTON");
						new_EC.className = 'flat';
						new_EC.innerHTML = "<img src='"+theme.build_icon("/static/selection/exam/exam_center_16.png",theme.icons_10.add)+"'/> New Interview Center";
						new_EC.onclick = function() {
							newCenter(this);
						};
						list.addHeader(new_EC);
						<?php } ?>
						list.makeRowsClickable(function(row){
							var ec_id = list.getTableKeyForRow('InterviewCenter',row.row_id);
							window.top.popup_frame('/static/selection/exam/exam_center_16.png','Interview Center','/dynamic/selection/page/interview/center_profile?onsaved=saved&id='+ec_id,null,95,95,function(frame,pop) {
								frame.saved = refreshPage;
							});
						});
					}
				);
			}

			function newCenter(button){
				require("context_menu.js",function(){
					var menu = new context_menu();
					menu.addIconItem(null, "Create an interview center in a new place", function() {
						window.top.popup_frame("/static/selection/exam/exam_center_16.png", "New Interview Center", "/dynamic/selection/page/interview/center_profile?onsaved=saved", null, 95, 95, function(frame,pop) {
							frame.saved = refreshPage;
						});
					});
					menu.addIconItem(null, "Create an interview center from an exam center", function() {
						window.top.popup_frame("/static/selection/exam/exam_center_16.png", "Create Interview Center From Exam Center", "/dynamic/selection/page/interview/create_center_from_exam?onsaved=saved", null, null, null, function(frame,pop) {
							frame.saved = refreshPage;
						});
					});
					menu.showBelowElement(button);
				});
			}

			function loadInterviewStatus() {
				var container = document.getElementById('interview_status');
				container.innerHTML = "<center><img src='"+theme.icons_16.loading+"'/></center>";
				service.html("selection","interview/status",null,container);
			}

			function refreshPage() {
				dl.reloadData();
				loadInterviewStatus();
			}
		</script>				
		<?php 
	}
	
}
?>