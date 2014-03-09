<?php 
interface PeopleProfilePagePlugin {
	
	public function getPriority();
	public function getIcon();
	public function getName();
	
	public function getURL($people_id);
	
}
?>