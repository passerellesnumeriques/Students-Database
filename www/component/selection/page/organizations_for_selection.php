<?php 
require_once("selection_page.inc");
class page_organizations_for_selection extends selection_page {
	
	public function get_required_rights() { return array(); }
	
	/**
	 * Create a data_list with all the selection organizations
	 * with the possibility to pick any
	 */
	public function execute_selection_page(&$page) {
		$page->add_javascript("/static/widgets/grid/grid.js");
		$page->add_javascript("/static/data_model/data_list.js");
		$page->onload("init_organizations_list();");
		$container_id = $page->generateID();
		
		$can_create = false;
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->getOrganizationCreator() == "Selection") {
					$can_create =  $pi->canInsertOrganization();
					break;
				}
			}
			if ($can_create) break;
		}
		$mode = null; // Variable that contains the using mode of this page
		if(isset($_GET["is"]) && isset($_GET["partners"])){
			$mode = "IS_partners";
			$partners = $_GET["partners"];
			$host_id = @$_GET["host"];
			if(!is_array($partners)) $partners = array();
		} else if (isset($_GET["ec"]) && isset($_GET["partners"])){
			$mode = "EC_partners";
			$partners = $_GET["partners"];
			$host_id = @$_GET["host"];
			if(!is_array($partners)) $partners = array();
		}
		?>
		<div style='width:100%;height:100%' id='<?php echo $container_id;?>'>
		</div>
		<script type='text/javascript'>
		var page_mode = <?php echo json_encode($mode);?>;
		<?php if($mode == "IS_partners" || $mode == "EC_partners"){?>
			var selected_partners = <?php echo json_encode($partners).";";?>
			var host_id = <?php echo json_encode($host_id).";";?>
		<?php }?>
		var dl;
		function init_organizations_list() {
			dl = new data_list(
				'<?php echo $container_id;?>',
				'Organization',
				['Organization.Name','Organization.Address','Organization.EMail','Organization.Phone'],
				[{category:'Organization',name:'Managed by',data:{type:'exact',value:"Selection"}}],
				function (list) {
					if(page_mode == "IS_partners" || page_mode == "EC_partners")
						list.grid.setSelectable(true);
					list.addTitle(null, "Organizations of Selection");
					<?php if ($can_create) {?>
					var new_org = document.createElement("DIV");
					new_org.className = 'button';
					new_org.innerHTML = "<img src='"+theme.icons_16.add+"'/> New Organization";
					new_org.onclick = function() {
						require("popup_window.js",function(){
							var p = new popup_window("New Organization", theme.icons_16.add, "");
							var frame = p.setContentFrame("/dynamic/contact/page/organization_profile?creator=Selection&organization=-1");
							p.addOkCancelButtons(function(){
								p.freeze();
								var win = getIFrameWindow(frame);
								var org = win.organization.getStructure();
								service.json("contact", "add_organization", org, function(res) {
									if (!res) { p.unfreeze(); return; }
									list.reloadData();
									p.close();
								});
							});
							p.show();
						});
					};
					list.addHeader(new_org);
					if(page_mode == "IS_partners" || page_mode == "EC_partners"){
						list.ondataloaded.add_listener(organizations_loaded);
						list.grid.onrowselectionchange = organizations_selection_changed;
					}
					if(page_mode == null){
						list.makeRowsClickable(function(row){
							var orga_id = list.getTableKeyForRow('Organization',row.row_id);
							require("popup_window.js",function() {
								var popup = new popup_window("Organization", "/static/contact/organization.png", "");
								popup.setContentFrame("/dynamic/contact/page/organization_profile?organization="+orga_id);
								popup.show();
							});
						});
					}
					<?php } ?>
				}
			);
		}
		function organizations_loaded(list) {
			for (var i = 0; i < selected_partners.length; ++i) {
				list.selectByTableKey("Organization", selected_partners[i]);
			}
			if(host_id != null){
				list.selectByTableKey("Organization",host_id);
				list.disableSelectByTableKey("Organization",host_id);
			}
		}
		function organizations_selection_changed(row_id, selected) {
			var partner_id = dl.getTableKeyForRow("Organization", row_id);
			if (selected)
				selected_partners.push(partner_id);
			else
				selected_partners.remove(partner_id);
		}
		</script>
		<?php 
	}
	
}
?>