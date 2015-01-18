<?php 
/* @var $model DataModel */

$model->addTable("News")
	->addPrimaryKey("id")
	->addString("section", 50, 1, false)
	->addString("category", 200, 0, true)
	->addString("html", 10000, 1, false)
	->addString("domain", 100, 1, false)
	->addString("username", 100, 1, false)
	->addInteger("timestamp")
	->addInteger("update_timestamp")
	->addString("tags", 2000, 0, true)
	->addEnum("type", array("activity","update"))
	->addForeignKey("reply_to", "News", true, false, true, true, false)
	->setRoot()
	->addIndex("reply_to")
	;

?>