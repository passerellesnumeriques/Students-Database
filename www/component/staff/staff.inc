<?php
/**
 * Component managing the list of staff
 */
class staff extends Component {
	
	public function getPluginImplementations() {
		require_once("StaffAdministrationPlugin.inc");
		require_once("StaffPeopleTypePlugin.inc");
		return array(
			new StaffAdministrationPlugin(),
			new StaffPeopleTypePlugin()
		);
	}
	
	public function requestStaffsForDates($start = null, $end = null) {
		$q = SQLQuery::create()->select("StaffPosition");
		if ($start <> null) {
			$q->where("StaffPosition.start >= '".SQLQuery::escape($start)."'");
			if ($end <> null)
				$q->where("StaffPosition.start < '".SQLQuery::escape($end)."'");
		}
		$q->groupBy("people");
		return $q;
	}
	
	/**
	 * Select the Staff table in an SQLQuery
	 * @param SQLQuery $q query to override
	 */
	public function selectStaffTable(&$q){
		$q->select("Staff");
	}
}
?>