<?php 
/**
 * Abstract class to define a service.
 */
abstract class Service {

	/**
	 * Generate general documentation (description) of this service.
	 */
	public abstract function documentation();
	/**
	 * Return an array: to access to this service, the user must comply with at least one of the element of this array.
	 * Each element can be either <ul>
	 * <li>the name of a right: the user needs this right</li>
	 * <li>a list of rights' name: the user needs all those rights</li>
	 * <li>a function: if it returns true, the user can access to the service</li>
	 * </ul>
	 */
	public abstract function get_required_rights();
	/**
	 * Generate the documentation of the expected input of this service.
	 */
	public abstract function input_documentation();
	/**
	 * Return the format of the output of this service (by default: text/json) 
	 * @return string
	 */
	public function get_output_format() { return "text/json"; }
	/**
	 * Generate the documentation of the output of this service.
	 */
	public abstract function output_documentation();
	/**
	 * Execute the service
	 * @param Component $component the component this service belongs to
	 */
	public abstract function execute(&$component);
	
}
?>