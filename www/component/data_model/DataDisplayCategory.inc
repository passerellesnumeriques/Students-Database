<?php 
namespace datamodel {
	
class DataDisplayCategory {
	
	private $name;
	private $icon;
	private $link;
	private $tables = array();
	
	public function __construct($name, $icon = null, $link = null) {
		$this->name = $name;
		$this->icon = $icon;
		$this->link = $link;
	}
	
	public function getName() { return $this->name; }
	public function getIcon() { return $this->icon; }
	public function getLink() { return $this->link; }
	
	public function addTable($table_name) {
		array_push($this->tables, $table_name);
	}
	
}

}
?>