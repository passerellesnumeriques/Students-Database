<?php
/* @var $model DataModel */
$model->addTable("Storage")
	->addPrimaryKey("id")
	->addString("mime", 200, 0, true)
	->addInteger("expire")
	->addInteger("revision", 32, 1, null, false, false)
	->addString("type", 100, 1, false, false)
	->allowReadAccess()
	->onRemove(function($table, $rows, $sub_model) {
		// make sure the file will not remain
		foreach ($rows as $row) {
			$path = PNApplication::$instance->storage->get_data_path($row["id"]);
			@unlink($path);
		}
	})
	;
?>