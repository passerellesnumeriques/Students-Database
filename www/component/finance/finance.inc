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
	
	public function getGeneralRegularPayments() {
		return SQLQuery::create()->select("FinanceRegularPayment")->execute();
	}
	
	public function getAllowances() {
		return SQLQuery::create()->select("Allowance")->execute();
	}
	
}
?>