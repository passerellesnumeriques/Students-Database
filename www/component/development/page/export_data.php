<?php
class page_export_data extends Page {
	
	public function getRequiredRights() {
		return array();
	}
	
	protected function execute() {
		echo "<div style='width:100%;height:100%;text-align:center;overflow:auto'>";
		echo "<div style='background-color:white;padding:10px;text-align:left;display:inline-block;'>";
		$domain = PNApplication::$instance->local_domain;
		require_once("component/data_model/Model.inc");
		if (!isset($_GET["start_table"])) {
			echo "What is the starting point of the data to export ?<br/><br/>";
			foreach (DataModel::get()->internalGetTables() as $table) {
				if (!$table->isRoot()) continue;
				echo "<a href='?start_table=".$table->getName()."'>";
				echo $table->getName();
				echo "</a>";
				echo "<br/>";
			}
		} else {
			$exported = array();
			$result = "";
			$this->export($_GET["start_table"], null, $exported, $result);
		}
		echo "</div>";
		echo "</div>";
	}
	
	private function export($table_name, $keys, &$exported, &$result) {
		$table = DataModel::get()->internalGetTable($table_name);
		$pkey = $table->getPrimaryKey();
		// retrieve the rows to export
		$q = SQLQuery::create()->bypassSecurity()->noWarning()->select($table_name);
		if ($keys <> null) {
			if (count($keys) == 0) return; // nothing to export
			$q->whereIn($table_name, $pkey->name, $keys);
		}
		$rows = $q->execute();
		if (count($rows) == 0) return; // nothing in the table
		// filter the rows to remove the ones already exported
		if (isset($exported[$table_name])) {
			for ($i = 0; $i < count($rows); $i++) {
				if (isset($exported[$table_name][$rows[$pkey->name]])) {
					// already exported
					array_splice($rows, $i, 1);
					$i--;
				}
			}
		}
		
		// we need first the foreign keys
		foreach ($table->internalGetColumns() as $col) {
			if ($col instanceof \datamodel\ForeignKey) {
				$fkeys = array();
				foreach ($rows as $row) {
					$fkey = $row[$col->name];
					if (!in_array($fkey, $fkeys)) array_push($fkeys, $fkey);
				}
				$this->export($col->foreign_table, $fkeys, $exported);
			}
		}
		
		if (!isset($exported[$table_name])) $exported[$table_name] = array();
		foreach ($rows as $row) {
			$key = $row[$pkey->name];
			$exported[$table_name][$key] = true;
			$result .= "{";
			$result .= "\"table\":".json_encode($table_name);
			$result .= ",\"key\":".$key;
			$result .= ",\"data\":{";
			$first = true;
			foreach ($row as $col=>$value) {
				if ($col == $pkey->name) continue;
				if ($first) $first = false; else $result .= ",";
				$result .= json_encode($col).":".json_encode($value);
			}
			$result .= "}}";
			$result .= "\n";
		}
	}

}
?>