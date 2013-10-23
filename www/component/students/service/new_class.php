<?php 
class service_new_class extends Service {
	
	public function get_required_rights() { return array("manage_batches"); }
	
	public function documentation() { echo "Create a new class within the given academic periods"; }
	public function input_documentation() { echo "name, specialization, from_period, to_period"; }
	public function output_documentation() { echo "On success, returns the ids of the newly created classes"; }
	
	public function execute(&$component, $input) {
		$period = SQLQuery::get_row("AcademicPeriod", $input["from_period"]);
		$periods = array_merge( 
			array($period),
			SQLQuery::create()->select("AcademicPeriod")->where("batch",$period["batch"])->where("start_date",">",$period["start_date"])->order_by("AcademicPeriod","start_date")->execute()
		);
		$ids = array();
		foreach ($periods as $p) {
			$id = SQLQuery::create()->insert("AcademicClass", array(
				"name"=>$input["name"],
				"specialization"=>$input["specialization"],
				"period"=>$p["id"]
			));
			if ($id <> 0) array_push($ids, $id);
			if ($input["to_period"] == $p["id"]) break;
		}
		echo json_encode($ids);
	}
	
}
?>