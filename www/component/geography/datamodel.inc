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
	->onBeforeUpdate(function(&$keys_fields_values, $sub_model_instance) {
		for ($i = 0; $i < count($keys_fields_values); $i++)
			$keys_fields_values[$i][1]["last_modified"] = time();
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
	->onInsert(function($inserted) {
		$to_update = array(array(array(),array("last_modified"=>time())));
		foreach ($inserted as $row)
			if (!in_array($row["fields_values"]["country"], $to_update[0][0]))
				array_push($to_update[0][0], $row["fields_values"]["country"]);
		SQLQuery::create()->bypassSecurity()->updateByKeys("Country", $to_update);
	})
	->onUpdate(function($keys_fields_values, $sub_model_instance) {
		$countries = array();
		foreach ($keys_fields_values as $updated) {
			$keys = $updated[0];
			$fields_values = $updated[1];
			if (!empty($fields_values["country"])) {
				if (!in_array($fields_values["country"], $countries))
					array_push($countries, $fields_values["country"]);
			} else {
				$list = SQLQuery::create()->bypassSecurity()->select("CountryDivision")->whereIn("CountryDivision","id",$keys)->field("country")->distinct()->executeSingleField();
				foreach ($list as $country_id)
					if (!in_array($country_id, $countries))
						array_push($countries, $country_id);
			}
		}
		SQLQuery::create()->bypassSecurity()->updateByKeys("Country", array(array($countries,array("last_modified"=>time()))));
	})
	->onRemove(function($table, $rows, $sub_model_instance){
		$to_update = array(array(),array("last_modified"=>time()));
		foreach ($rows as $row)
			if (!in_array($row["country"], $to_update[0][0]))
				array_push($to_update[0][0], $row["country"]);
		SQLQuery::create()->bypassSecurity()->updateByKeys("Country", $to_update);
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
	->onInsert(function($inserted) {
		$division_ids = array();
		foreach ($inserted as $row) if (!in_array($row["fields_values"]["country_division"], $division_ids)) array_push($division_ids,$row["fields_values"]["country_division"]);
		$countries = SQLQuery::create()->select("CountryDivision")->whereIn("CountryDivision","id",$division_ids)->field("country")->distinct()->executeSingleField();
		SQLQuery::create()->bypassSecurity()->updateByKeys("Country", array($countries, array("last_modified"=>time())));
	})
	->onUpdate(function($keys_fields_values, $sub_model_instance) {
		$divisions = array();
		foreach ($keys_fields_values as $updated) {
			$keys = $updated[0];
			$fields_values = $updated[1];
			if (!empty($fields_values["country_division"])) {
				if (!in_array($fields_values["country_division"], $divisions))
					array_push($divisions, $fields_values["country_division"]);
			} else {
				$list = SQLQuery::create()->bypassSecurity()->select("GeographicArea")->whereIn("GeographicArea","id",$keys)->field("country_division")->distinct()->executeSingleField();
				foreach ($list as $division_id)
					if (!in_array($division_id, $divisions))
						array_push($divisions, $division_id);
			}
		}
		$countries = SQLQuery::create()->select("CountryDivision")->whereIn("CountryDivision","id",$divisions)->field("country")->distinct()->executeSingleField();
		SQLQuery::create()->bypassSecurity()->updateByKeys("Country", array(array($countries, array("last_modified"=>time()))));
	})
	->onRemove(function($table, $rows, $sub_model_instance){
		$ids = array();
		foreach ($rows as $row) if (!in_array($row["country_division"], $ids)) array_push($ids, $row["country_division"]);
		$countries = SQLQuery::create()->select("CountryDivision")->whereIn("CountryDivision","id",$ids)->field("country")->distinct()->executeSingleField();
		SQLQuery::create()->bypassSecurity()->updateByKeys("Country", array($countries, array("last_modified"=>time())));
	})
	;
?>