<?php 
class service_new_group extends Service {
	
	public function getRequiredRights() { return array("manage_batches"); }
	
	public function documentation() { echo "Create a new class within the given academic periods"; }
	public function inputDocumentation() { echo "name, specialization, period, parent, type"; }
	public function outputDocumentation() { echo "On success, returns the <code>id</code>: the id of the newly created group"; }
	
	public function execute(&$component, $input) {
		$type = SQLQuery::create()->bypassSecurity()->select("StudentsGroupType")->whereValue("StudentsGroupType","id",$input["type"])->executeSingleRow();
		if ($type == null) {
			PNApplication::error("Invalid group type");
			return;
		}
		if (isset($input["parent"])) {
			if ($type["sub_groups"] <> 1) {
				PNApplication::error("A group ".$type["name"]." cannot have sub groups");
				return;
			}
			$parent = SQLQuery::create()->bypassSecurity()->select("StudentsGroup")->whereValue("StudentsGroup","id",$input["parent"])->executeSingleRow();
			if ($parent == null) {
				PNApplication::error("Invalid parent group");
				return;
			}
		}
		if ($type["specialization_dependent"] <> 1) {
			if (isset($input["specialization"])) {
				PNApplication::error("A group ".$type["name"]." cannot be inside a specialization");
				return;
			}
		}
		$name = trim($input["name"]);
		if ($name == "") {
			PNApplication::error("Name missing to create a group");
			return;
		}
		$same = SQLQuery::create()->select("StudentsGroup")
			->whereValue("StudentsGroup","parent",@$input["parent"])
			->whereValue("StudentsGroup","type",$input["type"])
			->whereValue("StudentsGroup","period",$input["period"])
			->whereValue("StudentsGroup","specialization",@$input["specialization"])
			->where("`name` LIKE '".SQLQuery::escape($input["name"])."'")
			->executeSingleRow();
		if ($same <> null) {
			PNApplication::error("This group already exists");
			return;
		}
		$id = SQLQuery::create()->insert("StudentsGroup", array(
			"name"=>$input["name"],
			"type"=>$type["id"],
			"period"=>$input["period"],
			"specialization"=>@$input["specialization"],
			"parent"=>@$input["parent"]
		));
		echo "{id:".$id."}";
	}
	
}
?>