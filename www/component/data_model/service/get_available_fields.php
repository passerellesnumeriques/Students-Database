<?php 
class service_get_available_fields extends Service {
	
	public function get_required_rights() {
		return array();
	}
	
	public function documentation() { echo "Start from the given table, and search for all reachable fields, and return the list of displayable fields"; }
	public function input_documentation() { echo "<code>table</code>: name of starting table"; }
	public function output_documentation() { 
		/* TODO */
	}
	
	public function execute(&$component, $input) {
		$table = $input["table"];

		require_once("component/data_model/DataPath.inc");
		$ctx = new DataPathBuilderContext();
		$paths = DataPathBuilder::search_from($ctx, $table);
		
// 		// filter paths, to remove the duplicate by passing by different paths
// 		for($i = 0; $i < count($paths); $i++) {
// 			$p = $paths[$i];
// 			$p_strong = true;
// 			for ($j = $i+1; $j < count($paths); ++$j) {
// 				$p2 = $paths[$j];
// 				if ($p->table <> $p2->table) continue;
// 				if ($p->field_name <> $p2->field_name) continue;
// 				// same table, same field: is it really 2 different informations ?
// 			}
// 		}
		
		echo "[";
		for ($i = 0; $i < count($paths); $i++) {
			if ($i>0) echo ",";
			$p = $paths[$i];
			echo "{";
			echo "path:".json_encode($p->get_string());
			$disp = $p->table->getDisplayableDataCategoryAndName($p->field_name);
			echo ",cat:".json_encode($disp[0]);
			echo ",name:".json_encode($disp[1]);
			// TODO allow to edit multiple
			$order = array();
			$pa = $p;
			while ($pa <> null) {
				array_splice($order, 0, 0, array($pa));
				$pa = $pa->parent;
			}
			$done = false;
			$locks = array();
			for ($j = 0; $j < count($order); $j++) {
				$pa = $order[$j];
				if ($pa instanceof DataPath_Join) {
					if ($pa->foreign_key->multiple) {
						if ($pa->parent->table == $pa->foreign_key->table) {
							// n>1: it does not belong to us, we can propose the choice
							$editable = $pa->parent->table->canModifyField($pa->foreign_key->name);
							echo ",editable:".($editable ? "true" : "false");
							echo ",sortable:true";
							if ($editable) {
								echo ",field_classname:'field_enum'";
								// build request to have the list of values
								$q = SQLQuery::create();
								$q->select(array($pa->table->getSQLNameFor($pa->sub_model)=>$pa->table_alias));
								$source_table_primarykey_alias = $ctx->new_field_alias();
								$q->field($pa->table_alias, $pa->table->getPrimaryKey()->name, $source_table_primarykey_alias);
								for ($k = $j+1; $k < count($order); $k++)
									$order[$k]->append_sql($q, $ctx);
								$values = $q->execute();
								$value_alias = $order[count($order)-1]->field_alias;
								echo ",field_args:{";
								echo "possible_values:[";
								$first = true;
								foreach ($values as $v) {
									if ($first) $first = false; else echo ",";
									echo "[".json_encode($v[$source_table_primarykey_alias]).",".json_encode($v[$value_alias])."]";
								}
								echo "]";
								echo ",can_be_null:".($pa->foreign_key->remove_foreign_when_primary_removed ? "false" : "true"); 
								echo "}";
								array_push($locks, array("table"=>$pa->parent->table->getSQLNameFor($pa->parent->sub_model),"column"=>$pa->foreign_key->name));
								echo ",edit:{table:".json_encode($pa->parent->table->getName()).",sub_model:".json_encode($pa->parent->sub_model).",column:".json_encode($pa->foreign_key->name).",can_be_null:".($pa->foreign_key->can_be_null ? "true" : "false")."}";
							} else {
								//$f = PNApplication::$instance->widgets->get_typed_field($col);
								//echo ",field_classname:".json_encode($f[0]);
								//echo ",field_args:".$f[1];
								echo ",field_classname:'field_text',field_args:{}";
							}
							$done = true;
							break;
						} else {
							// 1<n: list of values
							echo ",sortable:false";
						}
					}
				}
			}
			if (!$done) {
				$editable = $p->table->canModifyField($p->field_name) && $paths[$i]->is_unique();
				echo ",editable:".($editable ? "true" : "false");
				echo ",sortable:true";
				$col = $p->table->getColumn($p->field_name);
				$f = PNApplication::$instance->widgets->get_typed_field($col);
				echo ",field_classname:".json_encode($f[0]);
				echo ",field_args:".$f[1];
				if ($editable) {
					echo ",edit:{table:".json_encode($p->table->getName()).",sub_model:".json_encode($p->sub_model).",column:".json_encode($p->field_name).",can_be_null:".($col->can_be_null ? "true" : "false")."}";
				}
			}
			if ($editable) {
				array_push($locks, array("table"=>$p->table->getSQLNameFor($p->sub_model),"column"=>$p->field_name));
				echo ",locks:".json_encode($locks);
			}
			echo "}";
		}
		echo "]";
	}
	
}
?>