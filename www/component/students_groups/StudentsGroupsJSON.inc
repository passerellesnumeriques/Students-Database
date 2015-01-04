<?php
/**
 * Generate JSON structures for students groups
 */ 
class StudentsGroupsJSON {
	
	/**
	 * Add fields group_type_id, group_type_name, group_type_specialization_dependent, group_type_builtin, group_type_sub_groups
	 * @param SQLQuery $q the SELECT
	 * @no_name_check
	 */
	public static function GroupTypeSQL(&$q) {
		$q->field("StudentsGroupType","id","group_type_id");
		$q->field("StudentsGroupType","name","group_type_name");
		$q->field("StudentsGroupType","specialization_dependent","group_type_specialization_dependent");
		$q->field("StudentsGroupType","builtin","group_type_builtin");
		$q->field("StudentsGroupType","sub_groups","group_type_sub_groups");
	}
	
	/**
	 * Generate the JSON structure of a group type
	 * @param array $row row from database
	 * @return string JSON structure
	 * @no_name_check
	 */
	public static function GroupTypeJSON($row) {
		$s = "{";
		$s .= "id:".$row["group_type_id"];
		$s .= ",name:".json_encode($row["group_type_name"]);
		$s .= ",specialization_dependent:".($row["group_type_specialization_dependent"] == 1 ? "true" : "false");
		$s .= ",builtin:".($row["group_type_builtin"] == 1 ? "true" : "false");
		$s .= ",sub_groups:".($row["group_type_sub_groups"] == 1 ? "true" : "false");
		$s .= "}";
		return $s;
	}
	
	/**
	 * Generate a JSON array of group type structures
	 * @param array $rows rows from database
	 * @return string JSON
	 * @no_name_check
	 */
	public static function GroupsTypesJSON($rows) {
		$s = "[";
		for ($i = 0; $i < count($rows); $i++) {
			if ($i > 0) $s .= ",";
			$s .= self::GroupTypeJSON($rows[$i]);
		}
		$s .= "]";
		return $s;
	}
	
	/**
	 * Retrieve the given group type and generate the JSON structure
	 * @param integer $id group type id
	 * @return string JSON
	 * @no_name_check
	 */
	public static function getGroupTypeById($id) {
		$q = SQLQuery::create()->select("StudentsGroupType")->whereValue("StudentsGroupType","id",$id);
		self::GroupTypeSQL($q);
		return self::GroupTypeJSON($q->executeSingleRow());
	}
	
	/**
	 * Retrieve the list of existing group types and generate the JSON array
	 * @return string JSON
	 * @no_name_check
	 */
	public static function getGroupsTypes() {
		$q = SQLQuery::create()->select("StudentsGroupType");
		self::GroupTypeSQL($q);
		return self::GroupsTypesJSON($q->execute());
	}
	
	/**
	 * Retrieve the groups, by group type, for the given periods, and generate a JSON structure
	 * @param integer[]|null $periods_ids list of periods, or null for all periods
	 * @return string JSON
	 * @no_name_check
	 */
	public static function GroupsTypesStructurePerPeriod($periods_ids = null) {
		$q = SQLQuery::create()->select("StudentsGroup");
		if ($periods_ids <> null) $q->whereIn("StudentsGroup", "period", $periods_ids);
		$groups = $q->execute();
		return self::GroupsTypesStructurePerPeriodFromGroups($groups);
	}
	/**
	 * Genreate the JSON structure containing the given groups, organized by period, specialization, and group type 
	 * @param array $groups list of groups
	 * @return string JSON
	 * @no_name_check
	 */
	public static function GroupsTypesStructurePerPeriodFromGroups($groups) {
		$s = "[";
		$first_period = true;
		while (count($groups) > 0) {
			$period_id = $groups[0]["period"];
			if ($first_period) $first_period = false; else $s .= ",";
			$s .= "{";
			$s .= "period:$period_id";
			$groups_per_spe = array();
			for ($i = 0; $i < count($groups); $i++) {
				if ($groups[$i]["period"] <> $period_id) continue;
				$group = $groups[$i];
				array_splice($groups, $i, 1);
				$i--;
				$spe = $group["specialization"];
				if (!array_key_exists($spe, $groups_per_spe)) $groups_per_spe[$spe] = array();
				if (!array_key_exists($group["type"], $groups_per_spe[$spe])) $groups_per_spe[$spe][$group["type"]] = array();
				array_push($groups_per_spe[$spe][$group["type"]], $group);
			}
			$s .= ",specializations:[";
			$first_spe = true;
			foreach ($groups_per_spe as $spe=>$groups_per_type) {
				if ($first_spe) $first_spe = false; else $s .= ",";
				$s .= "{";
				$s .= "specialization:".($spe == null ? "null" : $spe);
				$s .= ",groups_types:[";
				$first_type = true;
				foreach ($groups_per_type as $type=>$groups_list) {
					if ($first_type) $first_type = false; else $s .= ",";
					$s .= "{";
					$s .= "group_type:$type";
					$groups_per_parent = array();
					foreach ($groups_list as $g) {
						if (!array_key_exists($g["parent"], $groups_per_parent)) $groups_per_parent[$g["parent"]] = array();
						array_push($groups_per_parent[$g["parent"]], $g);
					}
					$s .= ",groups:".self::GroupsHierarchy($groups_per_parent, null);
					$s .= "}";
				}
				$s .= "]";
				$s .= "}";
			}
			$s .= "]";
			$s .= "}";
		}
		$s .= "]";
		return $s;
	}
	
	/**
	 * Given a list of groups, generate a JSON structure of groups with their children
	 * @param array $groups flat list of groups
	 * @param integer $parent_id parent id of the structure to generate (null for the root, then called recursively)
	 * @return string JSON
	 * @no_name_check
	 */
	private static function GroupsHierarchy($groups, $parent_id) {
		if (!isset($groups[$parent_id])) return "[]";
		$s = "[";
		$first = true;
		foreach ($groups[$parent_id] as $g) {
			if ($first) $first = false; else $s .= ",";
			$s .= "{";
			$s .= "id:".$g["id"];
			$s .= ",name:".json_encode($g["name"]);
			$s .= ",type_id:".$g["type"];
			$s .= ",period_id:".$g["period"];
			$s .= ",specialization_id:".($g["specialization"] <> null ? $g["specialization"] : "null");
			$s .= ",parent_id:".($g["parent"] <> null ? $g["parent"] : "null");
			$s .= ",sub_groups:";
			$s .= self::GroupsHierarchy($groups, $g["id"]);
			$s .= "}";
		}
		$s .= "]";
		return $s;
	}
	
}
?>