<?php
namespace datamodel {
	require_once("Event.inc");
	/**
	 * This class contains the definition of a table in the database.<br/>
	 * 3 types of tables can be used:<ul>
	 *  <li>with a primary key which auto-increment</li>
	 *  <li>with a foreign key which is also a primary key (1 to 1 relation)</li>
	 *  <li>a table joining 2 other tables (1 to n relation), where only add and remove can be done</li>
	 * </ul>
	 * <br/>
	 * As a key feature of the data model is security, each table defines also its access rights: by default nothing can be done on the table, then access can be allowed:<ul>
	 *  <li> read (select):<ul>
	 *    <li> one specific right give read access: apply to all entries</li>
	 *    <li> only specific rows can be read: where clause will be automatically added to any select to apply the restrictions</li>
	 *    <li> some columns may have specific restrictions</li>
	 *  </ul></li>
	 *  <li> write (update):<ul>
	 *    <li> one specific right give modification access: apply to all entries</li>
	 *    <li> only specific rows can be modified: first a select will be done based on primary key to update, then filtered so only the rows allowed to be modified will remain in the update</li>
	 *  </ul></li>
	 *  <li> add (insert):<ul>
	 *    <li> one specific right give add access: apply to all entries</li>
	 *    <li> filter: check inserted values</li>
	 *  </ul></li>
	 *  <li> remove (delete):<ul>
	 *    <li> one specific right give remove access: apply to all entries</li>
	 *    <li> only specific rows with filters: same process as for write access</li>
	 *  </ul></li>
	 * </ul>
	 */
	class Table {

		/** @var \DataModel the model or sub model this table belongs to */
		private $model;
		/** @var string table name */
		private $name;
		/** @var Column[] list of columns */
		private $columns = array();
		/** @var array list of indexes to create for fast access to this table: each element of the array is an array with the first element being the index name in the database, and the following elements this list of columns to be indexed together */
		private $indexes = array();
		/** @var Column the primary key, or null of there is no*/
		private $primary_key = null;
		/** @var array if there is a primary key, this is null, else it defines the column(s) to be used as key */
		private $key = null;

		private $read_access_rights = array();
		private $read_access_from_foreign_table = array();
		private $read_access_filters = array();
		private $read_column_access_rights = array();
		private $write_access_rights = array();
		private $write_column_access_rights = array();
		private $write_access_filters = array();
		private $add_access_rights = array();
		private $add_access_filters = array();
		private $remove_access_rights = array();
		private $remove_access_filters = array();
		private $remove_access_from_foreign_table = array();
		
		private $row_description_provider = null;
		
		private $customization_right = null;
		private $customization_name = null;
		private $customization = array();
		
		/** @var boolean indicates if the table is a root table. If not, it means any row should be reachable from any root table, else it is a lost row which can never be used/removed */
		private $is_root = false;

		public $_key_submodels = array();
		
		// events
		/** @var Event */
		private $oninsert;
		/** @var Event */
		private $onremove;

		public function __construct(&$model, $name) {
			$this->model = &$model;
			$this->name = $name;
			$this->oninsert = new \Event();
			$this->onremove = new \Event();
		}

		public function _key_for_submodel(&$sm) {
			array_push($this->_key_submodels, $sm);
		}


		/**
		 * @return \DataModel
		 */
		public function &getModel() {
			return $this->model;
		}
		public function getName() {
			return $this->name;
		}
		public function getSQLName($sub_models) {
			if ($this->model instanceof \SubDataModel) {
				if (!isset($sub_models[$this->model->getParentTable()]))
					throw new \Exception("No sub model ".$this->model->getParentTable()." for table ".$this->name);
				return $this->name."_".$sub_models[$this->model->getParentTable()];
			}
			return $this->name;
		}
		public function getSQLNameFor($sub_model) {
			if ($this->model instanceof \SubDataModel)
				return $this->name."_".$sub_model;
			return $this->name;
		}

