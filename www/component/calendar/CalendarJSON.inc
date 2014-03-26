<?php
/**
 * Provides functionalities to convert structures between JSON and database
 */ 
class CalendarJSON {
	
	/**
	 * Retrieve a CalendarEvent JSON object from the database, after checking that the user is allowed to
	 * @param number $id CalendarEvent ID
	 * @param number $calendar_id Calendar ID
	 * @returns String CalendarEvent JSON
	 */
	public static function CalendarEventFromID($id, $calendar_id){
		//Check the right
		if (!PNApplication::$instance->calendar->canReadCalendar($calendar_id)) {
			PNApplication::error("Access denied");
			return false;
		}
		$q = SQLQuery::create()
			->bypassSecurity()
			->select("CalendarEvent")
			->whereValue("CalendarEvent", "id", $id);
		self::CalendarEventSQL($q);
		$row = $q->executeSingleRow();
		return self::CalendarEvent($q, $row);
	}
	
	/**
	 * Prepare a query to retrieve the data from CalendarEvent table
	 * @param SQLQuery $q
	 */
	public static function CalendarEventSQL(&$q){
		$alias = $q->getTableAlias("CalendarEvent");
		if($alias == null)
			$alias = "CalendarEvent";
		$q
			->field($alias,"id")
			->field($alias,"uid")
			->field($alias,"calendar")
			->field($alias,"start")
			->field($alias,"end")
			->field($alias,"all_day")
			->field($alias,"last_modified")
			->field($alias,"title")
			->field($alias,"description")
			->field($alias,"location_freetext")
			->field($alias,"organizer")
			->field($alias,"participation")
			->field($alias,"role")
			->field($alias,"app_link")
			->field($alias,"app_link_name");
	}
	
	/**
	 * Get a CalendarEvent JSON object from the data retrieved from the database
	 * @param SQLQuery $q
	 * @param Array $row the row retrieved from CalendarEvent table
	 * @return string CalendarEvent JSON object
	 */
	public static function CalendarEvent($q, $row){
		$data = "{";
		$data .= "id:".$row["id"];
		$data .= ",calendar_provider_id:'PN'";
		$data .= ",calendar_id:".$row["calendar"];
		$data .= ",uid:".json_encode($row["uid"]);
		$data .= ",start:".$row["start"];
		$data .= ",end:".$row["end"];
		$data .= ",all_day:".($row["all_day"] == "1" ? "true" : "false");
		$data .= ",last_modified:".json_encode($row["last_modified"]);
		$data .= ",title:".json_encode($row["title"]);
		$data .= ",description:".json_encode($row["description"]);
		$data .= ",location_freetext:".json_encode($row["location_freetext"]);
		$data .= ",organizer:".json_encode($row["organizer"]);
		$data .= ",participation:".json_encode($row["participation"]);
		$data .= ",role:".json_encode($row["role"]);
		$data .= ",app_link:".json_encode($row["app_link"]);
		$data .= ",app_link_name:".json_encode($row["app_link_name"]);
		$data .= "}";
		return $data;
	}
	
}
?>