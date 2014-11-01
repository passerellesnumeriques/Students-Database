<?php 
class service_search extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Search people"; }
	public function inputDocumentation() { echo "name, types"; }
	public function outputDocumentation() { echo "list of People objects"; }
	
	public function execute(&$component, $input) {
		$q = SQLQuery::create()->select("People");
		if (isset($input["types"])) {
			$w = "";
			for ($i = 0; $i < count($input["types"]); $i++) {
				if ($i > 0) $w .= " OR ";
				$w .= "`types` LIKE '%/".SQLQuery::escape($input["types"][$i])."/%'";
			}
			$q->where("(".$w.")");
		}
		$multiple_conditions = array();
		if (isset($input["name"])) {
			require_once("utilities.inc");
			$name = latinize($input["name"]);
			$words = explode(" ", $input["name"]);
			$conditions = array();
			// 1- all words
			$w = "";
			for ($i = 0; $i < count($words); $i++) {
				if ($i > 0) $w .= " AND ";
				$w .= "(`first_name` LIKE '%".SQLQuery::escape($words[$i])."%' OR `last_name` LIKE '%".SQLQuery::escape($words[$i])."%')";
			}
			array_push($conditions, "(".$w.")");
			// 2- at least one word
			if (count($words) > 1) {
				$w = "";
				for ($i = 0; $i < count($words); $i++) {
					if ($i > 0) $w .= " OR ";
					$w .= "`first_name` LIKE '%".SQLQuery::escape($words[$i])."%'";
					$w .= " OR ";
					$w .= "`last_name` LIKE '%".SQLQuery::escape($words[$i])."%'";
				}
				array_push($conditions, "(".$w.")");
			}
			array_push($multiple_conditions, $conditions);
		}
		
		// search
		require_once("component/people/PeopleJSON.inc");
		PeopleJSON::PeopleSQL($q, false);
		$list = array();
		if (count($multiple_conditions) == 0) {
			$list = $q->execute();
		} else {
			$ids = array();
			$this->executeMultipleConditions($q, $multiple_conditions, $list, $ids);
		}
		// print result
		echo PeopleJSON::Peoples($list);
	}
	
	private function executeMultipleConditions(&$q, &$multiple_conditions, &$list, &$known_ids) {
		$conditions = array_splice($multiple_conditions, 0, 1);
		$conditions = $conditions[0];
		foreach ($conditions as $cd) {
			$sub_q = new SQLQuery($q);
			$sub_q->where($cd);
			if (count($multiple_conditions) == 0) {
				$sub_list = $sub_q->execute();
				foreach ($sub_list as $people)
					if (!in_array($people["people_id"], $known_ids)) {
						array_push($known_ids, $people["people_id"]);
						array_push($list, $people);
					}
			} else {
				$this->executeMultipleConditions($sub_q, $multiple_conditions, $list, $known_ids);
			}
		}
	}
	
}
?>