		/**
		 * @param string $name name of the column to get
		 * @throws \Exception in case the column is not accessible based on the rights of the user
		 * @return Column|NULL the column or NULL if the column does not exist
		 */
		public function getColumn($name, $sub_models = null) {
			$columns = $this->_getColumns($sub_models);
			foreach ($columns as $c)
				if ($c->name == $name) {				
					if (!$this->canAccessColumn($c->name)) throw new \Exception("Access denied");
					return $c;
			}
			return null;
		}
		public function getColumnFor($name, $sub_model) {
			$sub_models = array();
			if ($sub_model <> null && $this->getModel() instanceof \SubDataModel)
				$sub_models[$this->getModel()->getParentTable()] = $sub_model;
			return $this->getColumn($name, $sub_models);
		}
		public function getColumns($sub_models = null) {
			$columns = $this->_getColumns($sub_models);
			$cols = array();
			foreach ($columns as $c)
				if ($this->canAccessColumn($c->name))
				array_push($cols, $c);
			return $cols;
		}
		public function getColumnsFor($sub_model = null) {
			$sub_models = array();
			if ($sub_model <> null && $this->model instanceof SubDataModel)
				$sub_models[$this->model->getParentTable()] = $sub_model;
			return $this->getColumns($sub_models);
		}

		public function internalGetColumnsFor($sub_model = null) {
			if ($sub_model == null) $sub_models = null;
			else $sub_models = array($this->model->getParentTable()=>$sub_model);
			return $this->_getColumns($sub_models);
		}
		public function internalGetColumns($sub_models = null) {
			return $this->_getColumns($sub_models);
		}
		public function internalGetColumn($name, $sub_models = null) {
			$columns = $this->_getColumns($sub_models);
			foreach ($columns as $c)
				if ($c->name == $name)
				return $c;
			return null;
		}
		public function internalGetColumnFor($name, $sub_model = null) {
			if ($sub_model == null || !($this->model instanceof \SubDataModel)) $sub_models = null;
			else $sub_models = array($this->model->getParentTable()=>$sub_model);
			$columns = $this->_getColumns($sub_models);
			foreach ($columns as $c)
				if ($c->name == $name)
				return $c;
			return null;
		}
		
		private function _getColumns($sub_models) {
			if ($this->customization_right == null) return $this->columns;
			if ($this->model instanceof \SubDataModel) {
				$name = $this->getSQLName($sub_models);
				if (!isset($this->customization[$name])) {
					$this->customization[$name] = $this->load_customization($name);
				}
				return array_merge($this->columns, $this->customization[$name]);
			}
			if (!isset($this->customization[$this->name])) {
				$this->customization[$this->name] = $this->load_customization($this->name);
			}
			return array_merge($this->columns, $this->customization[$this->name]);
		}
		
		/** For internal purpose only, used by table customization functionalities */
		public function forceReloadCustomization($sub_model) {
			if ($this->model instanceof \SubDataModel)
				$name = $this->getSQLNameFor($sub_model);
			else
				$name = $this->name;
			$this->customization[$name] = $this->load_customization($name);
		}
		
		/** Indicates if the table is a root table. If not, it means any row should be reachable from any root table, else it is a lost row which can never be used/removed
		 * @param string $is_root true to mark the table as root table
		 * @return \datamodel\Table this 
		 */
		public function &setRoot($is_root = true) {
			$this->is_root = $is_root;
			return $this;
		}
		/** Indicates if the table is a root table. If not, it means any row should be reachable from any root table, else it is a lost row which can never be used/removed
		 * @return boolean true if the table is marked as root table
		 */
		public function isRoot() { return $this->is_root; }

		/** Add a primary key (integer ID)
		 *  @return \datamodel\Table
		 */
		public function &addPrimaryKey($name, $size=32) {
			$c = new PrimaryKey($this, $name, $size);
			array_push($this->columns, $c);
			$this->primary_key = $c;
			return $this;
		}

		/**
		 * Add a foreign key to the given table
		 * @param string $name name of the foreign key
		 * @param string $table table pointed by the foreign key
		 * @param boolean $remove_foreign_when_primary_removed indicates if the row in this table should be removed when the associated primary key is removed.
		 * @param boolean $remove_primary_when_foreign_removed indicates if the associated primary key should be removed when a row in this table is removed.
		 * @param boolean $multiple indicates if several rows in this table can exist with the same foreign key (n to 1 relation)
		 * @param boolean|null $can_be_null indicates if this foreign key can be null. If not specified (null) it will be considered that the foreign key can be null if $remove_foreign_when_primary_removed is set to false (example of overriding this info is when we have a foreign key pointing to the same table to create a tree, and the root level needs to be null).
		 * @return \datamodel\Table
		 */
		public function &addForeignKey($name, $table, $remove_foreign_when_primary_removed, $remove_primary_when_foreign_removed, $multiple, $can_be_null = null, $unique = false) {
			$c = new ForeignKey($this, $name, $table, $remove_foreign_when_primary_removed, $remove_primary_when_foreign_removed, $multiple, $can_be_null, $unique);
			array_push($this->columns, $c);
			return $this;
		}

