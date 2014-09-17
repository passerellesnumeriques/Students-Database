<?php 
/* @var $model DataModel */

$category = new datamodel\DataDisplayCategory("Family Background", "/static/family/family_16.png");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("FamilyMember", $category);
$display->addJoinFrom("people",array("types"),function($columns){
	$types = PNApplication::$instance->people->parseTypes($columns["types"]);
	return in_array("student", $types) || in_array("applicant", $types) || in_array("family_member", $types);
});
$display->addFollow("family");
class NumberOfSiblings extends datamodel\DataDisplay {
	public function __construct($category) {
		$this->setTableAndCategory(DataModel::get()->internalGetTable("FamilyMember"), $category);
	}
	public function getDisplayName() { return "Siblings"; }
	public function canAccess($sub_model) { return true; } // TODO ?
	public function getHandledColumns() { return array(); }
	public function getTypedField($sub_model) { return array("field_integer","{min:0,can_be_null:true}"); }
	// TODO public function getTypedFilter($sub_model) { return array("filter_comparable","{value_field_classname:'field_integer',value_field_config:{min:0},can_be_null:true}"); }
	public function getHorizontalAlign() { return "center"; }
	public function isEditable() { return false; }
	public function isEditableForNewData() { return true; }
	public function getEditLocks($sub_model) { return array(); }
	public function isSortable() { return true; }
	public function isMandatory($sub_model) { return false; }
	public function buildSQL(&$q, $path) {
		$table_alias = $q->getTableAlias("FamilyMember");
		if ($table_alias == null) {
			$parent_table_alias = \DataPathSQLBuilder::build($path->parent, $q);
			$table_alias = $q->generateTableAlias();
			$q->join($parent_table_alias, "FamilyMember", array("id"=>"people",null=>array("member_type"=>"Child")), $table_alias);
		} else {
			if (!$q->isJoinAlias($table_alias))
				$q->whereValue($table_alias, "member_type", "Child");
			else
				$q->addConditionToJoin($table_alias, array("member_type"=>"Child"));
		}
		$family_alias = $q->generateFieldAlias();
		$q->field($table_alias, "family", $family_alias);
		$key_alias = $q->generateFieldAlias();
		$q->field($table_alias, "id", $key_alias);
		return array("family"=>$family_alias,"key"=>$key_alias,"data"=>null);
	}
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		// TODO
	}
	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path) {
		$families = array();
		foreach ($rows as $row) if ($row[$sql_aliases["family"]] <> null && !in_array($row[$sql_aliases["family"]],$families)) array_push($families,$row[$sql_aliases["family"]]);
		if (count($families) > 0)
			$families = SQLQuery::create()->bypassSecurity()->select("FamilyMember")->whereIn("FamilyMember","family",$families)->whereValue("FamilyMember","member_type","Child")->groupBy("FamilyMember","family")->countOneField("FamilyMember","family","nb_children")->field("FamilyMember","family","family")->execute();
		$alias = $original_query->generateFieldAlias();
		for ($i = 0; $i < count($rows); $i++) {
			if ($rows[$i][$sql_aliases["family"]] == null) $rows[$i][$alias] = null;
			else {
				$nb = 0;
				foreach ($families as $f) if ($f["family"] == $rows[$i][$sql_aliases["family"]]) { $nb = $f["nb_children"]; break; }
				if ($nb > 0) $nb--;
				$rows[$i][$alias] = $nb;
			}
		}
		$sql_aliases["data"] = $alias;
	}
	
	public function getNewData() { return null; }
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
		if ($value === null) return;
		$people_id = $joining_key_value_for_new_data;
		$id = SQLQuery::create()->bypassSecurity()->select("FamilyMember")->whereValue("FamilyMember","people",$people_id)->whereValue("FamilyMember","member_type","Child")->field("id")->executeSingleValue();
		if ($id == null) {
			// create new family
			$id = SQLQuery::create()->bypassSecurity()->insert("Family", array("entry_date"=>date("Y-m-d")));
		}
		$existing = SQLQuery::create()->bypassSecurity()->select("FamilyMember")->whereValue("FamilyMember","family",$id)->whereValue("FamilyMember","member_type","Child")->execute();
		// create missing children
		$nb = intval($value);
		$children = array();
		if (count($existing) < $nb+1)
			array_push($children, array("family"=>$id,"member_type"=>"Child","people"=>$people_id,"entry_date"=>date("Y-m-d")));
		for ($i = 0; $i < $nb-count($existing); $i++)
			array_push($children, array("family"=>$id,"member_type"=>"Child","people"=>null,"entry_date"=>date("Y-m-d")));
		if (count($children) > 0)
			SQLQuery::create()->bypassSecurity()->insertMultiple("FamilyMember", $children);
	}
	
}
$display->addDataDisplay(new NumberOfSiblings($category));
class ChildRank extends datamodel\DataDisplay {
	public function __construct($category) {
		$this->setTableAndCategory(DataModel::get()->internalGetTable("FamilyMember"), $category);
	}
	public function getDisplayName() { return "Child Rank"; }
	public function canAccess($sub_model) { return true; } // TODO ?
	public function getHandledColumns() { return array(); }
	public function getTypedField($sub_model) { return array("field_integer","{min:1,can_be_null:true}"); }
	// TODO public function getTypedFilter($sub_model) { return array("filter_comparable","{value_field_classname:'field_integer',value_field_config:{min:0},can_be_null:true}"); }
	public function getHorizontalAlign() { return "center"; }
	public function isEditable() { return false; }
	public function isEditableForNewData() { return true; }
	public function getEditLocks($sub_model) { return array(); }
	public function isSortable() { return true; }
	public function isMandatory($sub_model) { return false; }
	public function buildSQL(&$q, $path) {
		$table_alias = $q->getTableAlias("FamilyMember");
		if ($table_alias == null) {
			$parent_table_alias = \DataPathSQLBuilder::build($path->parent, $q);
			$table_alias = $q->generateTableAlias();
			$q->join($parent_table_alias, "FamilyMember", array("id"=>"people",null=>array("member_type"=>"Child")), $table_alias);
		} else {
			if (!$q->isJoinAlias($table_alias))
				$q->whereValue($table_alias, "member_type", "Child");
			else
				$q->addConditionToJoin($table_alias, array("member_type"=>"Child"));
		}
		$family_alias = $q->generateFieldAlias();
		$q->field($table_alias, "family", $family_alias);
		$key_alias = $q->generateFieldAlias();
		$q->field($table_alias, "id", $key_alias);
		$child_rank_alias = $q->generateFieldAlias();
		$q->field($table_alias, "child_rank", $child_rank_alias);
		return array("family"=>$family_alias,"key"=>$key_alias,"data"=>$child_rank_alias);
	}
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		// TODO
	}
	
	public function getNewData() { return null; }
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
		if ($value === null) return;
		$people_id = $joining_key_value_for_new_data;
		$id = SQLQuery::create()->bypassSecurity()->select("FamilyMember")->whereValue("FamilyMember","people",$people_id)->whereValue("FamilyMember","member_type","Child")->field("id")->executeSingleValue();
		if ($id == null) {
			// create new family
			$family_id = SQLQuery::create()->bypassSecurity()->insert("Family", array("entry_date"=>date("Y-m-d")));
			// insert child
			SQLQuery::create()->bypassSecurity()->insert("FamilyMember", array("family"=>$family_id,"people"=>$people_id,"member_type"=>"Child","child_rank"=>$value,"entry_date"=>date("Y-m-d")));
			return;
		}
		SQLQuery::create()->bypassSecurity()->updateByKey("FamilyMember", $id, array("child_rank"=>$value));
	}
	
}
$display->addDataDisplay(new ChildRank($category));
class ParentsSituation extends datamodel\DataDisplay {
	public function __construct($category) {
		$this->setTableAndCategory(DataModel::get()->internalGetTable("FamilyMember"), $category);
	}
	public function getDisplayName() { return "Parents Situation"; }
	public function canAccess($sub_model) { return true; } // TODO ?
	public function getHandledColumns() { return array(); }
	public function getTypedField($sub_model) {
		return PNApplication::$instance->widgets->get_typed_field(DataModel::get()->internalGetTable("Family")->internalGetColumn("parents_situation"));
	}
	public function getTypedFilter($sub_model) {
		return PNApplication::$instance->widgets->get_typed_filter(DataModel::get()->internalGetTable("Family")->internalGetColumn("parents_situation"));
	}
	public function getHorizontalAlign() { return "center"; }
	public function isEditable() { return false; }
	public function isEditableForNewData() { return true; }
	public function getEditLocks($sub_model) { return array(); }
	public function isSortable() { return true; }
	public function isMandatory($sub_model) { return false; }
	public function buildSQL(&$q, $path) {
		$table_alias = $q->getTableAlias("FamilyMember");
		if ($table_alias == null) {
			$parent_table_alias = \DataPathSQLBuilder::build($path->parent, $q);
			$table_alias = $q->generateTableAlias();
			$q->join($parent_table_alias, "FamilyMember", array("id"=>"people",null=>array("member_type"=>"Child")), $table_alias);
		} else {
			if (!$q->isJoinAlias($table_alias))
				$q->whereValue($table_alias, "member_type", "Child");
			else 
				$q->addConditionToJoin($table_alias, array("member_type"=>"Child"));
		}
		$family_alias = $q->generateTableAlias();
		$q->join($table_alias, "Family", array("family"=>"id"), $family_alias);
		$key_alias = $q->generateFieldAlias();
		$q->field($family_alias, "id", $key_alias);
		$data_alias = $q->generateFieldAlias();
		$q->field($family_alias, "parents_situation", $data_alias);
		return array("key"=>$key_alias,"data"=>$data_alias,"table"=>$family_alias);
	}
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		return $this->getColumnFilterCondition($data_aliases["table"], DataModel::get()->internalGetTable("Family")->internalGetColumn("parents_situation"), $filter, $q);
	}
	
	public function getNewData() { return null; }
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
		if ($value === null) return;
		$people_id = $joining_key_value_for_new_data;
		$id = SQLQuery::create()->bypassSecurity()->select("FamilyMember")->whereValue("FamilyMember","people",$people_id)->whereValue("FamilyMember","member_type","Child")->field("family")->executeSingleValue();
		if ($id == null) {
			// create new family
			$id = SQLQuery::create()->bypassSecurity()->insert("Family", array("parents_situation"=>$value,"entry_date"=>date("Y-m-d")));
			// insert child
			SQLQuery::create()->bypassSecurity()->insert("FamilyMember", array("family"=>$id,"people"=>$people_id,"member_type"=>"Child","entry_date"=>date("Y-m-d")));
			return;
		}
		SQLQuery::create()->bypassSecurity()->updateByKey("Family", $id, array("parents_situation"=>$value));
	}
}
$display->addDataDisplay(new ParentsSituation($category));
class ParentDataDisplay extends datamodel\DataDisplay {
	public function getSubDataDisplay() { return new ParentsSubDataDisplay($this); }
	public function __construct($category, $type) {
		$this->type = $type;
		$this->setTableAndCategory(DataModel::get()->internalGetTable("FamilyMember"), $category);
	}
	private $type;
	public function getDisplayName() { return $this->type; }
	public function canAccess($sub_model) { return true; } // TODO ?
	public function getHandledColumns() { return array(); }
	public function getTypedField($sub_model) {
		return array("field_parent","{type:".json_encode($this->type)."}");
	}
	public function isEditable() { return false; }
	public function isEditableForNewData() { return true; }
	public function getNewData() {
		return array("first_name"=>null,"last_name"=>null,"occupation"=>null,"education_level"=>null);
	}
	public function getEditLocks($sub_model) { return array(); }
	public function isSortable() { return false; }
	public function isMandatory($sub_model) { return false; }
	public function buildSQL(&$q, $path) {
		$table_alias = $q->getTableAlias("FamilyMember");
		if ($table_alias == null) {
			$parent_table_alias = \DataPathSQLBuilder::build($path->parent, $q);
			$table_alias = $q->generateTableAlias();
			$q->join($parent_table_alias, "FamilyMember", array("id"=>"people",null=>array("member_type"=>"Child")), $table_alias);
		} else {
			if (!$q->isJoinAlias($table_alias))
				$q->whereValue($table_alias, "member_type", "Child");
			else
				$q->addConditionToJoin($table_alias, array("member_type"=>"Child"));
		}
		$family_alias = $q->generateFieldAlias();
		$q->field($table_alias, "family", $family_alias);
		$key_alias = $q->generateFieldAlias();
		$q->field($table_alias, "id", $key_alias);
		return array("key"=>$key_alias,"data"=>null,"family"=>$family_alias);
	}
	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path) {
		$families_ids = array();
		foreach ($rows as $r) if ($r[$sql_aliases["family"]] <> null && !in_array($r[$sql_aliases["family"]], $families_ids)) array_push($families_ids, $r[$sql_aliases["family"]]);
		if (count($families_ids) > 0) {
			$q = SQLQuery::create()->bypassSecurity()
				->select("FamilyMember")
				->whereIn("FamilyMember","family",$families_ids)
				->whereValue("FamilyMember","member_type",$this->type)
				->fieldsOfTable("FamilyMember");
			PNApplication::$instance->people->joinPeople($q, "FamilyMember", "people", false);
			$res = $q->execute();
		} else
			$res = array();
		$alias = $original_query->generateFieldAlias();
		$sql_aliases["data"] = $alias;
		for ($i = 0; $i < count($rows); $i++) {
			$rows[$i][$alias] = null;
			foreach ($res as $r)
				if ($r["family"] == $rows[$i][$sql_aliases["family"]]) {
					$rows[$i][$alias] = $r;
					break;
				}
		}
	}
	
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		return null;
	}
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
		if ($value["first_name"] == null && $value["last_name"] == null && $value["occupation"] == null && $value["education_level"] == null) return;
		$family_id = SQLQuery::create()->bypassSecurity()
			->select("FamilyMember")
			->whereValue("FamilyMember","people",$joining_key_value_for_new_data)
			->whereValue("FamilyMember","member_type","Child")
			->field("family")
			->executeSingleValue();
		if ($family_id == null) {
			// create family
			$family_id = SQLQuery::create()->bypassSecurity()->insert("Family", array("entry_date"=>date("Y-m-d")));
			// insert child
			SQLQuery::create()->bypassSecurity()->insert("FamilyMember", array("family"=>$family_id,"people"=>$joining_key_value_for_new_data,"member_type"=>"Child","entry_date"=>date("Y-m-d")));
		}
		$member = SQLQuery::create()->bypassSecurity()
			->select("FamilyMember")
			->whereValue("FamilyMember","family",$family_id)
			->whereValue("FamilyMember","member_type",$this->type)
			->executeSingleRow();
		if ($member == null) {
			// new parent
			$people_id = null;
			if ($value["first_name"] <> null && $value["last_name"] <> null) {
				$people_id = PNApplication::$instance->people->createPeople(array("first_name"=>$value["first_name"],"last_name"=>$value["last_name"],"sex"=>($this->type == "Father" ? "M" : "F")), array("family_member"), true);
			}
			SQLQuery::create()->bypassSecurity()->insert("FamilyMember", array("family"=>$family_id,"member_type"=>$this->type,"people"=>$people_id,"occupation"=>$value["occupation"],"education_level"=>$value["education_level"],"entry_date"=>date("Y-m-d")));
		} else {
			// update
			$id = $member["id"];
			unset($member["id"]);
			if ($member["people"] == null && $value["first_name"] <> null && $value["last_name"] <> null)
				$member["people"] = PNApplication::$instance->people->createPeople(array("first_name"=>$value["first_name"],"last_name"=>$value["last_name"],"sex"=>($this->type == "Father" ? "M" : "F")), array("family_member"), true);
			else
				unset($member["people"]);
			$member["occupation"] = $value["occupation"];
			$member["education_level"] = $value["education_level"];
			$member["entry_date"] = date("Y-m-d");
			SQLQuery::create()->bypassSecurity()->updateByKey("FamilyMember", $id, $member);
		}
	}
}
class ParentsSubDataDisplay extends datamodel\SubDataDisplay {
	public function getDisplayNames() {
		return array(
			"Last Name",
			"First Name",
			"Age",
			"Occupation",
			"Education Level"
		);
	}
	public function isEditable($index) {
		if ($index == 2) return false;
		return true;
	}
}
$display->addDataDisplay(new ParentDataDisplay($category, "Father"));
$display->addDataDisplay(new ParentDataDisplay($category, "Mother"));
$model->addTableDataDisplay($display);

class FamilyBackgroundDataScreen extends datamodel\GenericDataScreen {
	protected function endDataRow($data, $value, $sub_index, $is_new, $can_edit) {
		$html = "";
		switch ($data->getDisplayName()) {
			case "Child Rank":
				if ($is_new) {
					$html .= "<td><img src='".theme::$icons_10["info"]."' title='The number of siblings is mandatory if you specify a child rank'/></td>";
				}
				break;
		}
		return $html;
	}
}
$model->addDataScreen(new FamilyBackgroundDataScreen($display));

$display = new datamodel\TableDataDisplay("Family", $category);
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);

?>