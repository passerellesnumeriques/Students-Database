<?php 
/* @var $model DataModel */

$model->addTable("Family")
	->addPrimaryKey("id")
	->addDate("entry_date", false, false)
	->addInteger("nb_children", 8, 0)
	->addInteger("nb_living_in_family", 8, 0)
	->addEnum("parents_situation", array("Married","Separated","Divorced","Widower"))
	->addReadAccessFromForeignTable("FamilyMember");
	;

$model->addTable("FamilyMember")
	->addForeignKey("family", "Family", true, false, true, false, false)
	->addForeignKey("people", "People", true, false, true, false, false)
	->setKey(array("family","people"))
	->addDate("entry_date", false, false)
	->addEnum("member_type",array("Father","Mother","Child"), true)
	->addString("other_member_type", 50, 1, false, false)
	->addString("education_level", 100, 1, true, false)
	->addString("occupation", 100, 1, true, false)
	->addBoolean("living_with_family", true)
	->addInteger("child_rank", 8, 1) // ? should we put it ?
	->addReadAccessFromForeignTable("People")
	;

?>