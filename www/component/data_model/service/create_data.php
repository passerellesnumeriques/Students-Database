<?php 
class service_create_data extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Generic service to create data"; }
	public function inputDocumentation() {
?>
<ul>
<li><code>root</code>: the table being the starting point</li>
<li><code>sub_model</code>: optional, the sub model of <code>root</code></li>
<li><code>sub_models</code>: optional, list of sub models to use</li>
<li><code>multiple</code>: indicates if we are creating several data at the same time, or a single one</li>
<li><code>paths</code>: the data with for each information the format:<ul>
	<li><code>path</code>: the path of a table from the root</li>
	<li><code>columns</code>: optional, to set values on specific columns on this table</li>
	<li><code>value</code>: value to give to the DataScreen to create the data</li>
</ul></li>
</ul>
<?php 
	}
	public function outputDocumentation() {
		echo "<code>key</code> the id of the new row created in the root table";
	}
	
	public function execute(&$component, $input) {
		$root_table_name = $input["root"];
		$sub_model = @$input["sub_model"];
		$sub_models = @$input["sub_models"];
		$multiple = isset($input["multiple"]);
		require_once("component/data_model/DataPath.inc");
		$paths = DataPathBuilder::searchFrom($root_table_name, $sub_model, false, $sub_models);
		$to_create = $input["paths"];
		for ($i = 0; $i < count($paths); $i++)
			$paths[$i]->{"found"} = false;
		foreach ($to_create as $tc)
			for ($i = 0; $i < count($paths); $i++)
				if ($paths[$i]->getString() == $tc["path"]) {
					$paths[$i]->found = true;
					$paths[$i]->{"columns"} = isset($tc["columns"]) ? $tc["columns"] : array();
					$paths[$i]->{"value"} = $tc["value"];
					//PNApplication::error("VALUE FOR PATH:<br/>\n PATH = ".$tc["path"]."<br/>\n VALUE = ".var_export($tc["value"],true)."<br/>\n\n");
					break;
				}
		for ($i = 0; $i < count($paths); $i++)
			if (!$paths[$i]->found) {
				$paths[$i]->{"columns"} = array();
				$paths[$i]->{"value"} = array();
				array_splice($paths, $i, 1);
				$i--;			
			}
		$root = DataPathBuilder::buildPathsTree($paths);
		SQLQuery::startTransaction();
		$key = $this->createData($root, $multiple);
		if (PNApplication::hasErrors())
			SQLQuery::rollbackTransaction();
		else {
			SQLQuery::commitTransaction();
			echo "{key:".json_encode($key)."}";
		}
	}
	
	/**
	 * Create the data for the given path
	 * @param array $path path,columns,value
	 * @param boolean $multiple indicates if it contains multiple entries or not
	 * @return array|integer the key of the created data, if applicable
	 */
	private function createData($path, $multiple) {
		$come_from = null;
		if ($path instanceof DataPath_Join && $path->isReverse())
			$come_from = $path->foreign_key->name;
		$found = false;
		foreach (DataModel::get()->getDataScreens() as $screen) {
			if (!$multiple && !($screen instanceof \datamodel\SimpleDataScreen)) continue;
			if ($multiple && !($screen instanceof \datamodel\MultipleDataScreen)) continue;
			$tables = $screen->getTables();
			if (count($tables) == 1 && $tables[0] == $path->table->getName()) {
				$key = $screen->createData(array($path), $multiple);
				$found = true;
				break;
			}				
		}
		if (!$found) {
			$display = DataModel::get()->getTableDataDisplay($path->table->getName());
			$screen = new datamodel\GenericDataScreen($display);
			$key = $screen->createData(array($path), $multiple);
		}
		return $key;
	}
	
}
?>