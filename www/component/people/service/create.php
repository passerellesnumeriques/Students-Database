<?php 
class service_create extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}

	/**
	 * @param people $component
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		// 1-check we are allowed
		$types = $input["types"];
		if (count($types) == 0) {
			PNApplication::error("No type specified");
			return;
		}
		foreach ($types as $type) {
			$pi = $component->getPeopleTypePlugin($type);
			if ($pi == null) {
				PNApplication::error("Unknown people type ".$type);
				return;
			}
			if (!($pi->canRemove())) {
				PNApplication::error("You are not allowed to create a ".$pi->getName());
				return;
			}
		}
		// 2- create entry in People
		require_once("component/data_model/Model.inc");
		$table = DataModel::get()->getTable("People");
		$display = $table->getDisplayHandler(null);
		$values = $input["tables"]["People"];
		$fields = array();
		$fields["types"] = "";
		foreach ($types as $type) {
			$fields["types"] .= "/".$type."/";
		}
		foreach ($display->getDisplayableData() as $data) {
			$value = null;
			foreach ($values as $v) if ($v["name"] == $data->getDisplayName()) { $value = $v["value"]; break; }
			$data->getFieldsToSave($value, $fields);
		}
		try {
			$people_id = SQLQuery::create()->bypassSecurity()->insert("People", $fields);
		} catch (Exception $e) {
			PNApplication::error($e);
			return;
		}
		// 3- go through plugins to create people
		require_once("component/people/PeopleCreatePlugin.inc");
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeopleCreatePlugin)) continue;
				$pi->create($input["tables"], $people_id);
			}
		}
		SQLQuery::commitTransaction();
		echo "{id:".$people_id."}";
	}
	
}
?>