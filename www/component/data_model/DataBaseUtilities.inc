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
		
		$first = true;
		foreach ($columns as $col) {
			if ($first) $first = false; else $sql .= ", ";
			$sql .= $col->get_sql();
		}
		$pk = $table->getPrimaryKey();
		if ($pk <> null)
			$sql .= ", PRIMARY KEY(`".$pk->name."`)";
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