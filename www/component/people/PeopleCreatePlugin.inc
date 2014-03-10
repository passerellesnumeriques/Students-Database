<?php 
interface PeopleCreatePlugin {
	
	public function isValidForTypes($types);
	
	public function getPriority();
	public function getIcon();
	public function getName();
	public function generateSection(&$page);
	
	public function create($tables_input, $people_id);
	
	// TODO getSupportedData...
	
}
?>