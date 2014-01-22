<?php 
require_once("/../selection_page.inc");
class page_IS_main_page extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(&$page){
		$page->add_javascript("/static/widgets/grid/grid.js");
		$page->add_javascript("/static/data_model/data_list.js");
		$page->onload("init_organizations_list();");
		$container_id = $page->generateID();
		$can_create = null;

	?>
		<div id = '<?php echo $container_id; ?>'>
		</div>
		<div id = "IS_status"> TODO finish main page (button add IS, field date, title link IS profile...)</div>
		
		<script style='width:100%;height:100%' type='text/javascript'>
			function init_organizations_list() {
				new data_list(
					'<?php echo $container_id;?>',
					'Information_session',
					['Information Session.Name'],
					[],
					function (list) {
						list.addTitle(null, "Information Sessions List");
						
					}
				);
			}
			
			
			
		</script>
	
	<?php		
	}
	
}