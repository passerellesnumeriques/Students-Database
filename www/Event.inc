<?php
/** Basic class to attach listeners to an event, and call each listener when the event is fired. */ 
class Event {
	
	/** @var array list of listeners */
	private $listeners = array();
	
	/** add a listener to this event
	 * @param object|null $instance the instance of the class where the method is, or null for a static method
	 * @param string $method the method name
	 */
	public function listen($instance, $method) {
		array_push($this->listeners, array($instance, $method));
	}
	/** Add a listener to this event
	 * @param function $fct anonymous function
	 */
	public function listenFunction($fct) {
		array_push($this->listeners, $fct);
	}
	
	/**
	 * Fire this event, calling all listeners with the given parameters
	 * @param array $parameters list of parameters to pass to the listeners
	 */
	public function fire($parameters = array()) {
		foreach ($this->listeners as $listener) {
			call_user_func_array($listener, $parameters);
		}
	}
	
	public function hasListeners() { return count($this->listeners) > 0; }
	
}
?>