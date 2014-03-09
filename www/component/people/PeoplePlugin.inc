<?php 
interface PeoplePlugin {

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