<?php
class service_get_address extends Service{
	public function get_required_rights(){return array();}
	public function input_documentation(){ echo "<code>id</code> the postal address ID"; }
	public function output_documentation(){ echo "a PostalAddress object"; }
	public function documentation() { echo "Return a PostalAddress object given its ID"; }
	public function execute(&$component,$input){
		if(isset($input["id"])){
			require_once("component/contact/ContactJSON.inc");
			echo ContactJSON::PostalAddressFromID($input["id"]);
		} else echo "false";
	}
}	
?>