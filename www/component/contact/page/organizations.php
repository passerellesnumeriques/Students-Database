<?php 
class page_organizations extends Page {
	
	public function getRequiredRights() { return array(); }
	
	/**
	 * Create a data_list with organizations with the possibility to pick any
	 */
	public function execute() {
		$this->addJavascript("/static/widgets/grid/grid.js");
		$this->addJavascript("/static/data_model/data_list.js");
		$this->onload("initList();");
		
		$creator = $_GET["creator"];
		
		$can_create = false;
		$can_read = false;
		$can_remove = false;
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->getOrganizationCreator() == $creator) {
					$can_create |= $pi->canInsertOrganization();
					$can_read |= $pi->canReadOrganization();
					$can_remove |= $pi->canRemoveOrganization();
					break;
				}
			}
		}
		if (!$can_read) {
			echo "<div><img src='".theme::$icons_16["error"]."' style='vertical-align:bottom'/> Access denied: you are not allowed to see organization from ".$creator."</div>";
			return;
		}
		if (isset($_POST["input"])) $input = json_decode($_POST["input"], true); else $input = array();
		?>
		<div style='width:100%;height:100%' id='org_list'>
		</div>
		<script type='text/javascript'>
		var selected = <?php echo isset($input["selected"]) ? json_encode($input["selected"]) : "null";?>;
		var selected_not_changeable = <?php echo isset($input["selected_not_changeable"]) ? json_encode($input["selected_not_changeable"]) : "null";?>;
		var can_create = <?php echo json_encode($can_create);?>;
		var can_remove = <?php echo json_encode($can_remove);?>;
		var creator = <?php echo json_encode($creator);?>;
		var dl;
		function initList() {
			dl = new data_list(
				'org_list',
				'Organization', null,
				[
					'Organization.Name',
					'Organization.Address.0',
					'Organization.Address.1',
					'Organization.EMail',
					'Organization.Phone'
				],
				[{category:'Organization',name:'Managed by',data:{type:'exact',value:"Selection"}}],
				250,
				function (list) {
					list.grid.makeScrollable();
					if (can_remove || selected != null || selected_no_changeable != null) {
						list.grid.setSelectable(true);
						list.ondataloaded.add_listener(organizations_loaded);
						list.grid.onrowselectionchange = organizations_selection_changed;
					}
					list.addTitle(null, "Organizations of "+creator);
					if (can_create) {
						var new_org = document.createElement("BUTTON");
						new_org.className = 'flat';
						new_org.innerHTML = "<img src='"+theme.build_icon("/static/contact/organization.png",theme.icons_10.add)+"'/> New Organization";
						new_org.onclick = function() {
							window.top.popup_frame(theme.icons_16.add, "New Selection Partner", "/dynamic/contact/page/organization_profile?creator=Selection&organization=-1",null,null,null,function(frame,p) {
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
							});
						};
						list.addHeader(new_org);
					}
					// TODO remove button
					list.makeRowsClickable(function(row){
						var orga_id = list.getTableKeyForRow('Organization',row.row_id);
						window.top.popup_frame("/static/contact/organization.png", "Organization Profile", "/dynamic/contact/page/organization_profile?organization="+orga_id,null,null,null,function(frame,p) {
							p.onclose = function() { list.reloadData(); };
						});
					});
				}
			);
		}
		function organizations_loaded(list) {
			if (selected != null)
				for (var i = 0; i < selected.length; ++i)
					list.selectByTableKey("Organization", selected[i]);
			if (selected_not_changeable != null)
				for (var i = 0; i < selected_not_changeable.length; ++i) {
					list.selectByTableKey("Organization",selected_not_changeable[i]);
					list.disableSelectByTableKey("Organization",selected_not_changeable[i]);
				}
		}
		function organizations_selection_changed(row_id, sel) {
			if (selected != null) {
				var org_id = parseInt(dl.getTableKeyForRow("Organization", row_id));
				if (sel) {
					if (!selected.contains(org_id))
						selected.push(org_id);
				} else
					selected.remove(org_id);
			}
		}
		</script>
		<?php 
	}
	
}
?>