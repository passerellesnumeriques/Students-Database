<?php
class service_save_data extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Save data into database"; }
	public function input_documentation() {
?>
<code>to_save</code>: an array of data to save, with for each element:
<ul>
	<li><code>path</code>: the DataPath to the table</li>
	<li><code>name</code>: the display name of the data to save</li>
	<li><code>key</code>: the key(s) of the data to save, as given by get_data_list</li>
	<li><code>value</code>: the value to save</li>
</ul>
<code>root_table</code>: the root table for the DataPath<br/>
<code>sub_model</code>: optional, sub model of the root table<br/>
<code>sub_models</code>: optional, sub models in which we can go to find data from the root table<br/>
<p>All the elements contained in to_save must be locked before calling this service; otherwise, the data update would fail.</p>
<?php		
	}
	public function output_documentation() { echo "return true on success"; }
	public function execute(&$component, $input) {
		$to_save = $input["to_save"];
		if (count($to_save) == 0) {
			PNApplication::error("Nothing to save");
			return;
		}
		require_once("component/data_model/DataPath.inc");
		$paths = DataPathBuilder::searchFrom($input["root_table"], @$input["sub_model"], false, @$input["sub_models"]);
		$tables_fields = new TablesToUpdate();
		foreach ($to_save as $t) {
			$path_found = false;
			foreach ($paths as $path) {
				if ($path->getString() == $t["path"]) {
					$come_from = null;
					if ($path instanceof DataPath_Join && $path->isReverse())
						$come_from = $path->foreign_key->name;
					$display = DataModel::get()->getTableDataDisplay($path->table->getName());
					$data_found = false;
					foreach ($display->getDataDisplay($come_from) as $d)
						if ($d->getDisplayName() == $t["name"]) {
							$d->saveData($t["key"], $t["value"], $path->sub_model, $tables_fields, null, null);
							$data_found = true;
							break;
						}
					if (!$data_found) PNApplication::error("Data '".$t["name"]."' not found in table ".$path->table->getName());
					$path_found = true;
					break;
				}
			}
			if (!$path_found) PNApplication::error("DataPath not found: ".$t["path"]);
		}
		if (!PNApplication::has_errors()) {
			$tables_fields->execute();
			echo "true";
		}
	}
}
?>