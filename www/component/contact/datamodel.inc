<?php
/* @var $model DataModel */
$model->addTable("Contact")
	->addPrimaryKey("id")
	->addEnum("type",array("email","phone","IM"),true,false)
	->addString("sub_type",30,0,true,false)
	->addString("contact",100,1,false,false)
	->addReadAccessFromForeignTable("OrganizationContact") // no need to check again
	->addReadAccessFromForeignTable("PeopleContact") // no need to check again
	->addReadFilter(function(&$q, $table_alias) {
		$table_people = Datamodel::get()->getTable("PeopleContact");
		$people_alias = $q->getTableAlias("PeopleContact");
		if ($people_alias == null) {
			$people_alias = $q->generateTableAlias();
			$q->join($table_alias, "PeopleContact", array("id"=>"contact"), $people_alias);
		}
		$where = $q->saveWhere();
		$table_people->filterReadAccess($q, $people_alias);
		$where_people = $q->saveWhere();

		$table_org = Datamodel::get()->getTable("OrganizationContact");
		$org_alias = $q->getTableAlias("OrganizationContact");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "OrganizationContact", array("id"=>"contact"), $org_alias);
		}
		$table_org->filterReadAccess($q, $org_alias);
		$where_org = $q->saveWhere();

		$q->where($where);
		$where = "((`".$people_alias."`.people IS NOT NULL";
		if ($where_people <> "") $where .= " AND ".$where_people;
		$where .= ") OR (`".$org_alias."`.organization IS NOT NULL";
		if ($where_org <> "") $where .= " AND ".$where_org;
		$where .= "))";
		$q->where($where);
	})
	->addWriteFilter(function(&$q, $table_alias){
		$table_people = Datamodel::get()->getTable("PeopleContact");
		$people_alias = $q->getTableAlias("PeopleContact");
		if ($people_alias == null) {
			$people_alias = $q->generateTableAlias();
			$q->join($table_alias, "PeopleContact", array("id"=>"contact"), $people_alias);
		}
		$q->field($people_alias, "people", "PEOPLE_CONTACT_FIELD");
		$where = $q->saveWhere();
		$table_people->prepareSelectToDetermineWriteAccess($q, $people_alias); 
		$where_people = $q->saveWhere();
		
		$table_org = Datamodel::get()->getTable("OrganizationContact");
		$org_alias = $q->getTableAlias("OrganizationContact");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "OrganizationContact", array("id"=>"contact"), $org_alias);
		}
		$q->field($org_alias, "organization", "ORG_CONTACT_FIELD");
		$table_org->prepareSelectToDetermineWriteAccess($q, $org_alias); 
		$where_org = $q->saveWhere();

		$q->where($where);
		$where = "((`".$people_alias."`.people IS NOT NULL";
		if ($where_people <> "") $where .= " AND ".$where_people;
		$where .= ") OR (`".$org_alias."`.organization IS NOT NULL";
		if ($where_org <> "") $where .= " AND ".$where_org;
		$where .= "))";
		$q->where($where);
	},function($rows) {
		$people_rows = array();
		$org_rows = array();
		foreach ($rows as $row) {
			if ($row["PEOPLE_CONTACT_FIELD"] <> null) array_push($people_rows, $row);
			if ($row["ORG_CONTACT_FIELD"] <> null) array_push($org_rows, $row);
		}
		$final = array();
		$table_people = Datamodel::get()->getTable("PeopleContact");
		array_push($final, $table_people->filterWriteAccess($people_rows)); 
		$table_org = Datamodel::get()->getTable("OrganizationContact");
		array_push($final, $table_org->filterWriteAccess($org_rows));
		return $final; 
	})
	->addRemoveFilter(function(&$q, $table_alias, &$locks){
		$table_people = Datamodel::get()->getTable("PeopleContact");
		$people_alias = $q->getTableAlias("PeopleContact");
		if ($people_alias == null) {
			$people_alias = $q->generateTableAlias();
			$q->join($table_alias, "PeopleContact", array("id"=>"contact"), $people_alias);
		}
		$q->field($people_alias, "people", "PEOPLE_CONTACT_FIELD");
		$where = $q->saveWhere();
		$table_people->prepareSelectToDetermineRemoveAccess($q, $people_alias, $locks);
		$where_people = $q->saveWhere();
		
		$table_org = Datamodel::get()->getTable("OrganizationContact");
		$org_alias = $q->getTableAlias("OrganizationContact");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "OrganizationContact", array("id"=>"contact"), $org_alias);
		}
		$q->field($org_alias, "organization", "ORG_CONTACT_FIELD");
		$table_org->prepareSelectToDetermineRemoveAccess($q, $org_alias, $locks);
		$where_org = $q->saveWhere();
		
		$q->where($where);
		$where = "((`".$people_alias."`.people IS NOT NULL";
		if ($where_people <> "") $where .= " AND ".$where_people;
		$where .= ") OR (`".$org_alias."`.organization IS NOT NULL";
		if ($where_org <> "") $where .= " AND ".$where_org;
		$where .= "))";
		$q->where($where);
		
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		$lock_id = DataBaseLock::lockTable("PeopleContact", $locked_by);
		if ($lock_id <> null) array_push($locks, $lock_id);
		else throw new Exception("The contacts are already locked by ".$locked_by.". Please try again later.");
		$locked_by = null;
		$lock_id = DataBaseLock::lockTable("OrganizationContact", $locked_by);
		if ($lock_id <> null) array_push($locks, $lock_id);
		else throw new Exception("The contacts are already locked by ".$locked_by.". Please try again later.");
	}, function($rows) {
		$people_rows = array();
		$org_rows = array();
		foreach ($rows as $row) {
			if ($row["PEOPLE_CONTACT_FIELD"] <> null) array_push($people_rows, $row);
			if ($row["ORG_CONTACT_FIELD"] <> null) array_push($org_rows, $row);
		}
		$final = array();
		$table_people = Datamodel::get()->getTable("PeopleContact");
		foreach ($table_people->filterWriteAccess($people_rows) as $row) array_push($final, $row);
		$table_org = Datamodel::get()->getTable("OrganizationContact");
		foreach ($table_org->filterWriteAccess($org_rows) as $row) array_push($final, $row);
		return $final;
	})
	;
	