		public function &setPrimary($name) {
			$c = null;
			foreach ($this->columns as $col) if ($col->name == $name) {
				$c = $col; break;
			}
			if ($c == null) throw new \Exception("Unknown column '".$name."' in table '".$this->name."': setPrimary");
			if (!$c->unique) throw new \Exception("Column '".$name."' in table '".$this->name."' cannot be a primary: it is not unique");
			$this->primary_key = $c;
			return $this;
		}

		public function getPrimaryKey() {
			return $this->primary_key;
		}

		/**
		 * Set a list of columns as the key of this table (values on those columns can identify uniquely a row)
		 * @param string[] $columns
		 * @return Table this
		 */
		public function &setKey($columns) {
			$this->key = $columns;
			return $this;
		}
		public function getKey() {
			return $this->key;
		}

		/** Add an integer column. Size is in bits
		 * @return \datamodel\Table
		 */
		public function &addInteger($name, $size=32, $min = null, $max = null, $can_be_null = true, $unique = false) {
			$c = new ColumnInteger($this, $name, $size, $min, $max, $can_be_null, $unique);
			array_push($this->columns, $c);
			return $this;
		}
		
		public function &addTimestamp($name, $size=32, $min = null, $max = null, $can_be_null = true, $unique = false){
			$c = new ColumnTimestamp($this, $name, $size, $min, $max, $can_be_null, $unique);
			array_push($this->columns, $c);
			return $this;
		}

		public function &addDecimal($name, $integer_digits=10, $decimal_digits = 2, $min = null, $max = null, $can_be_null = true, $unique = false) {
			$c = new ColumnDecimal($this, $name, $integer_digits, $decimal_digits, $min, $max, $can_be_null, $unique);
			array_push($this->columns, $c);
			return $this;
		}

		/** Add a boolean column */
		public function &addBoolean($name, $can_be_null = true) {
			$c = new ColumnBoolean($this, $name, $can_be_null);
			array_push($this->columns, $c);
			return $this;
		}

		/** Add a string column */
		public function &addString($name, $max_length=255, $min_length = 0, $can_be_null = true, $unique = false, $validation = null) {
			$c = new ColumnString($this, $name, $max_length, $min_length, $can_be_null, $unique, $validation);
			array_push($this->columns, $c);
			return $this;
		}

		/** Add an enum column, with the given possible values */
		public function &addEnum($name, $values, $can_be_null = true, $unique = false) {
		$c = new ColumnEnum($this, $name, $values, $can_be_null, $unique);
			array_push($this->columns, $c);
			return $this;
		}

		/** Add a date column */
		public function &addDate($name, $can_be_null = true, $unique = false, $minimum_date = null, $maximum_date = null) {
			$c = new ColumnDate($this, $name, $can_be_null, $unique, $minimum_date, $maximum_date);
			array_push($this->columns, $c);
			return $this;
		}

		/** Add a time column */
		public function &addTime($name, $can_be_null = true, $unique = false) {
			$c = new ColumnTime($this, $name, $can_be_null, $unique);
			array_push($this->columns, $c);
			return $this;
		}
		
		/**
		 * @param function $fct takes 3 parameters: fields_values, id, sub_model_instance
		 * @return \datamodel\Table
		 */
		public function onInsert($fct) {
			$this->oninsert->listenFunction($fct);
			return $this;
		}
		public function fireInsert($fields_values, $id, $sub_model_instance) {
			$this->oninsert->fire(array($fields_values,$id,$sub_model_instance));
		}
		/**
		 * @param function $fct takes 2 parameters: row, sub_model_instance
		 * @return \datamodel\Table
		 */
		public function onRemove($fct) {
			$this->onremove->listenFunction($fct);
			return $this;
		}
		public function fireRemove($row, $sub_model_instance) {
			$this->onremove->fire(array($row,$sub_model_instance));
		}
		public function hasRemoveListeners() {
			return $this->onremove->hasListeners();
		}
		
		/** Indicate one or several columns should be indexed for faster access.
		 * As parameters, this method expects the list of columns to be indexed together.
		 * @return \datamodel\Table
		 */
		public function &addIndex() {
			$nb = func_num_args();
			if ($nb == 1)
				array_push($this->indexes, array("index".count($this->indexes),func_get_arg(0)));
			else {
				$a = array("index".count($this->indexes));
				for ($i = 0; $i < $nb; $i++)
					array_push($a, func_get_arg($i));
				array_push($this->indexes, $a);
			}
			return $this;
		}


