<?php 
interface PeoplePlugin {

	/**
	 * Prepare the SQL request that will retrieve the people information, to determine which pages/sections to display in the people's profile
	 * @param SQLQuery $q
	 * @param integer $people_id
	 */
	public function preparePeopleProfilePagesRequest(&$q, $people_id);
	/** Return pages that will be displayed in a people's profile
	 * @param array $people the people row from the database
	 * @return array(page_id=>array(icon_16,title,url,priority,tooltip))
	 */
	public function getPeopleProfilePages($people_id, $people, $q);
	/** returns an array of (section_icon,section_title,section_path,priority) */
	public function getPeopleProfileGeneralInfoSections($people_id, $people, $q);

	
	/** returns an array of (icon,title,page_path,priority) */
	public function getCreatePeopleSupportedTypes();
	public function isCreatePeopleAllowed($people_type);
	public function getCreatePeoplePages($people_types);
	public function createPeople($people_id, $people_types, $input, &$create_data);
	public function rollbackCreatePeople($people_id, $people_types, $input, &$create_data);
	
	/** returns a list of DataDisplay, which can be imported when importing a people */
	public function getPeopleImportDataDisplay();
	/**
	 * @param array $input array of data=>JavaScriptDataDisplay,value=>value
	 * @param number $people_id
	 */
	public function importPeopleData($input, $people_id);
	
	/** return the conditions to put in the where clause, to select only allowed people
	 * @param SQLQuery $q
	 * @param string $people_table_alias 
	 */
	public function filterPeopleReadAccess(&$q, $people_table_alias);
	/**
	 * @param SQLQuery $q
	 * @param string $people_table_alias 
	 */
	public function prepareSelectPeopleWriteAccess(&$q, $people_table_alias);
	public function filterPeopleWriteAccess($people_rows);
	/**
	 * @param SQLQuery $q
	 * @param string $people_table_alias 
	 */
	public function prepareSelectPeopleRemoveAccess(&$q, $people_table_alias, &$locks);
	public function filterPeopleRemoveAccess($people_rows);
}
?>