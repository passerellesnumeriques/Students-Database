<?php 
require_once("PeoplePlugin.inc");
/**
 * Implementation of PeoplePlugin by the people component itself.
 */
class PeoplePeoplePlugin implements PeoplePlugin {
	
	public function preparePeopleProfilePagesRequest(&$q, $people_id) {}
	
	public function getPeopleProfilePages($people_id, $people, $q) {
		return array(
				"profile_people"=>array(
						"/static/people/profile_16.png",
						"General Information",
						"/dynamic/people/page/profile_people?people=".$people_id,
						1
				)
		);
	}
	
	public function getPeopleProfileGeneralInfoSections($people_id, $people, $q) {
		return array(
				array(
						"/static/people/people_info.png",
						"Personal Information",
						"component/people/page/profile_people_general.inc",
						1
				)
		);
	}
	
	public function getCreatePeopleSupportedTypes() { return array(); }
	public function isCreatePeopleAllowed($people_type) { return false; }
	public function getCreatePeoplePages($people_types) {
		return array(
				array("/static/people/people_info.png", "General Information", "component/people/page/create_people__personal_info.inc",1)
		);
	}
	public function createPeople($people_id, $people_types, $input, &$create_data) { return true; }
	public function rollbackCreatePeople($people_id, $people_types, $input, &$create_data) {}
	
	public function getPeopleImportDataDisplay() { return array(); }
	public function importPeopleData($input, $people_id) { return true; }
	
	public function filterPeopleReadAccess(&$q, $people_table_alias) {}
	public function prepareSelectPeopleWriteAccess(&$q, $people_table_alias) {}
	public function filterPeopleWriteAccess($people_rows) { return $people_rows; }
	public function prepareSelectPeopleRemoveAccess(&$q, $people_table_alias, &$locks) {}
	public function filterPeopleRemoveAccess($people_rows) { return $people_rows; }
	
}
?>