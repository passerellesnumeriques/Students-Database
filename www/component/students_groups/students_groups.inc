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
	public function getGroup($group_id) {
		return SQLQuery::create()->select("StudentsGroup")->where("id", $group_id)->executeSingleRow();
	}
	
	public function joinStudentToGroups(&$q, $from_table, $student_column, $join_group_info = false) {
		$q->join($from_table, "StudentGroup", array($student_column=>"people"));
		if ($join_group_info)
			$q->join("StudentGroup", "StudentsGroup", array("group"=>"id"));
	}
	
}
?>