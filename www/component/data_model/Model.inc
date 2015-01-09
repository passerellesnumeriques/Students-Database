<?php
use datamodel\DataDisplayCategory;
use datamodel\TableDataDisplay;
use datamodel\DataScreen;
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
			$model = &DataModel::$model;
			$components = PNApplication::sortComponentsByDependencies();
			// TODO better for prod version, but data model can include sub files....
			foreach ($components as $c) {
				$file = "component/".$c->name."/datamodel.inc";
				if (file_exists($file))
					include $file;
			}
			foreach ($components as $c) {
				$file = "component/".$c->name."/datamodel_delay.inc";
				if (file_exists($file))
					include $file;
			}
			foreach ($components as $c) {
				$file = "component/".$c->name."/datamodel_display.inc";
				if (file_exists($file))
					include $file;
			}
#DEV
			foreach ($model->internalGetTables(true) as $table) {
				if ($table->getPrimaryKey() <> null) continue;
				$key = $table->getKey();
				if ($key == null || count($key) == 0) PNApplication::error("Invalid datamodel: table ".$table->getName()." does not have a key");
			}
#END
		}
		return DataModel::$model;
	}
	
	/** @var array list of tables */
	protected $tables = array();
	/** @var SubDataModel[] list of sub models */
	private $sub_models = array();
	/** @var DataDisplayCategory[] list of categories */
	private $data_categories = array();
	/** @var TableDataDisplay[] list of table having displayable data */
	private $table_display = array();
	/** @var DataScreen[] list of screens */
	private $data_screens = array();
	
	/** Add a table to the model, and returns it
	 * @param string $name name of the table to ad
	 * @return \datamodel\Table the added table
	 */
	public function &addTable($name) {
#DEV
		if (isset($this->tables[$name])) throw new Exception("Table ".$name." already defined in the data model");
#END
		$table = new \datamodel\Table($this, $name);
		$this->tables[$name] = &$table;
#DEV
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
#END
		return $table;
	}

	/** Add a sub model
	 * @param string $key_table the table used as the key of the sub model
	 * @return SubDataModel the new sub model
	 */
	public function &addSubModel($key_table) {
		$sm = new SubDataModel($this, $key_table);
		$this->tables[$key_table]->_key_for_submodel($sm);
		array_push($this->sub_models, $sm);
		return $sm;
	}
	
	/** Get a sub model
	 * @param string $key_table the table serving as the key of the sub model
	 * @return SubDataModel the sub model
	 */
	public function &getSubModel($key_table) {
		for ($i = 0; $i < count($this->sub_models); $i++)
			if ($this->sub_models[$i]->getParentTable() == $key_table)
				return $this->sub_models[$i];
	}
	
	/** Returns the table if access is allowed.
	 * @param string $name name of the table to add	
	 * @return \datamodel\Table the table
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
	 * @param string $name table name
	 * @return datamodel\Table the table
	 */
	public function &internalGetTable($name) {
		if (isset($this->tables[$name]))
			return $this->tables[$name];
		foreach ($this->sub_models as &$sm)
			if (isset($sm->tables[$name]))
				return $sm->tables[$name];
		throw new \Exception("Unknown table '".$name."'");
	}
	
	/** Get a table using a SQL name (which may contain a sub model instance)
	 * @param string $name the SQL name
	 * @param boolean $bypass_security true to avoid security checks
	 * @throws \Exception if not found or access is not allowed
	 * @return datamodel\Table the table
	 */
	public function getTableFromSQLName($name, $bypass_security = false) {
		if ($this instanceof SubDataModel) return $this->parent_model->getTableFromSQLName($name, $bypass_security);
		
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
		if ($bypass_security || $t->canAccess()) return $t;
		throw new \Exception("Access denied: table ".$t->getName());
	}
	/**
	 * From the SQL name of a table, determine its sub model instance
	 * @param string $name the table SQL name
	 * @return NULL|integer the sub model
	 */
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
	 * @param boolean $include_sub_models if true, tables from sub models will be added in the result
	 * @return datamodel\Table[] list of tables
	 */
	public function &internalGetTables($include_sub_models = true) {
		$a = array();
		foreach ($this->tables as $name=>&$table)
			$a[$name] = &$table;
		if ($include_sub_models)
			foreach ($this->sub_models as &$sm)
				foreach ($sm->tables as $name=>&$table)
					$a[$name] = &$table;
		return $a;
	}

	/** Get the list of accessible tables
	 * @return datamodel\Table[] list of tables
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
	
	/** Get hte list of sub models
	 * @return SubDataModel[] the sub models
	 */
	public function getSubModels() {
		return $this->sub_models;
	}

	/** Add a category
	 * @param DataDisplayCategory $category the category
	 */
	public function addDataCategory($category) {
		array_push($this->data_categories, $category);
	}
	/** Get a category
	 * @param string $name name of the category to search
	 * @return DataCategory|NULL the category, or null if not found
	 */
	public function getDataCategory($name) {
		foreach ($this->data_categories as $c)
			if ($c->getName() == $name) 
				return $c;
		return null;
	}
	
	/** Add a table data display
	 * @param TableDataDisplay $display the table data display to add
	 */
	public function addTableDataDisplay($display) {
		array_push($this->table_display, $display);
	}
	/** Get the table data display for the given table
	 * @param string $table_name name of the table
	 * @return \datamodel\TableDataDisplay|null the table data display, or null if it does not exist
	 */
	public function getTableDataDisplay($table_name) {
		foreach ($this->table_display as $d)
			if ($d->getTableName() == $table_name)
				return $d;
		return null;
	}
	
	/** Add a screen
	 * @param DataScreen $screen the screen
	 */
	public function addDataScreen($screen) {
		array_push($this->data_screens, $screen);
	}
	/** Get all screens
	 * @return DataScreen[] the screens
	 */
	public function getDataScreens() {
		return $this->data_screens;
	}
	
}

