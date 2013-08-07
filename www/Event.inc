<?php
/** Basic class to attach listeners to an event, and call each listener when the event is fired. */ 
class Event {
	
	private $listeners = array();
	
	public function listen($instance, $method) {
		array_push($this->listeners, array($instance, $method));
	}
	
	public function fire($parameters = null) {
		foreach ($this->listeners as $listener) {
			call_user_func($listener, $parameters);
		}
	}
	
}
?>