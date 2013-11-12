<?php
class service_save_config extends Service{
	public function get_required_rights(){return array();}
	public function input_documentation(){
?>
<ul>
	<li><code>where</code>: {array} [{col1:val1},{col2:val2},...]</li>
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
		$old_config = @$input["old_config"];
		$final_fields = array();
		foreach($fields as $f){
			$name = null;
			$val = null;
			foreach($f as $index => $value){
				if($index == "name") $name = $value;
				if($index == "value") $val = $value;
			}
			$final_fields[$name] = $val;
		}
		
		
		
		/* Check if the Selection_campaign_config is empty */
		$q_is_empty = SQLQuery::create()->select("");
		$is_empty = $q_is_empty->execute();
		try{
			if($is_empty <> null){
				$final_old_config = array();
				foreach($old_config as $f){
					$name = null;
					$val = null;
					foreach($f as $index => $value){
						if($index == "name") $name = $value;
						if($index == "value") $val = $value;
					}
					$final_old_config[$name] = $val;
				}
			/* This is an update */
				$q_update = SQLQuery::create()->update("Selection_campaign_config", $final_fields, $final_old_config);
			} else {
			/* This is an insert */
				$q_insert = SQLQuery::create()->insert("Selection_campaign_config",$final_fields);
				//TODO: insert method try to get the id of the inserted row: create any problem??
			}
		} catch(Exception $e) {
			PNApplication::error($e->getMessage());
		}
		echo PNApplication::has_errors() ? "false" : "true";
	}
}	
?>