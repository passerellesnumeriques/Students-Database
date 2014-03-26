<?php
require_once("TableDefinition.inc");
require_once("DataDisplayCategory.inc");
require_once("TableDataDisplay.inc");
require_once("DataDisplay.inc");
require_once("DataScreen.inc");
/** Defines the data model of the application. Each component is supposed to populate this model with its own part of the model */
class DataModel {

	/** @static 
	 * @var DataModel singleton*/
	private static $model = null;
	/** retrieve the singleton instance of the data model
	 * @static 
	 * @return DataModel the data model */
	public static function &get() {
		if (DataModel::$model == null) {
			DataModel::$model = new DataModel();
			$done = array();
			foreach (PNApplication::$instance->components as $c) {
				DataModel::_get_component($c, $done, DataModel::$model);
			}
			$done = array();
			foreach (PNApplication::$instance->components as $c) {
				DataModel::_get_component_display($c, $done, DataModel::$model);
			}
		}
		return DataModel::$model;
	}
	private static function _get_component($c, &$done, &$model) {
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
				if (isset($hidden[PNApplication::$instance->current_domain])) {
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
	}
	private static function _get_component_display($c, &$done, &$model) {
		if (in_array($c->name, $done)) return;
		array_push($done, $c->name);
		foreach ($c->dependencies() as $dep)
			DataModel::_get_component_display(PNApplication::$instance->components[$dep], $done, $model);
		$file = "component/".$c->name."/datamodel_display.inc";
		if (file_exists($file))
			include $file;
	}
	
	protected $tables = array();
	private $sub_models = array();
	private $data_categories = array();
	private $table_display = array();
	private $data_screens = array();
	
	/** Add a table to the model, and returns it
	 *
	 * @param string $name name of the table to ad
	 * @return \datamodel\Table
	 */
	public function &addTable($name) {
		if (isset($this->tables[$name])) throw new Exception("Table ".$name." already defined in the data model");
		$table = new \datamodel\Table($this, $name);
		$this->tables[$name] = &$table;
		if (PNApplication::$instance->current_domain == "Dev") {
			$table->{"declaring_component"} = "unknown";
			try { throw new Exception(); }
			catch (Exception $e) {
				$root = str_replace("\\","/",realpath("component"))."/";
				foreach ($e->getTrace() as $trace) {
					$file = str_replace("\\","/",realpath($trace["file"]));
					if (substr($file,0,strlen($root)) <> $root) continue;
					$component = substr($file,strlen($root));
					$i = strpos($component, "/");
					if ($i === FALSE) continue;
					$component = substr($component,0,$i);
					$table->{"declaring_component"} = $component;
					//$table->{"file"} = $file;
					break;
				}
			}
		}
		return $table;
	}

	/**
	 * @param string $key_table
	 * @return SubDataModel
	 */
	public function &addSubModel($key_table) {
		$sm = new SubDataModel($this, $key_table);
		$this->tables[$key_table]->_key_for_submodel($sm);
		array_push($this->sub_models, $sm);
		return $sm;
	}
	
	/**
	 * @param string $key_table
	 * @return SubDataModel
	 */
	public function &getSubModel($key_table) {
		for ($i = 0; $i < count($this->sub_models); $i++)
			if ($this->sub_models[$i]->getParentTable() == $key_table)
			return $this->sub_models[$i];
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
	 * @return datamodel\Table
	 */
	public function &internalGetTable($name) {
		if (isset($this->tables[$name]))
			return $this->tables[$name];
		foreach ($this->sub_models as &$sm)
			if (isset($sm->tables[$name]))
				return $sm->tables[$name];
		throw new \Exception("Unknown table '".$name."'");
	}
	
	/**
	 * @param string $name
	 * @throws \Exception
	 * @return datamodel\Table
	 */
	public function getTableFromSQLName($name) {
		if ($this instanceof SubDataModel) return $this->parent_model->getTableFromSQLName($name);
		
		if (isset($this->tables[$name]))
			$t = $this->tables[$name];
		else {
			$t = null;
			foreach ($this->sub_models as $sm) {
				foreach ($sm->tables as $tbl_name=>$tbl) {
					if (substr($name,0,strlen($tbl_name)) <> $tbl_name) continue;
					if (substr($name,strlen($tbl_name),1) <> "_") continue;
					$instance = substr($name,strlen($tbl_name)+1);
					$id = intval($instance);
					if ($id == 0) continue;
					$t = $tbl;
					break;
				}
				if ($t <> null) break;
			}
		}
		if ($t == null) throw new \Exception("Table '".$name."' does not exist");
		if ($t->canAccess()) return $t;
		throw new \Exception("Access denied: table ".$t->getName());
	}
	public function getSubModelInstanceFromSQLName($name) {
		if ($this instanceof SubDataModel) return $this->parent_model->getSubModelInstanceFromSQLName($name);
		if (isset($this->tables[$name])) return null;
		foreach ($this->sub_models as $sm) {
			foreach ($sm->tables as $tbl_name=>$tbl) {
				if (substr($name,0,strlen($tbl_name)) <> $tbl_name) continue;
				if (substr($name,strlen($tbl_name),1) <> "_") continue;
				$instance = substr($name,strlen($tbl_name)+1);
				$id = intval($instance);
				if ($id == 0) continue;
				return $id;
			}
		}
		return null;
	}
	
	/** should be used only in very specific places, while ensuring that this will not give privileges to the user
	 * @return datamodel\Table[] 
	 */
	public function &internalGetTables() {
		$a = array();
		foreach ($this->tables as $name=>&$table)
			$a[$name] = &$table;
		foreach ($this->sub_models as &$sm)
			foreach ($sm->tables as $name=>&$table)
				$a[$name] = &$table;
		return $a;
	}

	/**
	 * @return datamodel\Table[]
	 */
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
	
	public function getSubModels() {
		return $this->sub_models;
	}

	public function addDataCategory($category) {
		array_push($this->data_categories, $category);
	}
	public function getDataCategory($name) {
		foreach ($this->data_categories as $c)
			if ($c->getName() == $name) 
				return $c;
		return null;
	}
	
	public function addTableDataDisplay($display) {
		array_push($this->table_display, $display);
	}
	/**
	 * @param string $table_name
	 * @return \datamodel\TableDataDisplay
	 */
	public function getTableDataDisplay($table_name) {
		foreach ($this->table_display as $d)
			if ($d->getTableName() == $table_name)
				return $d;
		return null;
	}
	
	public function addDataScreen($screen) {
		array_push($this->data_screens, $screen);
	}
	public function getDataScreens() {
		return $this->data_screens;
	}
	
	private function hide($table, $field) {
		$this->tables[$table]->hide($field);
	}

}

class DataCategoryLink {
	
	public $link;
	public $icon;
	
	public function __construct($link, $icon = null) {
		$this->link = $link;
		$this->icon = $icon;
	}
	
	public function getParameters() {
		$params = array();
		$i = 0;
		while (($j = strpos($this->link, "%", $i)) !== FALSE) {
			$k = strpos($this->link, "%", $j+1);
			if ($k === FALSE) break;
			$content = substr($this->link, $j+1, $k-$j-1);
			$l = strpos($content, ".");
			if ($l === FALSE) {
				$i = $k;
				continue;
			}
			array_push($params, array("table"=>substr($content,0,$l),"column"=>substr($content,$l+1),"name"=>$content));
			$i = $k+1;
		}
		return $params;
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
	
	public function addDataDisplayHandler($handler) {
		$this->parent_model->addDataDisplayHandler($handler);
	}
	public function getDataDisplayHandler($table) {
		return $this->parent_model->getDataDisplayHandler($table);
	}
	
	public function createTables($key) {
		if (SQLQuery::isTransactionStarted()) {
			PNApplication::error("You cannot insert in a table which is the key for a SubDataModel, if you are in a transaction: ".$this->getParentTable());
			return;
		}
		require_once("DataBaseUtilities.inc");
		foreach ($this->tables as $t)
			DataBaseUtilities::createTable(SQLQuery::getDataBaseAccessWithoutSecurity(), $t, "_".$key);
		if ($this->instances <> null)
			array_push($this->instances, $key);
	}
	
	public function removeTables($key) {
		if (SQLQuery::isTransactionStarted()) {
			PNApplication::error("You cannot delete an entry from a table which is the key for a SubDataModel, if you are in a transaction: ".$this->getParentTable());
			return;
		}
		require_once("DataBaseUtilities.inc");
		foreach ($this->tables as $t)
			DataBaseUtilities::dropTable(SQLQuery::getDataBaseAccessWithoutSecurity(), $t, "_".$key);
		if ($this->instances <> null)
			for ($i = 0; $i < count($this->instances); $i++)
			if ($this->instances[$i] == $key) {
				array_splice($this->instances, $i, 1);
				break;
			}
	}
	
	private $instances = null;
	public function getExistingInstances() {
		if ($this->instances == null) {
			$t = $this->parent_model->internalGetTable($this->parent_table);
			$this->instances = SQLQuery::create()->bypassSecurity()->select($this->parent_table)->field($this->parent_table, $t->getPrimaryKey()->name)->executeSingleField();
		}
		return $this->instances;
	}
	
}
?>