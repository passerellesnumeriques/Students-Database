<?php 
require_once("selection_page.inc");
class page_IS_main_page extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(&$page){
	?>
		<div>
		TODO main page
		</div>
		
	<?php
		echo "<br/>";
		$data =  PNApplication::$instance->selection->get_json_IS_data(1,true);
		echo "IS_data = ".$data["data"]."<br/><br/>";
		echo "IS_partners = ";
		$first = true;
		foreach($data["partners"] as $d){
			if(!$first) echo ", ";
			echo $d;
			$first = false;
		}
		echo "<br/><br/>";
		require_once("component/contact/service/get_json_contact_points_no_address.inc");
		echo "IS_contacts_points = ".get_json_contact_points_no_address($data["partners"]);
		
	}
	
}