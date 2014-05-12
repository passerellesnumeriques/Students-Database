<?php
class service_exam_center_status extends Service{
	public function getRequiredRights(){return array("can_access_selection_data");}
	public function inputDocumentation(){
		echo "No";
	}
	public function outputDocumentation(){
		?>Returns a JSON object
		<ul>
			<li><code>partners</code> {number} number of partners selected</li>
			<li><code>number_EC</code> {number} number of EC set</li>
			<li><code>EC_no_host</code> {null | array} null if all the EC have an host set, else array contaning objects about all the IS with no host: [{id:, name:},...]</li> 
		</ul>
		<?php
	}
	public function documentation(){
		echo "This service returns statistics to be displayed on the selection main page <br/> so anyone who is allowed to access selection data must <br/> be allowed to get these data: to avoid any problem, each request is done using bypass_security";
	}
	public function execute(&$component,$input){
		$partners = SQLQuery::create()
					->bypassSecurity()
					->select("ExamCenterPartner")
					->count()
					->executeSingleValue();
					
		$number_EC = SQLQuery::create()
					->bypassSecurity()
					->select("ExamCenter")
					->count()
					->executeSingleValue();
		
		$EC_with_no_host = PNApplication::$instance->selection->getAllECWithNoHost();

		echo "{partners:".json_encode($partners).", ";
		echo "number_EC:".json_encode($number_EC).", ";
		if($EC_with_no_host <> null){
			echo "EC_no_host:[";
			$first = true;
			foreach ($EC_with_no_host as $ec){
				if(!$first)
					echo ", ";
				$first = false;
				echo "{id:".json_encode($ec["id"]).", name:".json_encode($ec["name"])."}";
			}
			echo "]}";
		} else 
		echo "EC_no_host:null}";
	}
}	
?>