<?php
/* Lab 3 (plugin) */
class foobar extends Component{
	
	public function getPluginImplementations() {
		require_once("FoobarLabComponent.inc");
		return array(
			new FoobarLabComponent()
		);
	}
	
}
?>