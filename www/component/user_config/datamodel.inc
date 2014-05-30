<?php 
/* @var $model DataModel */

$model->addTable("UserConfig")
	->addForeignKey("user", "Users", true, false, true)
	->addString("component", 50, 1, false, false)
	->addString("name", 100, 1, false, false)
	->addString("value", 2000, 0, true, false)
	->setKey(array("user","component","name"))
	->addReadFilter(function(&$q, $alias) {
		$q->whereValue($alias, "user", PNApplication::$instance->user_management->user_id);
	})
	->addWriteFilter(function(&$q, $alias){
		$q->field("UserConfig", "user", "user_config_user_id");
	}, function($rows){
		$res = array();
		foreach ($rows as $row) if ($row["user_config_user_id"] == PNApplication::$instance->user_management->user_id) array_push($res, $row);
		return $res;
	})
	->addInsertFilter(function($values){
		return $values["user"] == PNApplication::$instance->user_management->user_id;
	})
	->addRemoveFilter(function(&$q, $table_alias, &$locks){
		$q->field($table_alias, "user", "user_config_user_id");
	}, function($rows){
		$res = array();
		foreach ($rows as $row) if ($row["user_config_user_id"] == PNApplication::$instance->user_management->user_id) array_push($res, $row);
		return $res;
	})
	;
?>