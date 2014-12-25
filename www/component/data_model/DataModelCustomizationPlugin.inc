<?php
/**
 * Plugin to declare a type of data we can add in a customizable table
 */ 
interface DataModelCustomizationPlugin {
	
	/** Identifier
	 * @return string identifier
	 */
	public function getId();
	/** Name of the type
	 * @return string the name
	 */
	public function getDisplayName();
	
	/** 
	 * Table to which this table refers to. In the customizable table, when a data of this type will be added, it will be represented as a foreign key to this table.
	 * @return string foreign table
	 */
	public function getForeignTable();
	
	/**
	 * Gives the path of the file containing the declaration of classes used for the DataDisplay
	 * @return string|null path of the file to include
	 */
	public function getDataDisplayFileToInclude();
	/**
	 * Return the DataDisplay
	 * @param string $col_name name of the column in the customized table
	 * @param string $display_name display name configured by the user on the customized table
	 * @param integer|null $sub_model sub model
	 * @param boolean $can_be_null indicates if the data is mandatory or not
	 * @return DataDisplay the data display
	 */
	public function getDataDisplay($col_name, $display_name, $sub_model, $can_be_null);
	
}
?>