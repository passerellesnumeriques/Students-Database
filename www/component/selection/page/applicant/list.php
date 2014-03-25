<?php 
require_once("component/selection/page/selection_page.inc");
class page_applicant_list extends selection_page {
	
	public function get_required_rights() { return array("see_applicant_info"); }
	
	/**
	 * Create a data_list of applicants
	 */
	public function execute_selection_page() {
		$this->add_javascript("/static/widgets/grid/grid.js");
		$this->add_javascript("/static/data_model/data_list.js");
		$this->onload("init_list();");
		$container_id = $this->generateID();
		$input = isset($_POST["input"]) ? json_decode($_POST["input"], true) : array();
		?>
		<div style='width:100%;height:100%' id='<?php echo $container_id;?>'>
		</div>
		<script type='text/javascript'>
		var dl;
		function init_list() {
			dl = new data_list(
				'<?php echo $container_id;?>',
				'Applicant', <?php echo PNApplication::$instance->selection->getCampaignId();?>,
				[
					'Selection.Applicant ID',
					'Personal Information.First Name',
					'Personal Information.Last Name',
					'Personal Information.Gender',
					'Personal Information.Birth Date'
				],
				<?php if (isset($input["filters"])) echo json_encode($input["filters"]); else echo "[]"; ?>,
				500,
				function (list) {
					var get_creation_data = function() {
						var data = {
							sub_models:{SelectionCampaign:<?php echo PNApplication::$instance->selection->getCampaignId();?>},
							prefilled_data:[]
						};
						var filters = list.getFilters();
						for (var i = 0; i < filters.length; ++i) {
							if (filters[i].category == "Selection") {
								if (filters[i].name == "Information Session") {
									if (!filters[i].or)
										data.prefilled_data.push({table:"Applicant",data:"Information Session",value:filters[i].data.value});
								}
							}
						}
						return data;
					};
					list.addTitle("/static/selection/applicant/applicants_16.png", "Applicants");
					var create_applicant = document.createElement("BUTTON");
					create_applicant.className = "button_verysoft";
					create_applicant.innerHTML = "<img src='"+theme.build_icon("/static/selection/applicant/applicant_16.png",theme.icons_10.add)+"' style='vertical-align:bottom'/> Create Applicant";
					create_applicant.onclick = function() {
						window.top.require("popup_window.js",function() {
							var p = new window.top.popup_window('New Applicant', theme.build_icon("/static/selection/applicant/applicant_16.png",theme.icons_10.add), "");
							var frame = p.setContentFrame("/dynamic/people/page/popup_create_people?types=applicant&ondone=reload_list", null, get_creation_data());
							frame.reload_list = reload_list;
							p.show();
						});
					};
					list.addHeader(create_applicant);
					var import_applicants = document.createElement("BUTTON");
					import_applicants.className = "button_verysoft";
					import_applicants.innerHTML = "<img src='"+theme.icons_16._import+"' style='vertical-align:bottom'/> Import Applicants";
					import_applicants.onclick = function() {
						window.top.require("popup_window.js",function() {
							var p = new window.top.popup_window('Import Applicants', theme.icons_16._import, "");
							var frame = p.setContentFrame("/dynamic/selection/page/applicant/popup_import?ondone=reload_list", null, get_creation_data());
							frame.reload_list = reload_list;
							p.show();
						});
					};
					list.addHeader(import_applicants);
					
					list.makeRowsClickable(function(row){
						window.top.popup_frame('/static/selection/applicant/applicant_16.png', 'Applicant', "/dynamic/people/page/profile?people="+list.getTableKeyForRow("People",row.row_id), {sub_models:{SelectionCampaign:<?php echo PNApplication::$instance->selection->getCampaignId();?>}}, 95, 95); 
					});
					
				}
			);
		}
		function reload_list() {
			dl.reloadData();
		};
		</script>
		<?php 
	}
	
}
?>