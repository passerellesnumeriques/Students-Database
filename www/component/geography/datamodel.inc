<?php
/* @var $model DataModel */
$model->addTable("Country")
	->addPrimaryKey("id")
	->addString("code",50,0,true,true)
	->addString("name",50,1,false,false)
	->setRoot()
	->allowReadAccess()
	->addWriteAccess("edit_geography",true)
	->addInsertAccess("edit_geography",true)
	->addRemoveAccess("manage_geography",true);
	
$model->addTable("CountryDivision")
	->addPrimaryKey("id")
	->addForeignKey("country","Country",true,false,true)
	->addForeignKey("parent","CountryDivision",true,false,true,true)
	->addString("name",50,0,true,false)
	->allowReadAccess()
	->addWriteAccess("edit_geography",true)
	->addInsertAccess("edit_geography",true)
	->addRemoveAccess("manage_geography",true);
	
$model->addTable("GeographicArea")
	->addPrimaryKey("id")
	->addForeignKey("country_division","CountryDivision",true,false,true)
	->addString("name",100,0,true,false)
	->addForeignKey("parent","GeographicArea",true,false,true,true)
	->allowReadAccess()
	->addWriteAccess("edit_geography",true)
	->addInsertAccess("edit_geography",true)
	->addRemoveAccess("manage_geography",true);