<?php 
class DataPathBuilder {
	
	public static function &search_from($table_name, $sub_models = null) {
		require_once("Model.inc");
		/* @var $model DataModel */
		$model = DataModel::get();
		$table = $model->getTable($table_name);
		$done = array();
		$context = new DataPathBuilderContext();
		$final_paths = array();
		if ($table->getModel() instanceof SubDataModel) {
			if ($sub_models <> null && isset($sub_models[$table->getModel()->getParentTable()])) {
				// we are in a defined sub model
				$path = new DataPath_Table($table, $sub_models[$table->getModel()->getParentTable()], $context->new_table_alias());
				DataPathBuilder::search_from_path($path, $context, $done, $final_paths, $sub_models);
			} else {
				// we need to go to all existing sub models
				foreach ($table->getModel()->getExistingInstances() as $sm) {
					$path = new DataPath_Table($table, $sm, $context->new_table_alias());
					DataPathBuilder::search_from_path($path, $context, $done, $final_paths, $sub_models);
				}
			}
		} else {
			$path = new DataPath_Table($table, null, $context->new_table_alias());
			DataPathBuilder::search_from_path($path, $context, $done, $final_paths, $sub_models);
		}
		return $final_paths;
	}
	
	private static function search_from_path(&$path, &$context, &$done, &$final_paths, $sub_models) {
		if (in_array($path->table->getSQLNameFor($path->sub_model),$done)) return;
		array_push($done, $path->table->getSQLNameFor($path->sub_model));		
		DataPathBuilder::add_fields($path, $context, $final_paths);
		DataPathBuilder::add_foreign_keys($path, $context, $done, $final_paths, $sub_models);
		DataPathBuilder::add_external_foreign_keys($path, $context, $done, $final_paths, $sub_models);
		DataPathBuilder::add_links($path, $context, $done, $final_paths, $sub_models);
		DataPathBuilder::add_external_links($path, $context, $done, $final_paths, $sub_models);
	}
	
	private static function add_fields(&$path, &$context, &$final_paths) {
		foreach ($path->table->getDisplayableData() as $field=>$name) {
			if (!$path->table->canAccessField($field)) continue;
			$p = new DataPath_Field($path, $field, $context->new_field_alias());
			array_push($final_paths, $p);
		}
	}
	
	private static function add_foreign_keys(&$path, &$context, &$done, &$final_paths, $sub_models) {
		foreach ($path->table->internalGetColumns() as $col) {
			if (!($col instanceof \datamodel\ForeignKey)) continue;
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
				$p = new DataPath_Join($path, $path->table, $path->sub_model, $path->table_alias, $t, $sm, $context->new_table_alias(), array($col->name=>$t->getPrimaryKey()->name));
				DataPathBuilder::search_from_path($p, $context, $done, $final_paths, $sub_models);
			} catch (Exception $e) {
				// not allowed
				continue;
			}
		}
	}
	
	private static function add_external_foreign_keys(&$path, &$context, &$done, &$final_paths, $sub_models) {
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
				$p = new DataPath_ReverseJoin($path, $t, $sm, $context->new_table_alias(), $path->table, $path->sub_model, $path->table_alias, array($col->name=>$path->table->getPrimaryKey()->name));
				DataPathBuilder::search_from_path($p, $context, $done, $final_paths, $sub_models);
			}
		}
	}
	
	private static function add_links(&$path, &$context, &$done, &$final_paths, $sub_models) {
		$model = DataModel::get();
		foreach ($path->table->getLinks() as $link) {
			// TODO multiple
			if (!$link->unique) continue;
			try {
				$t = $model->getTable($link->table);
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
				$p = new DataPath_Join($path, $path->table, $path->sub_model, $path->table_alias, $t, $sm, $context->new_table_alias(), $link->fields_matching);
				DataPathBuilder::search_from_path($p, $context, $done, $final_paths, $sub_models);
			} catch (Exception $e) {
				// not allowed
				continue;
			}
		}
	}
	
	private static function add_external_links(&$path, &$context, &$done, &$final_paths, $sub_models) {
		$model = DataModel::get();
		$tables = $model->getTables();
		foreach ($tables as &$ta) {
			foreach ($ta->getLinks() as $link) {
				if ($link->table <> $path->table->getName()) continue;
				// TODO multiple
				if (!$link->unique) continue;
				try {
					$t = $link->table_from;
					if (!$t->canAccess()) continue;
					$sm = null;
					if ($t->getModel() instanceof SubDataModel) {
						if ($path->table->getModel() instanceof SubDataModel) {
							if ($path->table->getModel()->getParentTable() <> $t->getModel()->getParentTable())
								continue; // different sub model
							$sm = $path->sub_model;
						}
					}
					$p = new DataPath_ReverseJoin($path, $t, $sm, $context->new_table_alias(), $path->table, $path->sub_model, $path->table_alias, $link->fields_matching);
					DataPathBuilder::search_from_path($p, $context, $done, $final_paths, $sub_models);
				} catch (Exception $e) {
					// not allowed
					continue;
				}
			}
		}
	}
	
	
}

class DataPathBuilderContext {
	
	public $table_alias_counter = 1;
	public $field_alias_counter = 1;
	
