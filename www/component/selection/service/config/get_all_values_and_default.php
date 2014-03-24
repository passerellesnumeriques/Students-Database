<?php
class service_config_get_all_values_and_default extends Service{
	public function get_required_rights(){return array("can_access_selection_data");}
	public function input_documentation(){
		echo "<code>name</code> config name attribute";	
	}
	public function output_documentation(){
		?>Object with two attributes:
		<ul>
		  <li><code>all_values</code> array of all the possible values</li>
		  <li><code>default_value</code> the default value</li>
		</ul>
		<?php
	}
	public function documentation(){
		echo "Get all the possible values for a config attribute, and its default value";
	}
	
	public function execute(&$component,$input){
		if(isset($input["name"])){
			$config = include 'component/selection/config.inc';
			foreach ($config as $name => $data){
				if($name == $input["name"]){
					echo "{all_values:[";
					$first = true;
					foreach($data[2] as $val){
						if(!$first) echo ", ";
						$first = false;
						echo json_encode($val);
					}
					echo "], default_value:".json_encode($data[1])."}";
					break;
				}
			}
		} else 
			echo "false";
	}
}	
?>