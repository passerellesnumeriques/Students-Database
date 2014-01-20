<?php
/** Basic abstraction layer on the DBMS used */
abstract class DataBaseSystem {

	const ERR_OK = 0;
	const ERR_CANNOT_CONNECT_TO_SERVER = 1;
	const ERR_INVALID_CREDENTIALS = 2;
	const ERR_DB_DOES_NOT_EXIST = 3;
	/** Connect to the given server, using given credentials and database name, returns one of the defined constant */
	public abstract function connect($server, $username, $password, $db_name = null);

	/** Executes a SQL request.
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
	 * @param string $sql_query SQL query to execute
	 * @param boolean $report_errors if true and an error occurs, the error will be added to <code>PNApplication</code>
	 */
	public function execute($sql_query, $report_errors = true) {
		$start = microtime(true);
		$res = $this->execute_sql($sql_query);
		$end = microtime(true);
		$this->log($sql_query,$this->get_last_error_number(),$this->get_last_error_message(),$end-$start);
		if ($res === false && $report_errors) {
			$err = $this->get_last_error_number();
			if ($err <> 0) {
				try {
					throw new Exception("DataBase Error #".$err.": ".$this->get_last_error_message()." [".$sql_query."]");
				} catch (Exception $e) {
					PNApplication::error($e);
				}
			}
		}
		return $res;
	}
	
	public function log($query, $error_number, $error_message, $time) {
		if (!PNApplication::$instance->current_domain == "Dev") return;
		if (count(PNApplication::$instance->development->current_request()->sql_queries) == 200)
			array_push(PNApplication::$instance->development->current_request()->sql_queries, array("TOO MUCH SQL QUERIES",0,null,0));
		else if (count(PNApplication::$instance->development->current_request()->sql_queries) < 200)
			array_push(PNApplication::$instance->development->current_request()->sql_queries, array($query, $error_number, $error_message, $time));
	}

	public abstract function select_db($db_name);
	protected abstract function execute_sql($query);
	public abstract function next_row($query_result);
	public abstract function get_insert_id();
	public abstract function affected_rows();
	public abstract function get_last_error_number();
	public abstract function get_last_error_message();
	
	public abstract function start_transaction();
	public abstract function commit();
	public abstract function rollback();

	public abstract function escape_string($str);

}
?>