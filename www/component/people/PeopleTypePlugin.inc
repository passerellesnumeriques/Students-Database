<?php 
interface PeopleTypePlugin {
	
	public function getSecurityPriority();
	
	public function getId();
	public function getName();
	
	public function canRead();
	public function canWrite();
	public function canRemove();
	
	public function getPeopleRowDescription($people);
}
?>