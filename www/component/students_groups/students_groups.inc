<?php 
class students_groups extends Component {
	
	/**
	 * Retrieve the StudentsGroupType information from database
	 * @param integer $group_type_id the requested group type
	 * @return array the row from the database
	 */
	public function getGroupType($group_type_id) {
		return SQLQuery::create()->select("StudentsGroupType")->where("id", $group_type_id)->executeSingleRow();
	}
	/**
	 * Retrieve a list of StudentsGroupType
	 * @param integer[] $group_type_ids ids
	 * @return array rows
	 */
	public function getGroupTypes($group_type_ids) {
		return SQLQuery::create()->select("StudentsGroupType")->whereIn("StudentsGroupType","id",$group_type_ids)->execute();
	}
	/**
	 * Retrieve the name of a StudentsGroupType
	 * @param integer $group_type_id the requested group type
	 * @return string the name
	 */
	public function getGroupTypeName($group_type_id) {
		return SQLQuery::create()->select("StudentsGroupType")->where("id", $group_type_id)->field("name")->executeSingleValue();
	}
	
	/**
	 * Retrieve the StudentsGroup information from database
	 * @param integer $group_id the requested group
	 * @param boolean $join_group_type if true, a join to StudentsGroupType will be done and the field group_type_name added
	 * @return array the row from the database
	 */
	public function getGroup($group_id, $join_group_type = false) {
		$q = SQLQuery::create()->select("StudentsGroup")->where("id", $group_id);
		if ($join_group_type) 
			$q->join("StudentsGroup","StudentsGroupType",array("type"=>"id"))
				->fieldsOfTable("StudentsGroup")
				->field("StudentsGroupType", "name", "group_type_name")
				;
		return $q->executeSingleRow();
	}
	/**
	 * Retrieve a list of StudentsGroup
	 * @param integer[] $groups_ids ids of the groups
	 * @return array rows from database
	 */
	public function getGroupsById($groups_ids) {
		return SQLQuery::create()->select("StudentsGroup")->whereIn("StudentsGroup","id",$groups_ids)->execute();
	}
	
	/**
	 * Get groups according to given parameters
	 * @param integer|null $type_id filter on one type
	 * @param integer|null $period_id filter on a specific period
	 * @param integer|null|boolean $spe_id filter on a specific specialization 
	 * @return array rows from database
	 */
	public function getGroups($type_id = null, $period_id = null, $spe_id = false) {
		$q = SQLQuery::create()->select("StudentsGroup");
		if ($type_id <> null) $q->whereValue("StudentsGroup","type",$type_id);
		if ($period_id <> null) $q->whereValue("StudentsGroup","period",$period_id);
		if ($spe_id !== false) $q->whereValue("StudentsGroup","specialization",$spe_id);
		return $q->execute();
	}
	
	/**
	 * Build a hierarchy of groups
	 * @param array $groups groups' rows from database
	 * @return array roots groups
	 */
	public function buildGroupTree($groups) {
		$roots = array();
		$this->buildGroupTreeLevel($roots, null, $groups);
		return $roots;
	}
	/**
	 * Put sub_groups in their parent
	 * @param array $list list of groups to fill
	 * @param integer $parent_id parent id
	 * @param array $groups all remaining groups
	 */
	private function buildGroupTreeLevel(&$list, $parent_id, &$groups) {
		for ($i = 0; $i < count($groups); $i++) {
			if ($groups[$i]["parent"] <> $parent_id) continue;
			$g = $groups[$i];
			array_splice($groups, $i, 1);
			$i--;
			$g["sub_groups"] = array();
			array_push($list, $g);
		}
		if (count($groups) > 0)
			for ($i = count($list)-1; $i >= 0; $i--)
				$this->buildGroupTreeLevel($list[$i]["sub_groups"], $list[$i]["id"], $groups);
	}
	
	/**
	 * Get the leaves of the groups tree
	 * @param array $roots roots groups
	 * @return array leaves
	 */
	public function getFinalGroupsFromTree($roots) {
		$list = array();
		foreach ($roots as $group) $this->buildFinalGroups($group, "", $list);
		return $list;
	}
	/**
	 * Build list of leaves
	 * @param array $group group
	 * @param string $path path
	 * @param array $list list of remaining groups
	 */
	private function buildFinalGroups($group, $path, &$list) {
		if (count($group["sub_groups"]) == 0) {
			$group["path"] = $path;
			array_push($list, $group);
			return;
		}
		if ($path <> "") $path .= "/";
		$path .= $group["name"];
		foreach ($group["sub_groups"] as $sg)
			$this->buildFinalGroups($sg, $path, $list);
	}
	
