<?php 
class teaching extends Component {

	public function getPluginImplementations() {
		require_once("TeacherPeopleTypePlugin.inc");
		require_once("TeacherAssignmentsPeopleProfilePagePlugin.inc");
		return array(
				new TeacherPeopleTypePlugin(),
				new TeacherAssignmentsPeopleProfilePagePlugin(),
		);
	}
	
	public function filterAssignedSubjects($subjects_ids, $people_id) {
		return SQLQuery::create()
			->select("SubjectTeaching")
			->whereIn("SubjectTeaching","subject",$subjects_ids)
			->join("SubjectTeaching","TeacherAssignment",array("id"=>"subject_teaching",null=>array("people"=>$people_id)))
			->whereValue("TeacherAssignment","people",$people_id) // ensure not null
			->field("SubjectTeaching","subject","subject")
			->distinct()
			->executeSingleField();
	}
	
	public function isAssignedToSubjectTeaching($people_id, $grouping_id) {
		return SQLQuery::create()->select("TeacherAssignment")
			->whereValue("TeacherAssignment","people",$people_id)
			->whereValue("TeacherAssignment","subject_teaching",$grouping_id)
			->limit(0,1)
			->executeSingleRow()
			<> null;
	}

	public function isAssignedToSubject($people_id, $subject_id, $group_id = null) {
		$q = SQLQuery::create()
			->select("SubjectTeaching")
			->whereValue("SubjectTeaching","subject",$subject_id);
		if ($group_id <> null)
			$q->join("SubjectTeaching","SubjectTeachingGroups",array("id"=>"subject_teaching",null=>array("group"=>$group_id)))->whereValue("SubjectTeachingGroups","group",$group_id);
		$q->join("SubjectTeaching","TeacherAssignment",array("id"=>"subject_teaching",null=>array("people"=>$people_id)))->whereValue("TeacherAssignment","people",$people_id);
		$q->limit(0,1);
		return $q->executeSingleRow() <> null;
	}
	
	public function isAssignedToSubjectAndStudents($people_id, $subject_id, $students_ids) {
		$groups_ids = SQLQuery::create()
			->select("SubjectTeaching")
			->whereValue("SubjectTeaching","subject",$subject_id)
			->join("SubjectTeaching","TeacherAssignment",array("id"=>"subject_teaching",null=>array("people"=>$people_id)))
			->whereValue("TeacherAssignment","people",$people_id)
			->join("SubjectTeaching","SubjectTeachingGroups",array("id"=>"subject_teaching"))
			->field("SubjectTeachingGroups","group")
			->distinct()
			->executeSingleField();
		return PNApplication::$instance->students_groups->areStudentsBelongingToGroups($students_ids, $groups_ids);
	}
	
	public function getTeachersAssignedForGrouping($grouping_id, $include_people_info = true) {
		$q = SQLQuery::create()->select("TeacherAssignment")->whereValue("TeacherAssignment","subject_teaching",$grouping_id);
		if ($include_people_info) {
			$q->fieldsOfTable("TeacherAssignment");
			PNApplication::$instance->people->joinPeople($q, "TeacherAssignment", "people", false);
		}
		return $q->execute();
	}
	
	public function getGroupingsForSubject($subject_id) {
		return SQLQuery::create()
			->select("SubjectTeaching")
			->whereValue("SubjectTeaching","subject",$subject_id)
			->field("SubjectTeaching","id")
			->executeSingleField();
	}
	
	public function getGroups($subject_teaching_id) {
		return SQLQuery::create()
			->select("SubjectTeachingGroups")
			->whereValue("SubjectTeachingGroups","subject_teaching",$subject_teaching_id)
			->field("group")
			->executeSingleField();
	}
	
	public function getGroupingIdFromGroup($subject_id, $group_id) {
		return SQLQuery::create()
			->select("SubjectTeaching")
			->whereValue("SubjectTeaching","subject",$subject_id)
			->join("SubjectTeaching","SubjectTeachingGroups",array("id"=>"subject_teaching"))
			->whereValue("SubjectTeachingGroups","group",$group_id)
			->field("SubjectTeaching","id")
			->executeSingleValue();
	}
	
}
?>