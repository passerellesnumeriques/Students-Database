<?php 
class service_interview_save_rule extends Service {
	
	public function getRequiredRights() { return array("manage_interview_criteria"); }
	
	public function documentation() { echo "Save an eligibility rule for interview"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>id</code>: rule's ID, or -1 for a new one</li>";
		echo "<li><code>parent</code>: parent rule's ID, or null for a root rule</li>";
		echo "<li><code>expected</code>: minimum total grade expected</li>";
		echo "<li><code>criteria</code>: list of ExamEligibilityRuleCriterion defining the criterion with it's coefficient</li>";
		echo "</ul>";
	}
	public function outputDocumentation() {
		echo "<code>id</code> the ID of the rule";
	}
	
	public function execute(&$component, $input) {
		$id = intval($input["id"]);
		$parent = $input["parent"];
		$criteria = $input["criteria"];
		
		SQLQuery::startTransaction();
		if ($id > 0) {
			SQLQuery::create()->updateByKey("InterviewEligibilityRule", $id, array("expected"=>$input["expected"]));
		} else
			$id = SQLQuery::create()->insert("InterviewEligibilityRule", array("parent"=>$parent,"expected"=>$input["expected"]));
		
		$rows = SQLQuery::create()->select("InterviewEligibilityRuleCriterion")->whereValue("InterviewEligibilityRuleCriterion", "rule", $id)->execute();
		if (count($rows) > 0)
			SQLQuery::create()->removeRows("InterviewEligibilityRuleCriterion", $rows);
		$to_insert = array();
		foreach ($criteria as $c) {
			if ($c["criterion"] == null || $c["criterion"] == "") {
				PNApplication::error("Invalid value");
				return;
			}
			$coef = floatval($c["coefficient"]);
			if ($coef <= 0) {
				PNApplication::error("Invalid coefficient");
				return;
			}
			array_push($to_insert, array(
				"rule"=>$id,
				"criterion"=>$c["criterion"],
				"coefficient"=>$coef
			));
		}
		SQLQuery::create()->insertMultiple("InterviewEligibilityRuleCriterion", $to_insert);
		if (PNApplication::hasErrors())
			return;
		SQLQuery::commitTransaction();
		echo "{id:".$id."}";
	}
	
}
?>