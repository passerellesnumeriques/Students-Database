<?php 
class service_get_data_list extends Service {
	
	public function get_required_rights() {
		return array();
	}
	
	public function documentation() { echo "Retrieve data from a list of DataPath"; }
	public function input_documentation() { echo "
<ul>
	<li><code>table</code>: name of starting table</li><li><code>fields</code>:[paths]</li>
	<li>optional: <code>actions</code>: if true, a list of possible links with icon are returned</li>
</ul>";
	}
	public function output_documentation() { /* TODO */ }

	public function get_output_format($input) {
		if (isset($input["export"])) {
			$format = $input["export"];
			if ($format == 'excel2007')
				return "application/vnd.ms-excel";
			if ($format == 'excel5')
				return "application/vnd.ms-excel";
			if ($format == 'csv')
				return "text/csv;charset=UTF-8";
			if ($format == 'pdf')
				return "Content-Type: application/pdf";
		}
		return "text/json";
	}
	
	public function execute(&$component, $input) {
		$table = $input["table"];
		$fields = $input["fields"];
		require_once("component/data_model/DataPath.inc");
		$possible = DataPathBuilder::search_from($table);
		$paths = array();
		foreach ($fields as $f) {
			$found = false;
			foreach ($possible as $p)
				if ($p->get_string() == $f["path"]) {
					array_push($paths, $p);
					$found = true;
					break;
				}
			if (!$found) {
				PNApplication::error("Unknown data path: ".$f["path"]);
				return;
			}
		}
		// init query with root table
		$q = SQLQuery::create();
		$t = DataModel::get()->getTable($table);
		$builder = new DataPathSQLBuilder();
		$alias = $builder->new_alias();
		$table_name = $t->getSQLName(null);
		$q->select(array($table_name=>$alias));
		// finalize query for each data
		$data_aliases = array();
		$display_data = array();
		$filters = array();
		$remaining_filters = $input["filters"];
		for ($i = 0; $i < count($fields); $i++) {
			$name = $fields[$i]["name"];
			$path = $paths[$i];
			$from = null;
			if ($path instanceof DataPath_Join && $path->is_reverse())
				$from = $path->foreign_key->name;
			$handler = $path->table->getDisplayHandler($from);
			if ($handler == null) {
				PNApplication::error("No display handler on table ".$path->table->getName()." for path ".$fields[$i]["path"]);
				return;
			}
			$data = null;
			foreach ($handler->getDisplayableData() as $d)
				if ($d->getDisplayName() == $name) { $data = $d; break; }
			if ($data == null) {
				PNApplication::error("No displayable data ".$name." on table ".$path->table->getName());
				return;
			}
			array_push($display_data, $data);
			$f = array();
			for ($j = 0; $j < count($remaining_filters); $j++) {
				$filter = $remaining_filters[$j];
				if ($filter["category"] <> $handler->category) continue;
				if ($filter["name"] <> $data->getDisplayName()) continue;
				array_splice($remaining_filters, $j, 1);
				$j--;
				$fil = array();
				array_push($fil, $filter["data"]);
				while (isset($filter["or"])) {
					$filter = $filter["or"];
					array_push($fil, $filter["data"]);
				}
				array_push($f, $fil);
			}
			array_push($filters, $f);
			array_push($data_aliases, $data->buildSQL($q, $path, $builder, $f));
		}
		
		// add filters not related to a displayed data
		foreach ($remaining_filters as $filter) {
			$found = false;
			foreach ($possible as $path) {
				$from = null;
				if ($path instanceof DataPath_Join && $path->is_reverse())
					$from = $path->foreign_key->name;
				$display = $path->table->getDisplayHandler($from);
				if ($display == null) continue;
				if ($display->category <> $filter["category"]) continue;
				foreach ($display->getDisplayableData() as $data) {
					if ($data->getDisplayName() <> $filter["name"]) continue;
					$found = true;
					$fil = array();
					array_push($fil, $filter["data"]);
					while (isset($filter["or"])) {
						$filter = $filter["or"];
						array_push($fil, $filter["data"]);
					}
					$filter_list = array($fil);
					$data->buildSQL($q, $path, $builder, $filter_list);
					break;
				}
				if ($found) break;
			}
			if (!$found) PNApplication::error("Invalid filter: unknown data '".$filter["name"]."' in category '".$filter["category"]."'");
		}
		
		// check if we have actions, then add necessary fields in the SQL request
		$actions = null;
		if (isset($input["actions"]) && $input["actions"]) {
			$actions = array();
			$categories = array();
			foreach ($display_data as $data) {
				$cat_name = $data->handler->category;
				if (!in_array($cat_name, $categories))
					array_push($categories, $cat_name);
			}
			$model = DataModel::get();
			foreach ($categories as $cat) {
				$links = $model->getDataCategoryLinks($cat);
				if ($links <> null)
					foreach ($links as $link)
						array_push($actions, array($link->link,$link->icon));
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
					$alias = $q->get_field_alias($q->get_table_alias($table), $col);
					if ($alias == null) {
						$alias = $builder->new_alias();
						$q->field($q->get_table_alias($table), $col, $alias);
					}
					$k = $kk+1;
					continue;
				}
			}
		}
		
