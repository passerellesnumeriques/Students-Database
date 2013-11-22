<?php 
require_once("selection_page.inc");
require_once("IS_profile.inc");
class page_IS_profile extends selection_page {
	public function get_required_rights() { return array(); }
	public function execute_selection_page(&$page){
	?>

		<div id='IS_profile'></div>
		
	<?php
		IS_profile($page,"IS_profile",1);
	}
	
}