<?php 
class training extends Component {
	
	/* TODO in training:
	 * can assign several teachers for one subject => can specify number of hours, so we can compute load...
	 * OR
	 * can merge 2 subjects in transcript
	 * 
	 * comment by subject, to be included in transcript
	 */
	
	public function getPluginImplementations() {
		require_once("TrainingApplicationSection.inc");
		return array(
			new TrainingApplicationSection()
		);
	}
	
}
?>