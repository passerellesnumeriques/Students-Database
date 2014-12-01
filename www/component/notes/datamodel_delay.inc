<?php 
/* @var DataModel $model */

require_once("component/notes/NotesPlugin.inc");
foreach (PNApplication::$instance->components as $c)
	foreach ($c->getPluginImplementations() as $pi)
		if ($pi instanceof NotesPlugin) {
			$sm_table = $pi->getAttachedSubModel();
			if ($sm_table <> null)
				$m = $model->getSubModel($sm_table);
			else
				$m = $model;
			$table_name = $pi->getAttachedTable();
			$m->addTable("Notes".$table_name)
				->addForeignKey("note","Note",true,true,false,false,true)
				->addForeignKey("attach", $table_name, true, false, true, false, false)
				->setPrimary("note")
				->addIndex("attach")
				->addRemoveAccessFromForeignTable($table_name)
				->addRemoveAccessFromForeignTable("Note")
				;
		}

?>