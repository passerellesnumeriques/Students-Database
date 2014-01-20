<?php
namespace datamodel {
	
abstract class DataDisplayHandler {
	/** @var \datamodel\Table table this handler belongs to (set by the Table, should not be modified) */
	public $table;
	/** @var string */
	public $category;
	/** @var string */
	public $display_name;
	
	public function __construct($category, $display_name) {
		$this->category = $category;
		$this->display_name = $display_name;
	}
	
	public function setTable($table) { $this->table = $table; }

	/**
	 * @return DataDisplay[]
	 */
	public abstract function getDisplayableData();
	/**
	 * @return boolean true if we can follow the foreign key from this table to find other data
	 */
	public function canFollow($foreign_key_name) { return true; }
	/**
	 * @return boolean true if we can follow foreign keys from other tables to this table to find other data
	 */
	public function canJoin() { return true; }
	
	/**
	 * Create a new entry in this table
	 * @param array $data array of array("name"=>the_data_name,"value"=>the_value)
	 */
	public abstract function createEntry($data, $sub_model, $preset_fields);
	
}


class FieldsDisplayHandler extends DataDisplayHandler {

	public $fields;
	public $readonly;

	public function __construct($category, $display_name, $fields, $readonly = array()) {
		parent::__construct($category, $display_name);
		$this->fields = $fields;
		$this->readonly = $readonly;
	}

	public function getDisplayableData() {
		$list = array();
		foreach ($this->fields as $f=>$d) {
			$col = @$this->table->getColumn($f);
			if ($col <> null)
				array_push($list, new SimpleDataDisplay($this, $f, $d, in_array($f, $this->readonly)));
		}
		return $list;
	}

	public function createEntry($data, $sub_model, $preset_fields) {
		$list = array();
		foreach ($data as $d) {
			$found = false;
			$err = false;
			foreach ($this->fields as $f=>$disp)
				if ($disp == $d["name"]) {
				$found = true;
				try {
					$col = @$this->table->getColumn($f);
					if ($col <> null)
						$list[$f] = $col->validate($d["value"]);
				} catch (\Exception $e) {
					\PNApplication::error("Invalid data ".$disp->getDisplayName().": ".$d["value"]);
					$err = true;
				}
				break;
			}
			if (!$found) {
				\PNApplication::error("Unknown data ".$d["name"]." in DataDisplayHandler of table ".$this->table->getName());
				$err = true;
			}
			if ($err) return null;
		}
		if ($preset_fields <> null)
			foreach ($preset_fields as $name=>$value) {
			$col = @$this->table->getColumn($name);
			if ($col <> null)
				$list[$name] = $col->validate($value);
		}
		$sub_models = null;
		if ($sub_model <> null) {
			$sm = $this->table->getModel()->getParentTable();
			$sub_models = array($sm=>$sub_model);
		}
		return \SQLQuery::create()->set_sub_models($sub_models)->insert($this->table->getName(), $list);
	}

}

class MultipleDisplayHandler extends DataDisplayHandler {

	public $content;
	public $stop_follow_foreign;
	public $stop_find_foreign;
	
	public function __construct($category, $display_name, $content, $stop_follow_foreign = array(), $stop_find_foreign = false) {
		parent::__construct($category, $display_name);
		$this->content = $content;
		$this->stop_follow_foreign = $stop_follow_foreign;
		$this->stop_find_foreign = $stop_find_foreign;
	}

	public function setTable($table) {
		parent::setTable($table);
		foreach ($this->content as $c)
			if ($c instanceof DataDisplayHandler)
			$c->setTable($table);
		else
			$c->handler = $this;
	}

	public function getDisplayableData() {
		$list = array();
		foreach ($this->content as $c)
			if ($c instanceof DataDisplayHandler)
			$list = array_merge($list, $c->getDisplayableData());
		else
			array_push($list, $c);
		return $list;
	}

	public function createEntry($data, $sub_model, $preset_fields) {
		// TODO ?
	}

	public function canFollow($foreign_key_name) {
		if (in_array($foreign_key_name, $this->stop_follow_foreign)) return false;
		return true;
	}
	public function canJoin() { return $this->stop_find_foreign; }
}

class ChoiceFieldDisplayHandler extends DataDisplayHandler {

	public $foreign_key_name;
	public $foreign_column_name;

	public function __construct($category, $display_name, $foreign_key_name, $foreign_column_name) {
		parent::__construct($category, $display_name);
		$this->foreign_key_name = $foreign_key_name;
		$this->foreign_column_name = $foreign_column_name;
	}

	public function getDisplayableData() {
		return array(
				new ChoiceDataDisplay($this, $this->foreign_key_name, $this->foreign_column_name, $this->display_name)
		);
	}

	public function createEntry($data, $sub_model, $preset_fields) {
		// TODO ?
	}

}

class StopDataDisplayHandler extends DataDisplayHandler {

	public function __construct() {
		parent::__construct(null, "");
	}

	public function getDisplayableData() { return array(); }
	public function createEntry($data, $sub_model, $preset_fields) {}
	
	public function canFollow($foreign_key_name) { return false; }
	public function canJoin() { return false; }

}

} // namespace datamodel

?>