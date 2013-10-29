<?php 
class widgets extends Component {

	/**
	 * @param \datamodel\Column $col
	 * @return array [classname,arguments_to_append]
	 */
	public function get_typed_field($col) {
		if ($col instanceof \datamodel\ColumnString) {
			$cfg = "{max_length:".$col->max_length;
			if ($col->min_length > 0) $cfg .= ",min_length:".$col->min_length;
			$cfg .= ",can_be_null:".json_encode($col->can_be_null);
			$cfg .= "}";
			return array("field_text", $cfg);
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
		return array("field_text", "{}");
	}
	/**
	 * @param \datamodel\Column $col
	 * @return array [classname,config]
	 */
	public function get_typed_filter($col) {
		if ($col instanceof \datamodel\ColumnString) {
			return array("filter_text", "null");
		}
		return array(null,"null");
	}
	
}
?>