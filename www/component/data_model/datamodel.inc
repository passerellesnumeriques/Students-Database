<?php 
$model->addTable("DataLocks")->addPrimaryKey("id")->addInteger("timestamp")->addString("locker_domain")->addString("locker_username")->addString("locked_table", 64)->addBoolean("full_table");
$model->addTable("DataLocksFields")->addForeignKey("lock_id", "DataLocks")->addInteger("timestamp")->addString("field")->addString("value");
?>