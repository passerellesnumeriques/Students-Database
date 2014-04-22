<?php
class service_exam_get_center_partners_array extends Service{
	public function get_required_rights(){return array("see_exam_center_detail");}
	public function input_documentation(){
		?>
		<ul>
			<li>
				<code>{array} partners_id</code>
			</li>
		</ul>
		<?php
	}
	public function output_documentation(){
		?>
		Array of partners objects. Each object contains:
		<ul>
			<li><code>organization</code> {number} the organization id</li>
			<li><code>organization_name</code> {string} the orgnaization name</li>
			<li><code>host</code> {boolean} true if this partner is the host</li>
			<li><code>host_address</code> {number} not null in the case of host == true; in that case, the value is the id of the selected partner postal address</li>
			<li><code>contact_points_selected</code> {array} ids of the contact points selected from this partner
		</ul>
		<?php
	}
	public function documentation(){
		echo "Get the array used in select_address.js";
	}
	public function execute(&$component,$input){
		if(count($input["partners_id"]) > 0){
			$names = PNApplication::$instance->contact->getOrganizationsNames($input["partners_id"]);	
			if(PNApplication::has_errors() || !isset($names[0]["id"])) echo "false";
			else {
				echo "[";
				$first = true;
				foreach($names as $partner){
					if(!$first) echo ", ";
					$first = false;
					echo "{organization:".json_encode($partner["id"]).", ";
					echo "organization_name:".json_encode($partner["name"]).", ";
					echo "host:null, ";
					echo "host_address:null, ";
					echo "contact_points_selected:[]}";
				}
				echo "]";
			}
		} else echo "[]";
	}
}
?>