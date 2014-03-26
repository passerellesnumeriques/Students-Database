<?php 
require_once("selection_page.inc");
class page_test_functionalities extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(){
// 		$id = SQLQuery::create()
// 			->insert("InformationSession",array(
// 			"date" => null,
// 			"geographic_area" => 1,
// 			"name" => "toto",
// 			"number_boys_expected" => 0,
// 			"number_girls_expected" => 0,
// 					"number_boys_real" => 0,
// 					"number_girls_real" => 0,
// 		));
// 			SQLQuery::create()
// 			->insertMultiple("InformationSessionPartner",array(
// 				array("information_session" => $id,
// 				"organization" => 1,
// 				"host" => false,
// 				"host_address" => null
// 				),
// 				array("information_session" => $id,
// 						"organization" => 2,
// 						"host" => false,
// 						"host_address" => null
// 				),
// 			));
			
// 			SQLQuery::create()->insert("InformationSessionContactPoint", array(
// 				"information_session" => $id,
// 				"organization" => 1,
// 				"people" => 91
// 			));
			
// 			echo 'done';
		
		?>
		<div id = "test"></div>
		<script type="text/javascript">
// 		require("select_area_and_matching_organizations.js",function(){
// 			new select_area_and_matching_organizations("test",5);
// 		});

// 		require("pop_select_area_and_partner.js",function(){
// 			new pop_select_area_and_partner({geographic_area_id:5});
// 		});

		require("create_partner_row.js",function(){
			var partner_data = {id:1, name:"Passerelles Numeriques"};
			var all = [{people_id:"91",people_last_name:"Huard",people_first_name:"Helene",people_designation:"Chef"},{people_id:"92",people_last_name:"toto",people_first_name:"toto",people_designation:"Boss"}];
			new create_partner_row("test",partner_data,[],all,true);
		});

		<?php 
// 		require_once '/../SelectionJSON.inc';
// 		echo "var IS_data = ".SelectionJSON::InformationSessionFromID(1).";";
// 		require_once("component/contact/service/get_json_contact_points_no_address.inc");
// 		echo "var cp = ".get_json_contact_points_no_address(array(1,2),false).";";
		?>
// 		require("select_address_2.js",function(){
// 			new select_address_2("test",IS_data, cp, true);
// 		});

// 		require("select_other_partners.js",function(){
// 			new select_other_partners("test",IS_data.partners,cp,true);
// 		});
		</script>
		<?php
	}
	
}