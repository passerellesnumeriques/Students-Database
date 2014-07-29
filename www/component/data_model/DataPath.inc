<?php
/**
 * Search paths to reach data starting from a table, and going through foreign keys 
 */ 
class DataPathBuilder {
	
	/**
	 * Search paths from the given table
	 * @param string $table_name the starting point of the search
	 * @param array $sub_models sub models 
	 * @return DataPath[] list of paths found
	 */
	public static function &searchFrom($table_name, $sub_model = null, $go_to_submodels = false, $sub_models = null) {
		require_once("Model.inc");
		/* @var $model DataModel */
		$model = DataModel::get();
		$table = $model->getTable($table_name);
		if ($sub_model <> null) {
			if ($sub_models == null) $sub_models = array();
			$sub_models[$table->getModel()->getParentTable()] = $sub_model;
		}
		$done = array();
		$final_paths = array();
		if ($table->getModel() instanceof SubDataModel) {
			if ($sub_model == null && $sub_models <> null && isset($sub_models[$table->getModel()->getParentTable()]))
				$sub_model = $sub_models[$table->getModel()->getParentTable()];
			if ($sub_model <> null) {
				// we are in a defined sub model
				$path = new DataPath_Table($table, $sub_model);
				DataPathBuilder::searchFromPath($path, $done, $final_paths, $sub_models, $go_to_submodels);
			} else if ($go_to_submodels) {
				// we need to go to all existing sub models
				foreach ($table->getModel()->getExistingInstances() as $sm) {
					$path = new DataPath_Table($table, $sm);
					$new_sub_models = array($table->getModel()->getParentTable()=>$sm);
					if ($sub_models <> null) $new_sub_models = array_merge($new_sub_models, $sub_models);
					DataPathBuilder::searchFromPath($path, $done, $final_paths, $new_sub_models, $go_to_submodels);
				}
			}
		} else {
			$path = new DataPath_Table($table, null);
			DataPathBuilder::searchFromPath($path, $done, $final_paths, $sub_models, $go_to_submodels);
		}
		unset($table);
		return $final_paths;
	}
	
	/**
	 * Search from the given path
	 * @param DataPath $path starting point
	 * @param string[] $done list of tables already analyzed 
	 * @param DataPath[] $final_paths list of paths to a final data found
	 * @param array $sub_models sub models
	 */
	private static function searchFromPath(&$path, &$done, &$final_paths, $sub_models, $go_to_submodels) {
		$multiple = 0;
		$tables = array();
		$p = $path;
		while ($p <> null) {
			if (in_array($p->table->getSQLNameFor($p->sub_model), $tables)) return; // 2 times the same table in the path
			array_push($tables, $p->table->getSQLNameFor($p->sub_model));
			if (!$p->unique) $multiple++;
			$p = $p->parent;
		}
		if ($multiple > 1) return;
		array_push($final_paths, $path);
		if (isset($done[$path->table->getSQLNameFor($path->sub_model)])) {
			if (in_array($multiple, $done[$path->table->getSQLNameFor($path->sub_model)])) return;
			array_push($done[$path->table->getSQLNameFor($path->sub_model)],$multiple);
		} else {
			$done[$path->table->getSQLNameFor($path->sub_model)] = array($multiple);
		}
		DataPathBuilder::addForeignKeys($path, $done, $final_paths, $sub_models, $go_to_submodels);
		DataPathBuilder::addExternalForeignKeys($path, $done, $final_paths, $sub_models, $go_to_submodels);
	}
	
	/**
	 * Go through foreign keys at the given path
	 * @param DataPath $path the path where to start
	 * @param string[] $done list of tables already analyzed
	 * @param DataPath[] $final_paths list of final paths already found
	 * @param array $sub_models list of sub models selection
	 */
	private static function addForeignKeys(&$path, &$done, &$final_paths, $sub_models, $go_to_submodels) {
		foreach ($path->table->internalGetColumns($sub_models) as $col) {
			if (!($col instanceof \datamodel\ForeignKey)) continue;
			if ($col->foreign_table == $path->table->getName()) continue; // link on itself
			try {
				$t = DataModel::get()->getTable($col->foreign_table);
				$sm = null;
				if ($t->getModel() instanceof SubDataModel) {
					if ($path->table->getModel() instanceof SubDataModel) {
						if ($path->table->getModel()->getParentTable() == $t->getModel()->getParentTable()) {
							// we are on the same sub model
							$sm = $path->sub_model;
						} else {
							// two different sub models
							if ($sub_models == null || !isset($sub_models[$t->getModel()->getParentTable()]))
								continue; // we don't have a sub model
							$sm = $sub_models[$t->getModel()->getParentTable()];
						}
					} else {
						// t is on a sub model, but not path
						if ($sub_models == null || !isset($sub_models[$t->getModel()->getParentTable()]))
							continue; // we don't have a sub model
						$sm = $sub_models[$t->getModel()->getParentTable()];
					}
				}
				$p = new DataPath_Join($path, $col, $t, $sm);
				$new_sub_models = array();
				if ($sm <> null) $new_sub_models[$t->getModel()->getParentTable()] = $sm;
				if ($sub_models <> null) $new_sub_models = array_merge($new_sub_models, $sub_models);
				DataPathBuilder::searchFromPath($p, $done, $final_paths, $new_sub_models, $go_to_submodels);
				unset($t);
			} catch (Exception $e) {
				// not allowed
				continue;
			}
		}
	}
	
