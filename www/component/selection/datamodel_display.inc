<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$sm->internalGetTable("Applicant")
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Applicant Information",
		array(
			"applicant_id"=>"Applicant ID",
			// "excluded"=>"Excluded",
			// "exclusion_reason"=>"Exclusion Reason",
		)
	));

?>