	/**
	 * From a list of student, attach their respective group (join to StudentGroup)
	 * @param SQLQuery $q the SELECT
	 * @param string $from_table table containing a reference to a student's people
	 * @param string $student_column foreign key to a people
	 * @param boolean $join_group_info if true, a join to StudentsGroup will be added
	 */
	public function joinStudentToGroups(&$q, $from_table, $student_column, $join_group_info = false) {
		$q->join($from_table, "StudentGroup", array($student_column=>"people"));
		if ($join_group_info)
			$q->join("StudentGroup", "StudentsGroup", array("group"=>"id"));
	}
	/**
	 * Get a SELECT on StudentGroup for the specific group id
	 * @param integer $group_id group id
	 * @return SQLQuery the SELECT
	 */
	public function getStudentsQueryForGroup($group_id) {
		return SQLQuery::create()->select("StudentGroup")->whereValue("StudentGroup","group",$group_id);
	}
	/**
	 * Get a SELECT on StudentGroup for the specific groups
	 * @param integer[] $groups_ids groups
	 * @param boolean $distinct if true, only one entry per group, else a row for each student will be returned
	 * @return SQLQuery the SELECT
	 */
	public function getStudentsQueryForGroups($groups_ids, $distinct = true) {
		$q = SQLQuery::create()->select("StudentGroup")->whereIn("StudentGroup","group",$groups_ids);
		if ($distinct) $q->distinct();
		return $q;
	}
	
	/**
	 * Check if the given students are attached to the given groups
	 * @param integer[] $students_ids students
	 * @param integer[] $groups_ids groups
	 * @return boolean true if all students are attached to one of the group, false if at least one student is not attached to any of the given groups
	 */
	public function areStudentsBelongingToGroups($students_ids, $groups_ids) {
		$list = SQLQuery::create()->select("StudentGroup")
			->whereIn("StudentGroup","people",$students_ids)
			->whereIn("StudentGroup","group",$groups_ids)
			->execute();
		foreach ($students_ids as $people_id) {
			$found = false;
			foreach ($list as $s) if ($s["people"] == $people_id) { $found = true; break; }
			if (!$found) return false; // student missing
		}
		return true;
	}
	
	/**
	 * Get the group of a student
	 * @param integer $people_id student
	 * @param integer[] $groups_ids groups to check
	 * @return integer|null group id
	 */
	public function getStudentGroupsAmong($people_id, $groups_ids) {
		return SQLQuery::create()
			->select("StudentGroup")
			->whereValue("StudentGroup","people",$people_id)
			->whereIn("StudentGroup","group",$groups_ids)
			->field("group")
			->executeSingleField();
	}
	
	public function assign_student_to_group($student_people_id, $group_type_id, $group_id, $period_id) {
		// check the group exists
		if ($group_id <> null) {
			$group = $this->getGroup($group_id);
			if ($group == null) { PNApplication::error("Invalid group id"); return false; }
			if ($group["period"] <> $period_id) { PNApplication::error("The group is not in the given academic period"); return false; }
			if ($group["type"] <> $group_type_id) { PNApplication::error("Invalid group and group type"); return false; }
		}
		// check the student exists
		$student = PNApplication::$instance->students->getStudent($student_people_id);
		if ($student == null) { PNApplication::error("Invalid student id"); return false; }
		// check the group specialization is compatible to student specialization
		if ($group_id <> null) {
			if ($group["specialization"] <> null && $group["specialization"] <> $student["specialization"]) { PNApplication::error("Student's specialization does not match with group's specialization"); return false; }
		}
		// get the assignment of this student for this academic period
		$assigned = SQLQuery::create()
			->select("StudentGroup")
			->whereValue("StudentGroup", "people", $student_people_id)
			->field("StudentGroup", "group", "group")
			->join("StudentGroup","StudentsGroup",array("group"=>"id"))
			->whereValue("StudentsGroup","period",$period_id)
			->whereValue("StudentsGroup","type",$group_type_id)
			->executeSingleValue();
		// do it
		if ($assigned <> null)
			SQLQuery::create()->removeKey("StudentGroup", array("people"=>$student_people_id,"group"=>$assigned));
		if ($group_id <> null)
			SQLQuery::create()->insert("StudentGroup", array("people"=>$student_people_id,"group"=>$group_id));
		return true;
	}
	
	public function getStudentGroup($people_id, $period_id, $group_type_id) {
		return SQLQuery::create()
			->select("StudentGroup")
			->whereValue("StudentGroup","people",$people_id)
			->join("StudentGroup","StudentsGroup",array("group"=>"id"))
			->whereValue("StudentsGroup","period",$period_id)
			->whereValue("StudentsGroup", "type", $group_type_id)
			->fieldsOfTable("StudentsGroup")
			->executeSingleRow()
			;
	}
	
	public function getStudentsForPeriod($period_id, $spe_id = false, $bypass_security = false) {
		$q = SQLQuery::create()->bypassSecurity($bypass_security)->select("StudentsGroup")->whereValue("StudentsGroup","period",$period_id);
		if ($spe_id !== false) $q->whereValue("StudentsGroup","specialization",$spe_id);
		$q->field("id");
		$groups_ids = $q->executeSingleField();
		return SQLQuery::create()->bypassSecurity($bypass_security)->select("StudentGroup")->whereIn("StudentGroup","group",$groups_ids)->field("StudentGroup","people")->distinct()->executeSingleField();
	}
	
	public function getStudentGroupSpecialization($people_id) {
		return SQLQuery::create()->select("StudentGroup")
			->whereValue("StudentGroup","people",$people_id)
			->join("StudentGroup","StudentsGroup",array("group"=>"id"))
			->whereNotNull("StudentsGroup", "specialization")
			->field("StudentsGroup","specialization")
			->limit(0, 1)
			->executeSingleValue();
	}
	
}
?>