<?php 
require_once("component/people/PeoplePlugin.inc");
/**
 * Implementation of PeoplePlugin for users
 */
class UserPeoplePlugin implements PeoplePlugin {
	
	public function preparePeopleProfilePagesRequest(&$q, $people_id) {
		$people_alias = $q->getTableAlias("People");
		$alias = $q->generateTableAlias();
		$q->join($people_alias, "UserPeople", array("id"=>"people"), $alias);
		$q->field($alias, "user");
	}
	public function getPeopleProfilePages($people_id, $people, $q) {
		if ($people["user"] == null) return array(); // not a user
		$user_id = $people["user"];
		return array(
				"user_rights"=>array(
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
	
	public function getPeopleImportDataDisplay() { return array(); }
	public function importPeopleData($input, $people_id) { return true; }
	
	public function filterPeopleReadAccess(&$q, $people_table_alias) {
		if (!PNApplication::$instance->user_management->has_right("consult_user_list")) return null;
		$alias = $q->getTableAlias("UserPeople");
		if ($alias == null) {
			$alias = $q->generateTableAlias();
			$q->join($people_table_alias, "UserPeople", array("id"=>"people"), $alias);
		}
		return "`".$alias."`.`user` IS NOT NULL";
	}
	
	public function prepareSelectPeopleWriteAccess(&$q, $people_table_alias) {
		if (!PNApplication::$instance->user_management->has_right("manage_users")) return;
		$alias = $q->getJoinAlias($people_table_alias, "UserPeople", array("id"=>"people"));
		if ($alias == null)
			$alias = $q->getJoinAlias("UserPeople", $people_table_alias, array("people"=>"id"));
		if ($alias == null) {
			$alias = $q->generateTableAlias();
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
		$alias = $q->getJoinAlias($people_table_alias, "UserPeople", array("id"=>"people"));
		if ($alias == null)
			$alias = $q->getJoinAlias("UserPeople", $people_table_alias, array("people"=>"id"));
		if ($alias == null) {
			$alias = $q->generateTableAlias();
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