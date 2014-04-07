<?php 
/**
 * Provides functionalities to convert structures between JSON and database
 */
require_once 'component/people/PeopleJSON.inc';
class StaffJSON {
	
	/**
	 * Get a JSON staff object from its ID
	 * @param number $id staff ID (people one)
	 * @no_name_check
	 */
	public static function StaffFromID($id){
		$q = SQLQuery::create()
			->select("Staff")
			->whereValue("Staff", "people", $id);
		self::StaffSQL($q);
		$row = $q->executeSingleRow();
		return self::Staff($q, $row);
	}
	
	/**
	 * Prepare the query to retrieve the fields from Staff table
	 * @param SQLQuery $q
	 * @no_name_check
	 */
	public static function StaffSQL(&$q){
		//Add the people fields
		PNApplication::$instance->people->joinPeople($q, "Staff", "people");
	}
	
	/**
	 * Prepare the query to retrieve the fields from StaffPosition table
	 * @param SQLQuery $q
	 * @no_name_check
	 */
	public static function StaffPositionSQL(&$q){
		//Add the staff position fields
		$alias = $q->getTableAlias("StaffPosition");
		if($alias == null)
			$alias = "StaffPosition";
		$q
			->field($alias,"position")
			->field($alias,"start")
			->field($alias,"end");
	}
	
	/**
	 * Prepare the query to retrieve the fields from StaffDepartment table
	 * @param SQLQuery $q
	 * @no_name_check
	 */
	public static function StaffDepartmentSQL(&$q){
		//Add the staff department field
		$alias = $q->getTableAlias("PNDepartment");
		if($alias == null)
			$alias = "PNDepartment";
		$q
			->field($alias,"name");
	}
	
	/**
	 * Get a JSON staff object from the rows retrieved from database
	 * @param SQLQuery $q query used to get the staff data
	 * @param array $row row retrieved from database containing the data retrieved
	 * @no_name_check
	 */
	public static function Staff($q, $row){
		$json = "{people:";
		$json .= PeopleJSON::People($q,$row);
		$json .= "}";
		return $json;
	}
	
}

?>