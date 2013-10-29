<?php 
require_once("students_list.inc");
class page_batch_list extends page_students_list {
	
	public function execute() {
		$batch = SQLQuery::create()->select("StudentBatch")->where("id",$_GET["batch"])->execute_single_row();
		$this->create_list("/static/students/batch_16.png", $batch["name"], "[{category:'Student',name:'Batch',data:{value:".$batch["id"]."}}]");
	}
		
}
?>