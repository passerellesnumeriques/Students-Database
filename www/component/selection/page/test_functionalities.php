<?php 
require_once("SelectionPage.inc");
class page_test_functionalities extends SelectionPage {
	public function getRequiredRights() { return array(); }
	public function executeSelectionPage(){
		$columns = explode(".", "exam_session.event.start");		
		require_once 'component/data_model/Model.inc';
		$t = DataModel::get()->getTable("Applicant");
		for ($i = 0; $i < count($columns); $i++) {
			$col = $t->getColumnFor($columns[$i], PNApplication::$instance->selection->getCampaignID());
			if ($i < count($columns)-1)
				$t = \DataModel::get()->getTable($col->foreign_table);
		}
		echo "<br/>";
		echo "<br/>";
		echo "<br/>";
		echo $col instanceof \datamodel\ColumnTimestamp;
	}
	
}