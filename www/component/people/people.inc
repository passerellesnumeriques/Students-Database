<?php
class people extends Component {

	public function create_people($first_name, $last_name, $middle_name = null, $bypass_security=false) {
		$q = SQLQuery::create();
		if ($bypass_security) $q->bypass_security();
		return $q->insert("People", array("first_name"=>$first_name,"last_name"=>$last_name,"middle_name"=>$middle_name));
	}
	
	public function get_profile_pages(&$people) {
		return array(
			"profile_people"=>array("/static/people/profile_16.png", "Personal Information", "/dynamic/people/page/profile_people?people=".$people, 1),
		);
	}
	
}
?>