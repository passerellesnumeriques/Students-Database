<?php 
interface PeopleCreateGeneralPlugin {
	
	public function getIcon();
	public function getName();
	public function generateSection();
	
	public function create($people_id);
	
}
?>