	/**
	 * Search other tables containing foreign keys to the current table
	 * @param DataPath $path the path where to start
	 * @param string[] $done list of tables already analyzed
	 * @param DataPath[] $final_paths list of paths already found
	 * @param array $sub_models selection of sub models
	 */
	private static function addExternalForeignKeys(&$path, &$done, &$final_paths, $sub_models, $go_to_submodels) {
		$model = DataModel::get();
		$tables = $model->getTables();
		foreach ($tables as &$t) {
			$sm = null;
			if ($t->getModel() instanceof SubDataModel) {
				if ($path->table->getModel() instanceof SubDataModel) {
					if ($path->table->getModel()->getParentTable() <> $t->getModel()->getParentTable())
						continue; // different sub model
					$sm = $path->sub_model;
				} else if ($sub_models <> null && isset($sub_models[$t->getModel()->getParentTable()])) {
					$sm = $sub_models[$t->getModel()->getParentTable()];
				} else {
					// did we already go through this sub model
					$p = $path->parent;
					while ($p <> null) {
						if (($p->table->getModel() instanceof SubDataModel) && $p->table->getModel()->getParentTable() == $t->getModel()->getParentTable()) {
							$sm = $p->sub_model;
							break;
						} 
						$p = $p->parent;
					}
					if ($sm == null) {
						// we don't have any sub model here
						// stop // TODO all sub models?
						continue;
					}
				}
			}
			foreach ($t->internalGetColumns($sub_models) as $col) {
				if (!($col instanceof \datamodel\ForeignKey)) continue;
				if ($col->foreign_table <> $path->table->getName()) continue;
				// we have one
				if ($t->getModel() instanceof SubDataModel && $sm == null) {
					// go through each instance
					if ($go_to_submodels) {
						foreach ($t->getModel()->getExistingInstances() as $sminstance) {
							$p = new DataPath_Join($path, $col, $col->table, $sminstance);
							$new_sub_models = $sub_models;
							$new_sub_models[$t->getModel()->getParentTable()] = $sminstance;
							DataPathBuilder::searchFromPath($p, $done, $final_paths, $new_sub_models, $go_to_submodels);
						}
					}
				} else {
					$p = new DataPath_Join($path, $col, $col->table, $sm);
					$new_sub_models = array();
					if ($sm <> null) $new_sub_models[$t->getModel()->getParentTable()] = $sm;
					if ($sub_models <> null) $new_sub_models = array_merge($new_sub_models, $sub_models);
					DataPathBuilder::searchFromPath($p, $done, $final_paths, $new_sub_models, $go_to_submodels);
				}
			}
			unset($t);
		}
	}
	
	/**
	 * Build a hierarchy of path, from a flat list of DataPath, by adding a children attribute
	 * @param DataPath[] $paths the flat list of paths
	 * @return DataPath the root data path
	 */
	public static function buildPathsTree($paths) {
		if (count($paths) == 0) return null;
		$root = $paths[0];
		while ($root->parent <> null) $root = $root->parent;
		$root->{"children"} = array();
		foreach ($paths as $p) {
			$p->{"children"} = array();
			DataPathBuilder::addTree($root, $p);
		}
		return $root;
	}
	/** Add the given path to the hierarchy
	 * @param DataPath $root the root of the hierarchy
	 * @param DataPath $path the path to add
	 */
	private static function addTree(&$root, $path) {
		$list = array();
		$p = $path;
		while ($p->parent <> null) {
			array_push($list, $p);
			$p = $p->parent;
		}
		$pos = $root;
		for ($i = count($list)-1; $i >= 0; $i--) {
			$p = $list[$i];
			$children = @$pos->children;
			if ($children == null) $pos->{"children"} = array();
			$found = false;
			foreach ($pos->children as $c)
				if ($c == $p) {
					$pos = $c;
					$found = true;
					break;
				}
			if (!$found) {
				array_push($pos->children, $p);
				$pos = $p;
			}
		}
	}
	
}

