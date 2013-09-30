<?php 
class service_save_entity extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {
		echo "Save or create an entity.";
	}
	public function input_documentation() {
?>
<ul>
	<li><code>table</code>: table.</li>
	<li><code>sub_model</code>: sub model or null.</li>
	<li><code>lock</code>: id of lock in case the entity already exists. If not specified, a new entity will be created.</li>
	<li><code>key</code>: primary key of the entity to save. If not specified, a new entity will be created.</li>
	<li><i>fields to save</i>: each name must start with 'field_' to avoid collision with other input parameters.</li>
</ul>
<?php
	}
	public function output_documentation() {
		echo "<code>key</code> the key of the entity";
	}
	
	public function execute(&$component, $input) {
		$table = $input["table"];
		$sub_model = @$input["sub_model"];
		$lock_id = @$input["lock"];
		$key = @$input["key"];
		
		require_once("component/data_model/Model.inc");
		$t = DataModel::get()->getTable($table);
		
		$sub_models = null;
		if ($t->getModel() instanceof SubDataModel && $sub_model <> null)
			$sub_models = array($t->getModel()->getParentTable()=>$sub_model);
		
		$fields = array();
		foreach ($t->getColumns($sub_models) as $col) {
			if (!isset($input["field_".$col->name])) continue;
			$fields[$col->name] = $input["field_".$col->name];
		}
		
		if ($lock_id == null || $key == null) {
			// this is an insert
			echo "{key:".SQLQuery::create()->insert($table, $fields, $sub_models)."}";
		} else {
			// this is an update
			SQLQuery::create()->update_by_key($table, $key, $fields, $sub_models, $lock_id);
			echo "{key:".$key."}";
		}
	}
	
}
?>