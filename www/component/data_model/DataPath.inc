<?php 
class DataPathBuilder {
	
	public static function &search_from($table_name, $sub_models = null) {
		require_once("Model.inc");
		/* @var $model DataModel */
		$model = DataModel::get();
		$table = $model->getTable($table_name);
		$done = array();
		$final_paths = array();
		if ($table->getModel() instanceof SubDataModel) {
			if ($sub_models <> null && isset($sub_models[$table->getModel()->getParentTable()])) {
				// we are in a defined sub model
				$path = new DataPath_Table($table, $sub_models[$table->getModel()->getParentTable()]);
				DataPathBuilder::search_from_path($path, $done, $final_paths, $sub_models);
			} else {
				// we need to go to all existing sub models
				foreach ($table->getModel()->getExistingInstances() as $sm) {
					$path = new DataPath_Table($table, $sm);
					DataPathBuilder::search_from_path($path, $done, $final_paths, $sub_models);
				}
			}
		} else {
			$path = new DataPath_Table($table, null);
			DataPathBuilder::search_from_path($path, $done, $final_paths, $sub_models);
		}
		unset($table);
		return $final_paths;
	}
	
	private static function search_from_path(&$path, &$done, &$final_paths, $sub_models) {
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
		DataPathBuilder::add_foreign_keys($path, $done, $final_paths, $sub_models);
		DataPathBuilder::add_external_foreign_keys($path, $done, $final_paths, $sub_models);
	}
	
	private static function add_foreign_keys(&$path, &$done, &$final_paths, $sub_models) {
		foreach ($path->table->internalGetColumns() as $col) {
			if (!($col instanceof \datamodel\ForeignKey)) continue;
			if ($col->foreign_table == $path->table->getName()) continue; // link on itself
			try {
				$t = DataModel::get()->getTable($col->foreign_table);
				$sm = null;
				if ($t instanceof SubDataModel) {
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
				DataPathBuilder::search_from_path($p, $done, $final_paths, $sub_models);
				unset($t);
			} catch (Exception $e) {
				// not allowed
				continue;
			}
		}
	}
	
	private static function add_external_foreign_keys(&$path, &$done, &$final_paths, $sub_models) {
		$model = DataModel::get();
		$tables = $model->getTables();
		foreach ($tables as &$t) {
			$sm = null;
			if ($t->getModel() instanceof SubDataModel) {
				if ($path->table->getModel() instanceof SubDataModel) {
					if ($path->table->getModel()->getParentTable() <> $t->getModel()->getParentTable())
						continue; // different sub model
					$sm = $path->sub_model;
				}
			}
			foreach ($t->internalGetColumns() as $col) {
				if (!($col instanceof \datamodel\ForeignKey)) continue;
				if ($col->foreign_table <> $path->table->getName()) continue;
				// we have one
				$p = new DataPath_Join($path, $col, $col->table, $sm);
				DataPathBuilder::search_from_path($p, $done, $final_paths, $sub_models);
			}
			unset($t);
		}
	}
	
	public static function build_paths_tree($paths) {
		if (count($paths) == 0) return null;
		$root = $paths[0];
		while ($root->parent <> null) $root = $root->parent;
		$root->{"children"} = array();
		foreach ($paths as $p) {
			$p->{"children"} = array();
			DataPathBuilder::add_tree($root, $p);
		}
		return $root;
	}
	private static function add_tree(&$root, $path) {
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

abstract class DataPath {

	/** @var DataPath */
	public $parent;
	/** @var datamodel\Table */
	public $table;
	public $sub_model;
	public $unique = true;
	
	/**
	 * @param DataPath $parent
	 * @param datamodel\Table $table
	 * @param unknown $sub_model
	 */
	public function __construct($parent, &$table, $sub_model) {
		$this->parent = $parent;
		$this->table = &$table;
		$this->sub_model = $sub_model;
	}
	
	public abstract function get_string();
	
	public final function is_unique() {
		if (!$this->unique) return false;
		if ($this->parent == null) return true;
		return $this->parent->is_unique();
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

	public function get_string() {
		$s = $this->table->getName();
		if ($this->sub_model <> null)
			$s .= "[".$this->sub_model."]";
		return $s;
	}
	
}

/** join following a foreign key */
class DataPath_Join extends DataPath {
	
	public $foreign_key;

	public function __construct($parent, &$foreign_key, &$target_table, $target_sub_model) {
		$this->foreign_key = $foreign_key;
		parent::__construct($parent, $target_table, $target_sub_model);
		if ($parent->table == $foreign_key->table)
			$this->unique = true;
		else 
			$this->unique = !$foreign_key->multiple;
	}
	
	public function get_string() {
		$s = $this->parent->get_string();
		$s .= $this->parent->table == $this->foreign_key->table ? ">" : "<";
		if (!$this->unique) $s.= $this->parent->table == $this->foreign_key->table ? ">" : "<";
		if ($this->foreign_key->can_be_null) $s .= "?";
		$s .= $this->table->getName();
		if ($this->sub_model <> null)
			$s .= "[".$this->sub_model."]";
		$s .= "(".$this->foreign_key->name.")";
		return $s;
	}
	
	public function is_reverse() {
		return $this->parent->table <> $this->foreign_key->table;
	}
	
}

class DataPathSQLBuilder {
	
	private $alias_counter = 0;
	
	public function new_alias() {
		return "a".($this->alias_counter++);
	}
	
	/**
	 * @param DataPath $path
	 * @param SQLQuery $q
	 */
	public function build($path, &$q) {
		if ($path instanceof DataPath_Join) {
			$parent_alias = $this->build($path->parent, $q);
			$match = array();
			if ($path->is_reverse())
				$match[$path->parent->table->getPrimaryKey()->name] = $path->foreign_key->name;
			else
				$match[$path->foreign_key->name] = $path->table->getPrimaryKey()->name; 
			$alias = $q->get_join_alias($parent_alias, $path->table->getSQLNameFor($path->sub_model), $match);
			if ($alias == null) {
				$alias = $this->new_alias();
				$q->join($parent_alias, $path->table->getSQLNameFor($path->sub_model), $match, $alias);
			}
			return $alias;
		}
		return $q->get_table_alias($path->table->getSQLNameFor($path->sub_model));
	}
	
}

?>