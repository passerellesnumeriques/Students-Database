<?php 
class service_search_people extends Service {
	
	public function get_required_rights() { return array("see_other_people_details"); }
	public function documentation() { echo "Search for people similar to the one given"; }
	public function input_documentation() { echo "The input contains the People table fields to search"; }
	public function output_documentation() { echo "The output is a list of People objects"; }
	
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
		foreach ($result as $row) {
			if ($first) $first = false; else echo ",";
			echo "{";
			$f = true;
			foreach ($table->getDisplayableData() as $col_name=>$disp) {
				if ($f) $f = false; else echo ",";
				echo $col_name.":".json_encode($row[$col_name]);
			}
			echo "}";
		}
		echo "]";
	}
	
}
?>