		public function &addReadAccess($right_name, $right_value) {
			array_push($this->read_access_rights, array($right_name, $right_value));
			return $this;
		}
		
		/**
		 * Allow the access to this table when coming from a foreign table (in a join request)
		 * If the user was allowed to access to the previous table, no need to check again for this table 
		 * @param string $foreign_table_name
		 * @return \datamodel\Table
		 */
		public function &addReadAccessFromForeignTable($foreign_table_name){
			array_push($this->read_access_from_foreign_table, $foreign_table_name);
			return $this;
		}
		/**
		 * Add a filter which will add where conditions in every select
		 * @param function $filter function taking 2 parameters: the SQLQuery to modify, and the table alias
		 * @return Table this
		 */
		public function &addReadFilter($filter) {
			array_push($this->read_access_filters, $filter);
			return $this;
		}
		/** Allow everybody to read in this table
		 * @return \datamodel\Table this
		 */
		public function &allowReadAccess() {
			$this->read_access_rights = null;
			return $this;
		}
		/** Allow to read the given column if the user has the given right
		 * @param string $column_name column name
		 * @param string $right_name necessary right
		 * @param mixed $right_value necessary right value
		 * @return Table this
		 */
		public function &addColumnReadAccess($column_name, $right_name, $right_value) {
			if (!isset($this->read_column_access_rights[$column_name]))
				$this->read_column_access_rights[$column_name] = array();
			array_push($this->read_column_access_rights[$column_name], array($right_name, $right_value));
			return $this;
		}
		/** Return true if the user MAY access to this table, meaning operations are allowed, but may be filtered later on
		 * @return boolean true if using this table is allowed
		 */
		public function canAccess() {
			if ($this->read_access_rights === null) return true;
			if (count($this->read_access_filters) > 0) return true;
			if (count($this->read_access_from_foreign_table) > 0) return true;
			foreach ($this->read_access_rights as $r)
				if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return true;
			foreach ($this->read_column_access_rights as $colname=>$list)
				foreach ($list as $r)
					if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return true;			
			return false;
		}
		
		public function getReadAccessFromForeignTable(){
			return $this->read_access_from_foreign_table;
		}
		public function hasReadFilter(){
			return count($this->read_access_filters) > 0;
		}
		public function hasReadRights(){
			if ($this->read_access_rights === null) return true;
			foreach ($this->read_access_rights as $r)
				if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return true;
			foreach ($this->read_column_access_rights as $colname=>$list)
				foreach ($list as $r)
					if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return true;
			return false;
		}
		public function canAccessColumn($column_name) {
			if ($this->read_access_rights === null) return true;
			if (count($this->read_access_from_foreign_table) > 0) return true;
			foreach ($this->read_access_rights as $r)
				if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return true;
			if (count($this->read_column_access_rights) > 0) {
				if (isset($this->read_column_access_rights[$column_name])) {
					foreach ($this->read_column_access_rights[$column_name] as $r)
						if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return true;
					return false;
				}
			}
			return count($this->read_access_filters) > 0;
		}
		public function filterReadAccess(&$q, $table_alias) {
			if ($this->read_access_rights === null) return;
			foreach ($this->read_access_rights as $r)
				if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return; // full access
			$where = $q->saveWhere();
			foreach ($this->read_access_filters as $filter)
				$filter($q, $table_alias);
			$filters_where = $q->saveWhere();
			$q->where($where);
			if ($filters_where <> "") {
				$key = $this->primary_key;
				if ($key == null)
					foreach ($this->columns as $col)
					if (!$col->can_be_null) {
					$key = $col; break;
				}
				if ($key == null) {
					// we cannot do it
					$q->where($filters_where);
				} else {
					// accept rows where there is no entry for this table (join left resulting in NULL values)
					$q->where("(`".$table_alias."`.`".$key->name."` IS NULL OR (".$filters_where."))");
				}
			}
		}

