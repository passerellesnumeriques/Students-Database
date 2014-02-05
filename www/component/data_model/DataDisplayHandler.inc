<?php
namespace datamodel {

/**
 * Provides information on how to display data for a Table, mainly by providing a list of DataDisplay.
 */
abstract class DataDisplayHandler {
	/** @var \datamodel\Table table this handler belongs to (set by the Table, should not be modified) */
	public $table;
	/** @var string category of data (i.e. 'Personal Information', 'Student', ...) */
	public $category;
	/** @var string a title for the table */
	public $display_name;
	
	/**
	 * @param string $category category of data (i.e. 'Personal Information', 'Student', ...)
	 * @param string $display_name a title for the table
	 */
	public function __construct($category, $display_name) {
		$this->category = $category;
		$this->display_name = $display_name;
	}
	
	/**
	 * Called by the table itself, should not be used. 
	 * @param datamodel\Table $table the table this handler belongs to
	 */
	public function setTable($table) { $this->table = $table; }

	/** Return the list of data that can be displayed
	 * @return DataDisplay[] list of data to display
	 */
	public abstract function getDisplayableData();
	/** Indicates if we should follow the given foreign key, when going through the data model to find data to display
	 * @param string $foreign_key_name the column's name of the foreign key
	 * @return boolean true if we can follow the foreign key from this table to find other data
	 */
	public function canFollow($foreign_key_name) { return true; }
	/** Indicates if we should go to other tables containing foreign key to this table, when going through the data model to find data to display
	 * @return boolean true if we can follow foreign keys from other tables to this table to find other data
	 */
	public function canJoin() { return true; }
	
	/**
	 * Create a new entry in this table
	 * @param array $data array of array("name"=>the_data_name,"value"=>the_value)
	 * @param number|null $sub_model sub model instance or null
	 * @param array $preset_fields associative array (name=&gt;value)
	 */
	public abstract function createEntry($data, $sub_model, $preset_fields);
	
}


/**
 * Most basic implementation of DataDisplayHandler: giving a list of columns which are data that can be displayed, it will create a SimpleDataDisplay for each column.
 */
class FieldsDisplayHandler extends DataDisplayHandler {

	/** @var array list of columns names */
	public $fields;
	/** @var array list of columns which are read-only */
	public $readonly;

	/**
	 * @param string $category category name
	 * @param string $display_name title
	 * @param array $fields list of columns' names
	 * @param array $readonly list of columns which cannot be edited
	 */
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
		return \SQLQuery::create()->selectSubModels($sub_models)->insert($this->table->getName(), $list);
	}

}


/**
 * Allow to mix different way of displaying data: mix of different DataDisplayHandler and different DataDisplay
 */
class MultipleDisplayHandler extends DataDisplayHandler {

	/** @var array list of things to display: DataDisplayHandler or DataDisplay */
	public $content;
	/** @var array list of foreign keys that should not be used to find new data when going through the data model */
	public $stop_follow_foreign;
	/** @var boolean value that will be returned by canJoin */ 
	public $stop_find_foreign;
	
	/**
	 * @param string $category
	 * @param string $display_name
	 * @param array $content list of DataDisplayHandler and/or DataDisplay
	 * @param array $stop_follow_foreign list of foreign keys that should not be used to find new data when going through the data model
	 * @param string $stop_find_foreign value that will be returned by canJoin
	 */
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


/**
 * For tables containing only one ChoiceDataDisplay: this handler wraps a single ChoiceDataDisplay
 */
class ChoiceFieldDisplayHandler extends DataDisplayHandler {

	/** @var string name of the column containing the foreign key */
	public $foreign_key_name;
	/** @var string name of the column to display in the foreign table */
	public $foreign_column_name;

	/**
	 * @param string $category
	 * @param string $display_name
	 * @param string $foreign_key_name name of the column containing the foreign key
	 * @param string $foreign_column_name name of the column to display in the foreign table
	 */
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

/**
 * When no data should be displayed from the table, and we should not use the table to reach any other data
 */
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