/**
 * Defines a sub-model
 */
class SubDataModel extends DataModel {
	/** @var DataModel the model containing this sub model */
	private $parent_model;
	/** @var string name of the table used as the key for this sub model */
	private $parent_table;
	/** @var array links helping to know which entities are linked to which elements of the parent model */
	private $links_to_parent = array();
	
	/**
	 * @param DataModel $parent model containing this sub model
	 * @param string $parent_table name of the table being the key for this sub model
	 */
	public function __construct(&$parent, $parent_table) {
		$this->parent_model = &$parent;
		$this->parent_table = $parent_table;
	}
	
	/**
	 * Get the parent data model
	 * @return DataModel parent
	 */
	public function &getParentModel() { return $this->parent_model; }
	/**
	 * Get the name of the table serving as the key for this sub model
	 * @return string the name of the table
	 */
	public function getParentTable() { return $this->parent_table; }
	
	/**
	 * Add a link between a table of this sub model, and a table of the parent model. This link will help finding in which sub model instance we can find data from a row in the parent model.
	 * @param string $sub_model_table name of the table in the sub model
	 * @param string $root_table name of the table in the parent model
	 * @param boolean $unique if true, only one link can exists for each entity
	 * @param string[]|null $needed_columns_to_join list of columns needed to know if we can join
	 * @param function $join_check_function function checking if a join is allowed
	 */
	public function addSubModelLink($sub_model_table, $root_table, $unique, $needed_columns_to_join = null, $join_check_function = null) {
		$t = $this->internalGetTable($sub_model_table);
		// check we have a foreign key
		if ($t == null) { PNApplication::error("Invalid table: ".$sub_model_table); return; }
		$fk = null;
		foreach ($t->internalGetColumns(null,false) as $col)
			if ($col instanceof datamodel\ForeignKey)
				if ($col->foreign_table == $root_table) { $fk = $col; break; }
		if ($fk == null) { PNApplication::error("Invalid sub model link: table ".$sub_model_table." does not have a foreign key to table ".$root_table); return; }
		// add the link table
		if ($unique)
			$link_table = $this->parent_model->addTable("smlink_".$sub_model_table."_".$root_table)
				->addForeignKey("root", $root_table, true, false, false, false, true)
				->addForeignKey("sm", $this->parent_table, true, false, true, false, false)
				->setPrimary("root")
				->allowReadAccess()
				->addRemoveAccessFromForeignTable($root_table)
				->addRemoveAccessFromForeignTable($this->parent_table)
				;
		else
			$link_table = $this->parent_model->addTable("smlink_".$sub_model_table."_".$root_table)
				->addForeignKey("root", $root_table, true, false, true, false, false)
				->addForeignKey("sm", $this->parent_table, true, false, true, false, false)
				->setKey(array("root","sm"))
				->addIndex("root")
				->allowReadAccess()
				->addRemoveAccessFromForeignTable($root_table)
				->addRemoveAccessFromForeignTable($this->parent_table)
				;
		$fake_category = new datamodel\DataDisplayCategory("");
		$display = new datamodel\TableDataDisplay("smlink_".$sub_model_table."_".$root_table, $fake_category);
		$display->addJoinFrom("root",$needed_columns_to_join,$join_check_function);
		$display->addFollowAll();
		$this->parent_model->addTableDataDisplay($display);
		
		$this->links_to_parent[$sub_model_table] = $root_table;
		// listen to events
		new SubModelLinkListener($t, $fk, $link_table, $unique);
	}
	
