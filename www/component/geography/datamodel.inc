<?php
/* @var $model DataModel */
$model->addTable("Country")
	->addPrimaryKey("id")
	->addString("code",50,0,true,true)
	->addString("name",50,1,false,false)
	->addDecimal("west", 3, 6, -180, 180, true, false)
	->addDecimal("east", 3, 6, -180, 180, true, false)
	->addDecimal("north", 3, 6, -90, 90, true, false)
	->addDecimal("south", 3, 6, -90, 90, true, false)
	->addTimestamp("last_modified",false,false)
	->setRoot()
	->allowReadAccess()
	->addWriteAccess("edit_geography",true)
	->addInsertAccess("edit_geography",true)
	->addRemoveAccess("edit_geography",true)
	->onBeforeUpdate(function(&$fields_values, $key, $sub_model_instance) {
		$fields_values["last_modified"] = time();
	})
	;
	
$model->addTable("CountryDivision")
	->addPrimaryKey("id")
	->addForeignKey("country","Country",true,false,true)
	->addForeignKey("parent","CountryDivision",true,false,true,true)
	->addString("name",50,0,true,false)
	->allowReadAccess()
	->addWriteAccess("edit_geography",true)
	->addInsertAccess("edit_geography",true)
	->addRemoveAccess("edit_geography",true)
	->onInsert(function($fields_values, $key, $sub_model_instance) {
		SQLQuery::create()->bypassSecurity()->updateByKey("Country", $fields_values["country"], array("last_modified"=>time()));
	})
	->onUpdate(function($fields_values, $key, $sub_model_instance) {
		if (empty($fields_values["country"]))
			$fields_values["country"] = SQLQuery::create()->bypassSecurity()->select("CountryDivision")->whereValue("CountryDivision","id",$key)->field("country")->executeSingleValue();
		SQLQuery::create()->bypassSecurity()->updateByKey("Country", $fields_values["country"], array("last_modified"=>time()));
	})
	->onRemove(function($row, $sub_model_instance){
		SQLQuery::create()->bypassSecurity()->updateByKey("Country", $row["country"], array("last_modified"=>time()));
	})
	;
	
$model->addTable("GeographicArea")
	->addPrimaryKey("id")
	->addForeignKey("country_division","CountryDivision",true,false,true)
	->addString("name",100,0,true,false)
	->addForeignKey("parent","GeographicArea",true,false,true,true)
	->addDecimal("west", 3, 6, -180, 180, true, false)
	->addDecimal("east", 3, 6, -180, 180, true, false)
	->addDecimal("north", 3, 6, -90, 90, true, false)
	->addDecimal("south", 3, 6, -90, 90, true, false)
	->addIndex("country_division")
	->allowReadAccess()
	->addWriteAccess("edit_geography",true)
	->addInsertAccess("edit_geography",true)
	->addRemoveAccess("edit_geography",true)
	->onInsert(function($fields_values, $key, $sub_model_instance) {
		$country = SQLQuery::create()->select("CountryDivision")->whereValue("CountryDivision","id",$fields_values["country_division"])->field("country")->executeSingleValue();
		SQLQuery::create()->bypassSecurity()->updateByKey("Country", $country, array("last_modified"=>time()));
	})
	->onUpdate(function($fields_values, $key, $sub_model_instance) {
		if (empty($fields_values["country_division"]))
			$fields_values["country_division"] = SQLQuery::create()->bypassSecurity()->select("GeographicArea")->whereValue("GeographicArea","id",$key)->field("country_division")->executeSingleValue();
		$country = SQLQuery::create()->select("CountryDivision")->whereValue("CountryDivision","id",$fields_values["country_division"])->field("country")->executeSingleValue();
		SQLQuery::create()->bypassSecurity()->updateByKey("Country", $country, array("last_modified"=>time()));
	})
	->onRemove(function($row, $sub_model_instance){
		$country = SQLQuery::create()->select("CountryDivision")->whereValue("CountryDivision","id",$row["country_division"])->field("country")->executeSingleValue();
		SQLQuery::create()->bypassSecurity()->updateByKey("Country", $country, array("last_modified"=>time()));
	})
	;
?>