		public function &addWriteAccess($right_name, $right_value) {
			array_push($this->write_access_rights, array($right_name, $right_value));
			return $this;
		}
		public function &addColumnWriteAccess($column_name, $right_name, $right_value) {
			if (!isset($this->write_column_access_rights[$column_name]))
				$this->write_column_access_rights[$column_name] = array();
			array_push($this->write_column_access_rights[$column_name], array($right_name, $right_value));
			return $this;
		}
		/** Add a filter for the UPDATE operations
		 * @param function $prepare_select prepare the SELECT to determine which rows can be updated. Takes 2 parameters: the SQLQuery, and the table alias.
		 * @param function $filter filter to rows which can be updated. Takes the list of rows as parameter, and returns the list of rows which can be updated.
		 * @return Table this
		 */
		public function &addWriteFilter($prepare_select, $filter) {
			array_push($this->write_access_filters, array($prepare_select, $filter));
			return $this;
		}
		public function canModifyEverything() {
			foreach ($this->write_access_rights as $r)
				if (\PNApplication::$instance->user_management->has_right($r[0], $r[1])) return true;
			return false;
		}
		public function canModifyColumn($column_name) {
			if ($this->canModifyEverything()) return true;
			if (isset($this->write_column_access_rights[$column_name])) {
				foreach ($this->write_column_access_rights[$column_name] as $r)
					if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return true;
				return false;
			}
			return true;
		}
		public function canModifyAColumn() {
			foreach ($this->write_column_access_rights as $column_name=>$rights) {
				foreach ($rights as $r)
					if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return true;
			}
			return false;
		}
		public function mayModify() {
			if ($this->canModifyEverything()) return true;
			if ($this->canModifyAColumn()) return true;
			if (count($this->write_access_filters) > 0) return true;
			return false;
		}
		public function prepareSelectToDetermineWriteAccess(&$q, $table_alias) {
			foreach ($this->write_access_rights as $r)
				if (\PNApplication::$instance->user_management->has_right($r[0], $r[1])) return false; // full access
			foreach ($this->write_access_filters as $f) {
				$prepare = $f[0];
				$prepare($q, $table_alias);
			}
			return true;
		}
		public function filterWriteAccess($rows) {
			foreach ($this->write_access_rights as $r)
				if (\PNApplication::$instance->user_management->has_right($r[0], $r[1])) return $rows; // full access
			foreach ($this->write_access_filters as $f) {
				$filter = $f[1];
				$rows = $filter($rows);
			}
			return $rows;
		}

		public function &addInsertAccess($right_name, $right_value) {
			array_push($this->add_access_rights, array($right_name,$right_value));
			return $this;
		}
		/** Add a filter on INSERT operations
		 * @param function $filter takes the list of (column=&gt;value) which will be inserted, and returns true if it is allowed
		 * @return \datamodel\Table this
		 */
		public function &addInsertFilter($filter) {
			array_push($this->add_access_filters, $filter);
			return $this;
		}
		public function mayInsert() {
			if ($this->canInsertAny()) return true;
			return count($this->add_access_filters) > 0;
		}
		public function canInsertAny() {
			foreach ($this->add_access_rights as $r)
				if (\PNApplication::$instance->user_management->has_right($r[0],$r[1])) return true;
			return false;
		}
		public function acceptInsert($values) {
			if ($this->canInsertAny()) return true;
			foreach ($this->add_access_filters as $filter)
				if ($filter($values)) return true;
			return false;
		}

		public function &addRemoveAccess($right_name, $right_value) {
			array_push($this->remove_access_rights, array($right_name,$right_value));
			return $this;
		}
		public function &addRemoveAccessFromForeignTable($foreign_table_name){
			array_push($this->remove_access_from_foreign_table, $foreign_table_name);
			return $this;
		}
		public function canRemoveFromForeignTable($foreign_table_name) {
			return in_array($foreign_table_name, $this->remove_access_from_foreign_table);
		}
		/** Add a filter for DELETE operations
		 * @param function $prepare_select prepare the SELECT to get rows that will be removed. Takes 3 parameters: the SQLQuery, the table alias, a list of locks created by this function.
		 * @param function $check takes the list of rows as parameters, and return the list of allowed rows
		 * @return \datamodel\Table this
		 */
		public function &addRemoveFilter($prepare_select, $check) {
			array_push($this->remove_access_filters, array($prepare_select, $check));
			return $this;
		}
		public function canRemoveAny() {
			foreach ($this->remove_access_rights as $r)
				if (\PNApplication::$instance->user_management->has_right($r[0], $r[1])) return true;
			return false;
		}
		public function mayRemove() {
			if ($this->canRemoveAny()) return true;
			return count($this->remove_access_filters) > 0;
		}
		public function prepareSelectToDetermineRemoveAccess(&$q, $table_alias, &$locks) {
			foreach ($this->remove_access_rights as $r)
				if (\PNApplication::$instance->user_management->has_right($r[0], $r[1])) return false; // full access
			foreach ($this->remove_access_filters as $f) {
				$prepare = $f[0];
				$prepare($q, $table_alias, $locks);
			}
			return true;
		}
		public function filterRemoveAccess($rows) {
			foreach ($this->remove_access_rights as $r)
				if (\PNApplication::$instance->user_management->has_right($r[0], $r[1])) return $rows; // full access
			foreach ($this->remove_access_filters as $f) {
				$filter = $f[1];
				$rows = $filter($rows);
			}
			return $rows;
		}
			
		
		public function setRowDescriptionProvider($provider_callback) {
			$this->row_description_provider = $provider_callback;
			return $this;
		}
		
