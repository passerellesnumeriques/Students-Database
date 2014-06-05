<?php
class service_get_json_contact_points_no_address extends Service{
	public function getRequiredRights(){return array();}
	public function documentation(){
		echo "Return contact points with their contacts, but not their addresses, from one or several organizations";
	}
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
	public function outputDocumentation(){
		?>
		List of:<ul>
			<li><code>organization</code>: organization ID</li>
			<li><code>organization_name</code>: name of the organization</li>
			<li><code>contact_points</code>: array of<ul>
				<li><code>people_id</code>: People ID</li>
				<li><code>people_last_name</code>: Last Name</li>
				<li><code>people_first_name</code>: First Name</li>
				<li><code>people_designation</code>: Designation in the organization</li>
				<li><code>contacts</code>: list of Contact JSON objects</li>
			</ul></li>
		</ul>
		<?php 
	}
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