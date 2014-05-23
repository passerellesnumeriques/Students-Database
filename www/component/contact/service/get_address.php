<?php
class service_get_address extends Service{
	public function getRequiredRights(){return array();}
	public function inputDocumentation(){
		?>
		Can be<ul>
		<li><code>id</code> the postal address ID</li>
		<li>Or<code>addresses</code> array of postal address IDS</li>
		</ul>
		<?php
	}
	public function outputDocumentation(){ echo "a PostalAddress object | array of PostalAddress objects"; }
	public function documentation() { echo "Return a PostalAddress object given its ID | array of PostalAddress objects"; }
	public function execute(&$component,$input){
		require_once("component/contact/ContactJSON.inc");
		if(isset($input["id"])){
			$res = ContactJSON::PostalAddressFromID($input["id"]);
			$res = $res != null ? $res : "null";
			echo $res;
		} else if(isset($input["addresses"])){
			$first = true;
			echo "[";
			foreach($input["addresses"] as $id){
				if(!$first)
					echo ", ";
				$first = false;
				$res = ContactJSON::PostalAddressFromID($id);
				$res = $res != null ? $res : "null";
				echo $res;
			}
			echo ']';
		} else echo "false";
	}
}	
?>