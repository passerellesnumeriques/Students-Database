<?php 
interface NewsPlugin {
	
	/** List of supported sections
	 * @return array list of sections code
	 */
	public function getSections();
	
}

interface NewsSection {
	
	/**
	 * Section name
	 * @return string section name
	 */
	public function getName();
	
	/**
	 * Get how the current user can access to the news of this section
	 * @return number 0 if no access, 1 for read-only access, 2 for read and write access
	 */
	public function getAccessRight();

	/**
	 * Name to display to the user
	 * @return string the name to display on the UI
	 */
	public function getDisplayName();
	
	/** Icon URL for the section
	 * @return string the url of an icon 32x32
	 */
	public function getIcon();
	
	/**
	 * List categories supported by this section
	 * @return array categories
	 */
	public function getCategories();

}

interface NewsCategory {
	
	/**
	 * Category name
	 * @return string category name
	 */
	public function getName();

	/**
	 * Get how the current user can access to the news of this category
	 * @return number 0 if no access, 1 for read-only access, 2 for read and write access
	 */
	public function getAccessRight();

	/**
	 * Name to display to the user
	 * @return string the name to display on the UI
	 */
	public function getDisplayName();
	
	/** Icon URL for the section
	 * @return string the url of an icon 16x16
	 */
	public function getIcon();
	
}

	
?>