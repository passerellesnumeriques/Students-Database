<?php
/* @var $model DataModel */
$model->addTable("Country")
	->addPrimaryKey("id")
	->addString("code",50,0,true,true)
	->addString("name",50,1,false,false)
	->allowReadAccess()
	->restrictWriteAccess("edit_geography",true)
	->restrictAddAccess("edit_geography",true)
	->restrictRemoveAccess("manage_geography",true);
	
$model->addTable("Country_division")
	->addPrimaryKey("id")
	->addForeignKey("country","Country",true,false,true)
	->addForeignKey("parent","Country_division",true,false,true,true)
	->addString("name",50,0,true,false)
	->allowReadAccess()
	->restrictWriteAccess("edit_geography",true)
	->restrictAddAccess("edit_geography",true)
	->restrictRemoveAccess("manage_geography",true);
	
$model->addTable("Geographic_area")
	->addPrimaryKey("id")
	->addForeignKey("country_division","Country_division",true,false,true)
	->addString("name",100,0,true,false)
	->addForeignKey("parent","Geographic_area",true,false,true,true)
	->allowReadAccess()
	->restrictWriteAccess("edit_geography",true)
	->restrictAddAccess("edit_geography",true)
	->restrictRemoveAccess("manage_geography",true);