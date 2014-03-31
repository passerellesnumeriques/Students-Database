<?php 
require_once("selection_page.inc");
class page_test_functionalities extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(){
		$columns = explode(".", "exam_session.event.start");
		require_once 'component/data_model/Model.inc';
		$t = DataModel::get()->getTable("Applicant");
		$last_table = $t;
		$q = \SQLQuery::create()->select($t->getName());
		// go to the table
		for ($i = 0; $i < count($columns)-1; $i++) {
			$col = $last_table->getColumnFor($columns[$i], PNApplication::$instance->selection->getCampaignId());
			var_dump($col->foreign_table);
			$ft = \DataModel::get()->getTable($col->foreign_table);
			echo "after";
			$q->join($last_table->getName(), $ft->getName(), array($columns[$i]=>$ft->getPrimaryKey()->name));
			$last_table = $ft;
		}
		echo "<br/>";
		echo "<br/>";
		echo "<br/>";
		echo $q->generate();
	}
	
}