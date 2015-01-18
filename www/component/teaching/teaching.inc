<?php
/**
 * Component handling information of teachers and assigned groups
 */ 
class teaching extends Component {

	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "PeopleTypePlugin":
				require_once("TeacherPeopleTypePlugin.inc");
				return array(new TeacherPeopleTypePlugin());
			case "PeopleProfilePagePlugin":
				require_once("TeacherAssignmentsPeopleProfilePagePlugin.inc");
				return array(new TeacherAssignmentsPeopleProfilePagePlugin());
		}
		return array();
	}
	
	/**
	 * Get the subjects assigned to the given teacher among the ones given
	 * @param integer[] $subjects_ids subjects to filter
	 * @param integer $people_id teacher
	 * @return integer[] assigned subjects id
	 */
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
	
	/**
	 * Check if a teacher is assigned to given groups
	 * @param integer $people_id teacher
	 * @param integer $grouping_id SubjectTeaching id representing the groups
	 * @return boolean true if the given teacher is assigned to the given grouping
	 */
	public function isAssignedToSubjectTeaching($people_id, $grouping_id) {
		return SQLQuery::create()->select("TeacherAssignment")
			->whereValue("TeacherAssignment","people",$people_id)
			->whereValue("TeacherAssignment","subject_teaching",$grouping_id)
			->limit(0,1)
			->executeSingleRow()
			<> null;
	}

	/**
	 * Check if a teacher is assigned to the given subject, and optionnally to a specific group
	 * @param integer $people_id teacher
	 * @param integer $subject_id subject
	 * @param integer|null $group_id group
	 * @return boolean true if the teacher is assigned
	 */
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
	
	/**
	 * Check if the given teacher is assigned to the given subject with the given students
	 * @param integer $people_id teacher
	 * @param integer $subject_id subject
	 * @param integer[] $students_ids students
	 * @return boolean true if assigned
	 */
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
	
	/**
	 * Get the teachers' assignments for the given grouping
	 * @param integer $grouping_id SubjectTeaching
	 * @param boolean $include_people_info if true, a join to the table People will be done
	 * @param boolean $bypass_security avoid security checks
	 * @return array rows of TeacherAssignment, optionnally joined to People
	 */
	public function getTeachersAssignedForGrouping($grouping_id, $include_people_info = true, $bypass_security = false) {
		$q = SQLQuery::create()->bypassSecurity($bypass_security)->select("TeacherAssignment")->whereValue("TeacherAssignment","subject_teaching",$grouping_id);
		if ($include_people_info) {
			$q->fieldsOfTable("TeacherAssignment");
			PNApplication::$instance->people->joinPeople($q, "TeacherAssignment", "people", false);
		}
		return $q->execute();
	}
	
	/**
	 * Get the groups assigned to the given teacher for the given subject
	 * @param integer $teacher_id teacher
	 * @param integer $subject_id subject
	 * @return integer[] groups' ids
	 */
	public function getAssignedGroups($teacher_id, $subject_id) {
		return SQLQuery::create()
			->select("TeacherAssignment")
			->whereValue("TeacherAssignment", "people", $teacher_id)
			->join("TeacherAssignment","SubjectTeaching",array("subject_teaching"=>"id"))
			->whereValue("SubjectTeaching", "subject", $subject_id)
			->join("SubjectTeaching","SubjectTeachingGroups",array("id"=>"subject_teaching"))
			->field("SubjectTeachingGroups","group","group")
			->executeSingleField();
	}
	
	/**
	 * Get the groupings (SubjectTeaching) for the given subject
	 * @param integer $subject_id subject
	 * @param boolean $bypass_security avoid security checks
	 * @return integer[] groupings ids
	 */
	public function getGroupingsForSubject($subject_id, $bypass_security = false) {
		return SQLQuery::create()
			->bypassSecurity($bypass_security)
			->select("SubjectTeaching")
			->whereValue("SubjectTeaching","subject",$subject_id)
			->field("SubjectTeaching","id")
			->executeSingleField();
	}
	
	/**
	 * Get all the groups for the given subject 
	 * @param integer $subject_id subject
	 * @param boolean $bypass_security avoid security checks
	 * @return integer[] groups' ids
	 */
	public function getGroupsForSubject($subject_id, $bypass_security = false) {
		return SQLQuery::create()
			->bypassSecurity($bypass_security)
			->select("SubjectTeaching")
			->whereValue("SubjectTeaching","subject",$subject_id)
			->join("SubjectTeaching","SubjectTeachingGroups",array("id"=>"subject_teaching"))
			->field("SubjectTeachingGroups","group")
			->executeSingleField();
	}
	
	/**
	 * Get the groups in a grouping
	 * @param integer $subject_teaching_id grouping
	 * @param boolean $bypass_security avoid security checks
	 * @return integer[] groups' ids
	 */
	public function getGroups($subject_teaching_id, $bypass_security = false) {
		return SQLQuery::create()
			->bypassSecurity($bypass_security)
			->select("SubjectTeachingGroups")
			->whereValue("SubjectTeachingGroups","subject_teaching",$subject_teaching_id)
			->field("group")
			->executeSingleField();
	}
	
	/**
	 * Get the grouping in which the given group belongs for the given subject
	 * @param integer $subject_id subject
	 * @param integer $group_id group
	 * @param boolean $bypass_security avoid security checks
	 * @return integer grouping id (SubjectTeaching)
	 */
	public function getGroupingIdFromGroup($subject_id, $group_id, $bypass_security = false) {
		return SQLQuery::create()
			->bypassSecurity($bypass_security)
			->select("SubjectTeaching")
			->whereValue("SubjectTeaching","subject",$subject_id)
			->join("SubjectTeaching","SubjectTeachingGroups",array("id"=>"subject_teaching"))
			->whereValue("SubjectTeachingGroups","group",$group_id)
			->field("SubjectTeaching","id")
			->executeSingleValue();
	}
	
	/**
	 * Get the grouping of a student for a subject
	 * @param integer $people_id student
	 * @param integer $subject_id subject
	 * @return array group_type (row from StudentsGroupType), group (the group the student belongs to), groups_together (list of other groups)
	 */
	public function getStudentGroupingInfoForSubject($people_id, $subject_id) {
		// get list of groupings for this subject
		$groupings = SQLQuery::create()->bypassSecurity(PNApplication::$instance->user_management->people_id == $people_id)->select("SubjectTeaching")->whereValue("SubjectTeaching","subject",$subject_id)->field("id")->executeSingleField();
		if (count($groupings) == 0) return null; // no grouping for this subject
		// get groups for all groupings
		$groupings_groups = SQLQuery::create()->select("SubjectTeachingGroups")->whereIn("SubjectTeachingGroups","subject_teaching",$groupings)->execute();
		// get groups details
		$groups_ids = array();
		foreach ($groupings_groups as $g) array_push($groups_ids, $g["group"]);
		$groups = PNApplication::$instance->students_groups->getGroupsById($groups_ids);
		$group_type_id = $groups[0]["type"];
		$group_type = PNApplication::$instance->students_groups->getGroupType($group_type_id);
		if ($group_type["sub_groups"]) {
			// group type supports sub-groups => we must check if the student is in a child
			$all_groups = PNApplication::$instance->students_groups->getGroups($group_type_id, $subject["period"]);
			$check_children = true;
			while ($check_children) {
				$check_children = false;
				foreach ($all_groups as $g)
					if (in_array($g["parent"],$groups_ids)) {
						array_push($groups_ids, $g["id"]);
						array_push($groups, $g);
						$check_children = true;
					}
			}
		}
		// get group of the student
		$student_groups_ids = PNApplication::$instance->students_groups->getStudentGroupsAmong($people_id, $groups_ids);
		if (count($student_groups_ids) == 0) return null; // not belonging to any group
		
		$result = array("group_type"=>$group_type);
		$group_id = $student_groups_ids[0];
		$found = false;
		do {
			foreach ($groupings_groups as $gg)
				if ($gg["group"] == $group_id) {
					$grouping_id = $gg["subject_teaching"];
					$found = true;
					break;
				}
			if ($found) break;
			if (!$found) {
				// the student is in a sub-group, we have to go back to the parent
				foreach ($groups as $g) if ($g["id"] == $group_id) { $group_id = $g["parent"]; break; }
			}
		} while (true);
		foreach ($groups as $g) if ($g["id"] == $group_id) { $result["group"] = $g; break; }
		$result["groups_together"] = array();
		foreach ($groupings_groups as $gg)
			if ($gg["subject_teaching"] == $grouping_id && $gg["group"] <> $group_id)
				foreach ($groups as $g) if ($g["id"] == $gg["group"]) { array_push($result["groups_together"], $g); break; }
		return $result;
	}
}
?>