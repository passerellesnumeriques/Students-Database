<?php
/**
 * Component managing information about any kind of people (a student, a contact point in a company, a staff...)
 */
class people extends Component {

	public function getPluginImplementations() {
		require_once("PeoplePluginsImplementations.inc");
		require_once("PeoplePictureStoragePlugin.inc");
		return array(
			new PeopleProfilePage(),
			new PeoplePictureStoragePlugin()
		);
	}
	
	public function parseTypes($types) {
		$list = array();
		while (substr($types,0,1) == "/") {
			$i = strpos($types, "/", 1);
			if ($i === false) break;
			$type = substr($types, 1, $i-1);
			$types = substr($types,$i);
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
	
	public function getSupportedPeopleTypes() {
		require_once("PeopleTypePlugin.inc");
		$list = array();
		foreach (PNApplication::$instance->components as $c) {
			if ($c == $this) continue;
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeopleTypePlugin)) continue;
				array_push($list, $pi);
			}
		}
		return $list;
	}
	
	public function addPeopleType($people_id, $new_type) {
		$people = $this->getPeople($people_id);
		if ($people == null)
			throw new Exception("Invalid people or no access to this people");
		$types = $this->parseTypes($people["types"]);
		if (in_array($new_type, $types)) return; // already
		$pi = $this->getPeopleTypePlugin($new_type);
		if ($pi == null)
			throw new Exception("Invalid people type ".$new_type);
		if (!$pi->canWrite())
			throw new Exception("You are not allowed to modify people of type ".$pi->getName());
		SQLQuery::create()->updateByKey("People", $people_id, array("types"=>$people["types"].$new_type."/"));
	}

	public function joinPeople(&$q, $from_table, $from_key, $include_picture_info = true) {
		require_once("PeopleJSON.inc");
		$q->join($from_table, "People", array($from_key=>"id"));
		PeopleJSON::PeopleSQL($q, $include_picture_info);
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
	
	public function getPeoples($peoples_ids,$include_picture_revision=false) {
		if (count($peoples_ids) == 0) return array();
		$q = SQLQuery::create()->select("People")->whereIn("People","id",$peoples_ids);
		if ($include_picture_revision) {
			PNApplication::$instance->storage->joinRevision($q, "People", "picture", "picture_revision");
			$q->fieldsOfTable("People");
		}
		return $q->execute();
	}
	public function getPeoplesSQLQuery($peoples_ids,$include_picture_revision=false) {
		if (count($peoples_ids) == 0) return array();
		$q = SQLQuery::create()->select("People")->whereIn("People","id",$peoples_ids);
		if ($include_picture_revision) {
			PNApplication::$instance->storage->joinRevision($q, "People", "picture", "picture_revision");
			$q->fieldsOfTable("People");
		}
		return $q;
	}
	
	public function &searchPeopleByFirstAndLastName($first_name, $last_name) {
		$q = SQLQuery::create()
			->select("People")
			->where("LOWER(`People`.`first_name`) LIKE LOWER('%".SQLQuery::escape(utf8_decode($first_name))."%')")
			->where("LOWER(`People`.`last_name`) LIKE LOWER('%".SQLQuery::escape(utf8_decode($last_name))."%')")
			;
		$q->fieldsOfTable("People");
		return $q;
	}
	
	public function canReadTypes($types) {
		if (is_string($types)) $types = $this->parseTypes($types);
		require_once("PeopleTypePlugin.inc");
		$plugin = null;
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeopleTypePlugin)) continue;
				if (in_array($pi->getId(), $types)) {
					if ($plugin == null || $plugin->getSecurityPriority() > $pi->getSecurityPriority())
						$plugin = $pi;
				}
			}
		 if ($plugin == null) return false;
		 return $plugin->canRead();
	}
	public function canModifyTypes($types) {
		if (is_string($types)) $types = $this->parseTypes($types);
		if ($types == null) $types = array();
		require_once("PeopleTypePlugin.inc");
		$plugin = null;
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi) {
			if (!($pi instanceof PeopleTypePlugin)) continue;
			if (in_array($pi->getId(), $types)) {
				if ($plugin == null || $plugin->getSecurityPriority() > $pi->getSecurityPriority())
					$plugin = $pi;
			}
		}
		if ($plugin == null) return false;
		return $plugin->canWrite();
	}
	
	public function canRead($people_id) {
		if (PNApplication::$instance->user_management->has_right("see_anybody")) return true;
		if ($people_id == PNApplication::$instance->user_management->people_id) return true;
		$p = $this->getPeople($people_id);
		return $p <> null;
	}
	public function canModify($people_id) {
		if (PNApplication::$instance->user_management->has_right("edit_anybody")) return true;
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