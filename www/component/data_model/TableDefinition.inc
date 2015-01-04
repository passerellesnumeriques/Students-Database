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

		/** @var array null for full access, empty array for no access, or list of array with 2 elements: right name, right value */
		private $read_access_rights = array();
		/** @var string[] list of table from which we can automatically access, when using a join */
		private $read_access_from_foreign_table = array();
		/** @var array list of functions which may modify a SELECT request to limit access to some rows */
		private $read_access_filters = array();
		/** @var array associative array to give read access to specific columns: the name of the column as key, and as a value an array with 2 elements: the right name, the right value */
		private $read_column_access_rights = array();
		/** @var array list of array with 2 elements: right name, right value */
		private $write_access_rights = array();
		/** @var array associative array to give write access to specific columns: the name of the column as key, and as a value an array with 2 elements: the right name, the right value */
		private $write_column_access_rights = array();
		/** @var array list of array with 2 elements: a function to prepare a SELECT request, and a function that filter only the modifiable rows */
		private $write_access_filters = array();
		/** @var array list of array with 2 elements: right name, right value */
		private $add_access_rights = array();
		/** @var array list of functions which can determine if we can insert the given values or not */
		private $add_access_filters = array();
		/** @var array list of array with 2 elements: right name, right value */
		private $remove_access_rights = array();
		/** @var array list of array with 2 elements: a function to prepare a SELECT request, and a function that filter only the modifiable rows */
		private $remove_access_filters = array();
		/** @var string[] list of table from which we can automatically remove, used when the remove from a table may need remove on this table */
		private $remove_access_from_foreign_table = array();
		/** @var string[] list of table from which we can automatically put null on a foreign key, used when this table contain a foreign key, which must be set to null when the corresponding entity is removed */
		private $put_null_access_from_foreign_table = array();
		
		/** @var function function which can give HTML describing a row */
		private $row_description_provider = null;
		
		/** @var string|null necessary right to modify the customization of the table. If null, this table is not customizable. */
		private $customization_right = null;
		/** @var string|null if this table is customizable, specify the name to display */
		private $customization_name = null;
		/** @var array associative array with a sub model as the key, and the customized columns as value */
		private $customization = array();
		
		/** @var boolean indicates if the table is a root table. If not, it means any row should be reachable from any root table, else it is a lost row which can never be used/removed */
		private $is_root = false;

		/** @var SubDataModel[] sub models for which this table is the key 
		 * @no_name_check */
		public $_key_submodels = array();
		
		// events
		/** @var Event fired when rows have been inserted */
		private $oninsert;
		/** @var Event fired when rows have been removed */
		private $onremove;
		/** @var array fired before rows will be removed */
		private $onbeforeremove;
		/** @var Event fired when rows have been modified */
		private $onupdate;
		/** @var Event fired before rows will be modified */
		private $onbeforeupdate;
		
		/**
		 * @param DataModel $model the data model
		 * @param string $name the table name
		 */
		public function __construct(&$model, $name) {
			$this->model = &$model;
			$this->name = $name;
			$this->oninsert = new \Event();
			$this->onremove = new \Event();
			$this->onbeforeremove = array();
			$this->onupdate = new \Event();
			$this->onbeforeupdate = new \Event();
		}

		/** Indicates this table is the key for the given sub model
		 * @param SubDataModel $sm the sub model
		 * @no_name_check
		 */
		public function _key_for_submodel(&$sm) {
			array_push($this->_key_submodels, $sm);
		}


		/** Get the model containing this table 
		 * @return \DataModel the data model
		 */
		public function &getModel() {
			return $this->model;
		}
		/** Get the name of this table
		 * @return string name of this table
		 */
		public function getName() {
			return $this->name;
		}
		/** Get the SQL name of this table for the given sub model
		 * @param array $sub_models the selected sub models
		 * @throws \Exception if the needed sub model is not given
		 * @return string the SQL name of this table
		 */
		public function getSQLName($sub_models) {
			if ($this->model instanceof \SubDataModel) {
				if (!isset($sub_models[$this->model->getParentTable()]))
					throw new \Exception("No sub model ".$this->model->getParentTable()." for table ".$this->name);
				return $this->name."_".$sub_models[$this->model->getParentTable()];
			}
			return $this->name;
		}
		/** Get the SQL name of this table for the given sub model
		 * @param integer|null $sub_model the selected sub model
		 * @return string the SQL name of this table
		 */
		public function getSQLNameFor($sub_model) {
			if ($this->model instanceof \SubDataModel)
				return $this->name."_".$sub_model;
			return $this->name;
		}

		/** Get a column by name
		 * @param string $name name of the column to get
		 * @param array|null $sub_models the selected sub models
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
		/** Get a column by name
		 * @param string $name name of the column to get
		 * @param integer|null $sub_model the sub model
		 * @return Column|NULL the column or NULL if the column does not exist
		 */
		public function getColumnFor($name, $sub_model) {
			$sub_models = array();
			if ($sub_model <> null && $this->getModel() instanceof \SubDataModel)
				$sub_models[$this->getModel()->getParentTable()] = $sub_model;
			return $this->getColumn($name, $sub_models);
		}
		/** Get the accessible columns
		 * @param array|null $sub_models selected sub models
		 * @return Column[] list of columns
		 */
		public function getColumns($sub_models = null) {
			$columns = $this->_getColumns($sub_models);
			$cols = array();
			foreach ($columns as $c)
				if ($this->canAccessColumn($c->name))
				array_push($cols, $c);
			return $cols;
		}
		/** Get the accessible columns
		 * @param integer|null $sub_model sub model
		 * @return Column[] list of columns
		 */
		public function getColumnsFor($sub_model = null) {
			$sub_models = array();
			if ($sub_model <> null && $this->model instanceof SubDataModel)
				$sub_models[$this->model->getParentTable()] = $sub_model;
			return $this->getColumns($sub_models);
		}

		/** Get all columns (without filtering based on user's rights)
		 * @param integer|null $sub_model sub model
		 * @return Column[] list of columns
		 */
		public function internalGetColumnsFor($sub_model = null) {
			if ($sub_model == null) $sub_models = null;
			else $sub_models = array($this->model->getParentTable()=>$sub_model);
			return $this->_getColumns($sub_models);
		}
		/** Get all columns (without filtering based on user's rights)
		 * @param array|null $sub_models sub models
		 * @param boolean $include_customization if true and this table is customizable, the result will contain the customized columns
		 * @return Column[] list of columns
		 */
		public function internalGetColumns($sub_models = null, $include_customization = true) {
			return $this->_getColumns($sub_models, $include_customization);
		}
		/** Get a column (without filtering based on user's rights)
		 * @param string $name the name of the column to search
		 * @param array|null $sub_models sub models
		 * @return Column|NULL the column or null if it does not exist
		 */
		public function internalGetColumn($name, $sub_models = null) {
			$columns = $this->_getColumns($sub_models);
			foreach ($columns as $c)
				if ($c->name == $name)
				return $c;
			return null;
		}
		/** Get a column (without filtering based on user's rights)
		 * @param string $name the name of the column to search
		 * @param integer|null $sub_model sub model
		 * @return Column|NULL the column or null if it does not exist
		 */
		public function internalGetColumnFor($name, $sub_model = null) {
			if ($sub_model == null || !($this->model instanceof \SubDataModel)) $sub_models = null;
			else $sub_models = array($this->model->getParentTable()=>$sub_model);
			$columns = $this->_getColumns($sub_models);
			foreach ($columns as $c)
				if ($c->name == $name)
				return $c;
			return null;
		}
		
		/** Get columns
		 * @param array|null $sub_models sub models
		 * @param boolean $include_customization if true and this table is customizable, the result will contain the customized columns
		 * @return Column[] list of columns
		 * @no_name_check
		 */
		private function _getColumns($sub_models, $include_customization = true) {
			if ($this->customization_right == null || !$include_customization) return $this->columns;
			if ($this->model instanceof \SubDataModel) {
				$name = $this->getSQLName($sub_models);
				if (!isset($this->customization[$name])) {
					$this->customization[$name] = $this->loadCustomization($name);
				}
				return array_merge($this->columns, $this->customization[$name]);
			}
			if (!isset($this->customization[$this->name])) {
				$this->customization[$this->name] = $this->loadCustomization($this->name);
			}
			return array_merge($this->columns, $this->customization[$this->name]);
		}
		
		/** For internal purpose only, used by table customization functionalities
		 * @param integer|null $sub_model sub model
		 */
		public function forceReloadCustomization($sub_model) {
			if ($this->model instanceof \SubDataModel)
				$name = $this->getSQLNameFor($sub_model);
			else
				$name = $this->name;
			$this->customization[$name] = $this->loadCustomization($name);
		}
		
		/** Check the customization is loaded for the given sub model, if not it will be loaded
		 * @param integer|null $sub_model sub model
		 */
		public function ensureCustomizationLoaded($sub_model) {
			if ($this->model instanceof \SubDataModel)
				$name = $this->getSQLNameFor($sub_model);
			else
				$name = $this->name;
			if (!isset($this->customization[$name]))
				$this->customization[$name] = $this->loadCustomization($name);
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
		 * @param string $name name of the column
		 * @param integer $size number of bits
		 * @return \datamodel\Table this
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
		 * @param boolean $unique indicates if the column must contain only unique values
		 * @return \datamodel\Table this
		 */
		public function &addForeignKey($name, $table, $remove_foreign_when_primary_removed, $remove_primary_when_foreign_removed, $multiple, $can_be_null = null, $unique = false) {
			$c = new ForeignKey($this, $name, $table, $remove_foreign_when_primary_removed, $remove_primary_when_foreign_removed, $multiple, $can_be_null, $unique);
			array_push($this->columns, $c);
			return $this;
		}

		/** Set a column as the primary key
		 * @param string $name the name of the column
		 * @throws \Exception if the column does not exist, or is not unique
		 * @return \datamodel\Table this
		 */
		public function &setPrimary($name) {
			$c = null;
			foreach ($this->columns as $col) if ($col->name == $name) { $c = $col; break; }
#DEV
			if ($c == null) throw new \Exception("Unknown column '".$name."' in table '".$this->name."': setPrimary");
			if (!$c->unique) throw new \Exception("Column '".$name."' in table '".$this->name."' cannot be a primary: it is not unique");
#END
			$this->primary_key = $c;
			return $this;
		}

		/** Get the primary key column
		 * @return Column|NULL the primary key, or null if the key is not on a single column
		 */
		public function getPrimaryKey() {
			return $this->primary_key;
		}

		/**
		 * Set a list of columns as the key of this table (values on those columns can identify uniquely a row)
		 * @param string[] $columns list of columns' names
		 * @return Table this
		 */
		public function &setKey($columns) {
			$this->key = $columns;
			return $this;
		}
		/**
		 * If this table has its key on several columns, return the list of columns
		 * @return string[] columns used as key
		 */
		public function getKey() {
			return $this->key;
		}

		/** Add an integer column.
		 * @param string $name name of the column
		 * @param integer $size number of bits
		 * @param integer|null $min minimum allowed value
		 * @param integer|null $max maximum allowed value
		 * @param boolean $can_be_null indicates if the column may contain NULL values
		 * @param boolean $unique indicates if the column must contain only unique values
		 * @return \datamodel\Table this
		 */
		public function &addInteger($name, $size=32, $min = null, $max = null, $can_be_null = true, $unique = false) {
			$c = new ColumnInteger($this, $name, $size, $min, $max, $can_be_null, $unique);
			array_push($this->columns, $c);
			return $this;
		}
		
		/** Add a timestamp column
		 * @param string $name name of the column
		 * @param boolean $can_be_null indicates if the column may contain NULL values
		 * @param boolean $unique indicates if the column must contain only unique values
		 * @return \datamodel\Table this
		 */
		public function &addTimestamp($name, $can_be_null = true, $unique = false){
			$c = new ColumnTimestamp($this, $name, $can_be_null, $unique);
			array_push($this->columns, $c);
			return $this;
		}

		/** Add a decimal column.
		 * @param string $name name of the column
		 * @param integer $integer_digits number of digits before the decimal part
		 * @param integer $decimal_digits number of digits in the decimal part
		 * @param number|null $min minimum allowed value
		 * @param number|null $max maximum allowed value
		 * @param boolean $can_be_null indicates if the column may contain NULL values
		 * @param boolean $unique indicates if the column must contain only unique values
		 * @return \datamodel\Table this
		 */
		public function &addDecimal($name, $integer_digits=10, $decimal_digits = 2, $min = null, $max = null, $can_be_null = true, $unique = false) {
			$c = new ColumnDecimal($this, $name, $integer_digits, $decimal_digits, $min, $max, $can_be_null, $unique);
			array_push($this->columns, $c);
			return $this;
		}

		/** Add a boolean column
		 * @param string $name name of the column
		 * @param boolean $can_be_null indicates if the column may contain NULL values
		 * @return \datamodel\Table this
		 */
		public function &addBoolean($name, $can_be_null = true) {
			$c = new ColumnBoolean($this, $name, $can_be_null);
			array_push($this->columns, $c);
			return $this;
		}

		/** Add a string column
		 * @param string $name name of the column
		 * @param integer $max_length maximum number of characters
		 * @param integer $min_length minimum number of characters
		 * @param boolean $can_be_null indicates if the column may contain NULL values
		 * @param boolean $unique indicates if the column must contain only unique values
		 * @param function|null $validation if specified, this function will be called before to put a value
		 * @param string|null $specific_lang if this column contain text in a specific language
		 * @return \datamodel\Table this
		 */
		public function &addString($name, $max_length=255, $min_length = 0, $can_be_null = true, $unique = false, $validation = null, $specific_lang = null) {
			$c = new ColumnString($this, $name, $max_length, $min_length, $can_be_null, $unique, $validation, $specific_lang);
			array_push($this->columns, $c);
			return $this;
		}

		/** Add an enum column, with the given possible values
		 * @param string $name name of the column
		 * @param string[] $values possible values
		 * @param boolean $can_be_null indicates if the column may contain NULL values
		 * @param boolean $unique indicates if the column must contain only unique values
		 * @return \datamodel\Table this
		 */
		public function &addEnum($name, $values, $can_be_null = true, $unique = false) {
			$c = new ColumnEnum($this, $name, $values, $can_be_null, $unique);
			array_push($this->columns, $c);
			return $this;
		}

		/** Add a date column
		 * @param string $name name of the column
		 * @param boolean $can_be_null indicates if the column may contain NULL values
		 * @param boolean $unique indicates if the column must contain only unique values
		 * @param string|null $minimum_date minimum date
		 * @param string|null $maximum_date maximum date
		 * @return \datamodel\Table this
		 */
		public function &addDate($name, $can_be_null = true, $unique = false, $minimum_date = null, $maximum_date = null) {
			$c = new ColumnDate($this, $name, $can_be_null, $unique, $minimum_date, $maximum_date);
			array_push($this->columns, $c);
			return $this;
		}

		/** Add a time column
		 * @param string $name name of the column
		 * @param boolean $can_be_null indicates if the column may contain NULL values
		 * @param boolean $unique indicates if the column must contain only unique values
		 * @return \datamodel\Table this
		 */
		public function &addTime($name, $can_be_null = true, $unique = false) {
			$c = new ColumnTime($this, $name, $can_be_null, $unique);
			array_push($this->columns, $c);
			return $this;
		}
		
		/** Add a column
		 * @param Column $col the column
		 * @return \datamodel\Table this
		 */
		public function &addColumn($col) {
			array_push($this->columns, $col);
			return $this;
		}
		
		/** Add a function to be called each time something is inserted into this table
		 * @param function $fct take as parameter and array, with each element being an associative array with 3 keys: fields_values, id, sub_model_instance
		 * @return \datamodel\Table this
		 */
		public function onInsert($fct) {
			$this->oninsert->listenFunction($fct);
			return $this;
		}
		/** Called by SQLQuery when something has been inserted
		 * @param array $inserted list of inserted values/keys
		 */
		public function fireInsert($inserted) {
			$this->oninsert->fire(array($inserted));
		}
		/** Indicates if the table has functions to be called when something has been inserted
		 * @return boolean true if it has functions to be called when something has been inserted
		 */
		public function hasInsertListeners() {
			return $this->oninsert->hasListeners();
		}
		/** Add a function to be called when some rows are going to be removed from this table, and gives the opportunity to reject the remove
		 * @param function $fct takes 3 parameters: table (of type Table), rows, sub_model_instance, and must return null if the remove is allowed, or a string containing the error message in HTML format
		 * @return \datamodel\Table this
		 */
		public function onBeforeRemove($fct) {
			array_push($this->onbeforeremove, $fct);
			return $this;
		}
		/** Add a function to be called when some rows have been removed
		 * @param function $fct takes 3 parameters: table, rows, sub_model_instance
		 * @return \datamodel\Table this
		 */
		public function onRemove($fct) {
			$this->onremove->listenFunction($fct);
			return $this;
		}
		/** Called by SQLQuery just before to remove rows
		 * @param string $table SQL name of the table
		 * @param array $rows rows which will be removed
		 * @param integer|null $sub_model_instance sub model
		 * @return string|NULL null if the remove is allowed, or a string containing the error message in HTML format
		 */
		public function fireBeforeRemove($table, $rows, $sub_model_instance) {
			foreach ($this->onbeforeremove as $fct) {
				$error = $fct($table, $rows, $sub_model_instance);
				if ($error <> null) return $error;
			}
			return null;
		}
		/** Called by SQLQuery when some rows have been removed
		 * @param string $table SQL name of the table
		 * @param array $rows rows which will be removed
		 * @param integer|null $sub_model_instance sub model
		 */
		public function fireRemove($table, $rows, $sub_model_instance) {
			$this->onremove->fire(array($table, $rows, $sub_model_instance));
		}
		/** Indicates if the table has functions to be called when something has been removed
		 * @return boolean true if it has functions to be called when something has been removed
		 */
		public function hasRemoveListeners() {
			return $this->onremove->hasListeners() || count($this->onbeforeremove) > 0;
		}
		/** Add a function to be called when some rows have been modified
		 * @param function $fct takes 2 parameters: keys_fields_values, sub_model_instance
		 * @return \datamodel\Table this
		 */
		public function onUpdate($fct) {
			$this->onupdate->listenFunction($fct);
			return $this;
		}
		/** Called by SQLQuery when some rows have been updated
		 * @param array $keys_fields_values what has been updated
		 * @param integer|null $sub_model_instance sub model
		 */
		public function fireUpdate($keys_fields_values, $sub_model_instance) {
			$this->onupdate->fire(array($keys_fields_values,$sub_model_instance));
		}
		/** Indicates if the table has functions to be called when something has been updated
		 * @return boolean true if it has functions to be called when something has been updated
		 */
		public function hasUpdateListeners() {
			return $this->onupdate->hasListeners();
		}
		/** Add a function to call before to modify something in this table
		 * @param function $fct takes 2 parameters: keys_fields_values, sub_model_instance
		 * @return \datamodel\Table this
		 */
		public function onBeforeUpdate($fct) {
			$this->onbeforeupdate->listenFunction($fct);
			return $this;
		}
		/** Called by SQLQuery just before to execute UPDATE request
		 * @param array $keys_fields_values what will be modified
		 * @param integer|null $sub_model_instance sub model
		 */
		public function fireBeforeUpdate(&$keys_fields_values, $sub_model_instance) {
			$this->onbeforeupdate->fire(array(&$keys_fields_values,$sub_model_instance));
		}
		/** Indicates if the table has functions to be called before to modify something
		 * @return boolean true if it has functions to be called before to modify something
		 */
		public function hasBeforeUpdateListeners() {
			return $this->onbeforeupdate->hasListeners();
		}
		
		/** Indicate one or several columns should be indexed for faster access.
		 * As parameters, this method expects the list of columns to be indexed together.
		 * @return \datamodel\Table this
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
		/** Get the indexes defined on this table
		 * @return array list of indexes, as an associative array: the index name as the key, a list of columns' name as the value
		 */
		public function getIndexes() {
			$list = array();
			foreach ($this->indexes as $name=>$cols) $list[$name] = $cols;
			return $list;
		}


		/** Add a read access based on user's right
		 * @param string $right_name the necessary right
		 * @param mixed $right_value the value of the right
		 * @return \datamodel\Table this
		 */
		public function &addReadAccess($right_name, $right_value) {
			array_push($this->read_access_rights, array($right_name, $right_value));
			return $this;
		}
		
		/**
		 * Allow the access to this table when coming from a foreign table (in a join request)
		 * If the user was allowed to access to the previous table, no need to check again for this table 
		 * @param string $foreign_table_name foreign table
		 * @return \datamodel\Table this
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
				if (\PNApplication::$instance->user_management->hasRight($r[0],$r[1])) return true;
			foreach ($this->read_column_access_rights as $colname=>$list)
				foreach ($list as $r)
					if (\PNApplication::$instance->user_management->hasRight($r[0],$r[1])) return true;			
			return false;
		}
		/** Return the list of tables from which we can automatically grant read access when doing a join
		 * @return string[] list of tables
		 */
		public function getReadAccessFromForeignTable(){
			return $this->read_access_from_foreign_table;
		}
		/** Indicates if this table has filters for read access
		 * @return boolean true if it has filters
		 */
		public function hasReadFilter(){
			return count($this->read_access_filters) > 0;
		}
		/** Check if the current user has necessary rights to read on this table
		 * @return boolean true if the current user has necessary rights to read on this table
		 */
		public function hasReadRights(){
			if ($this->read_access_rights === null) return true;
			foreach ($this->read_access_rights as $r)
				if (\PNApplication::$instance->user_management->hasRight($r[0],$r[1])) return true;
			foreach ($this->read_column_access_rights as $colname=>$list)
				foreach ($list as $r)
					if (\PNApplication::$instance->user_management->hasRight($r[0],$r[1])) return true;
			return false;
		}
		/** Check if the current user has necessary rights to read the given column
		 * @param string $column_name the column to read
		 * @return boolean true if the current user has necessary rights to read the given column
		 */
		public function canAccessColumn($column_name) {
			if ($this->read_access_rights === null) return true;
			if (count($this->read_access_from_foreign_table) > 0) return true;
			foreach ($this->read_access_rights as $r)
				if (\PNApplication::$instance->user_management->hasRight($r[0],$r[1])) return true;
			if (count($this->read_column_access_rights) > 0) {
				if (isset($this->read_column_access_rights[$column_name])) {
					foreach ($this->read_column_access_rights[$column_name] as $r)
						if (\PNApplication::$instance->user_management->hasRight($r[0],$r[1])) return true;
					return false;
				}
			}
			return count($this->read_access_filters) > 0;
		}
		/** Called to filter a SELECT request
		 * @param \SQLQuery $q the SELECT
		 * @param string $table_alias alias of this table
		 */
		public function filterReadAccess(&$q, $table_alias) {
			if ($this->read_access_rights === null) return;
			foreach ($this->read_access_rights as $r)
				if (\PNApplication::$instance->user_management->hasRight($r[0],$r[1])) return; // full access
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

		/** Add a right which grant write access to this table
		 * @param string $right_name the necessary right
		 * @param mixed $right_value the value of the right
		 * @return \datamodel\Table this
		 */
		public function &addWriteAccess($right_name, $right_value) {
			array_push($this->write_access_rights, array($right_name, $right_value));
			return $this;
		}
		/** Add a right which grant write access to the given column
		 * @param string $column_name the column
		 * @param string $right_name the necessary right
		 * @param mixed $right_value the value of the right
		 * @return \datamodel\Table this
		 */
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
		/** Check if the current user can modify any row on this table
		 * @return boolean true if the current user can modify any row on this table
		 */
		public function canModifyEverything() {
			foreach ($this->write_access_rights as $r)
				if (\PNApplication::$instance->user_management->hasRight($r[0], $r[1])) return true;
			return false;
		}
		/** Check if the current user can modify the given column
		 * @param string $column_name the column to modify
		 * @return boolean true if the current user can modify the given column
		 */
		public function canModifyColumn($column_name) {
			if ($this->canModifyEverything()) return true;
			if (isset($this->write_column_access_rights[$column_name])) {
				foreach ($this->write_column_access_rights[$column_name] as $r)
					if (\PNApplication::$instance->user_management->hasRight($r[0],$r[1])) return true;
				return false;
			}
			return true;
		}
		/** If the user cannot modify everything, check if the user can modify at leaast one column
		 * @return boolean true if the user can modify at leaast one column
		 */
		public function canModifyAColumn() {
			foreach ($this->write_column_access_rights as $column_name=>$rights) {
				foreach ($rights as $r)
					if (\PNApplication::$instance->user_management->hasRight($r[0],$r[1])) return true;
			}
			return false;
		}
		/** Check if the user MAY modify this table, meaning either everything can be modified, or a column can be modified, or it exists filters which may grant access to some rows
		 * @return boolean true if the user MAY modify this table
		 */
		public function mayModify() {
			if ($this->canModifyEverything()) return true;
			if ($this->canModifyAColumn()) return true;
			if (count($this->write_access_filters) > 0) return true;
			return false;
		}
		/** Prepare a SELECT in order to check which rows can be modified by the user
		 * @param \SQLQuery $q the SELECT to prepare
		 * @param string $table_alias alias of this table
		 * @return boolean false if it is not needed because the user can modify any row
		 */
		public function prepareSelectToDetermineWriteAccess(&$q, $table_alias) {
			foreach ($this->write_access_rights as $r)
				if (\PNApplication::$instance->user_management->hasRight($r[0], $r[1])) return false; // full access
			foreach ($this->write_access_filters as $f) {
				$prepare = $f[0];
				$prepare($q, $table_alias);
			}
			return true;
		}
		/** Filter rows which can be modified by the user
		 * @param array $rows list of rows returned by the SELECT previously prepared by prepareSelectToDetermineWriteAccess
		 * @return array list of rows which can be modified
		 */
		public function filterWriteAccess($rows) {
			foreach ($this->write_access_rights as $r)
				if (\PNApplication::$instance->user_management->hasRight($r[0], $r[1])) return $rows; // full access
			foreach ($this->write_access_filters as $f) {
				$filter = $f[1];
				$rows = $filter($rows);
			}
			return $rows;
		}

		/** Add a right which grant insert access to this table
		 * @param string $right_name the necessary right
		 * @param mixed $right_value the value of the right
		 * @return \datamodel\Table this
		 */
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
		/** Check if the user MAY insert data in this table, meaning either the user can insert anything, or it exists filters which may grant access to the user
		 * @return boolean true if the user MAY insert data in this table
		 */
		public function mayInsert() {
			if ($this->canInsertAny()) return true;
			return count($this->add_access_filters) > 0;
		}
		/** Check if the user can insert anything in this table
		 * @return boolean true if the user can insert anything in this table
		 */
		public function canInsertAny() {
			foreach ($this->add_access_rights as $r)
				if (\PNApplication::$instance->user_management->hasRight($r[0],$r[1])) return true;
			return false;
		}
		/** Ask if the given values can be inserted by the user
		 * @param array $values values to insert
		 * @return boolean true if accepted
		 */
		public function acceptInsert($values) {
			if ($this->canInsertAny()) return true;
			foreach ($this->add_access_filters as $filter)
				if ($filter($values)) return true;
			return false;
		}

		/** Add a right which grant remove access to this table
		 * @param string $right_name the necessary right
		 * @param mixed $right_value the value of the right
		 * @return \datamodel\Table this
		 */
		public function &addRemoveAccess($right_name, $right_value) {
			array_push($this->remove_access_rights, array($right_name,$right_value));
			return $this;
		}
		/** Allow to remove rows in this table if this is implied by a remove from another table
		 * @param string $foreign_table_name the table in which a remove imply a remove on this table
		 * @return \datamodel\Table this
		 */
		public function &addRemoveAccessFromForeignTable($foreign_table_name){
			array_push($this->remove_access_from_foreign_table, $foreign_table_name);
			return $this;
		}
		/** Check if remove is granted when coming from a remove from another table
		 * @param string $foreign_table_name the table from which the remove comes from
		 * @return boolean true if the remove is granted
		 */
		public function canRemoveFromForeignTable($foreign_table_name) {
			return in_array($foreign_table_name, $this->remove_access_from_foreign_table);
		}
		/** Allow to remove a link (put null in a foreign key) in this table if this is implied by a remove from another table
		 * @param string $foreign_table_name the table in which a remove imply to put null in this table
		 * @return \datamodel\Table this
		 */
		public function &addRemoveLinkFromForeignTable($foreign_table_name){
			array_push($this->put_null_access_from_foreign_table, $foreign_table_name);
			return $this;
		}
		/** Check if a remove from the given table allow us to put null in a foreign key
		 * @param string $foreign_table_name the table from which the remove comes from
		 * @return boolean true if allowed
		 */
		public function canPutNullFromForeignTable($foreign_table_name) {
			return in_array($foreign_table_name, $this->put_null_access_from_foreign_table);
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
		/** Check if the current user can remove any row in this table
		 * @return boolean true if the current user can remove any row in this table
		 */
		public function canRemoveAny() {
			foreach ($this->remove_access_rights as $r)
				if (\PNApplication::$instance->user_management->hasRight($r[0], $r[1])) return true;
			return false;
		}
		/** Check if the current user MAY remove some rows in this table, meaning either the user can remove any row, or filters exist which may grant access to the user
		 * @return boolean true if the current user MAY remove some rows in this table
		 */
		public function mayRemove() {
			if ($this->canRemoveAny()) return true;
			return count($this->remove_access_filters) > 0;
		}
		/** Prepare a SELECT in order to check which rows the current user is allowed to remove
		 * @param \SQLQuery $q the SELECT
		 * @param string $table_alias alias of this table in the SQLQuery
		 * @param array $locks if we need to lock some elements in this table between this request and the remove
		 * @return boolean false if this is not necessary because the user can remove any row
		 */
		public function prepareSelectToDetermineRemoveAccess(&$q, $table_alias, &$locks) {
			foreach ($this->remove_access_rights as $r)
				if (\PNApplication::$instance->user_management->hasRight($r[0], $r[1])) return false; // full access
			foreach ($this->remove_access_filters as $f) {
				$prepare = $f[0];
				$prepare($q, $table_alias, $locks);
			}
			return true;
		}
		/** Filter which rows the user can remove
		 * @param array $rows rows returned by the SELECT prepared using prepareSelectToDetermineRemoveAccess
		 * @return array the rows the user is allowed to remove
		 */
		public function filterRemoveAccess($rows) {
			foreach ($this->remove_access_rights as $r)
				if (\PNApplication::$instance->user_management->hasRight($r[0], $r[1])) return $rows; // full access
			foreach ($this->remove_access_filters as $f) {
				$filter = $f[1];
				$rows = $filter($rows);
			}
			return $rows;
		}
			
		/** Set a function which can provide HTML to describe a row in this table
		 * @param function $provider_callback the function
		 * @return \datamodel\Table this
		 */
		public function setRowDescriptionProvider($provider_callback) {
			$this->row_description_provider = $provider_callback;
			return $this;
		}
		/** Describe the row having the given key
		 * @param mixed $key the key
		 * @param integer|null $sub_model sub model
		 * @return string the HTML describing the row
		 */
		public function getRowDescriptionByKey($key, $sub_model = null) {
			$row = \SQLQuery::getRow($this, $key, $sub_model);
			return $this->getRowDescription($row);
		}
		/** Describe the given row
		 * @param array $row the row
		 * @return string the HTML describing the row
		 */
		public function getRowDescription($row) {
			if ($this->row_description_provider <> null) {
				return call_user_func($this->row_description_provider, $row);
			}
			$s = "Row on table ".$this->name.": ";
			$first = true;
			foreach ($this->columns as $col) {
				if ($first) $first = false; else $s .= ", ";
				$s .= $col->name."=";
				if (isset($row[$col->name])) $s .= toHTML($row[$col->name]);
				else $s .= "NULL";
			}
			return $s;
		}
		

		/**
		 * Make this table customizable
		 * @param string $name a display name
		 * @param string $right_name necessary right to modify customization
		 * @return \datamodel\Table this
		 */
		public function &customizable($name, $right_name) {
			$this->customization_right = $right_name;
			$this->customization_name = $name;
			return $this;
		}
		/** Indicates if this table is customizable
		 * @return boolean true if this table is customizable
		 */
		public function isCustomized() {
			return $this->customization_right <> null;
		}
		/** Get the display name for a customizable table
		 * @return string the display name
		 */
		public function getCustomizationName() {
			return $this->customization_name;
		}
		/** Get the necessary right to modify a customizable table
		 * @return string the necessary right
		 */
		public function getCustomizationRight() {
			return $this->customization_right;
		}
		/** Load customized columns
		 * @param string $table_name the SQL name
		 * @return array list of customized columns
		 */
		private function loadCustomization($table_name) {
			$data_path = realpath(dirname(__FILE__)."/../../data/".\PNApplication::$instance->current_domain);
			if (!file_exists($data_path."/custom_tables/$table_name"))
				return array();
			try {
				return include($data_path."/custom_tables/$table_name");
			} catch (\Exception $e) {
				\PNApplication::error("Error in custom table definition of $table_name", $e);
			}
		}

	}

	/**
	 * Abstract class describing a column in a table
	 */
	abstract class Column {

		/** @var Table the table this column belongs to */
		public $table;
		/** @var string name of this column */
		public $name;
		/** @var boolean true if this column accepts null values */
		public $can_be_null;
		/** @var boolean true if values in this column are unique */
		public $unique;

		/**
		 * @param Table $table the table this column belongs to
		 * @param string $name name of this column
		 * @param boolean $can_be_null true if this column accepts null values
		 * @param boolean $unique true if values in this column are unique
		 */
		public function __construct(&$table, $name, $can_be_null, $unique) {
			$this->table = &$table;
			$this->name = $name;
			$this->can_be_null = $can_be_null;
			$this->unique = $unique;
		}

		/**
		 * Get the SQL definition of this column
		 * @param \DataBaseSystem $db_system database connection
		 * @param string $sql_table_name SQL name of the table
		 * @return string the SQL definition
		 */
		public function getSQL($db_system, $sql_table_name) {
			$sql = "`".$this->name."` ";
			$sql .= $this->getSQLType($db_system, $sql_table_name);
			if (!$this->can_be_null) $sql .= " NOT NULL";
			return $sql;
		}
		/**
		 * Get the SQL type for this column
		 * @param \DataBaseSystem $db_system database connection
		 * @param string $sql_table_name SQL name of the table
		 */
		public abstract function getSQLType($db_system, $sql_table_name);

		/**
		 * Validate a value before to insert or update in this column
		 * @param mixed $value the value
		 * @return mixed the value to insert/update
		 */
		public function validate($value) {
			return $value;
		}
		
		/** Get how to display the given value from the database, in the screen
		 * @param mixed $value the value
		 * @return mixed what to display
		 */
		public function getDisplayValue($value) { return $value; }
		
		/** Get the specification of this column in JSON format
		 * @return string the JSON specification
		 */
		public function getJSONSpec() {
			return "\"can_be_null\":".json_encode($this->can_be_null).",\"unique\":".json_encode($this->unique);
		}

	}

	/**
	 * Integer column
	 */
	class ColumnInteger extends Column {

		/** @var integer number of bits */
		public $size;
		/** @var integer|null minimum value */
		public $min;
		/** @var integer|null maximum value */
		public $max;

		/**
		 * @param Table $table the table this column belongs to
		 * @param string $name name of this column
		 * @param integer $size number of bits
		 * @param integer|null $min minimum value
		 * @param integer|null $max maximum value
		 * @param boolean $can_be_null true if this column accepts null values
		 * @param boolean $unique true if values in this column are unique
		 */
		public function __construct(&$table, $name, $size, $min = null, $max = null, $can_be_null = true, $unique = false) {
			parent::__construct($table, $name, $can_be_null, $unique);
			$this->size = $size;
			$this->min = $min;
			$this->max = $max;
		}

		public function getSQLType($db_system, $sql_table_name) {
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
			throw new \Exception("Size of integer not supported: ".$this->size." bits, maximum is 64 bits");
		}

		public function validate($value) {
			if ($this->can_be_null && ($value === null || $value === "")) return null;
			if (!is_integer($value)) $value = intval($value);
			if ($this->min !== null && $value < $this->min) throw new \Exception("Minimum is ".$this->min);
			if ($this->max !== null && $value > $this->max) throw new \Exception("Maximum is ".$this->max);
			return $value;
		}
		
		public function getJSONSpec() {
			return parent::getJSONSpec().",\"size\":".json_encode($this->size).",\"min\":".json_encode($this->min).",\"max\":".json_encode($this->max);
		}
	}

	/**
	 * Decimal column
	 */
	class ColumnDecimal extends Column {

		/** @var integer number of digits before the decimal part */
		public $integer_digits;
		/** @var integer number of digits in the decimal part */
		public $decimal_digits;
		/** @var number|null minimum value */
		public $min;
		/** @var number|null maximum value */
		public $max;

		/**
		 * @param Table $table the table this column belongs to
		 * @param string $name name of this column
		 * @param integer $integer_digits number of digits before the decimal part
		 * @param integer $decimal_digits number of digits in the decimal part
		 * @param number|null $min minimum value
		 * @param number|null $max maximum value
		 * @param boolean $can_be_null true if this column accepts null values
		 * @param boolean $unique true if values in this column are unique
		 */
		public function __construct(&$table, $name, $integer_digits, $decimal_digits, $min = null, $max = null, $can_be_null = true, $unique = false) {
			parent::__construct($table, $name, $can_be_null, $unique);
			$this->integer_digits = $integer_digits;
			$this->decimal_digits = $decimal_digits;
			$this->min = $min;
			$this->max = $max;
		}

		public function getSQLType($db_system, $sql_table_name) {
			return "DECIMAL(".($this->integer_digits+$this->decimal_digits).",".$this->decimal_digits.")";
		}

		public function validate($value) {
			if ($this->can_be_null && ($value === null || $value === "")) return null;
			if (!is_double($value)) $value = doubleval($value);
			if ($this->min !== null && $value < $this->min) throw new \Exception("Minimum is ".$this->min);
			if ($this->max !== null && $value > $this->max) throw new \Exception("Maximum is ".$this->max);
			return $value;
		}

		public function getJSONSpec() {
			return parent::getJSONSpec().",\"integer_digits\":".json_encode($this->integer_digits).",\"decimal_digits\":".json_encode($this->decimal_digits).",\"min\":".json_encode($this->min).",\"max\":".json_encode($this->max);
		}
	}

	/**
	 * Boolean column
	 */
	class ColumnBoolean extends ColumnInteger {

		/**
		 * @param Table $table the table this column belongs to
		 * @param string $name name of this column
		 * @param boolean $unique true if values in this column are unique
		 */
		public function __construct(&$table, $name, $can_be_null = false) {
			parent::__construct($table, $name, 1, 0, 1, $can_be_null, false);
		}

		public function validate($value) {
			if ($this->can_be_null && $value === null) return null;
			return $value == true;
		}
	}

	/**
	 * Text column
	 */
	class ColumnString extends Column {

		/** @var integer| null maximum length */
		public $max_length;
		/** @var integer| null minimum length */
		public $min_length;
		/** @var function validation function */
		public $validation;
		/** @var string|null language of the text */
		public $specific_lang;

		/**
		 * @param Table $table the table this column belongs to
		 * @param string $name name of this column
		 * @param integer $max_length maximum length
		 * @param integer $min_length minimum length
		 * @param boolean $can_be_null true if this column accepts null values
		 * @param boolean $unique true if values in this column are unique
		 * @param function $validation function which may modify the text before to put it in the database
		 * @param string|null $specific_lang language of the text
		 */
		public function __construct(&$table, $name, $max_length, $min_length = 0, $can_be_null = true, $unique = false, $validation = null, $specific_lang = null) {
			parent::__construct($table, $name, $can_be_null, $unique);
			$this->max_length = $max_length;
			$this->min_length = $min_length;
			$this->validation = $validation;
			$this->specific_lang = $specific_lang;
		}

		public function getSQLType($db_system, $sql_table_name) {
			/* TODO choose the type depending on max_length */
			return "VARCHAR(".$this->max_length.")";
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

		public function getJSONSpec() {
			return parent::getJSONSpec().",\"min_length\":".json_encode($this->min_length).",\"max_length\":".json_encode($this->max_length);
		}
	}

	/**
	 * Enumeration column
	 */
	class ColumnEnum extends Column {

		/** @var string[] possible value */
		public $values;

		/**
		 * @param Table $table the table this column belongs to
		 * @param string $name name of this column
		 * @param string[] $values list of possible values
		 * @param boolean $can_be_null true if this column accepts null values
		 * @param boolean $unique true if values in this column are unique
		 */
		public function __construct(&$table, $name, $values, $can_be_null = true, $unique = false) {
			parent::__construct($table, $name, $can_be_null, $unique);
			$this->values = $values;
		}

		public function getSQLType($db_system, $sql_table_name) {
			$sql = "ENUM(";
			$first = true;
			foreach ($this->values as $v) {
				if ($first) $first = false; else $sql.=",";
				$sql .= "'".$db_system->escapeString($v)."'";
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
		
		public function getJSONSpec() {
			return parent::getJSONSpec().",\"values\":".json_encode($this->values);
		}
	}

	/**
	 * Date column.
	 * minimum and maximum dates are constraints and can be: null, a date, or the name of a date column in the same table
	 */
	class ColumnDate extends Column {

		/** @var string minimum date */
		public $minimum_date;
		/** @var string maximum date */
		public $maximum_date;

		/**
		 * @param Table $table the table this column belongs to
		 * @param string $name name of this column
		 * @param boolean $can_be_null true if this column accepts null values
		 * @param boolean $unique true if values in this column are unique
		 * @param string $minimum_date an SQL date, or the name of a date column in the same table, or null
		 * @param string $maximum_date an SQL date, or the name of a date column in the same table, or null
		 */
		public function __construct(&$table, $name, $can_be_null = true, $unique = false, $minimum_date = null, $maximum_date = null) {
			parent::__construct($table, $name, $can_be_null, $unique);
			$this->minimum_date = $minimum_date;
			$this->maximum_date = $maximum_date;
		}

		public function getSQLType($db_system, $sql_table_name) {
			return "DATE";
		}
		
		/** Convert an SQL date into a Unix timestamp
		 * @param string $sql_date SQL date
		 * @return integer Unix timestamp
		 */
		public static function toTimestamp($sql_date) {
			$s = explode("-", $sql_date);
			$y = intval($s[0]);
			$m = intval($s[1]);
			$d = intval($s[2]);
			$tz = date_default_timezone_get();
			date_default_timezone_set("GMT");
			$ts = mktime(0,0,0,$m,$d,$y);
			date_default_timezone_set($tz);
			return $ts;
		}
		public static function splitDate($sql_date) {
			$s = explode("-", $sql_date);
			return array(
				"year"=>intval($s[0]),
				"mon"=>intval($s[1]),
				"mday"=>intval($s[2])
			);
		}
		public static function toSQLDate($date) {
			return str_pad($date["year"], 4, "0", STR_PAD_LEFT)."-".str_pad($date["mon"], 2, "0", STR_PAD_LEFT)."-".str_pad($date["mday"], 2, "0", STR_PAD_LEFT);
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

		public function getJSONSpec() {
			return parent::getJSONSpec().",\"minimum_date\":".json_encode($this->minimum_date).",\"maximum_date\":".json_encode($this->maximum_date);
		}
	}

	/**
	 * Time column
	 */
	class ColumnTime extends Column {

		/**
		 * @param Table $table the table this column belongs to
		 * @param string $name name of this column
		 * @param boolean $can_be_null true if this column accepts null values
		 * @param boolean $unique true if values in this column are unique
		 */
		public function __construct(&$table, $name, $can_be_null = true, $unique = false) {
			parent::__construct($table, $name, $can_be_null, $unique);
		}


		public function getSQLType($db_system, $sql_table_name) {
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

	/**
	 * Primary key, with AUTO_INCREMENT
	 */
	class PrimaryKey extends ColumnInteger {

		/**
		 * @param Table $table the table this column belongs to
		 * @param string $name name of this column
		 * @param integer $size number of bits
		 */
		public function __construct(&$table, $name, $size) {
			parent::__construct($table, $name, $size, null, null, false, true);
		}

		public function getSQLType($db_system, $sql_table_name) {
			return parent::getSQLType($db_system, $sql_table_name)." AUTO_INCREMENT";
		}
	}

	/**
	 * Unix timestamp column
	 */
	class ColumnTimestamp extends ColumnInteger {
		/**
		 * @param Table $table the table this column belongs to
		 * @param string $name name of this column
		 * @param boolean $can_be_null true if this column accepts null values
		 * @param boolean $unique true if values in this column are unique
		 */
		public function __construct(&$table, $name, $can_be_null = true, $unique = false){
			parent::__construct($table, $name, 64, null, null, $can_be_null, $unique);
		}
		
		public function getDisplayValue($value) {
			if ($value == null) return null;
			return date("Y-m-d h:i", $value); 
		}
	}

	/**
	 * Foreign key
	 */
	class ForeignKey extends ColumnInteger {

		/** @var string foreign table */
		public $foreign_table;
		/** @var boolean in case a foreign key points to a row which is removed, if this is true the row containing the foreign key will be removed at the same time, if false NULL will be set in the foreign key */
		public $remove_foreign_when_primary_removed;
		/** @var boolean if true, when a row of this table is removed, the row pointed by the foreign key will be removed at the same time */
		public $remove_primary_when_foreign_removed;
		/** @var boolean if true it means we can have several rows in this table pointing to the same primary key (having the same foreign key) */
		public $multiple;

		/**
		 * @param Table $table the table this column belongs to
		 * @param string $name name of this column
		 * @param string $foreign_table name of the table this foreign key points to
		 * @param boolean $remove_foreign_when_primary_removed in case a foreign key points to a row which is removed, if this is true the row containing the foreign key will be removed at the same time, if false NULL will be set in the foreign key
		 * @param boolean $remove_primary_when_foreign_removed if true, when a row of this table is removed, the row pointed by the foreign key will be removed at the same time
		 * @param boolean $multiple if true it means we can have several rows in this table pointing to the same primary key (having the same foreign key)
		 * @param boolean $can_be_null true if this column accepts null values
		 * @param boolean $unique true if values in this column are unique
		 */
		public function __construct(&$table, $name, $foreign_table, $remove_foreign_when_primary_removed, $remove_primary_when_foreign_removed, $multiple, $can_be_null, $unique) {
			$this->foreign_table = $foreign_table;
			$this->remove_foreign_when_primary_removed = $remove_foreign_when_primary_removed;
			$this->remove_primary_when_foreign_removed = $remove_primary_when_foreign_removed;
			$this->multiple = $multiple;
			try { $ft = $table->getModel()->internalGetTable($foreign_table); }
			catch (\Exception $e) { $ft = null; }
			if ($ft == null && $table->getModel() instanceof \SubDataModel)
				try { $ft = $table->getModel()->getParentModel()->internalGetTable($foreign_table); }
				catch (\Exception $e) { $ft = null; }
			if ($ft == null) {
				\PNApplication::error("Invalid foreign key '".$name."' in table '".$table->getName()."': foreign table '".$foreign_table."' does not exist");
				$size = 32;
			} else
				$size = $ft->getPrimaryKey()->size;
			parent::__construct($table, $name, $size, null, null, $can_be_null === null ? !$remove_foreign_when_primary_removed : $can_be_null, $unique);
		}

		public function validate($value) {
			if ($value === null) {
				if (!$this->can_be_null) throw new \Exception("Missing");
				return null;
			}
			return parent::validate($value);
		}
		
		public function getJSONSpec() {
			return parent::getJSONSpec().",\"foreign_table\":".json_encode($this->foreign_table).",\"remove_foreign_when_primary_removed\":".json_encode($this->remove_foreign_when_primary_removed).",\"remove_primary_when_foreign_removed\":".json_encode($this->remove_primary_when_foreign_removed).",\"multiple\":".json_encode($this->multiple);
		}
	}

} // namespace datamodel
?>