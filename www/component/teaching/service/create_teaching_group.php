<?php 
class service_create_teaching_group extends Service {
	
	public function getRequiredRights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Create a new subject teaching, with the given groups of students"; }
	public function inputDocumentation() { echo "subject, groups"; }
	public function outputDocumentation() { echo "id: the created SubjectTeaching"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		// TODO check subject and groups are valid
		$id = SQLQuery::create()->bypassSecurity()->insert("SubjectTeaching", array("subject"=>$input["subject"]));
		$list = array();
		foreach ($input["groups"] as $gid)
			array_push($list, array("subject_teaching"=>$id, "group"=>$gid));
		SQLQuery::create()->insertMultiple("SubjectTeachingGroups", $list);
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "{id:$id}";
		}
	}
	
}
?>