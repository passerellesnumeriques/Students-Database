<?php 
class service_copy_classes extends Service {
	
	public function get_required_rights() { return array("manage_batches"); }
	
	public function documentation() { echo "Copy classes from an academic period to another"; }
	public function input_documentation() { echo "<code>source_period</code>, <code>target_period</code>: the periods' id"; }
	public function output_documentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$source = SQLQuery::create()->select("AcademicClass")->where("period", $input["source_period"])->execute();
		foreach ($source as $s) {
			$t = array();
			$t["period"] = $input["target_period"];
			$t["specialization"] = $s["specialization"];
			$t["name"] = $s["name"];
			SQLQuery::create()->insert("AcademicClass", $t);
		}
		echo "true";
	}
	
}
?>