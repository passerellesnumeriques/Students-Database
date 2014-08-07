<?php 
require_once("component/data_model/DataBaseUtilities.inc");
$db = SQLQuery::getDataBaseAccessWithoutSecurity();
DataBaseUtilities::importSQL($db, dirname(__FILE__)."/data/countries.sql");
DataBaseUtilities::importSQL($db, dirname(__FILE__)."/data/countrydivision.sql");
DataBaseUtilities::importSQL($db, dirname(__FILE__)."/data/geographicarea.sql");
?>