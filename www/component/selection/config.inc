<?php
/**
 * Return an array with all the possibles configs for selection component
 * Each config is defined by an array which contains:
 * [0]: config name
 * [1]: explanations: will be displayed on the manage_config page
 * [2]: default value. When a new selection campaign will be created, the config will be set with the default values
 * [3]: array containing all the possible values
 * [4]: {string} type, shall be "boolean" or ""
 */
return array(
	array("separate_boys_girls_IS","Separate the boys and girls figures for the information sessions",false,array(true,false),"boolean"),
	array("give_name_to_IS","Be able to set a custom name to the informations sessions",true,array(true,false),"boolean"),
	// array("give_name_to_IS_event","Be able to set the name of the calendar event related to the information session<br/><i>Note: otherwise, the event will be called \"Information session\"</i>",true,array(true,false),"boolean"),
	array("default_duration_IS","Pre-selected duration of an Information Session<br/><i>Note: if \"All day\" is selected you will <b>never</b> be able to select<br/> the starting / ending time</i>","3 hours",array("1 hour","2 hours","3 hours","4 hours","6 hours","8 hours","All day"),""),
	array("display_number_partners_IS_status","Display the total number of information session partners on the information session dashboard",true,array(true,false),"boolean"),

	//TODO when update, check in IS_profile.inc that $all_duration still matches with the good index!
	
	);
?>