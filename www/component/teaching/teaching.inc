<?php 
class teaching extends Component {

	/**
	 * Retrieve the StudentsGroupType information from database
	 * @param integer $group_type_id the requested group type
	 * @return array the row from the database
	 */
	public function getGroupType($group_type_id) {
		return SQLQuery::create()->select("StudentsGroupType")->where("id", $group_type_id)->executeSingleRow();
	}
	
	/**
	 * Retrieve the StudentsGroup information from database
	 * @param integer $group_id the requested group
	 * @return array the row from the database
	 */
	public function getGroup($group_id) {
		return SQLQuery::create()->select("StudentsGroup")->where("id", $group_id)->executeSingleRow();
	}
	
}
?>