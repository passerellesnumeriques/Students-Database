<?php 
class user_config extends Component {

	public function getConfig($component, $name) {
		return SQLQuery::create()
			->select("UserConfig")
			->whereValue("UserConfig", "user", PNApplication::$instance->user_management->user_id)
			->whereValue("UserConfig", "component", $component)
			->whereValue("UserConfig", "name", $name)
			->executeSingleValue();
	}
	
	public function saveConfig($component, $name, $value) {
		$key = array(
			"user"=>PNApplication::$instance->user_management->user_id,
			"component"=>$component,
			"name"=>$name,
		);
		$fields = array("value"=>$value);
		// check if exists
		$rows = SQLQuery::create()->select("UserConfig")->whereKey("UserConfig", $key)->execute(); 
		if (count($rows) > 0)
			SQLQuery::create()->updateByKey("UserConfig",$key,$fields);
		else
			SQLQuery::create()->insert("UserConfig", array_merge($key, $fields));
	}
	
}
?>