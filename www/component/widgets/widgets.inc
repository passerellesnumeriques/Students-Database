<?php 
class widgets extends Component {

	private $table_typed_field = array();
	
	public function register_table_typed_field($table, $field_classname, $field_args = "{}") {
		$this->table_typed_field[$table] = array($field_classname,$field_args);
	}
	
	/**
	 * @param \datamodel\Column $col
	 * @return array [classname,arguments_to_append]
	 */
	public function get_typed_field($col) {
		if ($col instanceof \datamodel\ColumnString) {
			return array("field_text", "{max_length:".$col->max_length."}");
		}
		if ($col instanceof \datamodel\ColumnInteger) {
			// TODO
			return array("field_text","{}");
		}
		if ($col instanceof \datamodel\ColumnEnum) {
			$args = "{possible_values:[";
			$first = true;
			foreach ($col->values as $v) {
				if ($first) $first = false; else $args .= ",";
				$args .= json_encode($v);
			}
			$args .= "],can_be_empty:".($col->can_be_null ? "true" : "false")."}";
			return array("field_enum",$args);
		}
		if ($col instanceof \datamodel\ColumnDate) {
			return array("field_date","{}");
		}
		if ($col instanceof \datamodel\ForeignKey) {
			// TODO create a generic field for a table
			if (isset($this->table_typed_field[$col->foreign_table]))
				return $this->table_typed_field[$col->foreign_table];
		}
		return array("field_text", "{}");
	}
		
}
?>