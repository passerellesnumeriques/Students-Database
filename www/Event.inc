<?php
/** Basic class to attach listeners to an event, and call each listener when the event is fired. */ 
class Event {
	
	private $listeners = array();
	
	public function listen($instance, $method) {
		array_push($this->listeners, array($instance, $method));
	}
	public function listenFunction($fct) {
		array_push($this->listeners, $fct);
	}
	
	public function fire($parameters = array()) {
		foreach ($this->listeners as $listener) {
			call_user_func_array($listener, $parameters);
		}
	}
	
}
?>