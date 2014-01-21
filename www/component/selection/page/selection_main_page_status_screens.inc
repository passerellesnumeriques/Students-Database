<?php
	/**
	 * return an array of arrays giving the data to generate the selection main page
	 * Each array contains:
	 * 		[0]: {string} step name
	 *		[1]: {string} javascript name use to generate the status screen
	 *		[1]: {string | HTML} To be displayed in the status screen header
	 * Each status script must call a service to get its required data
	 * to avoid having a loading time to long on the selection_main_page
	 */
	return array(
		array("information_session","IS_status.js","Information Sessions"),
		array("manage_exam","exam_status.js","Entrance Examination"),
	);

?>