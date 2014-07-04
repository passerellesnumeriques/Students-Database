<?php 
require_once("/../SelectionPage.inc");
class page_exam_eligibility_rules extends SelectionPage {
	public function getRequiredRights() { return array("see_exam_subject"); }
	public function executeSelectionPage(){
		$this->requireJavascript("vertical_layout.js");
		$this->onload("new vertical_layout('rules_page_container');");
		?>
		<div id='rules_page_container' style='width:100%;height:100%;overflow:hidden'>
			<div class='page_title'>
				Eligibility rules for written exams
			</div>
			<div layout="fill" id='rules_page_content' style="padding:10px;overflow:hidden">
			</div>
			<div class="page_footer">
			</div>
		</div>
		<?php 
	}	
}
?>