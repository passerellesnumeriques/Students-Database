<?php 
require_once("component/data_model/DataBaseUtilities.inc");
$db = SQLQuery::getDataBaseAccessWithoutSecurity();
DataBaseUtilities::importSQL($db, "component/curriculum/data/specialization.sql");
DataBaseUtilities::importSQL($db, "component/curriculum/data/curriculumsubjectcategory.sql");
?>