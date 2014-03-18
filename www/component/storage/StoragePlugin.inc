<?php 
interface StoragePlugin {
	
	public function getType();
	public function canRead($id);
	public function canWrite($id);
	
}
?>