<?php 
interface PeoplePlugin {
	
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