<?php
require_once("TableDefinition.inc");
/** Defines the data model of the application. Each component is supposed to populate this model with its own part of the model */
class DataModel {

	/** @var $model DataModel */
	private static $model = null;
	/** @return DataModel */
	public static function &get() {
		if (DataModel::$model == null) {
			$model = new DataModel();
			$done = array();
			foreach (PNApplication::$instance->components as $c) {
				DataModel::_get_component($c, $done, $model);
			}
			DataModel::$model = &$model;
		}
		return DataModel::$model;
	}
	private static function _get_component($c, $done, &$model) {
		if (in_array($c->name, $done)) return;
		array_push($done, $c->name);
		foreach ($c->dependencies() as $dep)
			DataModel::_get_component(PNApplication::$instance->components[$dep], $done, $model);
		$file = "component/".$c->name."/datamodel.inc";
		if (file_exists($file))
			include $file;
		if (PNApplication::$instance->current_domain <> null) {
			$file = "component/".$c->name."/datamodel_hidden.inc";
			if (file_exists($file)) {
				$hidden = include $file;
				$hidden = $hidden[PNApplication::$instance->current_domain];
				foreach ($hidden as $field) {
					$i = strpos($field, ".");
					$table = substr($field, 0, $i);
					$field = substr($field, $i+1);
					$model->hide($table,$field);
				}
			}
		}
	}

	protected $tables = array();
	private $data_category_link = array();
	private $sub_models = array();

	/** Add a table to the model, and returns it
	 *
	 * @param string $name name of the table to ad
	 * @return \datamodel\Table
	 */
	public function &addTable($name) {
		if (isset($thos->tables[$name])) throw new Exception("Table ".$name." already defined in the data model");
		$table = new \datamodel\Table($this, $name);
		$this->tables[$name] = &$table;
		return $table;
	}

	public function &addSubModel($key_table) {
		$sm = new SubDataModel($this, $key_table);
		$this->tables[$key_table]->_key_for_submodel($sm);
		array_push($this->sub_models, $sm);
		return $sm;
	}
	
	/** Returns the table if access is allowed.
	 *
	 * @param string $name name of the table to ad
	 * @return \datamodel\Table
	 * @throws \Exception if the table does not exist or access is not allowed
	 */
	public function &getTable($name) {
		if (isset($this->tables[$name])) {
			if ($this->tables[$name]->canAccess()) return $this->tables[$name];
			throw new \Exception("Access denied: table ".$name);
		}
		foreach ($this->sub_models as &$sm) {
			if (isset($sm->tables[$name])) {
				if ($sm->tables[$name]->canAccess()) return $sm->tables[$name];
				throw new \Exception("Access denied: table ".$name);
			}
		}
		if (!isset($this->tables[$name])) throw new \Exception("Unknown table ".$name);
	}

	/** should be used only in very specific places, while ensuring that this will not give privileges to the user
	 * @return \datamodel\Table
	 */
	public function &internalGetTable($name) {
		if (isset($this->tables[$name]))
			return $this->tables[$name];
		foreach ($this->sub_models as &$sm)
			if (isset($sm->tables[$name]))
				return $sm->tables[$name];
		throw new \Exception("Unknown table '".$name."'");
	}
	/** should be used only in very specific places, while ensuring that this will not give privileges to the user */
	public function &internalGetTables() {
		$a = array();
		foreach ($this->tables as $name=>&$table)
			$a[$name] = &$table;
		foreach ($this->sub_models as &$sm)
			foreach ($sm->tables as $name=>&$table)
				$a[$name] = &$table;
		return $a;
	}

	public function &getTables() {
		$a = array();
		foreach ($this->tables as $name=>&$table)
			if ($table->canAccess())
				$a[$name] = &$table;
		foreach ($this->sub_models as &$sm)
			foreach ($sm->tables as $name=>&$table)
				if ($table->canAccess())
					$a[$name] = &$table;
		return $a;
	}

	/** indicates which page to go in order to display information about the given category */
	public function addDataCategoryLink($category, $link) {
		$this->data_category_link[$category] = $link;
	}
	public function getDataCategoryLink($category) {
		return @$this->data_category_link[$category];
	}

	private function hide($table, $field) {
		$this->tables[$table]->hide($field);
	}

}

class SubDataModel extends DataModel {
	private $parent_model;
	private $parent_table;
	
	public function __construct(&$parent, $parent_table) {
		$this->parent_model = &$parent;
		$this->parent_table = $parent_table;
	}
	
	public function &getParentModel() { return $this->parent_model; }
	public function getParentTable() { return $this->parent_table; }
	
	public function create_tables($key) {
		require_once("DataBaseModel.inc");
		foreach ($this->tables as $t)
			DataBaseModel::create_table(SQLQuery::get_db_system_without_security(), $t, "_".$key);
	}
	
	public function remove_tables($key) {
		foreach ($this->tables as $t)
			SQLQuery::get_db_system_without_security()->execute("DROP TABLE `".$t->getName()."_".$key."`");
	}
	
}
?>