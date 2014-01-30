<?php
/** This class only aims at giving easy access to the DataBase connection */
class DataBase {

	/** @var $conn DataBaseSystem */
	public static $conn;

	public static function execute($sql_query, $report_errors = true) { return DataBase::$conn->execute($sql_query, $report_errors); }
	public static function nextRow($query_result) { return DataBase::$conn->nextRow($query_result); }
	public static function getInsertID() { return DataBase::$conn->getInsertID(); }
	public static function affectedRows() { return DataBase::$conn->affectedRows(); }
	public static function getLastErrorNumber() { return DataBase::$conn->getLastErrorNumber(); }
	public static function getLastErrorMessage() { return DataBase::$conn->getLastErrorMessage(); }

	public static function escapeString($str) { return DataBase::$conn->escapeString($str); }

}

$db_conf = include("conf/local_db");
require_once("DataBaseSystem_".$db_conf["type"].".inc");
$db_system_class = "DataBaseSystem_".$db_conf["type"];
DataBase::$conn = new $db_system_class;
DataBase::$conn->connect($db_conf["server"], $db_conf["user"], $db_conf["password"]);
?>