$model->addTable("PeopleContact")
	->addForeignKey("contact","Contact",true,true,true,false)
	->addForeignKey("people","People",true,false,true,false)
	->setKey(array("contact","people"))
	->addIndex("people")
	->addReadAccessFromForeignTable("People") // no need to check again
	->addReadFilter(function(&$q, $table_alias) {
		PNApplication::$instance->people->filterReadAccess($q, $table_alias, "people");
	})
	->addRemoveFilter(function(&$q,$table_alias,&$locks){
		PNApplication::$instance->people->prepareSelectToDetermineRemoveAccess($q, $table_alias, "people", $locks);
	},function($rows){
		return PNApplication::$instance->people->filterRemoveAccess($rows);
	})
	->addInsertFilter(function($values){
		return PNApplication::$instance->people->canModify($values["people"]);
	})
	;
	
$model->addTable("PostalAddress")
	->addPrimaryKey("id")
	->addForeignKey("country","Country",true,false,true,true)
	->addForeignKey("geographic_area","GeographicArea",false,false,true,true)
	->addString("street",100,0,true,false)
	->addString("street_number",50,0,true,false)
	->addString("building",100,0,true,false)
	->addString("unit",100,0,true,false)
	->addString("additional",100,0,true,false)
	->addString("address_type",100,0,true,false)
	->addDecimal("lat", 3, 6, -90, 90, true, false)
	->addDecimal("lng", 3, 6, -180, 180, true, false)
	->addReadAccessFromForeignTable("OrganizationAddress") // no need to check again
	->addReadAccessFromForeignTable("PeopleAddress") // no need to check again
	->addReadFilter(function(&$q, $table_alias) {
		$table_people = Datamodel::get()->getTable("PeopleAddress");
		$people_alias = $q->getTableAlias("PeopleAddress");
		if ($people_alias == null) {
			$people_alias = $q->generateTableAlias();
			$q->join($table_alias, "PeopleAddress", array("id"=>"address"), $people_alias);
		}
		$table_people->filterReadAccess($q, $people_alias);

		$table_org = Datamodel::get()->getTable("OrganizationAddress");
		$org_alias = $q->getTableAlias("OrganizationAddress");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "OrganizationAddress", array("id"=>"address"), $org_alias);
		}
		$table_org->filterReadAccess($q, $org_alias);

		$q->where("(`".$people_alias."`.people IS NOT NULL OR `".$org_alias."`.organization IS NOT NULL)");
	})
	->addWriteFilter(function(&$q, $table_alias){
		$table_people = Datamodel::get()->getTable("PeopleAddress");
		$people_alias = $q->getTableAlias("PeopleAddress");
		if ($people_alias == null) {
			$people_alias = $q->generateTableAlias();
			$q->join($table_alias, "PeopleAddress", array("id"=>"address"), $people_alias);
		}
		$q->field($people_alias, "people", "PEOPLE_ADDRESS_FIELD");
		$table_people->prepareSelectToDetermineWriteAccess($q, $people_alias); 
		
		$table_org = Datamodel::get()->getTable("OrganizationAddress");
		$org_alias = $q->getTableAlias("OrganizationAddress");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "OrganizationAddress", array("id"=>"address"), $org_alias);
		}
		$q->field($org_alias, "organization", "ORG_ADDRESS_FIELD");
		$table_org->prepareSelectToDetermineWriteAccess($q, $org_alias); 
	},function($rows) {
		$people_rows = array();
		$org_rows = array();
		foreach ($rows as $row) {
			if ($row["PEOPLE_ADDRESS_FIELD"] <> null) array_push($people_rows, $row);
			if ($row["ORG_ADDRESS_FIELD"] <> null) array_push($org_rows, $row);
		}
		$final = array();
		$table_people = Datamodel::get()->getTable("PeopleAddress");
		array_push($final, $table_people->filterWriteAccess($people_rows)); 
		$table_org = Datamodel::get()->getTable("OrganizationAddress");
		array_push($final, $table_org->filterWriteAccess($org_rows));
		return $final; 
	})
	->addRemoveFilter(function(&$q, $table_alias, &$locks){
		$table_people = Datamodel::get()->getTable("PeopleAddress");
		$people_alias = $q->getTableAlias("PeopleAddress");
		if ($people_alias == null) {
			$people_alias = $q->generateTableAlias();
			$q->join($table_alias, "PeopleAddress", array("id"=>"address"), $people_alias);
		}
		$q->field($people_alias, "people", "PEOPLE_ADDRESS_FIELD");
		$table_people->prepareSelectToDetermineRemoveAccess($q, $people_alias, $locks);
		
		$table_org = Datamodel::get()->getTable("OrganizationAddress");
		$org_alias = $q->getTableAlias("OrganizationAddress");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "OrganizationAddress", array("id"=>"address"), $org_alias);
		}
		$q->field($org_alias, "organization", "ORG_ADDRESS_FIELD");
		$table_org->prepareSelectToDetermineRemoveAccess($q, $org_alias, $locks);

		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		$lock_id = DataBaseLock::lockTable("PeopleAddress", $locked_by);
		if ($lock_id <> null) array_push($locks, $lock_id);
		else throw new Exception("The addresses are already locked by ".$locked_by.". Please try again later.");
		$locked_by = null;
		$lock_id = DataBaseLock::lockTable("OrganizationAddress", $locked_by);
		if ($lock_id <> null) array_push($locks, $lock_id);
		else throw new Exception("The addresses are already locked by ".$locked_by.". Please try again later.");
	}, function($rows) {
		$people_rows = array();
		$org_rows = array();
		foreach ($rows as $row) {
			if ($row["PEOPLE_ADDRESS_FIELD"] <> null) array_push($people_rows, $row);
			if ($row["ORG_ADDRESS_FIELD"] <> null) array_push($org_rows, $row);
		}
		$final = array();
		$table_people = Datamodel::get()->getTable("PeopleAddress");
		foreach ($table_people->filterWriteAccess($people_rows) as $row) array_push($final, $row);
		$table_org = Datamodel::get()->getTable("OrganizationAddress");
		foreach ($table_org->filterWriteAccess($org_rows) as $row) array_push($final, $row);
		return $final;
	})
	; // no direct access allowed
	
