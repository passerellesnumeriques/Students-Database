<?php 
class service_get_data_list extends Service {
	
	public function get_required_rights() {
		return array();
	}
	
	public function documentation() { echo "Retrieve data from a list of DataPath"; }
	public function input_documentation() { echo "<ul><li><code>table</code>: name of starting table</li><li><code>fields</code>:[paths]</li></ul>"; }
	public function output_documentation() {
?>
TODO
<?php 
	}
	
	public function execute(&$component) {
		$table = $_POST["table"];
		$fields = json_decode($_POST["fields"]);
		require_once("component/data_model/DataPath.inc");
		$ctx = new DataPathBuilderContext();
		$possible = DataPathBuilder::search_from($ctx, $table);
		$paths = array();
		foreach ($fields as $f) {
			foreach ($possible as $p)
				if ($p->get_string() == $f) {
					array_push($paths, $p);
					break;
				}
		}
		$q = SQLQuery::create();
		$t = DataModel::get()->getTable($table);
		$alias = null;
		foreach ($paths as $p) {
			$root = $p;
			while ($root <> null) {
				if ($root->table->getName() == $table) {
					$alias = $root->table_alias;
					break;
				}
				$root = $root->parent;
			}
			if ($alias <> null) break; 
		}
		$table_name = $t->getSQLName(null);
		$q->select(array($table_name=>$alias));
		foreach ($paths as $p)
			$p->append_sql($q, $ctx);

		//PNApplication::error($q->generate());
		$res = $q->execute();
		echo "{";
		
		echo "tables:[";
		for ($j = 0; $j < count($paths); $j++) {
			if ($j>0) echo ",";
			echo "{name:".json_encode($paths[$j]->parent->table->getName());
			echo ",sub_model:".json_encode($paths[$j]->parent->sub_model);
			echo ",keys:[";
			$keys = $this->get_keys_for($paths[$j]->parent->table, $paths[$j]->parent->sub_model);
			for ($i = 0; $i < count($keys); $i++) {
				if ($i>0) echo ",";
				echo json_encode($keys[$i]->name);
			};
			echo "]";
			echo "}";
		}
		echo "]";
			
		echo ",data:[";
		for ($i = 0; $i < count($res); $i++) {
			if ($i>0) echo ",";
			echo "[";
			for ($j = 0; $j < count($paths); $j++) {
				if ($j>0) echo ",";
				echo "{v:";
				echo json_encode($res[$i][$paths[$j]->field_alias]);
				echo ",k:[";
				if ($paths[$j]->parent->table_primarykey_alias <> null)
					echo json_encode($paths[$j]->parent->table->getPrimaryKey()->name).",".json_encode($res[$i][$paths[$j]->parent->table_primarykey_alias]);
				else {
					// no primary key
					$keys = $this->get_keys_for($paths[$j]->parent->table, $paths[$j]->parent->sub_model);
					for ($k = 0; $k < count($keys); $k++) {
						if ($k>0) echo ",";
						$alias = $q->get_field_alias($paths[$j]->parent->table_alias, $keys[$k]->name);
						if ($alias <> null)
							echo json_encode($res[$i][$alias]);
						else // TODO DEBUG
							PNApplication::error("No alias for ".$paths[$j]->parent->table_alias.".".$keys[$k]->name." in ".$q->generate());
					}
				}
				echo "]";
				echo "}";
			}
			echo "]";
		}
		echo "]}";
	}
	
	private function get_keys_for(&$table, $sub_model) {
		$pk = $table->getPrimaryKey();
		if ($pk <> null) return array($pk);
		foreach ($table->internalGetColumns($sub_model) as $col)
			if ($col->unique)
				return array($col);
		$unique = $table->getUnique();
		if ($unique <> null) {
			$keys = array();
			foreach ($unique as $u)
				array_push($keys, $table->internalGetColumn($u, $sub_model));
			return $keys;
		}
		foreach ($table->getLinks() as $link)
			if ($link->unique) {
				$keys = array();
				foreach ($link->fields_matching as $field=>$field2)
					array_push($keys, $link->table_from->internalGetColumn($field));
				return $keys;
			}
		return array();
	}
		
}
?>