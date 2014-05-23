<?php 
/* Contains services needed for processing exam results */
class service_exam_get_exam_center_location extends Service {
	public function getRequiredRights() {} // TODO
	public function documentation() { echo "get exam center partner location "; }
	public function inputDocumentation() { 
		echo "input string : center name";
	}
	public function outputDocumentation() { echo "return subject object on success"; }
	public function execute(&$component, $input) {

		/* get exam center id from its name */	
		$q = SQLQuery::create()->select("ExamCenter")
					->field("ExamCenter","id")
					->where("name",$input);
		$ec_id=$q->executeSingleField()[0];
		if ($ec_id==null)  return; // TODO : error handling ?
		
		/* Find the address of the host partner attached to the exam center */		
		$q=SQLQuery::create()->select("ExamCenter")
					->field("ExamCenterPartner","host_address")
					->join("ExamCenter","ExamCenterPartner",array("id"=>"exam_center"),null,array("exam_center"=>$ec_id,"host"=>true));
			
		$ec_partner_host=$q->executeSingleField()[0];
		if ($ec_partner_host==null)  return; // TODO : error handling ?
		
		/* Send result on output */
		echo $ec_partner_host;
		
	}
					
	

}
?>