$model->addTable("PeopleAddress")
	->addForeignKey("address","PostalAddress",true,true,true,false)
	->addForeignKey("people","People",true,false,true,false)
	->setKey(array("address","people"))
	->addIndex("people")
	->addReadAccessFromForeignTable("People") // no need to check again
	->addReadFilter(function(&$q, $table_alias) {
		PNApplication::$instance->people->filterReadAccess($q, $table_alias, "people");
	})
	->addRemoveFilter(function(&$q,$table_alias,&$locks){
		PNApplication::$instance->people->prepareSelectToDetermineRemoveAccess($q, $table_alias, "people", $locks);
	},function($rows){
		return PNApplication::$instance->people->filterRemoveAccess($rows);
	})
	->addInsertFilter(function($values){
		return PNApplication::$instance->people->canModify($values["people"]);
	})
	;

$model->addTable("Organization")
	->addPrimaryKey("id")
	->addString("name",100,1,false,false)
	->addString("creator",100,1,false,false)
	->addBoolean("fake",true) //if true, not displayable, internal usage only
	->setRoot()
	->addIndex("creator")
	->addReadFilter(function(&$q, $table_alias){
		require_once("component/contact/OrganizationPlugin.inc");
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->canReadOrganization()) array_push($allowed, $pi->getOrganizationCreator());
			}
		}
		if (count($allowed) == 0) {
			$q->where("`".$table_alias."`.`creator` IS NULL"); // remove all, nothing can be displayed
			return;
		}
		$w = "(";
		$first = true;
		foreach ($allowed as $creator) {
			if ($first) $first = false; else $w .= " OR ";
			$w .= "`".$table_alias."`.`creator`='".SQLQuery::escape($creator)."'";
		}
		$w .= ")";
		$q->where($w);
		// add a filter on the fake aspect of the organization
		$q->where("`".$table_alias."`.`fake` IS NULL");
	})
	->addWriteFilter(function(&$q, $table_alias){
		$q->field($table_alias, "creator", "ORG_CREATOR");
	},function($rows){
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->canWriteOrganization()) array_push($allowed, $pi->getOrganizationCreator());
			}
		}
		$final = array();
		foreach ($rows as $row)
			if (in_array($row["ORG_CREATOR"], $allowed))
				array_push($final, $row);
		return $final;
	})
	->addInsertFilter(function($values){
		if (!isset($values["creator"])) return false;
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->getOrganizationCreator() == $values["creator"])
					return $pi->canInsertOrganization();
			}
		}
		return false;
	})
	->addRemoveFilter(function(&$q, $table_alias, &$locks){
		$q->field($table_alias, "creator", "ORG_CREATOR");
		// TODO locks
	},function($rows){
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->canRemoveOrganization()) array_push($allowed, $pi->getOrganizationCreator());
			}
		}
		$final = array();
		foreach ($rows as $row)
			if (in_array($row["ORG_CREATOR"], $allowed))
				array_push($final, $row);
		return $final;
	})
	;

