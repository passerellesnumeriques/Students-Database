<?php 
class service_search_student_by_name extends Service {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function documentation() { echo "Search students matching the given string"; }
	public function input_documentation() { echo "<code>name</code>: string to search"; }
	public function output_documentation() {
		echo "List of matching student: {";
		echo "<code>people_id</code>";
		echo ",<code>first_name</code>";
		echo ",<code>last_name</code>";
		echo ",<code>batch_name</code>";
		echo "}";
	}
	
	public function execute(&$component, $input) {
		$name = $input["name"];
		$name = strtolower($name);
		$words = array();
		$last_start = 0;
		for ($i = 0; $i <= strlen($name); $i++) {
			if ($i == strlen($name) || !ctype_alpha(substr($name, $i, 1))) {
				if ($i > $last_start) {
					$word = substr($name, $last_start, $i-$last_start);
					$word = trim($word);
					if (strlen($word) > 0)
						array_push($words, $word);
				}
				$last_start = $i+1;
				continue;
			}
		}
		if (count($words) == 0) {
			echo "[]";
			return;
		}
		
		$q = SQLQuery::create()
			->select("Student")
			->join("Student", "People", array("people"=>"id"))
			->field("Student", "people", "people")
			->field("People", "first_name", "first_name")
			->field("People", "last_name", "last_name")
			->join("Student", "StudentBatch", array("batch"=>"id"))
			->field("StudentBatch", "name", "batch_name")
			;
		$where = "";
		foreach ($words as $word) {
			if (strlen($where) > 0) $where .= " OR ";
			$where .= "LOWER(`People`.`first_name`) LIKE '%".SQLQuery::escape($word)."%'";
		}
		$q->where($where);
		
		$students = $q->execute();
		$evaluate = function($s) use ($words) {
			$fn = strtolower($s["first_name"]);
			$ln = strtolower($s["last_name"]);
			// number of words / letters we can find
			$words_full = 0; $words_partial = 0;
			$words_full_letters = 0; $words_partial_letters = 0;
			foreach ($words as $word) {
				$if = strpos($fn, $word);
				$if_full = false;
				if ($if >= 0) {
					if ($if == 0 || !ctype_alpha(substr($fn, $if-1, 1))) {
						$end = $if+strlen($word);
						if ($end == strlen($fn) || !ctype_alpha(substr($fn, $end, 1))) {
							$if_full = true;
						}
					}
				}
				if ($if_full) {
					$words_full++;
					$words_full_letters += strlen($word);
					continue;
				}

				$il = strpos($ln, $word);
				$il_full = false;
				if ($il >= 0) {
					if ($il == 0 || !ctype_alpha(substr($ln, $il-1, 1))) {
						$end = $il+strlen($word);
						if ($end == strlen($ln) || !ctype_alpha(substr($ln, $end, 1))) {
							$il_full = true;
						}
					}
				}

				if ($il_full) {
					$words_full++;
					$words_full_letters += strlen($word);
					continue;
				}
				
				if ($if >= 0 || $il >= 0) {
					$words_partial = true;
					$words_partial_letters += strlen($word);
				}
			}
			
			return 
				$words_full * 500 + 
				$words_full_letters * 10 +
				$words_partial * 10 +
				$words_partial_letters * 1
				;
		};
		usort($students, function ($s1, $s2) use ($evaluate) {
			$v1 = $evaluate($s1);
			$v2 = $evaluate($s2);
			if ($v1 < $v2) return 1;
			if ($v1 > $v2) return -1;
			return 0;
		});
		echo "[";
		$first = true;
		foreach ($students as $s) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "people_id:".$s["people"];
			echo ",first_name:".json_encode($s["first_name"]);
			echo ",last_name:".json_encode($s["last_name"]);
			echo ",batch_name:".json_encode($s["batch_name"]);
			echo "}";
		}
		echo "]";
	}
	
}
?>