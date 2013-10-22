<?php 

/** This class provides the methods to update the DataBase, based on the current data model.
 * This will be used mainly during development, so each time a change is made in the data model,
 * the change are reflected automatically in the DataBase.
 * For instance, a column is added, or the type of a data changed... 
 */
class DataBaseModel {
	
	public static function update_model(&$model, &$db_system) {
		$ref = new ReflectionClass("DataModel");
		$p = $ref->getProperty("tables");
		$p->setAccessible(true);
		$tables = $p->getValue($model);
		foreach ($tables as $table) {
			DataBaseModel::update_table($table, $db_system);
		}
	}
	private static function update_table(&$table, &$db_system) {
		$res = $db_system->execute("DESCRIBE `".$table->getName()."`", false);
		if ($res === FALSE) { DataBaseModel::create_table($db_system, $table); return; }
		// TODO update if necessary
	}
	public static function create_table(&$db_system, &$table, $suffix = null) {
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
	
}
?>