$model->addTable("OrganizationType")
	->addPrimaryKey("id")
	->addString("name",100,1,false,false)
	->addString("creator",100,1,false,false)
	->addBoolean("builtin", false)
	->addIndex("creator")
	->addReadFilter(function(&$q, $table_alias){
		require_once("component/contact/OrganizationPlugin.inc");
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->canReadOrganization()) array_push($allowed, $pi->getOrganizationCreator());
			}
		}
		if (count($allowed) == 0) {
			$q->where("`".$table_alias."`.`creator` IS NULL"); // remove all, nothing can be displayed
			return;
		}
		$w = "(";
		$first = true;
		foreach ($allowed as $creator) {
			if ($first) $first = false; else $w .= " OR ";
			$w .= "`".$table_alias."`.`creator`='".SQLQuery::escape($creator)."'";
		}
		$w .= ")";
		$q->where($w);
	})
	->addWriteFilter(function(&$q, $table_alias){
		$q->field($table_alias, "creator", "ORG_TYPE_CREATOR");
	},function($rows){
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->canWriteOrganization()) array_push($allowed, $pi->getOrganizationCreator());
			}
		}
		$final = array();
		foreach ($rows as $row)
			if (in_array($row["ORG_TYPE_CREATOR"], $allowed))
				array_push($final, $row);
		return $final;
	})
	->addInsertFilter(function($values){
		if (!isset($values["creator"])) return false;
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->getOrganizationCreator() == $values["creator"])
					return $pi->canInsertOrganization();
			}
		}
		return false;
	})
	->addRemoveFilter(function(&$q, $table_alias, &$locks){
		$q->field($table_alias, "creator", "ORG_TYPE_CREATOR");
		// TODO locks
	},function($rows){
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->canRemoveOrganization()) array_push($allowed, $pi->getOrganizationCreator());
			}
		}
		$final = array();
		foreach ($rows as $row)
			if (in_array($row["ORG_TYPE_CREATOR"], $allowed))
			array_push($final, $row);
		return $final;
	})
	;

