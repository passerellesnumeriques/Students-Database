<?php
class service_save_cells extends Service {
	public function getRequiredRights() { return array(); }
	public function documentation() { echo "Save the different values in database"; }
	public function inputDocumentation() {
?>
<code>cells</code>: An array of
<ul>
	<li><code>table</code>: table name of the cells to save</li>
	<li><code>sub_model</code>: optional, only if the table is in a sub model</li>
	<li><code>keys</code>: array of key of the rows to save</li>
	<li><code>values</code>: array of<ul>
		<li><code>column</code>: column name of the cell to save</li>
		<li><code>value</code>: value to save</li>
	</ul></li>
</ul>
<?php		
	}
	public function outputDocumentation() { echo "return true on success"; }
	public function execute(&$component, $input) {
		$to_save = new TablesToUpdate();
		foreach ($input["cells"] as $todo)
			foreach ($todo["values"] as $val)
				foreach ($todo["keys"] as $row_key)
					$to_save->addValue($todo["table"], @$todo["sub_model"], $row_key, $val["column"], $val["value"]);
		$to_save->execute();
		echo PNApplication::hasErrors() ? "false" : "true";
	}
}
?>