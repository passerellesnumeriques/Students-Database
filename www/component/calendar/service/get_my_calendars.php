<?php 
class service_get_my_calendars extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function input_documentation() { echo "Nothing"; }
	public function output_documentation() { echo "A list of calendars: {id,name,color,writable}"; }
	public function documentation() { echo "Get the list of accessible calendars by the user"; }
	
	public function execute(&$component, $input) {
		$readable = $component->getAccessibleCalendars();
		$writable = $component->getWritableCalendars();
		$list = SQLQuery::create()->bypass_security()->select("Calendar")->where_in("Calendar", "id", $readable)->execute();
		echo "[";
		$first = true;
		foreach ($list as $cal) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$cal["id"];
			echo ",name:".json_encode($cal["name"]);
			echo ",color:".json_encode($cal["color"]);
			echo ",writable:".json_encode(in_array($cal["id"], $writable));
			echo "}";
		}
		echo "]";
	}
	
}
?>