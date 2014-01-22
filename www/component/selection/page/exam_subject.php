<?php 
require_once("selection_page.inc");
require_once("exam_subject.inc");
class page_exam_subject extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(&$page){
		$container_id = $page->generateID();
		$page->add_javascript("/static/widgets/page_header.js");
		$page->onload("var header = new page_header('exam_subject_header',true); header.setTitle('Exam Subject');");
		$id = null;
		if(!isset($_GET["id"]))
			$id = -1;
		else if($_GET["id"] == "-1")
			$id = -1;
		else
			$id = $_GET["id"];
		$campaign_id = null;
		if(isset($_GET["campaign_id"]))
			$campaign_id = $_GET["campaign_id"];
		if(isset($_GET["readonly"]))
			$read_only = $_GET["readonly"];
		else
			$read_only = false;
	?>
		<div id = 'exam_subject_header'>
			<!--<div id = "rename_exam_subject"></div>
			<div id = "edit_exam_subject"></div>
			<div id = "unedit_exam_subject"></div>
			<div id = "unedit_exam_subject"></div>
			<div id = "save_exam_subject"></div>
			<div id = "export_exam_subject"></div>-->
		</div>
		<div id = '<?php echo $container_id; ?>'></div>
	
	<?php
		exam_subject($page,$container_id,$id,$campaign_id,$read_only);
	}
	
}

