<?php 
class service_get_data_list extends Service {
	
	public function get_required_rights() {
		return array();
	}
	
	public function documentation() { echo "Retrieve data from a list of DataPath"; }
	public function input_documentation() {?>
<ul>
	<li><code>table</code>: name of starting table</li><li><code>fields</code>:[paths]</li>
	<li>optional: <code>actions</code>: if true, a list of possible links with icon are returned</li>
</ul>	
<?php }
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
			if ($p->is_unique())
				$p->append_sql($q, $ctx);

		$actions = null;
		if (isset($_POST["actions"]) && $_POST["actions"]) {
			$actions = array();
			$categories = array();
			foreach ($paths as $p) {
				$cat_name = $p->parent->table->getDisplayableDataCategoryAndName($p->field_name);
				if ($cat_name == null) continue;
				if (!in_array($cat_name[0], $categories))
					array_push($categories, $cat_name[0]);
			}
			$model = DataModel::get();
			foreach ($categories as $cat) {
				$link = $model->getDataCategoryLink($cat);
				$icon = $model->getDataCategoryLinkIcon($cat);
				if ($link <> null)
					array_push($actions, array($link,$icon));
			}
			foreach ($actions as &$action) {
				$k = 0;
				$link = $action[0];
				while (($k = strpos($link, "%", $k)) !== false) {
					$kk = strpos($link, "%", $k+1);
					if ($kk === false) break;
					$s = substr($link, $k+1, $kk-$k-1);
					$l = strpos($s, ".");
					$table = substr($s, 0, $l);
					$col = substr($s, $l+1);
					$alias = $q->get_field_alias($q->get_table_alias($table), $col, $ctx->new_field_alias());
					if ($alias == null)
						$q->field($table, $col);
					$k = $kk+1;
					continue;
				}
			}
		}
		
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
			echo "{values:[";
			for ($j = 0; $j < count($paths); $j++) {
				if ($j>0) echo ",";
				if ($paths[$j]->is_unique()) {
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
				} else {
					echo "{v:\"TODO: multiple values\"}";
					// TODO multiple
				}
			}
			echo "]";
			if ($actions !== null) {
				echo ",actions:[";
				$first = true;
				foreach ($actions as $action) {
					if ($first) $first = false; else echo ",";
					$k = 0;
					$link = $action[0];
					while ($k < strlen($link) && ($k = strpos($link, "%", $k)) !== false) {
						$kk = strpos($link, "%", $k+1);
						if ($kk === false) break;
						$s = substr($link, $k+1, $kk-$k-1);
						$l = strpos($s, ".");
						$table = substr($s, 0, $l);
						$col = substr($s, $l+1);
						$alias = $q->get_field_alias($q->get_table_alias($table), $col);
						if ($alias == null) {
							PNApplication::error("Missing field '".$col."' from table '".$table."' (alias '".$q->get_table_alias($table)."') in SQL request ".$q->generate());
							$k = $kk+1;
							continue;
						}
						$link = substr($link, 0, $k).$res[$i][$alias].substr($link, $kk+1);
						$k = $kk + strlen($res[$i][$alias]);
					}
					echo "{link:".json_encode($link).",icon:".json_encode($action[1])."}";
				}
				echo "]";
			}
			echo "}";
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