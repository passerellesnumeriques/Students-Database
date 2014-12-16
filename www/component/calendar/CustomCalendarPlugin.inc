<?php
/**
 * Interface to implement a custom calendar, which is a calendar generated (like birthdays)
 */ 
interface CustomCalendarPlugin {

	/**
	 * Get the internal identifier of the calendar
	 * @return string the ID
	 */
	public function getId();
	/**
	 * Display name of the calendar
	 * @return string the name
	 */
	public function getName();
	/**
	 * Icon of the calendar
	 * @return string the URL of the icon
	 */
	public function getIcon();
	/**
	 * Color of the calendar
	 * @return string the color
	 */
	public function getDefaultColor();
	/**
	 * Check if the current user can see this calendar
	 * @return boolean true if access allowed
	 */
	public function canAccess();
	/**
	 * Get the list of events for this calendar
	 * @return array list of events
	 */
	public function getEvents();
	
}
?>