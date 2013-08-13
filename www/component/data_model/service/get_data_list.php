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
		$possible = DataPathBuilder::search_from($table);
		$paths = array();
		foreach ($fields as $f) {
			foreach ($possible as $p)
				if ($p->get_string() == $f) {
					array_push($paths, $p);
					break;
				}
		}
		$q = SQLQuery::create();
		foreach ($paths as $p)
			$p->append_sql($q);
		//PNApplication::error($q->generate());
		$res = $q->execute();
		echo "{data:[";
		for ($i = 0; $i < count($res); $i++) {
			if ($i>0) echo ",";
			echo "[";
			for ($j = 0; $j < count($paths); $j++) {
				if ($j>0) echo ",";
				echo json_encode($res[$i][$paths[$j]->field_alias]);
			}
			echo "]";
		}
		echo "]}";
	}
		
}
?>