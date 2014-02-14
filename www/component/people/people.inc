<?php
/**
 * Component managing information about any kind of people (a student, a contact point in a company, a staff...)
 */
class people extends Component {

	public function getPluginImplementations() {
		require_once("PeoplePeoplePlugin.inc");
		return array(
			new PeoplePeoplePlugin()
		);
	}

	public function joinPeople(&$q, $from_table, $from_key) {
		require_once("PeopleJSON.inc");
		$q->join($from_table, "People", array($from_key=>"id"));
		PeopleJSON::PeopleSQL($q);
	}
	
	public function filterReadAccess(&$q, $from_table_alias, $from_table_foreign_key) {
		require_once("component/data_model/Model.inc");
		$table = Datamodel::get()->getTable("People");
		$people_alias = $q->getTableAlias("People");
		if ($people_alias == null) {
			$people_alias = $q->generateTableAlias();
			$q->join($from_table_alias, "People", array($from_table_foreign_key=>"id"), $people_alias);
		}
		$table->filterReadAccess($q, $people_alias);
	}
	public function prepareSelectToDetermineRemoveAccess(&$q, $from_table_alias, $from_table_foreign_key, &$locks) {
		$table = Datamodel::get()->getTable("People");
		$people_alias = $q->getTableAlias("People");
		if ($people_alias == null) {
			$people_alias = $q->generateTableAlias();
			$q->join($from_table_alias, "People", array($from_table_foreign_key=>"id"), $people_alias);
		}
		$table->prepareSelectToDetermineRemoveAccess($q, $people_alias, $locks);
	}
	public function filterRemoveAccess($rows) {
		$table = Datamodel::get()->getTable("People");
		return $table->filterRemoveAccess($rows);
	}
	
	public function canModify($people_id) {
		$q = SQLQuery::create();
		$table_alias = $q->generateTableAlias();
		$q->select(array("People"=>$table_alias));
		$q->whereValue($table_alias, "id", $people_id);
		$table = DataModel::get()->getTable("People");
		$table->prepareSelectToDetermineWriteAccess($q, $table_alias);
		$rows = $q->execute();
		$rows = $table->filterWriteAccess($rows);
		if (count($rows) == 0) return false;
		return true;
	}
}
?>