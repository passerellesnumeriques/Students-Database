<?php
/** This class only aims at giving easy access to the DataBase connection */
class DataBase {

	/** @var $conn DataBaseSystem */
	public static $conn;

	public static function execute($sql_query, $report_errors = true) { return DataBase::$conn->execute($sql_query, $report_errors); }
	public static function next_row($query_result) { return DataBase::$conn->next_row($query_result); }
	public static function get_insert_id() { return DataBase::$conn->get_insert_id(); }
	public static function affected_rows() { return DataBase::$conn->affected_rows(); }
	public static function get_last_error_number() { return DataBase::$conn->get_last_error_number(); }
	public static function get_last_error_message() { return DataBase::$conn->get_last_error_message(); }

	public static function escape_string($str) { return DataBase::$conn->escape_string($str); }

}

$db_conf = include("conf/local_db");
require_once("DataBaseSystem_".$db_conf["type"].".inc");
$db_system_class = "DataBaseSystem_".$db_conf["type"];
DataBase::$conn = new $db_system_class;
DataBase::$conn->connect($db_conf["server"], $db_conf["user"], $db_conf["password"]);
?>