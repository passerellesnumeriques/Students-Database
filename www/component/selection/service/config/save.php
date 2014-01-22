<?php
class service_config_save extends Service{
	public function get_required_rights(){return array();}
	public function input_documentation(){
?>
<ul>
	<li><code>fields</code>: config object of the page manage_config.inc</li>
</ul>
<?php	
	}
	public function output_documentation(){
		echo "<ul>";
		echo "<li>{boolean} true if done</li>";
		echo "<li>{boolean} else false</li>";
		echo "</ul>";
	}
	public function documentation(){}//TODO
	
	public function execute(&$component,$input){
		$fields = @$input["fields"];
		$final_fields = array();
		foreach($fields as $f){
			$name = null;
			$val = null;
			foreach($f as $index => $value){
				if($index == "name") $name = $value;
				if($index == "value") $val = json_encode($value);
			}
			$final_fields[$name] = $val;
		}
		$error = PNApplication::$instance->selection->save_config($final_fields);
		if($error <> null) PNApplication::error($configs);
		echo PNApplication::has_errors() ? "false" : "true";
	}
}	
?>