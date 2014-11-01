<?php 
interface SearchPlugin {

	public function getId();
	public function getName();
	
	public function getTitle($nb);
	public function hasCategories();
	public function sortByCategory($results);
	
	public function genericSearch($q);
	
	public function generateResultRow($row);
	
	public function getResultIcon($row);
	public function getResultTitle($row);
	public function getResultUrl($row);
	
}
?>