<?php 
interface PeopleProfilePagePlugin {

	public function isValidFor($people_id, $types);
	public function getPriority();
	public function getIcon();
	public function getName();
	public function getTooltip();
	public function getURL($people_id);
	
}
?>