<?php 
interface CustomCalendarPlugin {
	
	public function getId();
	public function getName();
	public function getIcon();
	public function getDefaultColor();
	public function canAccess();
	public function getEvents();
	
}
?>