<?php 
/* @var $model DataModel */

$category = new datamodel\DataDisplayCategory("Family Background", "/static/family/family_16.png");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("FamilyMember", $category);
$display->addJoinFrom("people",array("types"),function($columns){
	$types = PNApplication::$instance->people->parseTypes($columns["types"]);
	return in_array("student", $types) || in_array("applicant", $types);
});
$display->addFollow("family");
class NumberOfSiblings extends datamodel\DataDisplay {
	public function __construct($category) {
		$this->setTableAndCategory(DataModel::get()->internalGetTable("FamilyMember"), $category);
	}
	public function getDisplayName() { return "Siblings"; }
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
		}
		$family_alias = $q->generateFieldAlias();
		$q->field($table_alias, "family", $family_alias);
		return array("key"=>$family_alias,"data"=>null);
	}
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		// TODO
	}
	public function performSubRequests(&$original_query, &$rows, &$sql_aliases, $path) {
		$families = array();
		foreach ($rows as $row) if ($row[$sql_aliases["key"]] <> null && !in_array($row[$sql_aliases["key"]],$families)) array_push($families,$row[$sql_aliases["key"]]);
		if (count($families) > 0)
			$families = SQLQuery::create()->bypassSecurity()->select("FamilyMember")->whereIn("FamilyMember","family",$families)->whereValue("FamilyMember","member_type","Child")->groupBy("FamilyMember","family")->countOneField("FamilyMember","family","nb_children")->field("FamilyMember","family","family")->execute();
		$alias = $original_query->generateFieldAlias();
		for ($i = 0; $i < count($rows); $i++) {
			if ($rows[$i][$sql_aliases["key"]] == null) $rows[$i][$alias] = null;
			else {
				$nb = 0;
				foreach ($families as $f) if ($f["family"] == $rows[$i][$sql_aliases["key"]]) { $nb = $f["nb_children"]; break; }
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
		// create new family
		$id = SQLQuery::create()->bypassSecurity()->insert("Family", array("entry_date"=>date("Y-m-d")));
		// create children
		$children = array();
		array_push($children, array("family"=>$id,"member_type"=>"Child","people"=>$people_id,"entry_date"=>date("Y-m-d")));
		for ($i = 0; $i < intval($value); $i++)
			array_push($children, array("family"=>$id,"member_type"=>"Child","people"=>null,"entry_date"=>date("Y-m-d")));
		SQLQuery::create()->bypassSecurity()->insertMultiple("FamilyMember", $children);
	}
	
}
$display->addDataDisplay(new NumberOfSiblings($category));
class ChildRank extends datamodel\DataDisplay {
	public function __construct($category) {
		$this->setTableAndCategory(DataModel::get()->internalGetTable("FamilyMember"), $category);
	}
	public function getDisplayName() { return "Child Rank"; }
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
		}
		$family_alias = $q->generateFieldAlias();
		$q->field($table_alias, "family", $family_alias);
		$child_rank_alias = $q->generateFieldAlias();
		$q->field($table_alias, "child_rank", $child_rank_alias);
		return array("key"=>$family_alias,"data"=>$child_rank_alias);
	}
	public function getFilterCondition(&$q, $path, $data_aliases, $filter) {
		// TODO
	}
	
	public function getNewData() { return null; }
	
	public function saveData($key, $value, $sub_model, &$tables_fields, $joining_key_for_new_data, $joining_key_value_for_new_data) {
		if ($value === null) return;
		$people_id = $joining_key_value_for_new_data;
		$id = SQLQuery::create()->bypassSecurity()->select("FamilyMember")->whereValue("FamilyMember","people",$people_id)->whereValue("FamilyMember","member_type","Child")->field("id")->executeSingleValue();
		if ($id == null) return;
		SQLQuery::create()->bypassSecurity()->updateByKey("FamilyMember", $id, array("child_rank"=>$value));
	}
	
}
$display->addDataDisplay(new ChildRank($category));
$model->addTableDataDisplay($display);

$display = new datamodel\TableDataDisplay("Family", $category);
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);

?>