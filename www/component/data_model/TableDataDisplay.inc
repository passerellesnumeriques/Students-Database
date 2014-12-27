<?php
namespace datamodel {

/**
 * Defines a list of data on a table, and how we can reach this table from other tables
 */
class TableDataDisplay {
	
	/** @var string name of the table */
	private $table_name;
	/** @var DataDisplayCategory category the table belongs to */
	private $category;
	/** @var DataDisplay[] list of data */
	private $data_display = array();
	/** @var array|null null for all join allowed, or an associative array with the foreign key name as the key, and as the value additional information needed to determine if the join is allowed */
	private $join_from = array();
	/** @var boolean|string[] true if all foreign keys can be followed, or a list of foreign key name */
	private $follow = array();
	/** @var boolean true to allow to join to the primary key */
	private $join = false;
	
	/**
	 * @param string $table_name name of the table
	 * @param DataDisplayCategory $category the category this table belongs to
	 */
	public function __construct($table_name, $category) {
		$this->table_name = $table_name;
		$this->category = $category;
		$this->category->addTable($table_name);
	}
	
	/** Get the name of the table
	 * @return string table name
	 */
	public function getTableName() { return $this->table_name; }
	/** Get the table
	 * @return Table the table definition 
	 */
	public function getTable() { return \DataModel::get()->getTable($this->table_name); }
	/** Get the category
	 * @return DataDisplayCategory the category
	 */
	public function getCategory() { return $this->category; }
	
	/** Add a displayable data to the table
	 * @param DataDisplay $data_display the data to add
	 * @param string|null $come_from if specified, the data will be displayed only if we come from the given foreign key
	 * @param integer|null $specific_sub_model if specified, the data will be displayed only for one sub model instance
	 * @return \datamodel\TableDataDisplay this
	 */
	public function &addDataDisplay($data_display, $come_from = null, $specific_sub_model = null) {
		$data_display->setTableAndCategory(\DataModel::get()->internalGetTable($this->table_name), $this->category);
		if (!isset($this->data_display[$come_from]))
			$this->data_display[$come_from] = array();
		if (!isset($this->data_display[$come_from][$specific_sub_model]))
			$this->data_display[$come_from][$specific_sub_model] = array();
		array_push($this->data_display[$come_from][$specific_sub_model], $data_display);
		return $this;
	}
	
	/**
	 * Allow to display the data of this table if we join to the given foreign key
	 * @param string $foreign_key the foreign key
	 * @param array|null $needed_columns_from_foreign_table if specified, together with check_function, this is the list of columns we need to determine if the join is allowed or not
	 * @param function $check_function function determining if the join is allowed
	 * @return \datamodel\TableDataDisplay this
	 */
	public function &addJoinFrom($foreign_key, $needed_columns_from_foreign_table = null, $check_function = null) {
		if ($this->join_from === null) return $this;
		$this->join_from[$foreign_key] = array($needed_columns_from_foreign_table, $check_function);
		return $this;
	}
	/**
	 * Allow to join using any foreign key
	 * @return \datamodel\TableDataDisplay this
	 */
	public function &addJoinFromAll() {
		$this->join_from = null;
		return $this;
	}
	
	/**
	 * Allow to follow the given foreign key to find other data to be displayed
	 * @param string $foreign_key_name name of the foreign key
	 * @return \datamodel\TableDataDisplay this
	 */
	public function &addFollow($foreign_key_name) {
		if ($this->follow === true) return $this;
		array_push($this->follow, $foreign_key_name);
		return $this;
	}
	
	/**
	 * Allow to follow any foreign key of this table to find other data to be displayed
	 * @return \datamodel\TableDataDisplay this
	 */
	public function &addFollowAll() {
		$this->follow = true;
		return $this;
	}
	
	/**
	 * Allow to join to the primary key of this table
	 * @return \datamodel\TableDataDisplay this
	 */
	public function &addJoinToPrimary() {
		$this->join = true;
		return $this;
	}
	
	/**
	 * Return the list of data which can be displayed
	 * @param string $come_from if we reached this table through a foreign key of this table
	 * @param integer|null $sub_model sub model
	 * @return DataDisplay[] the data
	 */
	public function getDataDisplay($come_from, $sub_model) {
		$t = $this->getTable();
		if ($t->isCustomized()) $t->ensureCustomizationLoaded($sub_model);
		$data = array();
		if (isset($this->data_display[$come_from])) {
			if (isset($this->data_display[$come_from][null]))
				$data = array_merge($data, $this->data_display[$come_from][null]);
			if ($sub_model <> null && isset($this->data_display[$come_from][$sub_model]))
				$data = array_merge($data, $this->data_display[$come_from][$sub_model]);
			return $data;
		}
		if ($come_from == null) return array();
		if (isset($this->data_display[null])) {
			if (isset($this->data_display[null][null]))
				$data = array_merge($data, $this->data_display[null][null]);
			if ($sub_model <> null && isset($this->data_display[null][$sub_model]))
				$data = array_merge($data, $this->data_display[null][$sub_model]);
			return $data;
		}
		return array();
	}
	
	/**
	 * Get a data by its name
	 * @param string $name name of the data to search
	 * @param string $come_from if we reached this table through a foreign key of this table
	 * @param integer|null $sub_model sub model
	 * @return \datamodel\DataDisplay|NULL the data, or null if not found
	 */
	public function getDataDisplayByName($name, $come_from, $sub_model) {
		$data = $this->getDataDisplay($come_from, $sub_model);
		foreach ($data as $d)
			if ($d->getDisplayName() == $name) return $d;
		return null;
	}
	
	/**
	 * Check if we can join this table using the given foreign key
	 * @param string $foreign_key foreign key
	 * @return boolean true if allowed
	 */
	public function canJoinFrom($foreign_key) {
		if ($this->join_from === null) return true;
		return isset($this->join_from[$foreign_key]);
	}
	/**
	 * In case the join MAY be allowed, get the list of columns we need the values to determine if finally the join is allowed 
	 * @param string $foreign_key foreign key
	 * @return string[]|null columns names of null if we don't need any data
	 */
	public function getNeededColumnsToJoinFrom($foreign_key) {
		if ($this->join_from === null) return null;
		return $this->join_from[$foreign_key][0];
	}
	/**
	 * Check if finally we can join, in case getNeededColumnsToJoinFrom does not return null
	 * @param string $foreign_key foreign key
	 * @param array $columns values of the columns
	 * @return boolean true if join is allowed
	 */
	public function checkJoinFrom($foreign_key, $columns) {
		if ($this->join_from === null) return true;
		return $this->join_from[$foreign_key][1]($columns);
	}
	
	/**
	 * Check is we can follow the given foreign key
	 * @param string $foreign_key_name foreign key name
	 * @return boolean true if allowed
	 */
	public function canFollow($foreign_key_name) {
		if ($this->follow === true) return true;
		return in_array($foreign_key_name, $this->follow);
	}
	
	/**
	 * Check if we can join to this table using its primary key
	 * @return boolean true if allowed
	 */
	public function canJoinToPrimary() {
		return $this->join;
	}

	/** Give the opportunity to modify/add some data before to create a row in this table
	 * @param integer|array $key the key
	 * @param integer|null $sub_model sub model
	 * @param \TablesToUpdate $table_fields values to insert/update in the database
	 * @param string $come_from from which foreign key we come from
	 */
	public function finalizeCreateData($key, $sub_model, &$table_fields, $come_from) {}
	
}

} // namespace datamodel

?>