		// handle sort
		if (isset($input["sort_field"]) && isset($input["sort_order"])) {
			for ($i = 0; $i < count($display_data); $i++) {
				$data = $display_data[$i];
				if ($data->handler->category.".".$data->getDisplayName() == $input["sort_field"]) {
					if ($input["sort_order"] == "ASC") $asc = true;
					else if ($input["sort_order"] == "DESC") $asc = false;
					else break;
					$alias = $data_aliases[$i]["data"];
					$q->order_by($alias, $asc);
					break;
				}
			}
		}
		
		//echo $q->generate();

		// calculate the total number of entries
		$count = new SQLQuery($q);
		$count = $count->count("NB_DATA")->execute_single_row();
		$count = $count["NB_DATA"];
		
		// handle pages
		if (isset($input["page_size"])) {
			$nb = intval($input["page_size"]);
			if ($nb == 0) $nb = 1000;
			$page = isset($input["page"]) ? intval($input["page"]) : 0;
			if ($page == 0) $page = 1;
			$q->limit(($page-1)*$nb, $nb);
		}		
		// execute the query
		$res = $q->execute();
		
		echo "{";
		echo "count:".$count;
		echo ",data:[";
		$first = true;
		foreach ($res as $row) {
			if ($first) $first = false; else echo ",";
			echo "{values:[";
			$f = true;
			for ($i = 0; $i < count($display_data); $i++) {
				$a = $data_aliases[$i];
				$data = $display_data[$i];
				$path = $paths[$i];
				if ($f) $f = false; else echo ",";
				echo "{v:";
 				if (isset($a["data"]) && $a["data"] !== null)
 					echo json_encode($row[$a["data"]]);
 				else
 					echo json_encode($data->retrieveValue($row, $a, $path, $filters[$i]));
				if (isset($row[$a["key"]]))
					echo ",k:".json_encode($row[$a["key"]]);
				else {
					echo ",k:null";
					PNApplication::error("Missing key '".$a["key"]."' for data '".$data->getDisplayName()."' in table '".$data->handler->table->getName()."'");
				} 
				echo "}";
			}
			echo "]";
			if ($actions !== null) {
				echo ",actions:[";
				$first = true;
				foreach ($actions as &$action) {
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
						$link = substr($link, 0, $k).$row[$alias].substr($link, $kk+1);
						$k = $k + strlen($row[$alias]);
					}
					echo "{link:".json_encode($link).",icon:".json_encode($action[1])."}";
				}
				echo "]";
			}
			echo "}";
		}
		echo "]";
		echo "}";
	}
}
?>