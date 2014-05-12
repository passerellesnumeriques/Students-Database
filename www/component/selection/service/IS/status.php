<?php
if(!function_exists("getFigure")){
	function getFigure ($db_array){
		$total = 0;
		if(isset($db_array[0])){
			$total = 0;
			foreach($db_array as $value){
				$fig = intval($value);
				$total = $total + $fig;
			}
		}
		return $total;
	}
}
class service_IS_status extends Service{
	public function getRequiredRights(){return array("can_access_selection_data");}
	public function inputDocumentation(){
		echo "No";
	}
	public function outputDocumentation(){
		?>Returns a JSON object
		<ul>
			<li><code>boys_real</code> {number} number of boys real</li>
			<li><code>boys_expected</code> {number} number of boys expected</li>
			<li><code>girls_real</code> {number} number of girls real</li>
			<li><code>girls_expected</code> {number} number of girls expected</li>
			<li><code>partners</code> {number} number of partners selected</li>
			<li><code>number_IS</code> {number} number of IS existing</li>
			<li><code>separate_boys_girls</code> {boolean} true if the girls figures are separated from boys ones</li>
			<li><code>IS_no_host</code> {null | array} null if all the IS have an host set, else array contaning objects about all the IS with no host: [{id:, name:},...]</li> 
		</ul>
		<?php
	}
	public function documentation(){
		echo "This service returns statistics to be displayed on the selection main page <br/> so anyone who is allowed to access selection data must <br/> be allowed to get these data: to avoid any problem, each request is done using bypass_security";
	}
	public function execute(&$component,$input){
		$separate_boys_girls = PNApplication::$instance->selection->getOneConfigAttributeValue("separate_boys_girls_IS");
		$boys_real = null;
		$boys_expected = null;
		$girls_real = null;
		$girls_expected = null;
		$partners = null;
		
		$all_boys_real = SQLQuery::create()
					->bypassSecurity()
					->select("InformationSession")
					->field("number_boys_real")
					->executeSingleField();
		
		$boys_real = getFigure($all_boys_real);
		
		$all_boys_expected = SQLQuery::create()
					->bypassSecurity()
					->select("InformationSession")
					->field("number_boys_expected")
					->executeSingleField();
		
		$boys_expected = getFigure($all_boys_expected);
		
		$all_girls_real = SQLQuery::create()
					->bypassSecurity()
					->select("InformationSession")
					->field("number_girls_real")
					->executeSingleField();
		
		$girls_real = getFigure($all_girls_real);
		
		$all_girls_expected = SQLQuery::create()
					->bypassSecurity()
					->select("InformationSession")
					->field("number_girls_expected")
					->executeSingleField();
					
		$girls_expected = getFigure($all_girls_expected);
					
		$partners = SQLQuery::create()
					->bypassSecurity()
					->select("InformationSessionPartner")
					->count()
					->executeSingleValue();
					
		$number_IS = SQLQuery::create()
					->bypassSecurity()
					->select("InformationSession")
					->count()
					->executeSingleValue();
		
		$IS_with_no_host = PNApplication::$instance->selection->getAllISWithNoHost();

		echo "{boys_real:".json_encode($boys_real).", ";
		echo "boys_expected:".json_encode($boys_expected).", ";
		echo "girls_real:".json_encode($girls_real).", ";
		echo "girls_expected:".json_encode($girls_expected).", ";
		echo "partners:".json_encode($partners).", ";
		echo "number_IS:".json_encode($number_IS).", ";
		echo "separate_boys_girls:".json_encode($separate_boys_girls).", ";
		if($IS_with_no_host <> null){
			echo "IS_no_host:[";
			$first = true;
			foreach ($IS_with_no_host as $is){
				if(!$first)
					echo ", ";
				$first = false;
				echo "{id:".json_encode($is["id"]).", name:".json_encode($is["name"])."}";
			}
			echo "]}";
		} else 
		echo "IS_no_host:null}";
	}
}	
?>