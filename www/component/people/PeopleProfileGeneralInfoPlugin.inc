<?php 
interface PeopleProfileGeneralInfoPlugin {
	
	public function getPriority();
	public function getIcon();
	public function getName();
	
	public function isValidFor($people_id, $types);
	/**
	 * Prepare the SQL request that will retrieve the people information to generate the section
	 * @param SQLQuery $q
	 * @param integer $people_id
	 */
	public function prepareRequestForSection(&$q, $people_id);
	public function generateSection(&$page, $people_id, $row, $prepareRequest, $q);
	
}
?>