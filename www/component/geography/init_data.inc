<?php 
require_once("component/data_model/DataBaseUtilities.inc");
$db = SQLQuery::getDataBaseAccessWithoutSecurity();
DataBaseUtilities::importSQL($db, "component/geography/data/countries.sql");
DataBaseUtilities::importSQL($db, "component/geography/data/countrydivision.sql");
DataBaseUtilities::importSQL($db, "component/geography/data/geographicarea.sql");
?>