/** Abstract class to represent a path to a data */
abstract class DataPath {

	/** @var DataPath previous section of the path */
	public $parent;
	/** @var datamodel\Table table this path points to */
	public $table;
	/** @var number|null sub model instance */
	public $sub_model;
	/** @var boolean indicates if the data pointed by this path is unique or not (only 1 to 1 relations) */
	public $unique = true;
	
	/**
	 * @param DataPath $parent parent path
	 * @param datamodel\Table $table table
	 * @param number|null $sub_model sub model instance
	 */
	public function __construct($parent, &$table, $sub_model) {
		$this->parent = $parent;
		$this->table = &$table;
		$this->sub_model = $sub_model;
	}
	
	/** Get a representation of this path in a string
	 * @return string representation of this path
	 */
	public abstract function getString();
	
	/** Indicates if the data pointed by this path is unique or not (only 1 to 1 relations)
	 * @return boolean true if it is unique
	 */
	public final function isUnique() {
		if (!$this->unique) return false;
		if ($this->parent == null) return true;
		return $this->parent->isUnique();
	}
	
}

/** a simple select on a table, as a starting point */
class DataPath_Table extends DataPath {

	/**
	 * @param \datamodel\Table $table
	 * @param array|null $sub_models
	 */
	public function __construct(&$table, $sub_model) {
		parent::__construct(null, $table, $sub_model);
	}

	public function getString() {
		$s = $this->table->getName();
		if ($this->sub_model <> null)
			$s .= "[".$this->sub_model."]";
		return $s;
	}
	
}

/** join following a foreign key */
class DataPath_Join extends DataPath {
	
	/** @var \datamodel\ForeignKey the foreign key used for the join */
	public $foreign_key;

	public function __construct($parent, &$foreign_key, &$target_table, $target_sub_model) {
		$this->foreign_key = $foreign_key;
		parent::__construct($parent, $target_table, $target_sub_model);
		if ($parent->table == $foreign_key->table)
			$this->unique = true;
		else 
			$this->unique = !$foreign_key->multiple;
	}
	
	public function getString() {
		$s = $this->parent->getString();
		$s .= $this->parent->table == $this->foreign_key->table ? ">" : "<";
		if (!$this->unique) $s.= $this->parent->table == $this->foreign_key->table ? ">" : "<";
		if ($this->foreign_key->can_be_null) $s .= "?";
		$s .= $this->table->getName();
		if ($this->sub_model <> null)
			$s .= "[".$this->sub_model."]";
		$s .= "(".$this->foreign_key->name.")";
		return $s;
	}
	
	/** Indicates the direction of the join: reverse means the foreign table is the one pointing to our current table, if not reverse this is the current table which contains a foreign key to the foreign table
	 * @return boolean true if this is a reverse join
	 */
	public function isReverse() {
		return $this->parent->table <> $this->foreign_key->table;
	}
	
}

/** Builds an SQL request to reach the table pointed by a DataPath */
class DataPathSQLBuilder {
	
	/** Builds an SQL request to reach the table pointed by a DataPath
	 * @param DataPath $path the path
	 * @param SQLQuery $q the query to fill
	 * @return string the alias of the table pointed by the path
	 */
	public static function build($path, &$q) {
		if ($path instanceof DataPath_Join) {
			$parent_alias = DataPathSQLBuilder::build($path->parent, $q);
			$match = array();
			if ($path->isReverse())
				$match[$path->parent->table->getPrimaryKey()->name] = $path->foreign_key->name;
			else
				$match[$path->foreign_key->name] = $path->table->getPrimaryKey()->name; 
			$alias = $q->getJoinAlias($parent_alias, $path->table->getSQLNameFor($path->sub_model), $match);
			if ($alias == null) {
				$alias = $q->generateTableAlias();
				if ($path->sub_model) $q->selectSubModelForTable($path->table, $path->sub_model);
				$q->join($parent_alias, $path->table->getName(), $match, $alias);
			}
			return $alias;
		}
		$alias = $q->getTableAlias($path->table->getName());
		return $alias;
	}
	
}

?>