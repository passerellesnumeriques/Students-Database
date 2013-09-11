<?php 
class widgets extends Component {

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
			// TODO
			return array("field_text","{}");
		}
		return array("field_text", "{}");
	}
		
}
?>