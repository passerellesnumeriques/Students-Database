<?php 
class discipline extends Component {
	
	public function getPluginImplementations() {
		require_once("DisciplinePeoplePlugin.inc");
		return array(
			new DisciplinePeoplePlugin()
		);
	}
	
}
?>