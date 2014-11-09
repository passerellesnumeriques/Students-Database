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
	public function getGroupsById($groups_ids) {
		return SQLQuery::create()->select("StudentsGroup")->whereIn("StudentsGroup","id",$groups_ids)->execute();
	}
	
	public function getGroups($type_id = null, $period_id = null, $spe_id = false) {
		$q = SQLQuery::create()->select("StudentsGroup");
		if ($type_id <> null) $q->whereValue("StudentsGroup","type",$type_id);
		if ($period_id <> null) $q->whereValue("StudentsGroup","period",$period_id);
		if ($spe_id !== false) $q->whereValue("StudentsGroup","specialization",$spe_id);
		return $q->execute();
	}
	
	public function buildGroupTree($groups) {
		$roots = array();
		$this->buildGroupTreeLevel($roots, null, $groups);
		return $roots;
	}
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
	
	public function getFinalGroupsFromTree($roots) {
		$list = array();
		foreach ($roots as $group) $this->buildFinalGroups($group, "", $list);
		return $list;
	}
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
	
	public function joinStudentToGroups(&$q, $from_table, $student_column, $join_group_info = false) {
		$q->join($from_table, "StudentGroup", array($student_column=>"people"));
		if ($join_group_info)
			$q->join("StudentGroup", "StudentsGroup", array("group"=>"id"));
	}
	public function getStudentsQueryForGroup($group_id) {
		return SQLQuery::create()->select("StudentGroup")->whereValue("StudentGroup","group",$group_id);
	}
	public function getStudentsQueryForGroups($groups_ids, $distinct = true) {
		$q = SQLQuery::create()->select("StudentGroup")->whereIn("StudentGroup","group",$groups_ids);
		if ($distinct) $q->distinct();
		return $q;
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
	
}
?>