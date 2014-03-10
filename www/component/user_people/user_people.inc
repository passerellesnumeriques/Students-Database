<?php

class user_people extends Component {
	
	public $user_people_id;
	/** @var Event raised when a new user is created */
	public $local_user_people_created;
	
	function init() {
		PNApplication::$instance->user_management->logged_in->listen($this, "logged_in");
		$this->local_user_people_created = new Event();
	}
	
	public function getPluginImplementations() {
		require_once("UserPeoplePlugin.inc");
		require_once("UserPeopleProfilePagePlugin.inc");
		return array(
			new UserPeoplePlugin(),
			new UserPeopleProfilePagePlugin()
		);
	}
	
	public function _first_name_changed($sub_model, $key, $new_value) {
		// TODO not valid anymore
		if ($key == $this->user_people_id)
			$this->first_name = $new_value;
	}
	
	public function _last_name_changed($sub_model, $key, $new_value) {
		if ($key == $this->user_people_id)
			$this->last_name = $new_value;
	}
	
	public function logged_in() {
		$q = SQLQuery::create()
			->bypassSecurity() // here we just get name of the user, no need to check if he can see it
			->database("students_".PNApplication::$instance->user_management->domain)
			->select("UserPeople")
			->where("user",PNApplication::$instance->user_management->user_id)
			;
		PNApplication::$instance->people->joinPeople($q, "UserPeople", "people");
		$q->field("People", "id")
			->field("People", "first_name")
			->field("People", "last_name")
			;
		$people = $q->executeSingleRow();
		if ($people <> null) {
			if (PNApplication::$instance->user_management->domain == PNApplication::$instance->current_domain)
				$this->user_people_id = $people["id"];
			$this->first_name = $people["first_name"];
			$this->last_name = $people["last_name"];
		} else if (PNApplication::$instance->user_management->domain == PNApplication::$instance->local_domain) {
			// we have a local user without a People
			$this->user_people_id = -1;
			$this->first_name = "?";
			$this->last_name = "?";
		}
	}
	
	public function get_user_from_people($people_id) {
		$r = SQLQuery::create()->select("UserPeople")->field("user")->where("people",$people_id)->executeSingleRow();
		if ($r <> null)
			return $r["user"];
		return null;
	}
	public function get_people_from_user($user_id) {
		return SQLQuery::create()->select("UserPeople")->field("people")->where("user",$user_id)->executeSingleValue();
	}
	
	public function isPeopleUser($people_id) { return $this->get_user_from_people($people_id) <> null; }
	
	public function joinPeopleToUsers(&$q) {
		$users_alias = $q->getTableAlias("Users");
		$q->join($users_alias, "UserPeople", array("id"=>"user"));
		PNApplication::$instance->people->joinPeople($q, "UserPeople","people");
	}
	
}
?>