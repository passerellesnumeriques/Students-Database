<?php
class service_get_json_contact_points_no_address extends Service{
	public function getRequiredRights(){return array();}
	public function inputDocumentation(){
		?>
		<ul>
			<li>
				<code>organizations</code> can be an array(id1, id2, id3) or only one id
			</li>
			<li><code>contacts_details</code> boolean (optional), get also the contacts details for each contact points (id, type, sub_type...)
		</ul>
		<?php
	}
	public function outputDocumentation(){}//TODO
	public function documentation(){}//TODO
	public function execute(&$component,$input){
		require_once("get_json_contact_points_no_address.inc");
		if(isset($input["organizations"])){
			$contacts_details = @$input["contacts_details"];
			echo get_json_contact_points_no_address($input["organizations"], $contacts_details);
		} else 
			echo "false";
	}
}
?>