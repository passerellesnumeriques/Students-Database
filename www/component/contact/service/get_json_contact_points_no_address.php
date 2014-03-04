<?php
class service_get_json_contact_points_no_address extends Service{
	public function get_required_rights(){return array();}
	public function input_documentation(){
		?>
		<ul>
			<li>
				<code>organizations</code> can be an array(id1, id2, id3) or only one id
			</li>
			<li><code>contacts_details</code> boolean (optional), get also the contacts details for each contact points (id, type, sub_type...)
		</ul>
		<?php
	}
	public function output_documentation(){}//TODO
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