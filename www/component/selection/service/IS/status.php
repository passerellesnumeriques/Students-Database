<?php
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
class service_IS_status extends Service{
	public function get_required_rights(){return array();}
	public function input_documentation(){
	}
	public function output_documentation(){
		"<ul><li>{boys_real:, ";
		echo "boys_expected:, ";
		echo "girls_real:, ";
		echo "girls_expected:, ";
		echo "partners:, ";
		echo "number_IS:";
		echo "separate_boys_girls:}</li></ul>";
	}
	public function documentation(){
		?>
		<ul>
			<li>This service returns statistics to be displayed on the selection main page <br/> so anyone who is allowed to access selection data must <br/> be allowed to get these data: to avoid any problem, each request is done using bypass_security</li>
		</ul>
		<?php
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
					->select("Information_session")
					->field("number_boys_real")
					->executeSingleField();
		
		$boys_real = getFigure($all_boys_real);
		
		$all_boys_expected = SQLQuery::create()
					->bypassSecurity()
					->select("Information_session")
					->field("number_boys_expected")
					->executeSingleField();
		
		$boys_expected = getFigure($all_boys_expected);
		
		$all_girls_real = SQLQuery::create()
					->bypassSecurity()
					->select("Information_session")
					->field("number_girls_real")
					->executeSingleField();
		
		$girls_real = getFigure($all_girls_real);
		
		$all_girls_expected = SQLQuery::create()
					->bypassSecurity()
					->select("Information_session")
					->field("number_girls_expected")
					->executeSingleField();
					
		$girls_expected = getFigure($all_girls_expected);
					
		$partners = SQLQuery::create()
					->bypassSecurity()
					->select("Information_session_partner")
					->count()
					->executeSingleValue();
					
		$number_IS = SQLQuery::create()
					->bypassSecurity()
					->select("Information_session")
					->count()
					->executeSingleValue();

		echo "{boys_real:".json_encode($boys_real).", ";
		echo "boys_expected:".json_encode($boys_expected).", ";
		echo "girls_real:".json_encode($girls_real).", ";
		echo "girls_expected:".json_encode($girls_expected).", ";
		echo "partners:".json_encode($partners).", ";
		echo "number_IS:".json_encode($number_IS).", ";
		echo "separate_boys_girls:".json_encode($separate_boys_girls)."}";
	}
}	
?>