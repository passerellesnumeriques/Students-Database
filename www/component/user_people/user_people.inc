<?php
require_once("component/people/PeoplePlugin.inc"); 
class user_people extends Component implements PeoplePlugin {
	
	public $user_people_id;
	
	function init() {
		PNApplication::$instance->user_management->logged_in->listen($this, "logged_in");
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
	
	public function preparePeopleProfilePagesRequest(&$q, $people_id) {
		$people_alias = $q->get_table_alias("People");
		$alias = $q->table_id();
		$q->join($people_alias, "UserPeople", array("id"=>"people"), $alias);
		$q->field($alias, "user");
	}
	public function getPeopleProfilePages($people_id, $people, $q) {
		if ($people["user"] == null) return array(); // not a user 
		$user_id = $people["user"];
		return array(
			array(
				"/static/user_management/access_list.png", 
				"User Rights", 
				"/dynamic/user_management/page/user_rights?user=".$user_id, 
				1000
			)
		);
	}

	public function getPeopleProfileGeneralInfoSections($people_id, $people, $q) {}
	
	public function getCreatePeopleSupportedTypes() { return array("user"); }
	public function isCreatePeopleAllowed($people_type) {
		if ($people_type <> "user") return false;
		return PNApplication::$instance->user_management->has_right("manage_users", true);
	}
	public function getCreatePeoplePages($people_types) {}
	public function createPeople($people_id, $people_types, $input, &$create_data) {}
	public function rollbackCreatePeople($people_id, $people_types, $input, &$create_data) {}
	
	public function filterPeopleReadAccess(&$q, $people_table_alias) {
		if (!PNApplication::$instance->user_management->has_right("consult_user_list")) return null;
		$alias = $q->get_table_alias("UserPeople");
		if ($alias == null) {
			$alias = $q->table_id();
			$q->join($people_table_alias, "UserPeople", array("id"=>"people"), $alias);
		}
		return "`".$alias."`.`user` IS NOT NULL";
	}
	
	public function prepareSelectPeopleWriteAccess(&$q, $people_table_alias) {
		if (!PNApplication::$instance->user_management->has_right("manage_users")) return;
		$alias = $q->get_join_alias($people_table_alias, "UserPeople", array("id"=>"people"));
		if ($alias == null)
			$alias = $q->get_join_alias("UserPeople", $people_table_alias, array("people"=>"id"));
		if ($alias == null) {
			$alias = $q->table_id();
			$q->join($people_table_alias, "UserPeople", array("id"=>"people"),$alias);
		}
		$q->field($alias, "user", "PEOPLE_USER");
	}
	public function filterPeopleWriteAccess($people_rows) {
		if (!PNApplication::$instance->user_management->has_right("manage_users")) return array();
		$final = array();
		foreach ($people_rows as $r)
			if ($r["PEOPLE_USER"] <> null)
				array_push($final, $r);
		return $final;
	}
	
	public function prepareSelectPeopleRemoveAccess(&$q, $people_table_alias, &$locks) {
		if (!PNApplication::$instance->user_management->has_right("manage_users")) return;
		$alias = $q->get_join_alias($people_table_alias, "UserPeople", array("id"=>"people"));
		if ($alias == null)
			$alias = $q->get_join_alias("UserPeople", $people_table_alias, array("people"=>"id"));
		if ($alias == null) {
			$alias = $q->table_id();
			$q->join($people_table_alias, "UserPeople", array("id"=>"people"),$alias);
		}
		$q->field($alias, "user", "PEOPLE_USER");
	}
	public function filterPeopleRemoveAccess($people_rows) {
		if (!PNApplication::$instance->user_management->has_right("manage_users")) return array();
		$final = array();
		foreach ($people_rows as $r)
			if ($r["PEOPLE_USER"] <> null)
				array_push($final, $r);
		return $final;
	}
	
}
?>