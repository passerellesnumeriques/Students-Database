<?php 
class service_get_my_calendars extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function inputDocumentation() { echo "Nothing"; }
	public function outputDocumentation() { echo "A list of calendars: {id,name,color,writable,show,icon}"; }
	public function documentation() { echo "Get the list of accessible calendars by the user"; }
	
	public function execute(&$component, $input) {
		$readable = $component->getAccessibleCalendars();
		if (count($readable) > 0) { 
			$writable = $component->getWritableCalendars();
			$owned = $component->getOwnedCalendars();
			$list = SQLQuery::create()->bypassSecurity()
				->select("Calendar")
				->whereIn("Calendar", "id", $readable)
				->join("Calendar", "UserCalendarConfiguration", array("id"=>"calendar"), null, array("user"=>PNApplication::$instance->user_management->user_id))
				->field("Calendar", "id", "id")
				->field("Calendar", "name", "name")
				->field("Calendar", "color", "default_color")
				->field("Calendar", "icon", "icon")
				->field("UserCalendarConfiguration", "color", "user_color")
				->field("UserCalendarConfiguration", "show", "show")
				->execute();
		} else {
			$writable = array();
			$list = array();
		}
		echo "[";
		$first = true;
		foreach ($list as $cal) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$cal["id"];
			echo ",name:".json_encode($cal["name"]);
			echo ",color:".json_encode($cal["user_color"] <> null ? $cal["user_color"] : $cal["default_color"]);
			echo ",writable:".json_encode(in_array($cal["id"], $writable));
			echo ",show:".($cal["show"] !== null ? ($cal["show"] == false ? "false" : "true") : "true");
			echo ",icon:".json_encode($cal["icon"]);
			echo ",removable:".json_encode(in_array($cal["id"], $owned));
			echo "}";
		}
		require_once("component/calendar/CustomCalendarPlugin.inc");
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi)
				if ($pi instanceof CustomCalendarPlugin) {
					if (!$pi->canAccess()) continue;
					if ($first) $first = false; else echo ",";
					echo "{";
					echo "id:".json_encode($pi->getId());
					echo ",name:".json_encode($pi->getName());
					echo ",color:".json_encode($pi->getDefaultColor());
					echo ",writable:false";
					echo ",show:true";
					echo ",icon:".json_encode($pi->getIcon());
					echo ",removable:false";
					echo "}";
				}
		echo "]";
	}
	
}
?>