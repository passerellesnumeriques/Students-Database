<?php 
class service_interview_save_criteria extends Service {
	
	public function getRequiredRights() { return array("manage_interview_criteria"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		if (!$component->canEditInterviewCriteria()) {
			PNApplication::error("Criteria cannot be modified because some results have been already set");
			return;
		}
		SQLQuery::startTransaction();
		$criteria = SQLQuery::create()->select("InterviewCriterion")->execute();
		$to_insert = array();
		$to_update = array();
		foreach ($input["criteria"] as $c) {
			if ($c["id"] <= 0) {
				// new one
				array_push($to_insert, array("name"=>$c["name"],"max_score"=>$c["max_score"]));
			} else {
				// existing one
				array_push($to_update, array(array($c["id"]),array("name"=>$c["name"],"max_score"=>$c["max_score"])));
				for ($i = 0; $i < count($criteria); $i++)
					if ($criteria[$i]["id"] == $c["id"]) {
						array_splice($criteria, $i, 1);
						break;
					}
			}
		}
		if (count($to_insert) > 0)
			SQLQuery::create()->bypassSecurity()->insertMultiple("InterviewCriterion", $to_insert);
		if (count($to_update) > 0)
			SQLQuery::create()->bypassSecurity()->updateByKeys("InterviewCriterion", $to_update);
		if (count($criteria) > 0)
			SQLQuery::create()->bypassSecurity()->removeRows("InterviewCriterion", $criteria);
		if (PNApplication::hasErrors()) return;
		SQLQuery::commitTransaction();
		echo "true";
	}
	
}
?>