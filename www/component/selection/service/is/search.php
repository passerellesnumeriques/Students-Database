<?php 
class service_is_search extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function documentation() { echo "Search for Information Sessions"; }
	public function inputDocumentation() { echo "min_date, max_date, area_id"; }
	public function outputDocumentation() { echo "List"; }
	
	public function execute(&$component, $input) {
		$q = SQLQuery::create()->select("InformationSession");
		// join event
		PNApplication::$instance->calendar->joinCalendarEvent($q, "InformationSession", "date");
		// join geographic area
		$default_area_alias = PNApplication::$instance->geography->joinGeographicArea($q, "InformationSession", "geographic_area");
		// join partner
		$q->join("InformationSession", "InformationSessionPartner", array("id"=>"information_session",null=>array("host"=>1)));
		PNApplication::$instance->contact->joinOrganization($q, "InformationSessionPartner", "organization");
		
		// restrict event date
		if (isset($input["min_date"]) && $input["min_date"] <> null)
			$q->where("(`CalendarEvent`.`start` >= '".SQLQuery::escape($input["min_date"])."' OR `CalendarEvent`.`start` IS NULL)");
		if (isset($input["max_date"]) && $input["max_date"] <> null)
			$q->where("(`CalendarEvent`.`end` <= '".SQLQuery::escape($input["max_date"])."' OR `CalendarEvent`.`end` IS NULL)");
		
		// restrict area
		if (isset($input["area_id"]) && $input["area_id"] <> null) {
			$area = PNApplication::$instance->geography->getArea($input["area_id"]);
			$q->where("(`".$default_area_alias."`.`parent`=".$area["parent"].")");
		}
		
		$q->field("InformationSession","id","is_id");
		$q->field("InformationSession","name","is_name");
		$q->field("InformationSession","date","event_id");
		$q->field("CalendarEvent","start","start_date");
		$q->field("InformationSession","geographic_area","geographic_area_id");
		$q->field("GeographicArea","name","geographic_area_name");
		$q->field("Organization","name","partner_name");
		
		$list = $q->execute();
		
		if (count($list) > 0) {
			$ids = array();
			foreach ($list as $is) array_push($ids, $is["is_id"]);
			$q = SQLQuery::create()->select("InformationSessionAnimator")->whereIn("InformationSessionAnimator","information_session",$ids);
			PNApplication::$instance->people->joinPeople($q, "InformationSessionAnimator", "people", false);
			$q->field("InformationSessionAnimator","information_session","is");
			$q->field("InformationSessionAnimator","custom_name","custom_name");
			$who = $q->execute();
		} else
			$who = array();
		
		echo "[";
		$first = true;
		foreach ($list as $is) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$is["is_id"];
			echo ",name:".json_encode($is["is_name"]);
			echo ",event_id:".json_encode($is["event_id"]);
			echo ",start_date:".json_encode($is["start_date"]);
			echo ",geographic_area_id:".json_encode($is["geographic_area_id"]);
			echo ",geographic_area_name:".json_encode($is["geographic_area_name"]);
			echo ",partner_name:".json_encode($is["partner_name"]);
			echo ",who:[";
			$first_who = true;
			foreach ($who as $w) {
				if ($w["is"] <> $is["is_id"]) continue;
				if ($first_who) $first_who = false; else echo ",";
				if ($w["custom_name"] <> null) echo json_encode($w["custom_name"]);
				else echo "{people:".PeopleJSON::People($w)."}";
			}
			echo "]";
			echo "}";
		}
		echo "]";
	}
	
}
?>