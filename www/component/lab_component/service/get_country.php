<?php 
class service_get_country extends Service {
	
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { return "just a service for a lab training"; }
	public function inputDocumentation() { echo "name"; }
	public function outputDocumentation() { echo "country"; }
	
	public function execute(&$component, $input) {
		
		$q = SQLQuery::create()->select("LabTable")
					->field("LabTable","country")
					->whereValue("LabTable","name",$input);
		$country=$q->executeSingleValue();
	
				
		/* return datas to client  */ 
		echo json_encode($country);
		
	}
	
}
?>