	/** Check if a link exists
	 * @param string $sub_model_table_name table in the sub model
	 * @param string $root_table_name table in the parent model
	 * @return boolean true if a link exists between the 2 tables
	 */
	public function hasLink($sub_model_table_name, $root_table_name) {
		if (!isset($this->links_to_parent[$sub_model_table_name])) return false;
		return $this->links_to_parent[$sub_model_table_name] == $root_table_name;
	}
	public function isLinkUnique($sub_model_table_name) {
		return $this->parent_model->internalGetTable("smlink_".$sub_model_table_name."_".$this->links_to_parent[$sub_model_table_name])->getPrimaryKey() <> null;
	}
	/**
	 * Check if a link exists
	 * @param string $table_name table in the sub model
	 * @return boolean true if the table is linked to a table in the parent model
	 */
	public function hasLinkOnSubModelTable($table_name) {
		return array_key_exists($table_name, $this->links_to_parent);
	}
	/**
	 * Get the table of the parent model linked to the given table of the sub model
	 * @param string $sub_model_table_name table of the sub model
	 * @return string table of the parent model
	 */
	public function getLinkedRootTable($sub_model_table_name) {
		return @$this->links_to_parent[$sub_model_table_name];
	}
	/**
	 * Get the list of tables of the parent model having a link to a table in this sub model
	 * @return string[] list of tables
	 */
	public function getLinkedRootTables() {
		return array_values($this->links_to_parent);
	}
	/**
	 * Get the table of the sub model having a link to the given table of the parent model
	 * @param string $root_table name of the table in the parent model
	 * @return string|NULL the table of the sub model
	 */
	public function getLinkedSubModelTable($root_table) {
		foreach ($this->links_to_parent as $sm_table=>$rt)
			if ($rt == $root_table) return $sm_table;
		return null;
	}
	
	/**
	 * Called when a new sub model is created
	 * @param integer $key the new key
	 * @param DataBaseSystem|null $db_system database connection to use
	 */
	public function createTables($key, $db_system = null) {
		if (SQLQuery::isTransactionStarted()) {
			PNApplication::error("You cannot insert in a table which is the key for a SubDataModel, if you are in a transaction: ".$this->getParentTable());
			return;
		}
		require_once("DataBaseUtilities.inc");
		foreach ($this->tables as $t)
			DataBaseUtilities::createTable($db_system == null ? SQLQuery::getDataBaseAccessWithoutSecurity() : $db_system, $t, "_".$key);
		if ($this->instances <> null)
			array_push($this->instances, $key);
	}
	
