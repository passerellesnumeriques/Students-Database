<?php
/* @var $model DataModel */
$model->addDataCategoryLink("Personal Information", "/dynamic/people/page/profile?plugin=people&people=%People.id%", "/static/people/profile_16.png");
$model->addTable("People")
	->addPrimaryKey("id")
	->addString("first_name", 100, 1, false)
	->addString("middle_name", 100, 1, true)
	->addString("last_name", 100, 1, false)
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
	->restrictReadAccess("see_other_people_details",true)
	->allowSpecificReadAccess("id",function() { return PNApplication::$instance->user_people->user_people_id; })
	->restrictWriteAccess("edit_people_details",true)
	->restrictAddAccess("create_people",true)
	->restrictRemoveAccess("remove_people",true)
	;
?>