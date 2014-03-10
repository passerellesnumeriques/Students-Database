<?php 
class discipline extends Component {
	
	public function getPluginImplementations() {
		require_once("DisciplinePeopleProfilePagePlugin.inc");
		return array(
			new DisciplinePeopleProfilePagePlugin()
		);
	}
	
}
?>