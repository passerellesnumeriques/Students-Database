<?php
class service_IS_get_partners_array extends Service{
	public function get_required_rights(){return array();}
	public function input_documentation(){
		?>
		<ul>
			<li>
				<code>{array} partners_id</code>
			</li>
		</ul>
		<?php
	}
	public function output_documentation(){}//TODO
	public function documentation(){}//TODO
	public function execute(&$component,$input){
		if(count($input["partners_id"]) > 0){
			$q = SQLQuery::create()->select("Organization")
						->field("id")
						->field("name")
						->whereIn("Organization","id",$input["partners_id"])
						->execute();
						
			if(PNApplication::has_errors() || !isset($q[0]["id"])) echo "false";
			else {
				echo "[";
				$first = true;
				foreach($q as $partner){
					if(!$first) echo ", ";
					$first = false;
					echo "{organization:".json_encode($partner["id"]).", ";
					echo "organization_name:".json_encode($partner["name"]).", ";
					echo "host:".json_encode(null).", ";
					echo "host_address:".json_encode(null).", ";
					echo "contact_points_selected:[]}";
				}
				echo "]";
			}
		} else echo "[]";
	}
}
?>