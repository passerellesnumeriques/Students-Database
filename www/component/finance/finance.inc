<?php 
class finance extends Component {
	public function getPluginImplementations($interface_name) {
		switch ($interface_name) {
			case "PeopleProfilePagePlugin":
				require_once("StudentFinanceProfilePage.inc");
				return array(
					new StudentFinanceProfilePage()
				);
		}
		return array();
	}
}
?>