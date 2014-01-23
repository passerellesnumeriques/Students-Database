<?php 
class page_home extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('admin_page');");
		?>
		<div id='admin_page' icon='/static/administration/admin_32.png' title='Administration' page='/dynamic/administration/page/dashboard'>
		<?php
		require_once("component/administration/AdministrationPlugin.inc");
		foreach (PNApplication::$instance->components as $name=>$c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof AdministrationPlugin)) continue;
				foreach ($pi->getAdministrationPages() as $page) {
					echo "<a class='page_menu_item' href='".$page->getPage()."' target='admin_page_content'><img src='".$page->getIcon16()."'/>".$page->getTitle()."</a>";
				}
			}
		} 
		?>
		</div>
		<?php 
	}
	
}
?>