	/**
	 * Called when a sub model is removed
	 * @param integer $key the key
	 */
	public function removeTables($key) {
		if (SQLQuery::isTransactionStarted()) {
			PNApplication::error("You cannot delete an entry from a table which is the key for a SubDataModel, if you are in a transaction: ".$this->getParentTable());
			return;
		}
		require_once("DataBaseUtilities.inc");
		$path = realpath(dirname(__FILE__)."/../../data/".PNApplication::$instance->current_domain);
		foreach ($this->tables as $t) {
			DataBaseUtilities::dropTable(SQLQuery::getDataBaseAccessWithoutSecurity(), $t, "_".$key);
			if ($t->isCustomized())
				@unlink($path."/custom_tables/".$t->getName()."_".$key);
		}
		if ($this->instances <> null)
			for ($i = 0; $i < count($this->instances); $i++)
			if ($this->instances[$i] == $key) {
				array_splice($this->instances, $i, 1);
				break;
			}
	}
	
	/** @var integer[] cache of the list of sub model instances */
	private $instances = null;
	/**
	 * Get the list of existing sub models
	 * @return integer[] list of sub models
	 */
	public function getExistingInstances() {
		if ($this->instances === null) {
			$t = $this->parent_model->internalGetTable($this->parent_table);
			$this->instances = SQLQuery::create()->bypassSecurity()->select($this->parent_table)->field($this->parent_table, $t->getPrimaryKey()->name)->executeSingleField();
		}
		return $this->instances;
	}
	
	// delegate to parent
	
	public function addDataCategory($category) { $this->parent_model->addDataCategory($category); }
	public function getDataCategory($name) { return $this->parent_model->getDataCategory($name); }
	public function addTableDataDisplay($display) { $this->parent_model->addTableDataDisplay($display); }
	public function getTableDataDisplay($table_name) { return $this->parent_model->getTableDataDisplay($table_name); }
	public function addDataScreen($screen) { $this->parent_model->addDataScreen($screen); }
	public function getDataScreens() { return $this->parent_model->getDataScreens(); }
	
}

/**
 * Internal, used by the SubModelData class to listen to events in order to keep links updated
 */
class SubModelLinkListener {
	
	/**
	 * @param Table $sub_model_table table of the sub model
	 * @param string $sub_model_foreign_key key in the sub model linking to a table of the parent model
	 * @param Table $link_table table of the parent model
	 * @param boolean $unique true if only one link can exist for each entity
	 */
	public function __construct($sub_model_table, $sub_model_foreign_key, $link_table, $unique) {
		$this->link_table = $link_table;
		$this->sub_model_foreign_key = $sub_model_foreign_key;
		$this->unique = $unique;
		$sub_model_table->onInsert(array($this,"onInsert"));
		$sub_model_table->onRemove(array($this,"onRemove"));
	}
	
	/** @var datamodel\Table $link_table the table of the parent model */
	private $link_table;
	/** @var datamodel\ForeignKey $sub_model_foreign_key foreign key being the link */
	private $sub_model_foreign_key;
	/** @var boolean true if only one link can exist for each entity */
	private $unique;
	
	/** Listen to event when something is inserted in the sub model table
	 * @param array $inserted inserted data
	 */
	public function onInsert($inserted) {
		$to_insert = array();
		foreach ($inserted as $row) array_push($to_insert, array(
			"root"=>$row["fields_values"][$this->sub_model_foreign_key->name],
			"sm"=>$row["sub_model_instance"]
		));
		SQLQuery::create()->bypassSecurity()->insertMultiple($this->link_table->getName(), $to_insert);
	}

	/**
	 * Listen to event when something is removed in the sub model table
	 * @param string $table table
	 * @param array $rows removed rows
	 * @param integer $sub_model_instance sub model
	 */
	public function onRemove($table, $rows, $sub_model_instance) {
		$keys = array();
		if ($this->unique) {
			foreach ($rows as $row) array_push($keys, $row[$this->sub_model_foreign_key->name]);
		} else {
			foreach ($rows as $row) array_push($keys, array("root"=>$row[$this->sub_model_foreign_key->name],"sm"=>$sub_model_instance));
		}
		SQLQuery::create()->bypassSecurity()->removeKeys($this->link_table->getName(), $keys);
	}
	
}
?>