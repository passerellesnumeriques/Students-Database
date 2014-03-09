<?php 
interface PeopleTypePlugin {
	
	public function getSecurityPriority();
	
	public function getId();
	public function getName();
	
	public function generateGeneralInfoSection($people_id);
	
	public function generateCreateSection();
	public function create($people_id);
	
}
?>