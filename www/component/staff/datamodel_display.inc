<?php 
$model->internalGetTable("StaffPosition")
	->setDisplayHandler(null, new datamodel\MultipleDisplayHandler("Staff", array(
		new datamodel\FieldsDisplayHandler("Staff", array(
			"position" => "Position",
			"start" => "Position Start",
			"end" => "Position End"
		)),
		new datamodel\ChoiceDataDisplay(null, "department", "name", "Department")
	)));

?>