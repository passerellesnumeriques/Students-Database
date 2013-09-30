<?php
require_once("component/people/ProfilePlugin.inc"); 
class user_people extends Component implements ProfilePlugin {
	
	public $user_people_id;
	public $first_name;
	public $last_name;
	
	function init() {
		PNApplication::$instance->user_management->logged_in->listen($this, "logged_in");
		PNApplication::$instance->data_model->register_data_changed("People", "first_name", $this, "_first_name_changed");
		PNApplication::$instance->data_model->register_data_changed("People", "last_name", $this, "_last_name_changed");
	}
	
	public function _first_name_changed($sub_model, $key, $new_value) {
		if ($key == $this->user_people_id)
			$this->first_name = $new_value;
	}
	
	public function _last_name_changed($sub_model, $key, $new_value) {
		if ($key == $this->user_people_id)
			$this->last_name = $new_value;
	}
	
	public function logged_in() {
		$people = SQLQuery::create()
		->database("students_".PNApplication::$instance->user_management->domain)
		->select("UserPeople")
		->where("user",PNApplication::$instance->user_management->user_id)
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
	
	public function get_user_from_people($people_id) {
		$r = SQLQuery::create()->select("UserPeople")->field("user")->where("people",$people_id)->execute_single_row();
		if ($r <> null)
			return $r["user"];
		return null;
	}
	public function get_people_from_user($user_id) {
		return SQLQuery::create()->select("UserPeople")->field("people")->where("user",$user_id)->execute_single_value();
	}
	
	public function get_profile_pages(&$people) {
		$user_id = @$_GET["user"];
		if ($user_id == null && $people <> null)
			$user_id = $this->get_user_from_people($people);
		return array(
			"user_rights"=>array("/static/user_management/access_list.png", "User Rights", "/dynamic/user_management/page/user_rights?user=".$user_id, 1000),
		);
	}

	
}
?>