$model->addTable("OrganizationTypes")
	->addForeignKey("organization","Organization",true,false,true,false)
	->addForeignKey("type","OrganizationType",true,false,true,false)
	->setKey(array("organization","type"))
	->addIndex("organization")
	->addReadFilter(function(&$q, $table_alias){
		$org_alias = $q->getTableAlias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}

		require_once("component/contact/OrganizationPlugin.inc");
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->canReadOrganization()) array_push($allowed, $pi->getOrganizationCreator());
			}
		}
		if (count($allowed) == 0) {
			$q->where("`".$org_alias."`.`creator` IS NULL"); // remove all, nothing can be displayed
			return;
		}
		$w = "(";
		$first = true;
		foreach ($allowed as $creator) {
			if ($first) $first = false; else $w .= " OR ";
			$w .= "`".$org_alias."`.`creator`='".SQLQuery::escape($creator)."'";
		}
		$w .= ")";
		$q->where($w);
	})
	->addWriteFilter(function(&$q, $table_alias){
		$org_alias = $q->getTableAlias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$q->field($org_alias, "creator", "ORG_CREATOR");
	},function($rows){
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->canWriteOrganization()) array_push($allowed, $pi->getOrganizationCreator());
			}
		}
		$final = array();
		foreach ($rows as $row)
			if (in_array($row["ORG_CREATOR"], $allowed))
				array_push($final, $row);
		return $final;
	})
	->addInsertFilter(function($values){
		try {
			$org = SQLQuery::create()->select("Organization")->whereValue("Organization", "id", $values["organization"])->executeSingleRow();
		} catch (Exception $e) { return false; }
		if ($org == null) return false;
		
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->getOrganizationCreator() == $org["creator"])
					return $pi->canInsertOrganization();
			}
		}
		return false;
	})
	->addRemoveFilter(function(&$q, $table_alias, &$locks){
		$org_alias = $q->getTableAlias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$q->field($org_alias, "creator", "ORG_CREATOR");
		// TODO locks
	},function($rows){
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->canRemoveOrganization()) array_push($allowed, $pi->getOrganizationCreator());
			}
		}
		$final = array();
		foreach ($rows as $row)
			if (in_array($row["ORG_CREATOR"], $allowed))
			array_push($final, $row);
		return $final;
	})
	;

$model->addTable("OrganizationContact")
	->addForeignKey("organization","Organization",true,false,true,false)
	->addForeignKey("contact","Contact",true,false,true,false)
	->setKey(array("organization","contact"))
	->addIndex("organization")
	->addReadAccessFromForeignTable("Organization") // if we come already from the organization, no need to check again
	->addReadFilter(function(&$q, $table_alias) {
		$table = Datamodel::get()->getTable("Organization");
		$org_alias = $q->getTableAlias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$table->filterReadAccess($q, $org_alias);
	})
	->addRemoveFilter(function(&$q,$table_alias,&$locks){
		$table = Datamodel::get()->getTable("Organization");
		$org_alias = $q->getTableAlias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$table->prepareSelectToDetermineRemoveAccess($q, $org_alias, $locks);
	},function($rows){
		$table = Datamodel::get()->getTable("Organization");
		return $table->filterRemoveAccess($rows);
	})
	->addInsertFilter(function($values){
		$q = SQLQuery::create();
		$table_alias = $q->generateTableAlias();
		$q->select(array("Organization"=>$table_alias));
		$q->whereValue($table_alias, "id", $values["organization"]);
		$table = DataModel::get()->getTable("Organization");
		$table->prepareSelectToDetermineWriteAccess($q, $table_alias);
		$rows = $q->execute();
		$rows = $table->filterWriteAccess($rows);
		if (count($rows) == 0) return false;
		return true;
	})
	;
	