	public function new_table_alias() {
		return "t".($this->table_alias_counter++);
	}
	public function new_field_alias() {
		return "f".($this->field_alias_counter++);
	}
	
}

abstract class DataPath {

	/** @var $parent DataPath */
	public $parent;
	/** @var $table \datamodel\Table */
	public $table;
	public $sub_model;
	public $table_alias;
	
	public function __construct($parent, &$table, $sub_model, $table_alias) {
		$this->parent = $parent;
		$this->table = &$table;
		$this->sub_model = $sub_model;
		$this->table_alias = $table_alias;
	}
	
	public abstract function get_string();
	
}

/** a simple select on a table, as a starting point */
class DataPath_Table extends DataPath {

	/**
	 * @param \datamodel\Table $table
	 * @param array|null $sub_models
	 * @param string $table_alias
	 */
	public function __construct(&$table, $sub_model, $table_alias) {
		parent::__construct(null, $table, $sub_model, $table_alias);
	}

	public function get_string() {
		$s = $this->table->getName();
		if ($this->sub_model <> null)
			$s .= "[".$this->sub_model."]";
		return $s;
	}
	
	/**
	 * @param SQLQuery $q
	 */
	public function build_sql(&$q) {
		$q->select(array($this->table->getSQLNameFor($this->sub_model) => $this->table_alias));		
	}
	
}

/** column of a table, as ending point */
class DataPath_Field extends DataPath {
	
	public $field_name;
	public $field_alias;
	
	/**
	 * @param DataPath $parent_path
	 * @param string $field_name
	 * @param string $field_alias
	 */
	public function __construct(&$parent_path, $field_name, $field_alias) {
		parent::__construct($parent_path, $parent_path->table, $parent_path->sub_model, $parent_path->table_alias);
		$this->field_name = $field_name;
		$this->field_alias = $field_alias;
	}
	
	public function get_string() {
		return $this->parent->get_string().".".$this->field_name;
	}
	
	/**
	 * @param SQLQuery $q
	 */
	public function append_sql(&$q) {
		$this->parent->build_sql($q);
		$q->field($this->table_alias, $this->field_name, $this->field_alias);
	}
}

/** simple join, following a foreign key on the current table */
class DataPath_Join extends DataPath {
	
	public $from_table;
	public $from_sub_model;
	public $from_table_alias;
	public $matching_fields;
	
	public function __construct($parent, &$from_table, &$from_sub_model, $from_table_alias, &$to_table, &$to_sub_model, $to_table_alias, $matching_fields) {
		parent::__construct($parent, $to_table, $to_sub_model, $to_table_alias);
		$this->from_table = &$from_table;
		$this->from_sub_model = $from_sub_model;
		$this->from_table_alias = $from_table_alias;
		$this->matching_fields = $matching_fields;
	}
	
	public function get_string() {
		$s = $this->parent->get_string();
		$s .= ">".$this->table->getName();
		if ($this->sub_model <> null)
			$s .= "[".$this->sub_model."]";
		$s .= "(";
		$first = true;
		foreach ($this->matching_fields as $src=>$dst) {
			if ($first) $first = false; else $s .= ",";
			$s .= $src."=";
			if (is_array($dst))
				$s .= json_encode($dst);
			else
				$s .= $dst;
		}
		$s .= ")";
		return $s;
	}
	
	/**
	 * @param SQLQuery $q
	 */
	public function build_sql(&$q) {
		$this->parent->build_sql($q);
		$alias = $q->get_join_alias($this->parent->table_alias, $this->table->getSQLName($this->sub_model), $this->matching_fields);
		if ($alias <> $this->table_alias)
			$q->join($this->parent->table_alias, $this->table->getSQLName($this->sub_model), $this->matching_fields, $this->table_alias);
	}
	
}

/** join to a table containing a foreign key to the current table */
class DataPath_ReverseJoin extends DataPath {
	
	public $to_table;
	public $to_sub_model;
	public $to_table_alias;
	public $matching_fields;
	
	public function __construct($parent, &$from_table, &$from_sub_model, $from_table_alias, &$to_table, &$to_sub_model, $to_table_alias, $matching_fields) {
		parent::__construct($parent, $from_table, $from_sub_model, $from_table_alias);
		$this->to_table = &$to_table;
		$this->to_sub_model = $to_sub_model;
		$this->to_table_alias = $to_table_alias;
		$this->matching_fields = $matching_fields;
	}
	
	public function get_string() {
		$s = $this->parent->get_string();
		$s .= ">".$this->table->getName();
		if ($this->sub_model <> null)
			$s .= "[".$this->sub_model."]";
		$s .= "(";
		$first = true;
		foreach ($this->matching_fields as $src=>$dst) {
			if ($first) $first = false; else $s .= ",";
			if (is_array($dst))
				$s .= json_encode($dst);
			else
				$s .= $dst;
		}
		$s .= ")";
		return $s;
	}
	
	/**
	 * @param SQLQuery $q
	 */
	public function build_sql(&$q) {
		$alias = $q->get_join_alias($this->to_table_alias, $this->table->getSQLName($this->sub_model), $this->matching_fields);
		if ($alias <> $this->table_alias)
			$q->join($this->to_table_alias, $this->table->getSQLName($this->sub_model), $this->matching_fields, $this->table_alias);
	}
	
}

?>