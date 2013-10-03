<?php 
class service_search extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Perform a search on the database"; }
	public function input_documentation() {
		echo "<ul>";
		echo "<li><code>table</code>, <code>column</code>, <code>sub_model</code>: where to search in the database</li>";
		echo "<li><code>q</code>: the search request</li>";
		echo "</ul>";
	}
	public function output_documentation() {
		echo "Array of search results, or false in case no result was found. Each result has the following format:<ul>";
		echo "<li><code>value</code>: value found</li>";
		echo "<li><code>links:[{url:xxx,icon:yyy}]</code>: list of links associated with this data</li>";
		echo "</ul>";
	}
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		$results = array();
		foreach (DataModel::get()->getTables() as $table) {
			foreach ($table->getDisplayableData() as $field=>$disp) {
				$this->search($table, $field, $disp, $results, $input["q"]);
			}
		}
	}
	private function search($table, $field, $disp, &$results, $search) {
		if ($table->getModel() instanceof SubDataModel) {
			foreach ($table->getModel()->getExistingInstances() as $sm)
				$this->search($table, $field, $sm, $disp, $results, $search);
		} else
			$this->search($table, $field, null, $disp, $results, $search);
	}
	private function search($table, $field, $sub_model, $disp, &$results, $search) {
		$col = $table->getColumn($field, $sub_model);
		$q = SQLQuery::create();
		$q->select(array($table->getSQLNameFor($sub_model)=>"the_table"));
		$q->field("the_table", $col->name, "the_value");
		
		$q_start = new SQLQuery($q);
		$q_start->where("`the_table`.`".$col->name."` LIKE '".$q->escape($search)."%'");
		$q_contains = new SQLQuery($q);
		$q_contains->where("`the_table`.`".$col->name."` NOT LIKE '".$q->escape($search)."%'");
		$q_contains->where("`the_table`.`".$col->name."` LIKE '%".$q->escape($search)."%'");
		// TODO
	}
		
		
// 		require_once("component/data_model/Model.inc");
// 		$table = DataModel::get()->getTable($input["table"]);
// 		$col = $table->getColumnFor($input["column"], $input["sub_model"]);
// 		$table_name = $table->getSQLNameFor($input["sub_model"]);
// 		$category = $table->getDisplayableDataCategory($col->name);
// 		$links = DataModel::get()->getDataCategoryLinks($category);
// 		$has_others_tables = false;
// 		foreach ($links as $link)
// 			foreach ($link->getParameters() as $param)
// 				if ($param["table"] <> $input["table"]) {
// 					$has_others_tables = true;
// 					break;
// 				}
// 		if (!$has_others_tables) {
// 			$q = SQLQuery::create()->select($table_name)->field($table_name, $col->name, "the_value");
// 			for ($i = 0; $i < count($links); $i++) {
// 				$link = $links[$i];
// 				$params = $link->getParameters();
// 				for ($j = 0; $j < count($params); $j++) {
// 					$q->field($table_name, $params[$j]["column"], "param".$i."_".$j);					
// 				}
// 			}
// 		} else {
// // 			$q = SQLQuery::create()->select($table_name)->field($table_name, $col->name, "the_value");
// // 			require_once("component/data_model/DataPath.inc");
// // 			$ctx = new DataPathBuilderContext(); 
// // 			for ($i = 0; $i < count($links); $i++) {
// // 				$link = $links[$i];
// // 				$params = $link->getParameters();
// // 				for ($j = 0; $j < count($params); $j++) {
// // 					if ($params[$j]["table"] <> $input["table"]) {
// // 						$tn = $q->get_table_alias($params[$j]["table"]);
// // 						if ($tn == null) {
// // 							$tn = DataPathBuilder::reach_table($input["table"], $params[$j]["table"], $q, $ctx);
// // 						}
// // 					} else 
// // 						$tn = $table_name;
// // 					$q->field($tn, $params[$j]["column"], "param".$i."_".$j);					
// // 				}
// // 			}
// 		}
// 		$q->where("`".$table_name."`.`".$col->name."` LIKE '%".$q->escape($input["q"])."%'");
// 		$res = $q->execute();
// 		if ($res == null || count($res) == 0) { echo "false"; return; }
// 		echo "[";
// 		$first = true;
// 		foreach ($res as $r) {
// 			if ($first) $first = false; else echo ",";
// 			echo "{";
// 			echo "value:".json_encode($r["the_value"]);
// 			echo ",links:[";
// 			$first_link = true;
// 			for ($i = 0; $i < count($links); $i++) {
// 				$link = $links[$i];
// 				$params = $link->getParameters();
// 				$url = $link->link;
// 				for ($j = 0; $j < count($params); $j++) {
// 					$url = str_replace("%".$params[$j]["name"]."%", $r["param".$i."_".$j], $url);
// 				}
// 				if ($first_link) $first_link = false; else echo ",";
// 				echo "{url:".json_encode($url).",icon:".json_encode($link->icon)."}";
// 			}
// 			echo "]";
// 			echo "}";
// 		}
// 		echo "]";
// 	}
	
}
?>