$model->addTable("OrganizationAddress")
	->addForeignKey("organization","Organization",true,false,true,false)
	->addForeignKey("address","PostalAddress",true,false,true,false)
	->setKey(array("organization","address"))
	->addIndex("organization")
	->addReadAccessFromForeignTable("Organization") // if we come already from the organization, no need to check again
	->addReadFilter(function(&$q, $table_alias) {
		$table = Datamodel::get()->getTable("Organization");
		$org_alias = $q->getTableAlias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$table->filterReadAccess($q, $org_alias);
	})
	->addRemoveFilter(function(&$q,$table_alias, &$locks){
		$table = Datamodel::get()->getTable("Organization");
		$org_alias = $q->getTableAlias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$table->prepareSelectToDetermineRemoveAccess($q, $org_alias, $locks);
	},function($rows){
		$table = Datamodel::get()->getTable("Organization");
		return $table->filterRemoveAccess($rows);
	})
	->addInsertFilter(function($values){
		$q = SQLQuery::create();
		$table_alias = $q->generateTableAlias();
		$q->select(array("Organization"=>$table_alias));
		$q->whereValue($table_alias, "id", $values["organization"]);
		$table = DataModel::get()->getTable("Organization");
		$table->prepareSelectToDetermineWriteAccess($q, $table_alias);
		$rows = $q->execute();
		$rows = $table->filterWriteAccess($rows);
		if (count($rows) == 0) return false;
		return true;
	})
	;
	
$model->addTable("ContactPoint")
	->addForeignKey("organization","Organization",true,false,true,false)
	->addForeignKey("people","People",true,false,true,false)
	->setKey(array("organization","people"))
	->addIndex("organization")
	->addString("designation",100,1,false,false)
	->addReadAccessFromForeignTable("Organization") // if we come already from the organization, no need to check again
	->addReadFilter(function(&$q, $table_alias) {
		$table = Datamodel::get()->getTable("Organization");
		$org_alias = $q->getTableAlias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$table->filterReadAccess($q, $org_alias);
	})
	->addRemoveFilter(function(&$q,$table_alias, &$locks){
		$table = Datamodel::get()->getTable("Organization");
		$org_alias = $q->getTableAlias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$table->prepareSelectToDetermineRemoveAccess($q, $org_alias, $locks);
	},function($rows){
		$table = Datamodel::get()->getTable("Organization");
		return $table->filterRemoveAccess($rows);
	})
	->addInsertFilter(function($values){
		$q = SQLQuery::create();
		$table_alias = $q->generateTableAlias();
		$q->select(array("Organization"=>$table_alias));
		$q->whereValue($table_alias, "id", $values["organization"]);
		$table = DataModel::get()->getTable("Organization");
		$table->prepareSelectToDetermineWriteAccess($q, $table_alias);
		$rows = $q->execute();
		$rows = $table->filterWriteAccess($rows);
		if (count($rows) == 0) return false;
		return true;
	})
	->addWriteFilter(function(&$q, $table_alias){
		$org_alias = $q->getTableAlias("Organization");
		if ($org_alias == null) {
			$org_alias = $q->generateTableAlias();
			$q->join($table_alias, "Organization", array("organization"=>"id"), $org_alias);
		}
		$q->field($org_alias, "creator", "ORG_CREATOR");
	},function($rows){
		$allowed = array();
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->canWriteOrganization()) array_push($allowed, $pi->getOrganizationCreator());
			}
		}
		$final = array();
		foreach ($rows as $row)
			if (in_array($row["ORG_CREATOR"], $allowed))
				array_push($final, $row);
		return $final;
	})
	;
	
$model->addTable("Place")
	->addPrimaryKey("id")
	->addString("name",250,1,false,false)
	->addForeignKey("address", "PostalAddress", true, true, false, false, true)
	;
?>