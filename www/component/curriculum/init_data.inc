<?php 
require_once("component/data_model/DataBaseUtilities.inc");
$db = SQLQuery::getDataBaseAccessWithoutSecurity();
DataBaseUtilities::importSQL($db, dirname(__FILE__)."/data/specialization.sql");
DataBaseUtilities::importSQL($db, dirname(__FILE__)."/data/curriculumsubjectcategory.sql");
?>