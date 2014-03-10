<?php
/**
 * Component managing information about any kind of people (a student, a contact point in a company, a staff...)
 */
class people extends Component {

	public function getPluginImplementations() {
		require_once("PeoplePeoplePlugin.inc");
		require_once("PeoplePluginsImplementations.inc");
		return array(
			new PeoplePeoplePlugin(),
			new PeopleProfilePage(),
			new PeopleGeneralInfo(),
			new PersonalInfoPeopleCreatePlugin(),
		);
	}
	
	public function parseTypes($types) {
		$list = array();
		while (substr($types,0,1) == "/") {
			$i = strpos($types, "/", 1);
			if ($i === false) break;
			$type = substr($types, 1, $i-1);
			$types = substr($types,$i+1);
			array_push($list, $type);
		}
		return $list;
	}
	
	public function getPeopleTypePlugin($type) {
		require_once("PeopleTypePlugin.inc");
		foreach (PNApplication::$instance->components as $c) {
			if ($c == $this) continue;
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeopleTypePlugin)) continue;
				if ($pi->getId() == $type) return $pi;
			}
		}
		return null;
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
	
	public function getPeople($people_id) {
		return SQLQuery::create()->select("People")->whereValue("People","id",$people_id)->executeSingleRow();
	}
	
	public function canRead($people_id) {
		$p = $this->getPeople($people_id);
		return $p <> null;
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
	public function canRemove($people_id, &$locks) {
		$q = SQLQuery::create()->select("People");
		$q->whereValue("People","id",$people_id);
		DataModel::get()->getTable("People")->prepareSelectToDetermineRemoveAccess($q, "People", $locks);
		$rows = $q->execute();
		$rows = DataModel::get()->getTable("People")->filterRemoveAccess($rows);
		return count($rows) == 1;		
	}
}
?>