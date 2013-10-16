<?php
class contact extends Component{
	
	public function init() {
		PNApplication::$instance->widgets->register_table_typed_field("Postal_address", "field_address");
	}
	
}
?>