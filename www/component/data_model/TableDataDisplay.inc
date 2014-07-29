<?php
namespace datamodel {

class TableDataDisplay {
	
	private $table_name;
	private $category;
	private $data_display = array();
	private $join_from = array();
	private $follow = array();
	private $join = false;
	
	public function __construct($table_name, $category) {
		$this->table_name = $table_name;
		$this->category = $category;
	}
	
	public function getTableName() { return $this->table_name; }
	public function getTable() { return \DataModel::get()->getTable($this->table_name); }
	public function getCategory() { return $this->category; }
	
	public function &addDataDisplay($data_display, $come_from = null, $specific_sub_model = null) {
		$data_display->setTableDataDisplay($this);
		if (!isset($this->data_display[$come_from]))
			$this->data_display[$come_from] = array();
		if (!isset($this->data_display[$come_from][$specific_sub_model]))
			$this->data_display[$come_from][$specific_sub_model] = array();
		array_push($this->data_display[$come_from][$specific_sub_model], $data_display);
		return $this;
	}
	
	public function &addJoinFrom($foreign_key, $needed_columns_from_foreign_table = null, $check_function = null) {
		if ($this->join_from === null) return $this;
		$this->join_from[$foreign_key] = array($needed_columns_from_foreign_table, $check_function);
		return $this;
	}
	public function &addJoinFromAll() {
		$this->join_from = null;
		return $this;
	}
	
	public function &addFollow($foreign_key_name) {
		if ($this->follow === true) return $this;
		array_push($this->follow, $foreign_key_name);
		return $this;
	}
	
	public function &addFollowAll() {
		$this->follow = true;
		return $this;
	}
	
	public function &addJoinToPrimary() {
		$this->join = true;
		return $this;
	}
	
	public function getDataDisplay($come_from, $sub_model) {
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
	
	public function getDataDisplayByName($name, $come_from, $sub_model) {
		$data = $this->getDataDisplay($come_from, $sub_model);
		foreach ($data as $d)
			if ($d->getDisplayName() == $name) return $d;
		return null;
	}
	
	public function canJoinFrom($foreign_key) {
		if ($this->join_from === null) return true;
		return isset($this->join_from[$foreign_key]);
	}
	public function getNeededColumnsToJoinFrom($foreign_key) {
		if ($this->join_from === null) return null;
		return $this->join_from[$foreign_key][0];
	}
	public function checkJoinFrom($foreign_key, $columns) {
		if ($this->join_from === null) return true;
		return $this->join_from[$foreign_key][1]($columns);
	}
	
	public function canFollow($foreign_key_name) {
		if ($this->follow === true) return true;
		return in_array($foreign_key_name, $this->follow);
	}
	
	public function canJoinToPrimary() {
		return $this->join;
	}

	/**
	 * @param \TablesToUpdate $table_fields
	 */
	public function finalizeCreateData($key, $sub_model, &$table_fields, $come_from) {}
	
}

} // namespace datamodel

?>