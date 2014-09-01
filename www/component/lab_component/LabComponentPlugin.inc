<?php
/* Lab 3 (creating a simple plugin)
 step 1 : defining the interface */
interface LabComponentPlugin {
	
	/* should return a string corresponding to a sound */
	public function getSound();
	/* should return a string corresponding to a smell*/
	public function getSmell();
	

}

?>