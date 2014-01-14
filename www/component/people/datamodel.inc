<?php
require_once("utilities.inc");
/* @var $model DataModel */
$model->addDataCategoryLink("Personal Information", "/dynamic/people/page/profile?plugin=people&people=%People.id%", "/static/people/profile_16.png");
$model->addTable("People")
	->addPrimaryKey("id")
	->addString("first_name", 100, 1, false, false, function($s) { return $s === null ? null : capitalizeWordsFirstLetter($s); })
	->addString("middle_name", 100, 1, true, false, function($s) { return $s === null ? null : capitalizeWordsFirstLetter($s); })
	->addString("last_name", 100, 1, false, false, function($s) { return $s === null ? null : capitalizeWordsFirstLetter($s); })
	->addString("khmer_first_name", 100, 1, true)
	->addString("khmer_last_name", 100, 1, true)
	->addEnum("sex", array("M","F"))
	->addDate("birth", true)
	->addForeignKey("picture", "Storage", false, false, false)
	->addInteger("picture_version")
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Personal Information", "Personal Information",
		array(
			"first_name"=>"First Name",
			"middle_name"=>"Middle Name",
			"last_name"=>"Last Name",
			"khmer_first_name"=>"Khmer First Name",
			"khmer_last_name"=>"Khmer Last Name",
			"sex"=>"Gender",
			"birth"=>"Birth Date",
		)
	))
	->addReadFilter(function(&$q, $table_alias) {
		// allow to read user own info
		$w = "(`".$table_alias."`.`id`='".PNApplication::$instance->user_people->user_people_id."')";
		// restrict by type of people
		require_once("PeoplePlugin.inc");
		foreach (PNApplication::$instance->components as $name=>$c) {
			if (!($c instanceof PeoplePlugin)) continue;
			$wc = $c->filterPeopleReadAccess($q, $table_alias);
			if ($wc <> null)
				$w .= " OR (".$wc.")";
		}
		$q->where("(".$w.")");
	})
	->addWriteFilter(function(&$q, $table_alias) {
		require_once("PeoplePlugin.inc");
		foreach (PNApplication::$instance->components as $name=>$c) {
			if (!($c instanceof PeoplePlugin)) continue;
			$c->prepareSelectPeopleWriteAccess($q, $table_alias);
		}
	},function($rows) {
		require_once("PeoplePlugin.inc");
		$final = array();
		foreach (PNApplication::$instance->components as $name=>$c) {
			if (!($c instanceof PeoplePlugin)) continue;
			foreach ($c->filterPeopleWriteAccess($rows) as $row) {
				array_push($final, $row);
				for ($i = 0; $i < count($rows); $i++)
					if ($rows[$i] == $row) {
						array_splice($rows, $i, 1);
						break;
					}
			}
		}
		return $final;
	})
	->addRemoveFilter(function(&$q, $table_alias, &$locks) {
		require_once("PeoplePlugin.inc");
		foreach (PNApplication::$instance->components as $name=>$c) {
			if (!($c instanceof PeoplePlugin)) continue;
			$c->prepareSelectPeopleRemoveAccess($q, $table_alias, $locks);
		}
	},function($rows) {
		require_once("PeoplePlugin.inc");
		$final = array();
		foreach (PNApplication::$instance->components as $name=>$c) {
			if (!($c instanceof PeoplePlugin)) continue;
			foreach ($c->filterPeopleRemoveAccess($rows) as $row) {
				array_push($final, $row);
				for ($i = 0; $i < count($rows); $i++)
					if ($rows[$i] == $row) {
						array_splice($rows, $i, 1);
						break;
					}
			}
		}
		return $final;
	})
	;
?>