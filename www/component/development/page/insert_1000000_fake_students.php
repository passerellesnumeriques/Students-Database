<?php 
class page_insert_1000000_fake_students extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		for ($j = 0; $j < 100; ++$j) {
			set_time_limit(1000);
			$rows = array();
			for ($i = 0; $i < 10000; $i++) {
				$row = array(
					"first_name"=>"fake",
					"last_name"=>"student ".($j*10000+$i+1),
					"sex"=>"M",
					"types"=>"/student/"
				);
				array_push($rows, $row);
			}
			$peoples_ids = SQLQuery::create()->bypassSecurity()->noWarning()->insertMultiple("People", $rows);
			set_time_limit(1000);
			$rows = array();
			foreach ($peoples_ids as $pid) {
				$row = array(
					"people"=>$pid,
					"batch"=>2
				);
				array_push($rows, $row);
			}
			SQLQuery::create()->bypassSecurity()->noWarning()->insertMultiple("Student", $rows);
		}
		echo "1 000 000 fake students added.<br/><a href='tools'>Back to development tools page</a>";
	}
	
}
?>