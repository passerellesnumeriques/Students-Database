<?php 
namespace datamodel {

/**
 * Category of DataDisplay. Used to group DataDisplay.
 */
class DataDisplayCategory {
	
	/** @var string name of the category */
	private $name;
	/** @var string|null URL of the icon */
	private $icon;
	/** @var array list of tables using this category */
	private $tables = array();
	
	public function __construct($name, $icon = null) {
		$this->name = $name;
		$this->icon = $icon;
	}
	
	public function getName() { return $this->name; }
	public function getIcon() { return $this->icon; }
	
	public function addTable($table_name) {
		array_push($this->tables, $table_name);
	}
	
	public function getTables() {
		return $this->tables;
	}
	
}

}
?>