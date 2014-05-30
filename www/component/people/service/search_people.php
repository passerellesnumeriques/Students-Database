<?php 
class service_search_people extends Service {
	
	public function getRequiredRights() { return array("see_other_people_details"); }
	public function documentation() { echo "Search for people similar to the one given"; }
	public function inputDocumentation() { echo "The input contains the People table fields to search"; }
	public function outputDocumentation() { echo "The output is a list of People objects"; }
	
	public function execute(&$component, $input) {
		// first, strict to all fields
		$q = SQLQuery::create()->select("People");
		foreach ($input as $name=>$value) if ($value <> null) $q->where($name, $value);
		$result = $q->execute();
		// then, very similar to all fields
		$q = SQLQuery::create()->select("People");
		foreach ($input as $name=>$value) if ($value <> null) $q->where("`".SQLQuery::escape($name)."` LIKE '%".SQLQuery::escape($value)."%'");
		$res = $q->execute();
		foreach ($res as $row) {
			$found = false;
			foreach ($result as $r) if ($r["id"] == $row["id"]) { $found = true; break; }
			if (!$found) array_push($result, $row);
		}
		// TODO continue;
		echo "[";
		$first = true;
		$table = DataModel::get()->getTable("People");
		$display = $table->getDisplayHandler(null);
		foreach ($result as $row) {
			if ($first) $first = false; else echo ",";
			echo "[";
			$f = true;
			foreach ($display->getDisplayableData() as $data) {
				if ($f) $f = false; else echo ",";
				echo "{name:".json_encode($data->getDisplayName()).",value:".json_encode($data->getData($row["id"], null, $row))."}";
			}
			echo "]";
		}
		echo "]";
	}
	
}
?>