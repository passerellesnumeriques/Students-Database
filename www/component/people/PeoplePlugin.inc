<?php 
interface PeoplePlugin {

	/** TODO
	 * Creation:
	 *  - by typeS
	 *  - sections by specific plugin for creation
	 *  - import to define ?
	 *  
	 * Remove:
	 *  - possibly just remove some types, let messages to administrator to finish the job ?
	 * 
	 */
	
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
}
?>