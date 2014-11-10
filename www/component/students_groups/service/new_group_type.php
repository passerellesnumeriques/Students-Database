<?php 
class service_new_group_type extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Create a new group type"; }
	public function inputDocumentation() { echo "name, specialization_dependent, sub_groups"; }
	public function outputDocumentation() { echo "id of the new group type if the creation succeed"; }
	
	public function execute(&$component, $input) {
		$id = SQLQuery::create()->insert("StudentsGroupType",array(
			"name"=>$input["name"],
			"specialization_dependent"=>(@$input["specialization_dependent"] ? "1" : "0"),
			"sub_groups"=>(@$input["sub_groups"] ? "1" : "0"),
			"builtin"=>0
		));
		if ($id > 0)
			echo "{id:".$id."}";
	}
	
}
?>