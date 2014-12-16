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
		<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
			<div style='flex:1 1 auto' id='org_list'></div>
			<?php if ($can_remove && !isset($input["selected"]) && !isset($input["selected_not_changeable"])) { ?>
			<div style='flex:none' class='page_footer'>
				With selected organizations: 
				<button class='action' onclick='markObsolete();' title="Hide the selected organizations, but they won't be removed, meaning you can still show them back, and any history about those organizations will remain">
					<img src='<?php echo theme::$icons_16["hide"];?>'/> Mark them as obsolete (hide)
				</button>
				<button class='action red' onclick='removeSelected();' title="Remove completely the selected organizations, meaning any information related to this organization will be lost">
					<img src='<?php echo theme::$icons_16["remove_white"];?>'/> Remove them
				</button>
			</div>
			<?php } ?>
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
					'Organization.Types',
					'Organization.Address.0',
					'Organization.Address.1',
					'Organization.EMail',
					'Organization.Phone'
				],
				[
					{category:'Organization',name:'Managed by',data:{type:'exact',value:"Selection"},force:true},
					{category:'Organization',name:'Is obsolete (hidden)',data:{values:[0]},force:false}
				],
				250,
				'Organization.Name',
				true,
				function (list) {
					list.grid.makeScrollable();
					if (can_remove || selected != null || selected_not_changeable != null) {
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
										win.organization.notes.save(res.id, function() {
											list.reloadData();
											p.close();
										});
									});
								});
							});
						};
						list.addHeader(new_org);
					}
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
		function markObsolete() {
			if (!dl || !dl.grid || dl.grid.getSelectionByRowId().length == 0) {
				alert("You didn't select any organization");
				return;
			}
			var sel = dl.grid.getSelectionByRowId();
			confirm_dialog("Are you sure you want to hide the "+(sel.length > 1 ? sel.length : "")+" selected organization"+(sel.length > 1 ? "s" : "")+" ?",function(yes) {
				if (!yes) return;
				var ids = [];
				for (var i = 0; i < sel.length; ++i)
					ids.push(dl.getTableKeyForRow("Organization", sel[i]));
				var locker = lock_screen();
				service.json("data_model","save_cells",{cells:[{table:'Organization',keys:ids,values:[{column:'obsolete',value:1}]}]},function(res){
					unlock_screen(locker);
					dl.reloadData();
				});
			});
		}
		function removeSelected() {
			if (!dl || !dl.grid || dl.grid.getSelectionByRowId().length == 0) {
				alert("You didn't select any organization");
				return;
			}
			var sel = dl.grid.getSelectionByRowId();
			var ids = [];
			for (var i = 0; i < sel.length; ++i)
				ids.push(dl.getTableKeyForRow("Organization", sel[i]));
			var ask_next = function(index) {
				if (index == ids.length) {
					dl.reloadData();
					return;
				}
				window.top.datamodel.confirm_remove("Organization", ids[index], function() {
					ask_next(index+1);
				},function() {
					ask_next(index+1);
				});
			};
			ask_next(0);
		}
		</script>
		<?php 
	}
	
}
?>