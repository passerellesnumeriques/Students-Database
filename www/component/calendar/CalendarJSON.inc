<?php
/**
 * Provides functionalities to convert structures between JSON and database
 */ 
class CalendarJSON {
	
	public static function Event($id, $calendar_id) {
		//Check the right
		if (!PNApplication::$instance->calendar->canReadCalendar($calendar_id)) {
			PNApplication::error("Access denied");
			return false;
		}
		$q = SQLQuery::create()->bypassSecurity()
			->select("CalendarEvent")
			->whereValue("CalendarEvent", "id", $id)
			->whereValue("CalendarEvent", "calendar", $calendar_id) // make sure the calendar is correct, so the access right check before was correct
			->join("CalendarEvent","CalendarEventFrequency",array("id"=>"event"))
			;
		$row = $q->executeSingleRow();
		$row["attendees"] = SQLQuery::create()->bypassSecurity()->select("CalendarEventAttendee")->whereValue("CalendarEventAttendee","event",$id)->execute();
		return self::JSON($row);
	}
	
	public static function Events($ids, $calendar_id) {
		if (count($ids) == 0) return "[]";
		//Check the right
		if (!PNApplication::$instance->calendar->canReadCalendar($calendar_id)) {
			PNApplication::error("Access denied");
			return false;
		}
		$q = SQLQuery::create()->bypassSecurity()
			->select("CalendarEvent")
			->whereIn("CalendarEvent", "id", $ids)
			->whereValue("CalendarEvent", "calendar", $calendar_id) // make sure the calendar is correct, so the access right check before was correct
			->join("CalendarEvent","CalendarEventFrequency",array("id"=>"event"))
			;
		$rows = $q->execute();
		$attendees = SQLQuery::create()->bypassSecurity()->select("CalendarEventAttendee")->whereIn("CalendarEventAttendee","event",$ids)->execute();
		for ($i = 0; $i < count($rows); $i++) {
			$rows[$i]["attendees"] = array();
			foreach ($attendees as $a) if ($a["event"] == $rows[$i]["id"]) array_push($rows[$i]["attendees"], $a);
		}
		$data = "[";
		$first = true;
		foreach ($rows as $row) {
			if ($first) $first = false; else $data .= ",";
			$data .= self::JSON($row);
		}
		$data .= "]";
		return $data;
	}
	
	public static function JSON($row) {
		$data = "{";
		$data .= "id:".$row["id"];
		$data .= ",calendar_provider_id:'PN'";
		$data .= ",calendar_id:".(ctype_digit($row["calendar"]) ? $row["calendar"] : json_encode($row["calendar"]));
		$data .= ",uid:".json_encode($row["uid"]);
		$data .= ",start:new Date(".$row["start"]."*1000)";
		$data .= ",end:new Date(".$row["end"]."*1000)";
		$data .= ",all_day:".($row["all_day"] == "1" ? "true" : "false");
		$data .= ",last_modified:".json_encode($row["last_modified"]);
		$data .= ",title:".json_encode($row["title"]);
		$data .= ",description:".json_encode(@$row["description"]);
		$data .= ",location_freetext:".json_encode(@$row["location_freetext"]);
		$data .= ",app_link:".json_encode(@$row["app_link"]);
		$data .= ",app_link_name:".json_encode(@$row["app_link_name"]);
		$data .= ",frequency:";
		if (@$row["frequency"] == null) $data .= "null";
		else {
			$data .= "{";
			$data .= "frequency:".json_encode($row["frequency"]);
			$data .= ",until:".(@$row["until"] <> null ? "new Date(".$row["until"]."*1000)" : "null");
			$data .= ",count:".(@$row["count"] > 0 ? $row["count"] : "null");
			$data .= ",interval:".(@$row["interval"] > 0 ? $row["interval"] : "null");
			$data .= ",by_month:".json_encode(@$row["by_month"]);
			$data .= ",by_week_no:".json_encode(@$row["by_week_no"]);
			$data .= ",by_year_day:".json_encode(@$row["by_year_day"]);
			$data .= ",by_month_day:".json_encode(@$row["by_month_day"]);
			$data .= ",by_week_day:".json_encode(@$row["by_week_day"]);
			$data .= ",by_hour:".json_encode(@$row["by_hour"]);
			$data .= ",by_setpos:".json_encode(@$row["by_setpos"]);
			$data .= ",week_start:".json_encode(@$row["week_start"]);
			$data .= "}";
		}
		$data .= ",attendees:[";
		$first = true;
		if (@$row["attendees"] <> null)
		foreach ($row["attendees"] as $a) {
			if ($first) $first = false; else $data .= ",";
			$data .= "{";
			$data .= "name:".json_encode($a["name"]);
			$data .= ",email:".json_encode($a["email"]);
			$data .= ",participation:".json_encode($a["participation"]);
			$data .= ",role:".json_encode($a["role"]);
			$data .= ",people_id:".json_encode($a["people"]);
			$data .= ",contact_id:".json_encode($a["people_email"]);
			$data .= "}";
		}
		$data .= "]";
		$data .= "}";
		return $data;
	}
	
}
?>