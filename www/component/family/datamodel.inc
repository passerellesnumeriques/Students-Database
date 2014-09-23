<?php 
/* @var $model DataModel */

$model->addTable("Family")
	->addPrimaryKey("id")
	->addDate("entry_date", false, false)
	->addEnum("parents_situation", array("Married","Separated","Divorced","Widower"),true)
	->addReadAccessFromForeignTable("FamilyMember");
	;

$model->addTable("FamilyMember")
	->addPrimaryKey("id")
	->addForeignKey("family", "Family", true, false, true, false, false)
	->addForeignKey("people", "People", true, false, true, true, false)
	->addDate("entry_date", false, false)
	->addEnum("member_type",array("Father","Mother","Child"), true)
	->addString("other_member_type", 50, 1, true, false)
	->addString("education_level", 100, 1, true, false)
	->addString("occupation", 100, 1, true, false)
	->addEnum("occupation_type", array("Regular","Irregular"), true, false)
	->addBoolean("living_with_family", true)
	->addInteger("child_rank", 8, 1) // ? should we put it ?
	->addString("comment",250,0,true,false)
	->addIndex("family","member_type") // to find easily all children, or parents
	->addReadAccessFromForeignTable("People")
	->addReadAccessFromForeignTable("FamilyMember")
	->addRemoveAccessFromForeignTable("People")
	;

?>