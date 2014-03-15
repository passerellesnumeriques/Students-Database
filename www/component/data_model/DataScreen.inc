<?php 
namespace datamodel {
	
	abstract class DataScreen {
		
		public abstract function getTables();
		
		public abstract function getIcon();
		public abstract function getName();
		
		public abstract function generate(&$page, $paths, $values, $prefilled_values, $structure_name);
		
		public abstract function createData($paths);
		
	}
	
}
?>