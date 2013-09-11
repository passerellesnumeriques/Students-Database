<?php 
class user_people extends Component {
	
	public $user_people_id;
	public $first_name;
	public $last_name;
	
	function init() {
		PNApplication::$instance->user_management->logged_in->listen($this, "logged_in");
	}
	
	public function logged_in() {
		$people = SQLQuery::create()
		->database("students_".PNApplication::$instance->user_management->domain)
		->select("UserPeople")
		->where("username",PNApplication::$instance->user_management->username)
		->join("UserPeople", "People", array("people"=>"id"))
		->field("People", "id")
		->field("People", "first_name")
		->field("People", "last_name")
		->bypass_security() // here we just get name of the user, no need to check if he can see it
		->execute_single_row();
		if ($people <> null) {
			if (PNApplication::$instance->user_management->domain == PNApplication::$instance->current_domain)
				$this->user_people_id = $people["id"];
			$this->first_name = $people["first_name"];
			$this->last_name = $people["last_name"];
		} else if (PNApplication::$instance->user_management->domain == PNApplication::$instance->local_domain) {
			// we have a local user without a People => create it
			$people_id = PNApplication::$instance->people->create_people("?","?",null,true);
			SQLQuery::create()->bypass_security()->insert("UserPeople", array("username"=>PNApplication::$instance->user_management->username,"people"=>$people_id));
			$this->user_people_id = $people_id;
			$this->first_name = "?";
			$this->last_name = "?";
		}
	}
	
	public function get_user_from_people($people, &$domain, &$username) {
		if ($domain == null)
			$domain = PNApplication::$instance->current_domain;
		$r = SQLQuery::create()->select("UserPeople")->field("username")->where("people",$people)->execute_single_row();
		if ($r <> null)
			$username = $r["username"];
	}
	public function get_people_from_user($domain, $username) {
		return SQLQuery::create()->select("UserPeople")->field("people")->where("username",$username)->execute_single_value();
	}
	
}
?>