<?php
class service_get_json_contact_points_no_address extends Service{
	public function get_required_rights(){return array();}
	public function input_documentation(){
		?>
		<ul>
			<li>
				<code>partners_id</code>can be an array(id1, id2, id3) or only on id
			</li>
		</ul>
		<?php
	}
	public function output_documentation(){}//TODO
	public function documentation(){}//TODO
	public function execute(&$component,$input){
		require_once("get_json_contact_points_no_address.inc");
		echo get_json_contact_points_no_address($input["partners_id"]);
	}
}
?>