<?php
class service_save_IS extends Service{
	public function get_required_rights(){return array();}
	public function input_documentation(){
	?>
	<ul>
		<li>id: optional </li>
		<li>date: optional </li>
		<li>number_expected: optional </li>
		<li>number_real: optional </li>
		<li>name: optional </li>
	</ul>
	<?php
	}
	public function output_documentation(){
	?>
	<ul>
		<li> It was an insert:
			<ul>
				<li> id: the id generated </li>
				<li> boolean: false if an error occured </li>
			</ul>
		</li>
		<li> It was an update:
			<ul>
				<li> boolean: false if an error occured, else true </li>
			</ul>
		</li>
		
		
	</ul>
	<?php
	}
	public function documentation(){
		echo "Service that save an information session. All the parameters are optional, but nothing will be done in case input is an empty array.<br/>";
		echo "If an id is given, an update is done. Otherwise, an insert is performed.";
	}
	public function execute(&$component,$input){
		$data = array();
		if(isset($input["date"])) $data["date"] = $input["date"];
		if(isset($input["number_expected"])) $data["number_expected"] = $input["number_expected"];
		if(isset($input["number_real"])) $data["number_real"] = $input["number_real"];
		if(isset($input["name"])) $data["name"] = $input["name"];
		if(count($data) > 0){
			if(!isset ($input["id"])){
				$id = PNApplication::$instance->selection->save_IS(null,$data);
				if(PNApplication::has_errors()) echo "false";
				else echo "{id:".$id."}";
			} else {
				PNApplication::$instance->selection->save_IS($input["id"],$data);
				if(PNApplication::has_errors()) echo "false";
				else echo "true";
			}
		} else echo "false";
	}
}	
?>