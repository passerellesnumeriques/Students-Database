<?php 
class data_model extends Component {
	
	public $data_changed_listeners = array();
	public $data_removed_listeners = array();
	public $data_added_listeners = array();
	
	public function register_data_changed($table, $column, $instance, $callback) {
		array_push($this->data_changed_listeners, array($table, $column, $instance, $callback));
	}
	
	public function register_data_removed($table, $instance, $callback) {
		array_push($this->data_removed_listeners, array($table, $instance, $callback));
	}
	
	public function register_data_added($table, $instance, $callback) {
		array_push($this->data_added_listeners, array($table, $instance, $callback));
	}
	
	public function has_data_changed_listeners($table) {
		foreach ($this->data_changed_listeners as $listener)
			if ($listener[0] == $table)
				return true;
		return false;
	}
	
	public function signal_data_changed($table, $column, $sub_model, $row_key, $new_value) {
		foreach ($this->data_changed_listeners as $listener)
			if ($listener[0] == $table && $listener[1] == $column)
				call_user_func_array(array($listener[2],$listener[3]), array($sub_model, $row_key, $new_value));
		PNApplication::$instance->signal_event("data_model_data_changed", $table.($sub_model <> null ? "[".$sub_model."]" : "").".".$column."(".$row_key.")", $new_value);
	}
	
	public function signal_data_removed($table, $sub_model, $row_key) {
		// TODO
	}
	
	public function signal_data_added($table, $sub_model, $row_key) {
		// TODO
	}
}
?>