		public function getRowDescriptionByKey($key, $sub_model = null) {
			$row = \SQLQuery::getRow($this, $key, $sub_model);
			return $this->getRowDescription($row);
		}
		public function getRowDescription($row) {
			if ($this->row_description_provider <> null) {
				return call_user_func($this->row_description_provider, $row);
			}
			$s = "Row on table ".$this->name.": ";
			$first = true;
			foreach ($this->columns as $col) {
				if ($first) $first = false; else $s .= ", ";
				$s .= $col->name."=";
				if (isset($row[$col->name])) $s .= htmlentities($row[$col->name]);
				else $s .= "NULL";
			}
			return $s;
		}
		

		public function &customizable($name, $right_name) {
			$this->customization_right = $right_name;
			$this->customization_name = $name;
			return $this;
		}
		public function isCustomized() {
			return $this->customization_right <> null;
		}
		public function getCustomizationName() {
			return $this->customization_name;
		}
		public function getCustomizationRight() {
			return $this->customization_right;
		}
		private function load_customization($table_name) {
			$data_path = realpath(dirname($_SERVER["SCRIPT_FILENAME"])."/data/".\PNApplication::$instance->current_domain);
			if (!file_exists($data_path."/custom_tables/$table_name"))
				return array();
			try {
				return include($data_path."/custom_tables/$table_name");
			} catch (\Exception $e) {
				\PNApplication::error("Error in custom table definition of $table_name", $e);
			}
		}

	}

	abstract class Column {

		public $table;
		public $name;
		public $can_be_null;
		public $unique;

		public function __construct(&$table, $name, $can_be_null, $unique) {
			$this->table = &$table;
			$this->name = $name;
			$this->can_be_null = $can_be_null;
			$this->unique = $unique;
		}

		public function get_sql() {
			$sql = "`".$this->name."` ";
			$sql .= $this->get_sql_type();
			if (!$this->can_be_null) $sql .= " NOT NULL";
			return $sql;
		}
		public abstract function get_sql_type();

		public function validate($value) {
			return $value;
		}
		
		public function getDisplayValue($value) { return $value; }

	}

	class ColumnInteger extends Column {

		public $size;
		public $min, $max;

		public function __construct(&$table, $name, $size, $min = null, $max = null, $can_be_null = true, $unique = false) {
			parent::__construct($table, $name, $can_be_null, $unique);
			$this->size = $size;
			$this->min = $min;
			$this->max = $max;
		}

		public function get_sql_type() {
			if ($this->size == 1)
				return "BIT(1)";
			if ($this->size <= 8)
				return "TINYINT";
			if ($this->size <= 16)
				return "SMALLINT";
			if ($this->size <= 24)
				return "MEDIUMINT";
			if ($this->size <= 32)
				return "INT";
			if ($this->size <= 64)
				return "BIGINT";
			throw new Exception("Size of integer not supported: ".$this->size." bits, maximum is 64 bits");
		}

		public function validate($value) {
			if ($this->can_be_null && ($value === null || $value === "")) return null;
			if (!is_integer($value)) $value = intval($value);
			if ($this->min !== null && $value < $this->min) throw new \Exception("Minimum is ".$this->min);
			if ($this->max !== null && $value > $this->max) throw new \Exception("Maximum is ".$this->max);
			return $value;
		}
	}

	class ColumnDecimal extends Column {

		public $integer_digits, $decimal_digits;
		public $min, $max;

		public function __construct(&$table, $name, $integer_digits, $decimal_digits, $min = null, $max = null, $can_be_null = true, $unique = false) {
			parent::__construct($table, $name, $can_be_null, $unique);
			$this->integer_digits = $integer_digits;
			$this->decimal_digits = $decimal_digits;
			$this->min = $min;
			$this->max = $max;
		}

		public function get_sql_type() {
			return "DECIMAL(".($this->integer_digits+$this->decimal_digits).",".$this->decimal_digits.")";
		}

