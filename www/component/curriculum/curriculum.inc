<?php
/**
 * Handles curriculum information: students batches, academic periods, specializations, classes, and subjects. However, nothing is linked to a student, this is done by the student component 
 */ 
class curriculum extends Component {
	
	public function getPluginImplementations() {
		require_once("CurriculumNewsPlugin.inc");
		return array(
			new CurriculumNewsPlugin()
		);
	}
	
}
?>