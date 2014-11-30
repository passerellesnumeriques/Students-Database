<?php 
interface PeopleCreationStep {
	
	public function getId();
	
	public function isApplicable($people);
	
	public function generatePageFor($peoples_ids, &$page);
	
}
?>