		public function validate($value) {
			if ($this->can_be_null && ($value === null || $value === "")) return null;
			if (!is_double($value)) $value = doubleval($value);
			if ($this->min !== null && $value < $this->min) throw new \Exception("Minimum is ".$this->min);
			if ($this->max !== null && $value > $this->max) throw new \Exception("Maximum is ".$this->max);
			return $value;
		}
	}

	class ColumnBoolean extends ColumnInteger {

		public function __construct(&$table, $name, $can_be_null = false) {
			parent::__construct($table, $name, 1, 0, 1, $can_be_null, false);
		}

		public function validate($value) {
			if ($this->can_be_null && $value === null) return null;
			return $value == true;
		}
	}

	class ColumnString extends Column {

		public $max_length;
		public $min_length;
		public $validation;

		public function __construct(&$table, $name, $max_length, $min_length = 0, $can_be_null = true, $unique = false, $validation = null) {
			parent::__construct($table, $name, $can_be_null, $unique);
			$this->max_length = $max_length;
			$this->min_length = $min_length;
			$this->validation = $validation;
		}

		public function get_sql_type() { /* TODO choose the type depending on max_length */ return "VARCHAR(".$this->max_length.")";
		}

		public function validate($value) {
			if ($this->validation <> null)
				$value = call_user_func($this->validation, $value);
			if ($value === null) {
				if (!$this->can_be_null) throw new \Exception("Cannot be empty");
				return null;
			}
			if (strlen($value) < $this->min_length) {
				if (strlen($value) == 0) {
					if ($this->can_be_null)
						return null; // convert empty string into null
					throw new \Exception("Cannot be empty");
				}
				throw new \Exception("Must have a length of at least ".$this->min_length);
			}
			if (strlen($value) > $this->max_length)
				throw new \Exception("Cannot be greater than ".$this->max_length);
			return $value;
		}
	}

	class ColumnEnum extends Column {

		public $values;

		public function __construct(&$table, $name, $values, $can_be_null = true, $unique = false) {
			parent::__construct($table, $name, $can_be_null, $unique);
			$this->values = $values;
		}

		public function get_sql_type() {
			$sql = "ENUM(";
			$first = true;
			foreach ($this->values as $v) {
				if ($first) $first = false; else $sql.=",";
				$sql .= "'".\SQLQuery::escape($v)."'";
			}
			$sql .= ")";
			return $sql;
		}

		public function validate($value) {
			if ($value === null) {
				if ($this->can_be_null) return null;
				throw new \Exception("Cannot be empty");
			}
			foreach ($this->values as $v)
				if ($value == $v) return $v;
			if ($value == "" && $this->can_be_null) return null;
			$error = "Invalid value '".$value."' (allowed values are: ";
			$first = true;
			foreach ($this->values as $v) {
				if ($first) $first = false; else $error .= ",";
				$error .= "'".$v."'";
			}
			$error .= ")";
			throw new \Exception($error);
		}
	}

	/**
	 * Date column.
	 * minimum and maximum dates are constraints and can be: null, a date, or the name of a date column in the same table
	 */
	class ColumnDate extends Column {

		public $minimum_date;
		public $maximum_date;

		public function __construct(&$table, $name, $can_be_null = true, $unique = false, $minimum_date = null, $maximum_date = null) {
			parent::__construct($table, $name, $can_be_null, $unique);
			$this->minimum_date = $minimum_date;
			$this->maximum_date = $maximum_date;
		}

		public function get_sql_type() {
			return "DATE";
		}
		
		public static function toTimestamp($sql_date) {
			$s = explode("-", $sql_date);
			$y = intval($s[0]);
			$m = intval($s[1]);
			$d = intval($s[2]);
			return mktime(0,0,0,$m,$d,$y);
		}

