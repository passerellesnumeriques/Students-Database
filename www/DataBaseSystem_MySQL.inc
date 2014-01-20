<?php
require_once("DataBaseSystem.inc");
/** Implementation of DataBaseSystem, using MySQL */
class DataBaseSystem_MySQL extends DataBaseSystem {

	public function connect($server, $username, $password, $db_name = null) {
		//$this->conn = mysql_pconnect($server, $username, $password);
		//if ($this->conn === false)
			$this->conn = mysqli_connect($server, $username, $password);
		if ($this->conn === false)
			return DataBaseSystem::ERR_CANNOT_CONNECT_TO_SERVER;
		if ($db_name <> null)
			if (!mysqli_select_db($db_name, $this->conn))
				return DataBaseSystem::ERR_DB_DOES_NOT_EXIST;
		return DataBaseSystem::ERR_OK;
	}

	private $conn = null;

	public function select_db($db_name) {
		return mysqli_select_db($this->conn, $db_name);
	}

	protected function execute_sql($sql_query) {
		//echo $sql_query."<br/><br/>";
		return mysqli_query($this->conn, $sql_query);
	}

	public function next_row($query_result) {
		$row = mysqli_fetch_array($query_result);
		if ($row === NULL) return false;
		return $row;
	}

	public function get_insert_id() {
		return mysqli_insert_id($this->conn);
	}

	public function affected_rows() {
		return mysqli_affected_rows($this->conn);
	}

	public function get_last_error_number() {
		return mysqli_errno($this->conn);
	}

	public function get_last_error_message() {
		return mysqli_error($this->conn);
	}

	public function escape_string($str) {
		return mysqli_real_escape_string($this->conn, $str);
	}
	
	public function start_transaction() {
		mysqli_autocommit($this->conn, false);
		mysqli_query($this->conn, "START TRANSACTION;");
	}
	public function commit() {
		mysqli_commit($this->conn);
		mysqli_autocommit($this->conn, true);
	}
	public function rollback() {
		mysqli_rollback($this->conn);
		mysqli_autocommit($this->conn, true);
	}

}
?>