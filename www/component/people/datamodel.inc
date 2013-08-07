<?php
/* @var $model DataModel */
$model->addDataCategoryLink("Personal Information", "/dynamic/people/page/profile?plugin=people&people=%People.id%");
$model->addTable("People")
	->addPrimaryKey("id")
	->addString("first_name", 100, 1, false)
	->addString("middle_name", 100, 1, true)
	->addString("last_name", 100, 1, false)
	->addString("khmer_first_name", 100, 1, true)
	->addString("khmer_last_name", 100, 1, true)
	->addEnum("sex", array("M","F"))
	->addDate("birth", true)
	->addForeignKey("picture", "Storage")
	->addInteger("picture_version")
	->addDisplayableData("first_name", "Personal Information", "First Name")
	->addDisplayableData("middle_name", "Personal Information", "Middle Name")
	->addDisplayableData("last_name", "Personal Information", "Last Name")
	->addDisplayableData("khmer_first_name", "Personal Information", "Khmer First Name")
	->addDisplayableData("khmer_last_name", "Personal Information", "Khmer Last Name")
	->addDisplayableData("sex", "Personal Information", "Sex")
	->addDisplayableData("birth", "Personal Information", "Birth Date")
	->restrictReadAccess("see_other_people_details",true)
	->allowSpecificReadAccess("id",function() { return PNApplication::$instance->user_people->user_people_id; })
	->restrictWriteAccess("edit_people_details",true);
?>