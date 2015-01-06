<?php 
class service_get_datamodel extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) {
		if (isset($_GET["output"]) && $_GET["output"] == "sql")
			return "text/plain;charset=UTF-8";
		return parent::getOutputFormat($input);
	}
	
	public function execute(&$component, $input) {
		require_once("component/data_model/Model.inc");
		if (isset($_GET["output"]) && $_GET["output"] == "sql") {
			$model = DataModel::get();
			require_once("component/data_model/DataBaseUtilities.inc");
			$ref = new ReflectionClass("DataModel");
			$p = $ref->getProperty("tables");
			$p->setAccessible(true);
			$tables = $p->getValue($model);
			foreach ($tables as $table) {
				echo DataBaseUtilities::createTable(SQLQuery::getDataBaseAccessWithoutSecurity(), $table, null, true);
				echo ";\n";
			}
		} else {
			echo "{\"model\":";
			require_once 'component/data_model/DataModelJSON.inc';
			echo DataModelJSON::model(DataModel::get());
			echo "}";
		}
	}
		
}
?>