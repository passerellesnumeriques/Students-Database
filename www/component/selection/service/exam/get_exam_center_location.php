<?php 
/* Contains services needed for processing exam results */
class service_exam_get_exam_center_location extends Service {
	public function getRequiredRights() {} // TODO
	public function documentation() { echo "get exam center partner location "; }
	public function inputDocumentation() { 
		echo "<code>id</code>: exam center id";
	}
	public function outputDocumentation() { echo "return exam center host address id"; }
	public function execute(&$component, $input) {

		/* Find the address of the host partner attached to the exam center */		
		$q=SQLQuery::create()->select("ExamCenter")
					->where("id",$input["id"])
					->join("ExamCenter","ExamCenterPartner",array("id"=>"exam_center"),null,array("host"=>true))
					->field("ExamCenterPartner","host_address")
					;
			
		$ec_partner_host=$q->executeSingleValue();
		/* Send result on output */
		echo $ec_partner_host;
		
	}

}
?>