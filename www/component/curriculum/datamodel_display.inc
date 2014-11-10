<?php
$category = new datamodel\DataDisplayCategory("Student", "/static/students/student_16.png");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("StudentBatch", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("name", "Batch Name"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("start_date", "Integration Date", true));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("end_date", "Graduation Date", true));
$model->addTableDataDisplay($display);
?>