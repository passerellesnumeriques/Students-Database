<?php 
require_once("component/selection/page/selection_page.inc");
class page_applicant_list extends selection_page {
	
	public function getRequiredRights() { return array("see_applicant_info"); }
	
	/**
	 * Create a data_list of applicants
	 * The property of datalist can be given by a post "input" variable. This variable can be set using prepare_applicants_list.js script
	 */
	public function execute_selection_page() {
		$this->addJavascript("/static/widgets/grid/grid.js");
		$this->addJavascript("/static/data_model/data_list.js");
		$this->onload("init_list();");
		$container_id = $this->generateID();
		$input = isset($_POST["input"]) ? json_decode($_POST["input"], true) : array();
		?>
		<div style='width:100%;height:100%' id='<?php echo $container_id;?>'>
		</div>
		<script type='text/javascript'>
		var dl;
		var can_create = <?php echo (isset($input["can_create"]) ? json_encode($input["can_create"]) : "true");?>;
		var can_import = <?php echo (isset($input["can_import"]) ? json_encode($input["can_import"]) : "true");?>;
		var clickable = <?php echo (isset($input["clickable"]) ? json_encode($input["clickable"]) : "true");?>;
		var filters = <?php if (isset($input["filters"])) echo json_encode($input["filters"]); else echo "[]"; ?>;
		var selectable = <?php if (isset($input["selectable"])) echo json_encode($input["selectable"]); else echo "false"; ?>;
		var applicants_locked = <?php if (isset($input["applicants_locked"])) echo json_encode($input["applicants_locked"]); else echo "[]"; ?>;
		var applicants_selected = <?php if (isset($input["applicants_preselected"])) echo json_encode($input["applicants_preselected"]); else echo "[]"; ?>;
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
				filters,
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
					if(can_create){
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
					}
					if(can_import){
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
					}
					if(clickable){
						list.makeRowsClickable(function(row){
							window.top.popup_frame('/static/selection/applicant/applicant_16.png', 'Applicant', "/dynamic/people/page/profile?people="+list.getTableKeyForRow("People",row.row_id), {sub_models:{SelectionCampaign:<?php echo PNApplication::$instance->selection->getCampaignId();?>}}, 95, 95); 
						});
					}
					if(selectable){
						list.grid.setSelectable(true);
						list.ondataloaded.add_listener(applicants_loaded);
						list.grid.onrowselectionchange = applicants_selection_changed;
					}
				}
			);
		}
		function reload_list() {
			dl.reloadData();
		};

		function applicants_loaded(list){
			for (var i = 0; i < applicants_selected.length; ++i) {
				list.selectByTableKey("Applicant", applicants_selected[i]);
			}
			for(var i = 0; i < applicants_locked.length; i++){
				list.disableSelectByTableKey("Applicant",applicants_locked[i]);
			}
		}

		function applicants_selection_changed(row_id, selected){
			var people_id = dl.getTableKeyForRow("Applicant", row_id);
			if (selected)
				applicants_selected.push(people_id);
			else
				applicants_selected.remove(people_id);
		}
		</script>
		<?php 
	}
	
}
?>