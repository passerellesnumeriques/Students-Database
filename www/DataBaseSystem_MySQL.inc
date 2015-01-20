<?php
require_once("DataBaseSystem.inc");
/** Implementation of DataBaseSystem, using MySQL */
class DataBaseSystem_MySQL extends DataBaseSystem {

	public function connect($server, $username, $password, $db_name = null, $port = null) {
		//$this->conn = mysql_pconnect($server, $username, $password);
		//if ($this->conn === false)
			$this->conn = mysqli_connect($server, $username, $password, "", $port == null ? 3306 : $port);
		if ($this->conn === false)
			return DataBaseSystem::ERR_CANNOT_CONNECT_TO_SERVER;
		if ($db_name <> null)
			if (!mysqli_select_db($db_name, $this->conn))
				return DataBaseSystem::ERR_DB_DOES_NOT_EXIST;
		mysqli_query($this->conn, "set character_set_client='utf8'"); 
		mysqli_query($this->conn, "set character_set_results='utf8'"); 
		mysqli_query($this->conn, "set collation_connection='utf8_general_ci'");
  		return DataBaseSystem::ERR_OK;
	}

	/** @var resource $conn the connection to MySQL server */
	private $conn = null;

	public function selectDatabase($db_name) {
		return mysqli_select_db($this->conn, $db_name);
	}

	protected function executeSQL($sql_query) {
		//echo $sql_query."<br/><br/>";
		return mysqli_query($this->conn, $sql_query);
	}

	public function nextRow($query_result) {
		$row = mysqli_fetch_assoc($query_result);
		if ($row === NULL) return false;
		return $row;
	}
	public function nextRowArray($query_result) {
		$row = mysqli_fetch_array($query_result);
		if ($row === NULL) return false;
		return $row;
	}
	public function fetchRows($query_result) {
		return mysqli_fetch_all($query_result, MYSQLI_ASSOC);
	}
	public function fetchRowsArray($query_result) {
		return mysqli_fetch_all($query_result, MYSQLI_NUM);
	}
	
	public function getInsertID() {
		return mysqli_insert_id($this->conn);
	}

	public function affectedRows() {
		return mysqli_affected_rows($this->conn);
	}

	public function getLastErrorNumber() {
		return mysqli_errno($this->conn);
	}

	public function getLastErrorMessage() {
		return mysqli_error($this->conn);
	}

	public function escapeString($str) {
		return mysqli_real_escape_string($this->conn, $str);
	}
	
	public function startTransaction() {
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