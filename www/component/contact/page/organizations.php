<?php 
class page_organizations extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$this->addJavascript("/static/widgets/grid/grid.js");
		$this->addJavascript("/static/data_model/data_list.js");
		$this->onload("init_organizations_list();");
		$container_id = $this->generateID();
		
		$can_create = false;
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof OrganizationPlugin)) continue;
				if ($pi->getOrganizationCreator() == $_GET["creator"]) {
					$can_create =  $c->canInsertOrganization();
					break;
				}
			}
		}
		
		?>
		<div style='width:100%;height:100%' id='<?php echo $container_id;?>'>
		</div>
		<script type='text/javascript'>
		function init_organizations_list() {
			new data_list(
				'<?php echo $container_id;?>',
				'Organization', null,
				['Contacts.Name'],
				[{category:'Contacts',name:'Managed by',data:{type:'exact',value:<?php echo json_encode($_GET["creator"]); ?>}}],
				250
				function (list) {
					list.grid.setSelectable(true);
					list.addTitle(null, "Organizations of <?php echo $_GET["creator"];?>");
					<?php if ($can_create) {?>
					var new_org = document.createElement("DIV");
					new_org.className = 'button';
					new_org.innerHTML = "<img src='"+theme.icons_16.add+"'/> New Organization";
					new_org.onclick = function() {
						require("popup_window.js",function(){
							var p = new popup_window("New Organization", theme.icons_16.add, "");
							var frame = p.setContentFrame("/dynamic/contact/page/organization_profile?creator=<?php echo $_GET["creator"];?>&organization=-1");
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
					<?php } ?>
				}
			);
		}
		</script>
		<?php 
	}
	
}
?>