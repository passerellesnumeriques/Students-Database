<?php 
class students extends Component {
	
	public function get_profile_pages(&$people) {
		$r = SQLQuery::create()->select("Student")->where("people",$people)->execute_single_row();
		if ($r == null) return array(); // not a student
		return array(
			"profile_batch"=>array("/static/people/profile_16.png", "Batch & Classes", "/dynamic/students/page/profile_batch?people=".$people, 10),
		);
	}
	
}
?>