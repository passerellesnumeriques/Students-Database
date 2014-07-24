<?php
require_once("utilities.inc");
/* @var $model DataModel */

$model->addTable("People")
	->addPrimaryKey("id")
	->addString("first_name", 100, 1, false, false, function($s) { return $s === null ? null : capitalizeWordsFirstLetter($s); })
	->addString("middle_name", 100, 1, true, false, function($s) { return $s === null ? null : capitalizeWordsFirstLetter($s); })
	->addString("last_name", 100, 1, false, false, function($s) { return $s === null ? null : capitalizeWordsFirstLetter($s); })
	->addString("khmer_first_name", 100, 1, true)
	->addString("khmer_last_name", 100, 1, true)
	->addEnum("sex", array("M","F"), false)
	->addDate("birth", true)
	->addString("types", 1024, 0, false, false)
	->addForeignKey("picture", "Storage", false, false, false)
	->setRowDescriptionProvider(function($row) {
		$s = $row["first_name"];
		if ($row["middle_name"] <> null && $row["middle_name"] <> "") $s .= " ".$row["middle_name"];
		$s .= " ".$row["last_name"];
		$types = PNApplication::$instance->people->parseTypes($row["types"]);
		require_once("component/people/PeopleTypePlugin.inc");
		$first = true;
		foreach ($types as $type) {
			foreach (PNApplication::$instance->components as $c) {
				foreach ($c->getPluginImplementations() as $pi) {
					if (!($pi instanceof PeopleTypePlugin)) continue;
					if ($pi->getId() <> $type) continue;
					$descr = $pi->getPeopleRowDescription($row);
					if ($descr <> null && $descr <> "") {
						if ($first) {
							$first = false;
							$s .= " (";
						} else {
							$s .= ", ";
						}
						$s .= $descr;
					}
				}
			}
		}
		if (!$first) $s .= ")";
		return $s;
	})
	->addReadFilter(function(&$q, $table_alias) {
		if (PNApplication::$instance->user_management->has_right("see_anybody")) return;
		// allow to read user own info
		$w = "(`".$table_alias."`.`id`='".PNApplication::$instance->user_management->people_id."')";
		// restrict by type of people
		$types = array();
		require_once("PeopleTypePlugin.inc");
		foreach (PNApplication::$instance->components as $name=>$c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeopleTypePlugin)) continue;
				array_push($types, $pi);
			}
		}
		usort($types, function ($type1, $type2) {
			return $type1->getSecurityPriority() - $type2->getSecurityPriority();
		});
		foreach ($types as $type) {
			if (!($type->canRead())) break;
			$w .= " OR (`".$table_alias."`.'types' LIKE '%/".$type->getId()."/%')";
		}
		$q->where("(".$w.")");
	})
	->addWriteFilter(function(&$q, $table_alias) {
		if (PNApplication::$instance->user_management->has_right("edit_anybody")) return;
		// restrict by type of people
		$types = array();
		require_once("PeopleTypePlugin.inc");
		foreach (PNApplication::$instance->components as $name=>$c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeopleTypePlugin)) continue;
				array_push($types, $pi);
			}
		}
		usort($types, function ($type1, $type2) {
			return $type1->getSecurityPriority() - $type2->getSecurityPriority();
		});
		foreach ($types as $type) {
			if (!($type->canWrite())) break;
			$w .= " OR (`".$table_alias."`.'types' LIKE '%/".$type->getId()."/%')";
		}
		$q->where("(".$w.")");
	},function($rows) {
		return $rows;
	})
	->addRemoveFilter(function(&$q, $table_alias, &$locks) {
		if (PNApplication::$instance->user_management->has_right("edit_anybody")) return;
		$q->field($table_alias, "types", "people_types");
	},function($rows) {
		if (PNApplication::$instance->user_management->has_right("edit_anybody")) return $rows;
		$final = array();
		require_once("PeopleTypePlugin.inc");
		foreach ($rows as $row) {
			$types = PNApplication::$instance->people->parseTypes($row["people_types"]);
			$ok = true;
			foreach (PNApplication::$instance->components as $name=>$c) {
				foreach ($c->getPluginImplementations() as $pi) {
					if (!($pi instanceof PeopleTypePlugin)) continue;
					if (!in_array($pi->getId(), $types)) continue;
					if (!($pi->canRemove())) { $ok = false; break; }	
				}
				if (!$ok) break;
			}
			if ($ok) array_push($final, $row);
		}
		return $final;
	})
	->addInsertFilter(function($values) {
		if (!isset($values["types"])) return false;
		$types = PNApplication::$instance->people->parseTypes($values["types"]);
		require_once("PeopleTypePlugin.inc");
		$types_ok = array();
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeopleTypePlugin)) continue;
				if (!in_array($pi->getId(), $types)) continue;
				if (!$pi->canRemove()) continue;
				if (in_array($pi->getId(), $types_ok)) continue;
				array_push($types_ok, $pi->getId());
			}
		if (count($types_ok) <> count($types)) return false;
		return true;
	})
	;
?>