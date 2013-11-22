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
		echo PNApplication::$instance->selection->get_json_IS_data(1);
	}
	
}