		public function validate($value) {
			if ($value === "") $value = null;
			if ($value === null) {
				if (!$this->can_be_null) throw new \Exception("You must enter a valid date");
				return null;
			}
			$s = explode("-", $value);
			if (count($s) <> 3) throw new \Exception("Invalid date format (must be YYYY-MM-DD)");
			$year = intval($s[0]);
			$month = intval($s[1]);
			$day = intval($s[2]);
			if ($year == 0 || $month == 0 || $day == 0) throw new \Exception("Invalid date format (must be YYYY-MM-DD)");
			if ($this->minimum_date <> null) {
				if (ctype_digit(substr($this->minimum_date,0,1))) {
					$s = explode("-", $this->minimum_date);
					$y = intval($s[0]);
					$m = intval($s[1]);
					$d = intval($s[2]);
					if ($year < $y) throw new \Exception("Invalid date: minimum is ".$this->minimum_date);
					if ($year == $y) {
						if ($month < $m) throw new \Exception("Invalid date: minimum is ".$this->minimum_date);
						if ($month == $m) {
							if ($day < $d) throw new \Exception("Invalid date: minimum is ".$this->minimum_date);
						}
					}
				} else {
					// TODO compare with other column
				}
			}
			if ($this->maximum_date <> null) {
				if (ctype_digit(substr($this->maximum_date,0,1))) {
					$s = explode("-", $this->maximum_date);
					$y = intval($s[0]);
					$m = intval($s[1]);
					$d = intval($s[2]);
					if ($year > $y) throw new \Exception("Invalid date: maximum is ".$this->maximum_date);
					if ($year == $y) {
						if ($month > $m) throw new \Exception("Invalid date: maximum is ".$this->maximum_date);
						if ($month == $m) {
							if ($day > $d) throw new \Exception("Invalid date: maximum is ".$this->maximum_date);
						}
					}
				} else {
					// TODO compare with other column
				}
			}
			return $value;
		}
	}

	class ColumnTime extends Column {

		public function __construct(&$table, $name, $can_be_null = true, $unique = false) {
			parent::__construct($table, $name, $can_be_null, $unique);
		}


		public function get_sql_type() {
			return "TIME";
		}

		public function validate($value) {
			if ($value === "") $value = null;
			if ($value === null) {
				if (!$this->can_be_null) throw new \Exception("You must enter a valid time");
				return null;
			}
			// TODO check if valid format
			return $value;
		}
	}

	class PrimaryKey extends ColumnInteger {

		public function __construct(&$table, $name, $size) {
			parent::__construct($table, $name, $size, false, true);
		}

		public function get_sql_type() {
			return "INT(".$this->size.") NOT NULL AUTO_INCREMENT";
		}
	}
	
	class ColumnTimestamp extends ColumnInteger {
		public function __construct(&$table, $name, $size, $min = null, $max = null, $can_be_null = true, $unique = false){
			parent::__construct($table, $name, $size, $min, $max, $can_be_null, $unique);
		}
		
		public function get_sql_type(){
			return "INT(".$this->size.")";
		}
		
		public function getDisplayValue($value) {
			if ($value == null) return null;
			return date("Y-m-d h:i", $value); 
		}
	}

	class ForeignKey extends ColumnInteger {

		public $foreign_table;
		public $remove_foreign_when_primary_removed;
		public $remove_primary_when_foreign_removed;
		public $multiple;

		public function __construct(&$table, $name, $foreign_table, $remove_foreign_when_primary_removed, $remove_primary_when_foreign_removed, $multiple, $can_be_null, $unique) {
			$this->foreign_table = $foreign_table;
			$this->remove_foreign_when_primary_removed = $remove_foreign_when_primary_removed;
			$this->remove_primary_when_foreign_removed = $remove_primary_when_foreign_removed;
			$this->multiple = $multiple;
			try { $ft = $table->getModel()->internalGetTable($foreign_table);
			}
			catch (\Exception $e) {
				$ft = null;
			}
			if ($ft == null && $table->getModel() instanceof \SubDataModel)
				try { $ft = $table->getModel()->getParentModel()->internalGetTable($foreign_table);
			}
			catch (\Exception $e) {
				$ft = null;
			}
			if ($ft == null) {
				\PNApplication::error("Invalid foreign key '".$name."' in table '".$table->getName()."': foreign table '".$foreign_table."' does not exist");
				$size = 32;
			} else
				$size = $ft->getPrimaryKey()->size;
			parent::__construct($table, $name, $size, null, null, $can_be_null === null ? !$remove_foreign_when_primary_removed : $can_be_null, $unique);
		}

		public function get_sql_type() {
			try { $ft = $this->table->getModel()->internalGetTable($this->foreign_table);
			}
			catch (\Exception $e) {
				$ft = null;
			}
			if ($ft == null && $this->table->getModel() instanceof \SubDataModel)
				try { $ft = $this->table->getModel()->getParentModel()->internalGetTable($this->foreign_table);
			}
			catch (\Exception $e) {
				$ft = null;
			}
			return "INT(".$ft->getPrimaryKey()->size.")";
		}

		public function validate($value) {
			if ($value === null) {
				if (!$this->can_be_null) throw new \Exception("Missing");
				return null;
			}
			return parent::validate($value);
		}
	}

} // namespace datamodel
?>