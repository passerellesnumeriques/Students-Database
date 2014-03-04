<?php
class service_get_address extends Service{
	public function get_required_rights(){return array();}
	public function input_documentation(){
		?>
		Can be<ul>
		<li><code>id</code> the postal address ID</li>
		<li>Or<code>addresses</code> array of postal address IDS</li>
		</ul>
		<?php
	}
	public function output_documentation(){ echo "a PostalAddress object | array of PostalAddress objects"; }
	public function documentation() { echo "Return a PostalAddress object given its ID | array of PostalAddress objects"; }
	public function execute(&$component,$input){
		require_once("component/contact/ContactJSON.inc");
		if(isset($input["id"])){
			echo ContactJSON::PostalAddressFromID($input["id"]);
		} else if(isset($input["addresses"])){
			$first = true;
			echo "[";
			foreach($input["addresses"] as $id){
				if(!$first)
					echo ", ";
				$first = false;
				echo ContactJSON::PostalAddressFromID($id);
			}
			echo ']';
		} else echo "false";
	}
}	
?>