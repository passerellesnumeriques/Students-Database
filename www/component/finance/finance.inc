<?php 
class finance extends Component {
	public function getPluginImplementations() {
		require_once("StudentFinanceProfilePage.inc");
		return array(
			new StudentFinanceProfilePage()
		);
	}
	
	public function getGeneralRegularPayments() {
		return SQLQuery::create()->select("FinanceRegularPayment")->execute();
	}
}
?>