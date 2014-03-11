<?php 
/* @var $model DataModel */

$model->addDataCategoryLink("Personal Information", "/dynamic/people/page/profile?plugin=people&people=%People.id%", "/static/people/profile_16.png");

$model->internalGetTable("People")
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Personal Information",
		array(
			"first_name"=>"First Name",
			"middle_name"=>"Middle Name",
			"last_name"=>"Last Name",
			"khmer_first_name"=>"Khmer First Name",
			"khmer_last_name"=>"Khmer Last Name",
			"sex"=>"Gender",
			"birth"=>"Birth Date",
		)
	));

?>