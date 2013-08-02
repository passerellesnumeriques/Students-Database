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
	 * If report_errors is true, and an error occured during execution of the SQL request,
	 * this error will be automatically reported to the list of errors of the application */
	public function execute($sql_query, $report_errors = true) {
		$res = $this->execute_sql($sql_query);
		if ($res === false && $report_errors) {
			$err = $this->get_last_error_number();
			if ($err <> 0)
				PNApplication::error("DataBase Error #".$err.": ".$this->get_last_error_message()." [".$sql_query."]");
		}
		return $res;
	}

	public abstract function select_db($db_name);
	protected abstract function execute_sql($query);
	public abstract function next_row($query_result);
	public abstract function get_insert_id();
	public abstract function affected_rows();
	public abstract function get_last_error_number();
	public abstract function get_last_error_message();

	public abstract function escape_string($str);

}
?>