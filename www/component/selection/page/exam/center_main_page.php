<?php
require_once("component/selection/page/SelectionPage.inc");
require_once("component/selection/SelectionJSON.inc");
class page_exam_center_main_page extends SelectionPage {
	
	public function getRequiredRights() {return array("see_exam_center");}
	
	/**
	 * Create two sections: one containing the exam center caracteristics and the other one the data related to applicant assignment
	 * @see SelectionPage::executeSelectionPage()
	 */
	public function executeSelectionPage() {
		$this->addJavascript("/static/widgets/grid/grid.js");
		$this->addJavascript("/static/data_model/data_list.js");
		$this->onload("initExamCentersList();");
		$this->requireJavascript("section.js");
		$this->onload("sectionFromHTML('exam_status_section');");
		$this->onload("loadExamCenterStatus();");
		
		$can_create = PNApplication::$instance->user_management->has_right("manage_exam_center",true);
		?>
		<div style="display:flex;flex-direction:row">
			<div style ="display:inline-block;padding:5px;flex:none">
				<div id='exam_status_section' title='Status' collapsable='false' css='soft' style='display:inline-block;'>
					<div id='exam_status' class='selection_status'></div>
				</div>
				<div style='margin-top:10px'>
					<button class='action' onclick="window.top.popupFrame('/static/contact/address_16.png','Map','/dynamic/selection/page/map?type=exam',null,95,95);"><img src='/static/contact/address_16.png'/> Open Map</button>
				</div>
			</div>
			
			<div style="padding:5px;display:inline-block;flex:1 1 auto;">
				<div id='exam_centers_list' class="section soft">
				</div>
			</div>
		</div>
		<script type='text/javascript'>
			var dl;
			function initExamCentersList() {
				dl = new data_list(
					'exam_centers_list',
					'ExamCenter', <?php echo PNApplication::$instance->selection->getCampaignId();?>,
					[
						'Exam Center.Name',
						'Exam Center.Applicants',
						'Exam Center.Rooms',
						'Exam Center.Sessions',
						'Exam Center.Eligible for Interview'
					],
					[],
					-1,
					null,
					null,
					function (list) {
						list.addTitle("/static/selection/exam/exam_center_16.png", "Exam Centers");
						<?php if ($can_create) {?>
						var new_EC = document.createElement("BUTTON");
						new_EC.className = 'flat';
						new_EC.innerHTML = "<img src='"+theme.build_icon("/static/selection/exam/exam_center_16.png",theme.icons_10.add)+"'/> New Exam Center";
						new_EC.onclick = function() {
							newCenter(this);
						};
						list.addHeader(new_EC);
						<?php } ?>
						list.makeRowsClickable(function(row){
							var ec_id = list.getTableKeyForRow('ExamCenter',row.row_id);
							window.top.popupFrame('/static/selection/exam/exam_center_16.png','Exam Center','/dynamic/selection/page/exam/center_profile?onsaved=saved&id='+ec_id,null,95,95,function(frame,pop) {
								frame.saved = refreshPage;
							});
						});
					}
				);
			}

			function newCenter(button){
				require("context_menu.js",function(){
					var menu = new context_menu();
					menu.addIconItem(theme.icons_16.add, "Create a center in a new place", function() {
						window.top.popupFrame("/static/selection/exam/exam_center_16.png", "New Exam Center", "/dynamic/selection/page/exam/center_profile?onsaved=saved", null, 95, 95, function(frame,pop) {
							frame.saved = refreshPage;
						});
					});
					menu.addHtmlItem("<img src='/static/selection/is/is_16.png'/> <img src='"+theme.icons_16.right+"'/> <img src='/static/selection/exam/exam_center_16.png'/> Create a center from an Information Session", function() {
						window.top.popupFrame("/static/selection/exam/exam_center_16.png", "Create Exam Center From Information Session", "/dynamic/selection/page/exam/create_center_from_is?onsaved=saved", null, null, null, function(frame,pop) {
							frame.saved = refreshPage;
						});
					});
					menu.showBelowElement(button);
				});
			}

			function loadExamCenterStatus() {
				var container = document.getElementById('exam_status');
				container.innerHTML = "<center><img src='"+theme.icons_16.loading+"'/></center>";
				service.html("selection","exam/status",null,container);
			}

			function refreshPage() {
				dl.reloadData();
				loadExamCenterStatus();
			}
		</script>				
		<?php 
	}
}