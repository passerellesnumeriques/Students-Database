<?php 
/* @var $model DataModel */
/* @var $sm SubDataModel */

$model->addDataCategoryLink("Information Session", "/dynamic/selection/page/IS/profile?id=%InformationSession.id%", "/static/selection/IS/IS_16.png");

$sm->internalGetTable("InformationSession")
	->setDisplayHandler(null,
		new datamodel\MultipleDisplayHandler("Information Session", array(
			new datamodel\FieldsDisplayHandler("Information Session",
				array(
					"name" => "Name",
				),
				array(
					"name",
				),"selection"
			),
			new CalendarEventDateDataDisplay(null, "Date", "date")
		),array("date"))
	);
?>