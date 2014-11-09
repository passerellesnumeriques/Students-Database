<?php 
class service_update_teaching_groups extends Service {
	
	public function getRequiredRights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Update list of groups for the given SubjectTeaching"; }
	public function inputDocumentation() { echo "subject_teaching, groups"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		// TODO check input
		$rows = SQLQuery::create()->select("SubjectTeachingGroups")->whereValue("SubjectTeachingGroups","subject_teaching",$input["subject_teaching"])->execute();
		SQLQuery::create()->removeRows("SubjectTeachingGroups", $rows);
		$list = array();
		foreach ($input["groups"] as $gid)
			array_push($list, array("subject_teaching"=>$input["subject_teaching"], "group"=>$gid));
		SQLQuery::create()->insertMultiple("SubjectTeachingGroups", $list);
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>