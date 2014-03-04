<?php 

/** Utility methods for DataBase, not included in SQLQuery because it should be used exceptionnaly
 */
class DataBaseUtilities {
	
	/**
	 * Create a table in the database
	 * @param DataBaseSystem $db_system the connection to the database
	 * @param datamodel\Table $table the table to create
	 * @param string|null $suffix any suffix to add at the end of the table name 
	 */
	public static function createTable($db_system, &$table, $suffix = null) {
		$sql = "CREATE TABLE `".$table->getName().($suffix <> null ? $suffix : "")."` (";
		
		$ref = new ReflectionClass("\datamodel\Table");
		$p = $ref->getProperty("columns");
		$p->setAccessible(true);
		$columns = $p->getValue($table);
		$p = $ref->getProperty("indexes");
		$p->setAccessible(true);
		$indexes = $p->getValue($table);
		
		$first = true;
		foreach ($columns as $col) {
			if ($first) $first = false; else $sql .= ", ";
			$sql .= $col->get_sql();
		}
		$pk = $table->getPrimaryKey();
		if ($pk <> null)
			$sql .= ", PRIMARY KEY(`".$pk->name."`)";
		else {
			$key = $table->getKey();
			$sql .= ", UNIQUE KEY `table_key` (";
			$first = true;
			foreach ($key as $colname) {
				if ($first) $first = false; else $sql .= ",";
				$sql .= "`".$colname."`";
			}
			$sql .= ")";
		}
		foreach ($indexes as $index) {
			$sql .= ", KEY `".$index[0]."` (";
			for ($i = 1; $i < count($i); $i++) {
				if ($i > 1) $sql .= ",";
				$sql .= "`".$index[$i]."`";
			}
			$sql .= ")";
		}
		$sql .= ")";
		$db_system->execute($sql);
	}
	
	/**
	 * Remove a table from the database
	 * @param DataBaseSystem $db_system the connection to the database
	 * @param datamodel\Table $table the table to remove
	 * @param string|null $suffix any suffix to add at the end of the table name 
	 */
	public static function dropTable($db_system, &$table, $suffix = null) {
		$db_system->execute("DROP TABLE `".$table->getName().($suffix <> null ? $suffix : "")."`");
	}
}
?>