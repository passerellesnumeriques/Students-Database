<?php
namespace datamodel {

/**
 * Provides information on how to display data for a Table, mainly by providing a list of DataDisplay.
 */
abstract class DataDisplayHandler {
	/** @var \datamodel\Table table this handler belongs to (set by the Table, should not be modified) */
	private $table;
	/** @var string category of data (i.e. 'Personal Information', 'Student', ...) */
	private $category;
	
	/**
	 * @param string $category category of data (i.e. 'Personal Information', 'Student', ...)
	 */
	public function __construct($category) {
		$this->category = $category;
	}
	
	public final function getTable() { return $this->table; }
	public final function getCategory() { return $this->category; }
	
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
	
}


/**
 * Most basic implementation of DataDisplayHandler: giving a list of columns which are data that can be displayed, it will create a SimpleDataDisplay for each column.
 */
class FieldsDisplayHandler extends DataDisplayHandler {

	/** @var array list of columns names */
	private $fields;
	/** @var array list of columns which are read-only */
	private $readonly;

	/**
	 * @param string $category category name
	 * @param array $fields list of columns' names
	 * @param array $readonly list of columns which cannot be edited
	 */
	public function __construct($category, $fields, $readonly = array()) {
		parent::__construct($category);
		$this->fields = $fields;
		$this->readonly = $readonly;
	}

	public function getDisplayableData() {
		$list = array();
		foreach ($this->fields as $f=>$d) {
			$col = @$this->getTable()->getColumn($f);
			if ($col <> null)
				array_push($list, new SimpleDataDisplay($this, $f, $d, in_array($f, $this->readonly)));
		}
		return $list;
	}

}


/**
 * Allow to mix different way of displaying data: mix of different DataDisplayHandler and different DataDisplay
 */
class MultipleDisplayHandler extends DataDisplayHandler {

	/** @var array list of things to display: DataDisplayHandler or DataDisplay */
	private $content;
	/** @var array list of foreign keys that should not be used to find new data when going through the data model */
	private $stop_follow_foreign;
	/** @var boolean value that will be returned by canJoin */ 
	private $stop_find_foreign;
	
	/**
	 * @param string $category
	 * @param array $content list of DataDisplayHandler and/or DataDisplay
	 * @param array $stop_follow_foreign list of foreign keys that should not be used to find new data when going through the data model
	 * @param string $stop_find_foreign value that will be returned by canJoin
	 */
	public function __construct($category, $content, $stop_follow_foreign = array(), $stop_find_foreign = false) {
		parent::__construct($category);
		$this->content = $content;
		foreach ($this->content as $c) if ($c instanceof DataDisplay) $c->setHandler($this);
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

	public function canFollow($foreign_key_name) {
		if (in_array($foreign_key_name, $this->stop_follow_foreign)) return false;
		return true;
	}
	public function canJoin() { return $this->stop_find_foreign; }
}


/**
 * For tables containing only one DataDisplay: this handler wraps it
 */
class SingleDataDisplayHandler extends DataDisplayHandler {

	private $data_display;
	
	public function __construct($category, $data_display) {
		parent::__construct($category);
		$this->data_display = $data_display;
		$this->data_display->setHandler($this);
	}

	public function getDisplayableData() {
		return $this->data_display;
	}

}

/**
 * When no data should be displayed from the table, and we should not use the table to reach any other data
 */
class StopDataDisplayHandler extends DataDisplayHandler {

	public function __construct() {
		parent::__construct(null);
	}

	public function getDisplayableData() { return array(); }
	
	public function canFollow($foreign_key_name) { return false; }
	public function canJoin() { return false; }

}

} // namespace datamodel

?>