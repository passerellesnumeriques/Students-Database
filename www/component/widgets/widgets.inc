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
			if ($col->specific_lang <> null) $cfg .= ",lang:".json_encode($col->specific_lang);
			$cfg .= "}";
 			return array("field_text", $cfg);
		}		
		if ($col instanceof \datamodel\ColumnTimestamp) {
			$cfg = "{show_time:true,data_is_seconds:true}";
 			return array("field_timestamp",$cfg);
		}
		if ($col instanceof \datamodel\ColumnBoolean) {
			if ($col->can_be_null) {
				return array("field_enum","{possible_values:[[0,'No'],[1,'Yes']],can_be_null:true}");
			} else {
				return array("field_boolean","{}");
			}
		}
		if ($col instanceof \datamodel\ColumnInteger) {
			$cfg = "{";
			$cfg .= "can_be_null:".json_encode($col->can_be_null);
			if ($col->min !== null) $cfg .= ",min:".json_encode($col->min);
			if ($col->max !== null) $cfg .= ",max:".json_encode($col->max);
			$cfg .= "}";
 			return array("field_integer",$cfg);
		}
		if ($col instanceof \datamodel\ColumnDecimal) {
			$cfg = "{";
			$cfg .= "can_be_null:".json_encode($col->can_be_null);
			if ($col->min !== null) $cfg .= ",min:".json_encode($col->min);
			if ($col->max !== null) $cfg .= ",max:".json_encode($col->max);
			$cfg .= ",integer_digits:".$col->integer_digits;
			$cfg .= ",decimal_digits:".$col->decimal_digits;
			$cfg .= "}";
 			return array("field_decimal",$cfg);
		}
		if ($col instanceof \datamodel\ColumnEnum) {
			$cfg = "{possible_values:[";
			$first = true;
			foreach ($col->values as $v) {
				if ($first) $first = false; else $cfg .= ",";
				$cfg .= json_encode($v);
			}
			$cfg .= "],can_be_null:".($col->can_be_null ? "true" : "false")."}";
 			return array("field_enum",$cfg);
		}
		if ($col instanceof \datamodel\ColumnDate) {
			$cfg = "{can_be_null:".($col->can_be_null ? "true" : "false");
			if ($col->minimum_date <> null) {
				if (ctype_digit(substr($col->minimum_date,0,1)))
					$cfg .= ",minimum:".json_encode($col->minimum_date);
				else
					$cfg .= ",minimum_cell:".json_encode($col->minimum_date);
			}
			if ($col->maximum_date <> null) {
				if (ctype_digit(substr($col->maximum_date,0,1)))
					$cfg .= ",maximum:".json_encode($col->maximum_date);
				else
					$cfg .= ",maximum_cell:".json_encode($col->maximum_date);
			}
			$cfg .= "}";
 			return array("field_date",$cfg);
		}
		return array("field_blank", "{}");
	}
	/**
	 * @param \datamodel\Column $col
	 * @return array [classname,config]
	 */
	public function get_typed_filter($col) {
		if ($col instanceof \datamodel\ColumnString) {
			return array("filter_text", "null");
		}
		if ($col instanceof \datamodel\ColumnEnum) {
			$cfg = "{possible_values:[";
			$first = true;
			foreach ($col->values as $v) {
				if ($first) $first = false; else $cfg .= ",";
				$cfg .= json_encode($v);
			}
			$cfg .= "]";
			$cfg .= ",can_be_null:".($col->can_be_null ? "true" : "false");
			$cfg .= "}";
			return array("filter_enum", $cfg);
		}
		if ($col instanceof \datamodel\ColumnBoolean) {
			$cfg = "{possible_values:[[1,'Yes'],[0,'No']]";
			$cfg .= ",can_be_null:".($col->can_be_null ? "true" : "false");
			$cfg .= "}";
			return array("filter_enum", $cfg);
		}
		if ($col instanceof \datamodel\ColumnInteger ||
			$col instanceof \datamodel\ColumnDecimal ||
			$col instanceof \datamodel\ColumnDate ||
			$col instanceof \datamodel\ColumnTimestamp) {
			$field = $this->get_typed_field($col);
			$cfg = "{value_field_classname:".json_encode($field[0]).",value_field_config:".$field[1].",can_be_null:".($col->can_be_null ? "true" : "false")."}";
			return array("filter_comparable", $cfg);
		}
		return array(null,"null");
	}
	
	public function create_typed_field(&$page, $variable_name, $table, $column, $editable, $data) {
		require_once("component/data_model/Model.inc");
		$t = DataModel::get()->getTable($table);
		$col = $t->getColumn($column);
		$tf = $this->get_typed_field($col);
		$page->addJavascript("/static/widgets/typed_field/typed_field.js");
		$page->addJavascript("/static/widgets/typed_field/".$tf[0].".js");
		echo $variable_name." = new ".$tf[0]."(".$data.",".$editable.",".$tf[1].");";
	}
	
}
?>